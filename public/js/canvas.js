import Echo from "https://cdn.jsdelivr.net/npm/laravel-echo@2.2.6/+esm";
import Pusher from "https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/+esm";
window.Pusher = Pusher;

const cfg = window.DRAWING_CONFIG,
  canvas = document.getElementById("canvas"),
  ctx = canvas.getContext("2d"),
  status = document.getElementById("status"),
  finishBtn = document.getElementById("finish-drawing"),
  newDrawingBtn = document.getElementById("new-drawing"),
  aiStatus = document.getElementById("ai-status"),
  aiResultBox = document.getElementById("ai-result"),
  aiImageEl = document.getElementById("ai-image"),
  downloadAiLink = document.getElementById("download-ai"),
  retryAiBtn = document.getElementById("retry-ai"),
  aiErrorEl = document.getElementById("ai-error");

let bg = null,
  drawing = false,
  points = [],
  activeStrokeId = null,
  liveStrokes = [],
  lastSend = 0,
  erase = false,
  dirtyActivity = true,
  countdownTimer = null,
  finishState = {
    localLocked: false,
    remoteLocked: false,
    finalized: false,
    aiStarted: false,
    aiAttempted: false,
    timerEndsAt: 0,
    lastCanvasDataUrl: "",
    status: "drawing",
  };

const logical = { w: 800, h: 600 };
let strokes = [...(cfg.initialStrokes || [])];

function leaveToLanding(message) {
  status.textContent = message;
  setTimeout(() => (location.href = cfg.landingUrl || "/"), 900);
}

function checkAuth(res) {
  if (res.status === 403) {
    leaveToLanding("Session ended");
    return false;
  }
  return true;
}

function getRemainingSeconds() {
  if (!finishState.timerEndsAt) return 0;
  return Math.max(0, Math.ceil((finishState.timerEndsAt - Date.now()) / 1000));
}

function applyServerState(serverState) {
  const state = serverState?.state || "drawing";
  finishState.status = state;
  finishState.localLocked = state === "waiting" && serverState?.finishedByIp === cfg.meIp;
  finishState.remoteLocked = state === "waiting" && serverState?.finishedByIp && serverState?.finishedByIp !== cfg.meIp;
  finishState.finalized = state === "finalized";
  finishState.timerEndsAt = serverState?.deadlineAt ? Date.parse(serverState.deadlineAt) : 0;

  if (state === "waiting") {
    startCountdown(finishState.timerEndsAt);
  } else if (countdownTimer) {
    clearInterval(countdownTimer);
    countdownTimer = null;
  }

  updateStatusText();
  setFinishButtonState();

  if (serverState?.imageUrl) {
    showAiImage(serverState.imageUrl);
  } else if (state === "finalized") {
    finalizeDrawing(Boolean(serverState?.canGenerateAi));
  }
}

function updateStatusText() {
  if (finishState.aiStarted) {
    status.textContent = "AI is generating…";
    status.className = "text-xs text-sky-300";
    return;
  }

  if (finishState.finalized) {
    status.textContent = "Finalizing…";
    status.className = "text-xs text-amber-300";
    return;
  }

  if (finishState.status === "waiting") {
    const remaining = getRemainingSeconds();
    const message = finishState.localLocked
      ? `Waiting for the other player… ${formatCountdown(remaining)}`
      : `Player 2 has ${formatCountdown(remaining)} to finish…`;
    status.textContent = message;
    status.className = `text-xs ${remaining <= 30 ? "text-red-400" : "text-amber-300"}`;
    return;
  }

  status.textContent = "Drawing…";
  status.className = "text-xs text-emerald-400";
}

function formatCountdown(seconds) {
  const total = Math.max(0, seconds);
  const minutes = Math.floor(total / 60);
  const remainder = total % 60;
  return `${minutes}:${String(remainder).padStart(2, "0")}`;
}

function setFinishButtonState() {
  const locked = finishState.localLocked || finishState.finalized;
  finishBtn.disabled = locked;
  finishBtn.classList.toggle("opacity-50", locked);
  finishBtn.classList.toggle("cursor-not-allowed", locked);
  if (finishState.localLocked && !finishState.finalized) {
    finishBtn.textContent = "Waiting…";
  } else {
    finishBtn.textContent = "Finish Drawing";
  }
}

function fit() {
  const rect = canvas.getBoundingClientRect();
  const d = window.devicePixelRatio || 1;
  if (!rect.width || !rect.height) return;

  canvas.width = Math.round(rect.width * d);
  canvas.height = Math.round(rect.height * d);
  ctx.setTransform(canvas.width / logical.w, 0, 0, canvas.height / logical.h, 0, 0);
  redrawBackground();
}

