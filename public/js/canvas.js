import Echo from "https://cdn.jsdelivr.net/npm/laravel-echo@2.2.6/+esm";
import Pusher from "https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/+esm";
window.Pusher = Pusher;
const cfg = window.DRAWING_CONFIG,
  canvas = document.getElementById("canvas"),
  ctx = canvas.getContext("2d"),
  status = document.getElementById("status");
let bg = null,
  drawing = false,
  points = [],
  lastSend = 0,
  erase = false,
  dirtyActivity = true;
const logical = { w: 800, h: 600 };

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

function fit() {
  const r = canvas.getBoundingClientRect(),
    d = window.devicePixelRatio || 1;
  canvas.width = r.width * d;
  canvas.height = r.height * d;
  ctx.setTransform(
    (d * r.width) / logical.w,
    0,
    0,
    (d * r.height) / logical.h,
    0,
    0,
  );
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
  for (const s of cfg.initialStrokes || []) drawStroke(s);
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
  .listenForWhisper("stroke", drawStroke)
  .listen(".bot.stroke", (e) => drawStroke(e))
  .listen(".canvas.cleared", () => redrawBackground())
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
canvas.addEventListener("pointerdown", (e) => {
  drawing = true;
  canvas.setPointerCapture(e.pointerId);
  points = [pos(e)];
  dirtyActivity = true;
});
canvas.addEventListener("pointermove", (e) => {
  dirtyActivity = true;
  if (!drawing) return;
  const p = pos(e),
    a = points[points.length - 1];
  points.push(p);
  const s = { points: [a, p], ...style() };
  drawStroke(s);
  const now = Date.now();
  if (now - lastSend > 50) {
    channel.whisper("stroke", s);
    lastSend = now;
  }
});
async function end() {
  if (!drawing) return;
  drawing = false;
  if (points.length < 2) return;
  const s = { points: [...points], ...style() };
  channel.whisper("stroke", s);
  try {
    const res = await fetch(`/session/${cfg.code}/record-stroke`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": cfg.csrf,
        Accept: "application/json",
      },
      body: JSON.stringify({ ...s, drawn_at: new Date().toISOString() }),
    });
    checkAuth(res);
  } catch {}
  points = [];
}
canvas.addEventListener("pointerup", end);
canvas.addEventListener("pointercancel", end);
document.getElementById("eraser").onclick = () => {
  erase = !erase;
  document.getElementById("eraser").classList.toggle("bg-indigo-700", erase);
};
document.getElementById("clear").onclick = async () => {
  cfg.initialStrokes = [];
  redrawBackground();
  try {
    const res = await fetch(`/session/${cfg.code}/clear`, {
      method: "POST",
      headers: { "X-CSRF-TOKEN": cfg.csrf, Accept: "application/json" },
    });
    checkAuth(res);
  } catch {}
};
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
