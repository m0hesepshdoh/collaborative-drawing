import Echo from "https://cdn.jsdelivr.net/npm/laravel-echo@2.2.6/+esm";
import Pusher from "https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/+esm";

window.Pusher = Pusher;

const config = window.ADMIN_REVERB_CONFIG;
const previews = window.ADMIN_SESSION_PREVIEWS || [];

if (config && previews.length) {
  const echo = new Echo({
    broadcaster: "reverb",
    key: config.key,
    wsHost: config.host,
    wsPort: config.port,
    wssPort: config.port,
    forceTLS: config.scheme === "https",
    enabledTransports: ["ws", "wss"],
    authEndpoint: "/broadcasting/auth",
    auth: {
      headers: {
        "X-CSRF-TOKEN": config.csrf,
        Accept: "application/json",
      },
    },
  });

  for (const preview of previews) {
    const canvas = document.querySelector(`[data-session-preview="${preview.code}"]`);
    const status = document.querySelector(`[data-preview-status="${preview.code}"]`);
    if (!canvas) continue;

    const context = canvas.getContext("2d");
    const ratio = window.devicePixelRatio || 1;
    canvas.width = 800 * ratio;
    canvas.height = 600 * ratio;

    let strokes = [...(preview.strokes || [])];
    let liveStrokes = [];
    let background = null;

    function drawStroke(stroke) {
      if (!stroke.points || stroke.points.length < 2) return;
      context.save();
      context.strokeStyle = stroke.color;
      context.lineWidth = stroke.size;
      context.lineCap = "round";
      context.lineJoin = "round";
      context.beginPath();
      context.moveTo(stroke.points[0].x, stroke.points[0].y);
      for (let index = 1; index < stroke.points.length; index++) {
        context.lineTo(stroke.points[index].x, stroke.points[index].y);
      }
      context.stroke();
      context.restore();
    }

    function redraw() {
      context.setTransform(ratio, 0, 0, ratio, 0, 0);
      context.clearRect(0, 0, 800, 600);
      context.fillStyle = "#f6f1ea";
      context.fillRect(0, 0, 800, 600);
      if (background?.complete && background.naturalWidth) {
        context.save();
        context.globalAlpha = 0.3;
        context.drawImage(background, 0, 0, 800, 600);
        context.restore();
      }
      for (const stroke of strokes) drawStroke(stroke);
      for (const stroke of liveStrokes) drawStroke(stroke);
    }

    if (preview.background) {
      background = new Image();
      background.onload = redraw;
      background.src = preview.background;
    }
    redraw();

    const channel = echo.private(`session.${preview.code}`);
    channel
      .subscribed(() => {
        if (status) status.textContent = "Live";
      })
      .error(() => {
        if (status) status.textContent = "Preview unavailable";
      })
      .listenForWhisper("stroke", (stroke) => {
        liveStrokes.push(stroke);
        redraw();
      })
      .listenForWhisper("reset", () => {
        strokes = [];
        liveStrokes = [];
        redraw();
      })
      .listen(".stroke.recorded", (stroke) => {
        if (stroke.client_stroke_id) {
          liveStrokes = liveStrokes.filter(
            (liveStroke) => liveStroke.client_stroke_id !== stroke.client_stroke_id,
          );
        }
        strokes.push(stroke);
        redraw();
      })
      .listen(".bot.stroke", (stroke) => {
        strokes.push(stroke);
        redraw();
      })
      .listen(".canvas.cleared", () => {
        strokes = [];
        liveStrokes = [];
        redraw();
      });
  }
}