function redrawBackground() {
  ctx.clearRect(0, 0, logical.w, logical.h);
  if (bg) {
    ctx.save();
    ctx.globalAlpha = 0.3;
    ctx.drawImage(bg, 0, 0, logical.w, logical.h);
    ctx.restore();
  }
  for (const s of strokes) drawStroke(s);
  for (const s of liveStrokes) drawStroke(s);
}

if (cfg.background) {
  bg = new Image();
  bg.crossOrigin = "anonymous";
  bg.onload = redrawBackground;
  bg.src = cfg.background;
}

window.addEventListener("resize", fit);
fit();

function pos(e) {
  const r = canvas.getBoundingClientRect();
  return {
    x: ((e.clientX - r.left) * logical.w) / r.width,
    y: ((e.clientY - r.top) * logical.h) / r.height,
  };
}

function createStrokeId() {
  return globalThis.crypto?.randomUUID?.() || `${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

function drawStroke(s) {
  if (!s.points || s.points.length < 2) return;
  ctx.save();
  ctx.strokeStyle = s.color;
  ctx.lineWidth = s.size;
  ctx.lineCap = "round";
  ctx.lineJoin = "round";
  ctx.beginPath();
  ctx.moveTo(s.points[0].x, s.points[0].y);
  for (let i = 1; i < s.points.length; i++)
    ctx.lineTo(s.points[i].x, s.points[i].y);
  ctx.stroke();
  ctx.restore();
}

function addStroke(s) {
  strokes.push(s);
  drawStroke(s);
}

const scheme = cfg.reverb.scheme,
  echo = new Echo({
    broadcaster: "reverb",
    key: cfg.reverb.key,
    wsHost: cfg.reverb.host,
    wsPort: cfg.reverb.port,
    wssPort: cfg.reverb.port,
    forceTLS: scheme === "https",
    enabledTransports: ["ws", "wss"],
    authEndpoint: "/broadcasting/auth",
    auth: { headers: { "X-CSRF-TOKEN": cfg.csrf, Accept: "application/json" } },
  });
const channel = echo.private("session." + cfg.code);
channel
  .subscribed(() => (status.textContent = "Connected"))
  .error(() => (status.textContent = "WebSocket unavailable"));
channel
  .listenForWhisper("stroke", (stroke) => {
    liveStrokes.push(stroke);
    drawStroke(stroke);
  })
  .listenForWhisper("reset", () => resetDrawingSession())
  .listen(".bot.stroke", (e) => addStroke(e))
  .listen(".stroke.recorded", (stroke) => {
    if (stroke.client_stroke_id) {
      liveStrokes = liveStrokes.filter((liveStroke) => liveStroke.client_stroke_id !== stroke.client_stroke_id);
    }
    addStroke(stroke);
  })
  .listen(".canvas.cleared", () => {
    strokes = [];
    liveStrokes = [];
    redrawBackground();
  })
  .listen(".player.kicked", (e) => {
    if (e.ip === cfg.meIp) {
      leaveToLanding("You were removed");
    }
  });

function style() {
  return {
    color: erase ? "#ffffff" : document.getElementById("color").value,
    size: erase ? 20 : Number(document.getElementById("size").value),
  };
}

function canDrawNow() {
  return !finishState.localLocked && !finishState.finalized && !finishState.aiStarted;
}

canvas.addEventListener("pointerdown", (e) => {
  if (!canDrawNow()) return;
  drawing = true;
  canvas.setPointerCapture(e.pointerId);
  points = [pos(e)];
  activeStrokeId = createStrokeId();
  dirtyActivity = true;
});

canvas.addEventListener("pointermove", (e) => {
  dirtyActivity = true;
  if (!drawing || !canDrawNow()) return;
  const p = pos(e),
    a = points[points.length - 1];
  points.push(p);
  const s = { points: [a, p], client_stroke_id: activeStrokeId, ...style() };
  addStroke(s);
  const now = Date.now();
  if (now - lastSend > 50) {
    channel.whisper("stroke", s);
    lastSend = now;
  }
});

async function end() {
  if (!drawing || !canDrawNow()) return;
  drawing = false;
  if (points.length < 2) return;
  const s = { points: [...points], ...style() };
  try {
    const res = await fetch(`/session/${cfg.code}/record-stroke`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": cfg.csrf,
        "X-Socket-ID": echo.socketId() || "",
        Accept: "application/json",
      },
      body: JSON.stringify({ ...s, client_stroke_id: activeStrokeId, drawn_at: new Date().toISOString() }),
    });
    if (!checkAuth(res)) return;
    if (!res.ok) {
      const payload = await res.json().catch(() => ({}));
      throw new Error(payload?.message || "Unable to save stroke to the session.");
    }
  } catch (error) {
    status.textContent = error.message || "Unable to save stroke.";
    status.className = "text-xs text-red-400";
  } finally {
    points = [];
    activeStrokeId = null;
  }
}

canvas.addEventListener("pointerup", end);
canvas.addEventListener("pointercancel", end);

document.getElementById("eraser").onclick = () => {
  erase = !erase;
  document.getElementById("eraser").classList.toggle("bg-indigo-700", erase);
};

document.getElementById("clear").onclick = async () => {
  cfg.initialStrokes = [];
  strokes = [];
  liveStrokes = [];
  redrawBackground();
  try {
    const res = await fetch(`/session/${cfg.code}/clear`, {
      method: "POST",
      headers: { "X-CSRF-TOKEN": cfg.csrf, Accept: "application/json" },
    });
    checkAuth(res);
  } catch {}
};

function startCountdown(deadlineAt) {
  if (!deadlineAt) return;
  finishState.timerEndsAt = deadlineAt;
  if (countdownTimer) return;
  countdownTimer = setInterval(() => {
    updateStatusText();
    const remaining = getRemainingSeconds();
    if (remaining <= 0) {
      clearInterval(countdownTimer);
      countdownTimer = null;
      syncSessionState();
    }
  }, 250);
  updateStatusText();
}

async function syncSessionState() {
  try {
    const response = await fetch(`/session/${cfg.code}/state`, {
      headers: { Accept: "application/json" },
    });
    if (!checkAuth(response) || !response.ok) return;
    applyServerState(await response.json());
  } catch {}
}

async function requestFinish() {
  if (finishState.localLocked || finishState.finalized) return;

  try {
    const res = await fetch(`/session/${cfg.code}/finish`, {
      method: "POST",
      headers: { "X-CSRF-TOKEN": cfg.csrf, Accept: "application/json" },
    });
    const payload = await res.json();
    if (!res.ok) {
      throw new Error(payload?.message || "Unable to finish the drawing.");
    }
    applyServerState(payload);
    if (payload.state === "finalized") {
      finalizeDrawing(true);
    }
  } catch (error) {
    status.textContent = error.message || "Unable to finish.";
    status.className = "text-xs text-red-400";
  }
}

function finalizeDrawing(generateAi = false) {
  finishState.finalized = true;
  finishState.localLocked = true;
  finishState.remoteLocked = true;
  finishState.status = "finalized";
  if (countdownTimer) {
    clearInterval(countdownTimer);
    countdownTimer = null;
  }
  finishState.timerEndsAt = 0;
  updateStatusText();
  setFinishButtonState();
  if (generateAi && !finishState.aiStarted && !finishState.aiAttempted) {
    finishState.aiAttempted = true;
    exportCanvasAsPngForAi();
  }
}

finishBtn.addEventListener("click", requestFinish);

async function exportCanvasAsPngForAi() {
  if (finishState.aiStarted) return;
  finishState.aiStarted = true;
  aiResultBox.classList.remove("hidden");
  aiResultBox.scrollIntoView({ behavior: "smooth", block: "nearest" });
  aiStatus.textContent = "AI is generating…";
  aiErrorEl.classList.add("hidden");
  aiErrorEl.textContent = "";
  aiImageEl.hidden = true;
  downloadAiLink.classList.add("hidden");
  retryAiBtn.classList.add("hidden");

  try {
    const blob = await new Promise((resolve, reject) => {
      canvas.toBlob((result) => {
        if (!result) {
          reject(new Error("Could not export canvas as PNG."));
          return;
        }
        resolve(result);
      }, "image/png");
    });

    const reader = new FileReader();
    finishState.lastCanvasDataUrl = await new Promise((resolve, reject) => {
      reader.onload = () => resolve(reader.result);
      reader.onerror = () => reject(new Error("Failed to encode canvas image."));
      reader.readAsDataURL(blob);
    });

    await requestSenseNovaImage();
  } catch (error) {
    showAiError(error.message || "Could not prepare the drawing for AI generation.");
    finishState.aiStarted = false;
    updateStatusText();
  }
}

function showAiError(message) {
  aiStatus.textContent = "Done!";
  aiErrorEl.textContent = message;
  aiErrorEl.classList.remove("hidden");
  retryAiBtn.classList.remove("hidden");
}

async function requestSenseNovaImage() {
  try {
    const response = await fetch(`/session/${cfg.code}/generate-ai`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": cfg.csrf,
        Accept: "application/json",
      },
      body: JSON.stringify({
        image_data_url: finishState.lastCanvasDataUrl,
        prompt:
          "This is a rough hand-drawn sketch made collaboratively by two people. Turn it into a polished, finished illustration. Preserve every drawn shape, object, and layout exactly as it appears — do not add, remove, or move any elements. Only clean up the lines, add color, shading, and texture to make it look like a professional piece of art. Keep the composition and proportions identical to the sketch.",
      }),
    });

    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(payload?.message || payload?.error?.message || `SenseNova request failed (${response.status}).`);
    }

    const imageUrl = payload?.image_url || payload?.url || payload?.result;
    if (!imageUrl) {
      throw new Error("SenseNova returned no usable image URL.");
    }

    showAiImage(imageUrl);
  } catch (error) {
    showAiError(error.message || "SenseNova image generation failed.");
    finishState.aiStarted = false;
  } finally {
    finishState.aiStarted = false;
    updateStatusText();
  }
}

function showAiImage(imageUrl) {
  aiStatus.textContent = "AI finished the drawing";
  aiImageEl.src = imageUrl;
  aiImageEl.hidden = false;
  downloadAiLink.href = imageUrl;
  downloadAiLink.download = "ai-finished-drawing.png";
  downloadAiLink.classList.remove("hidden");
  retryAiBtn.classList.remove("hidden");
  aiResultBox.classList.remove("hidden");
  aiResultBox.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

function extractImageUrl(value) {
  if (!value || typeof value !== "object") return null;

  if (typeof value.image_url === "string" && value.image_url) return value.image_url;
  if (typeof value.imageUrl === "string" && value.imageUrl) return value.imageUrl;
  if (typeof value.url === "string" && value.url) return value.url;
  if (typeof value.result === "string" && value.result) return value.result;

  if (Array.isArray(value)) {
    for (const item of value) {
      const match = extractImageUrl(item);
      if (match) return match;
    }
  }

  for (const key of Object.keys(value)) {
    const nested = value[key];
    if (typeof nested === "string" && /^(https?:)?\/\//i.test(nested)) {
      return nested;
    }

    const match = extractImageUrl(nested);
    if (match) return match;
  }

  return null;
}

retryAiBtn.addEventListener("click", () => {
  if (!finishState.lastCanvasDataUrl) {
    showAiError("There is no finished drawing to retry yet.");
    return;
  }
  finishState.aiAttempted = true;
  requestSenseNovaImage();
});

async function resetDrawingSession() {
  try {
    const res = await fetch(`/session/${cfg.code}/reset`, {
      method: "POST",
      headers: { "X-CSRF-TOKEN": cfg.csrf, Accept: "application/json" },
    });
    const payload = await res.json();
    if (!res.ok) {
      throw new Error(payload?.message || "Unable to reset the session.");
    }
    cfg.initialStrokes = [];
    strokes = [];
    liveStrokes = [];
    finishState.localLocked = false;
    finishState.remoteLocked = false;
    finishState.finalized = false;
    finishState.aiStarted = false;
    finishState.aiAttempted = false;
    finishState.timerEndsAt = 0;
    finishState.status = payload.state || "drawing";
    aiImageEl.hidden = true;
    aiImageEl.removeAttribute("src");
    downloadAiLink.classList.add("hidden");
    retryAiBtn.classList.add("hidden");
    aiErrorEl.classList.add("hidden");
    aiErrorEl.textContent = "";
    aiResultBox.classList.add("hidden");
    setFinishButtonState();
    redrawBackground();
    if (countdownTimer) {
      clearInterval(countdownTimer);
      countdownTimer = null;
    }
    updateStatusText();
  } catch (error) {
    status.textContent = error.message || "Reset failed.";
    status.className = "text-xs text-red-400";
  }
}

newDrawingBtn.addEventListener("click", async () => {
  await resetDrawingSession();
  channel.whisper("reset", { playerIp: cfg.meIp });
});

setInterval(async () => {
  if (!dirtyActivity) return;
  dirtyActivity = false;
  try {
    const res = await fetch(`/session/${cfg.code}/heartbeat`, {
      method: "POST",
      headers: { "X-CSRF-TOKEN": cfg.csrf, Accept: "application/json" },
    });
    checkAuth(res);
  } catch {}
}, 60000);

window.addEventListener("beforeunload", () =>
  echo.leave("session." + cfg.code),
);

updateStatusText();
setFinishButtonState();
syncSessionState();
setInterval(syncSessionState, 2000);
