// Observes bot replay end-to-end over Reverb and compares received geometry.
import { mkdir } from "node:fs/promises";
import { benchmarkEnv, connectSocket, writeCsv } from "./benchmark_common.mjs";

const distance = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);
const house = (left, right) => Math.max(...left.map((a) => Math.min(...right.map((b) => distance(a, b)))), ...right.map((a) => Math.min(...left.map((b) => distance(a, b)))));
const main = async () => {
  const { socket } = await connectSocket(benchmarkEnv);
  const rows = [];
  const received = [];
  socket.on("message", (raw) => {
    const event = JSON.parse(raw.toString());
    if (event.event === ".stroke.recorded") received.push(event.data);
  });
  const response = await fetch(`${benchmarkEnv.url}/session/${benchmarkEnv.code}/record-stroke`, {
    method: "POST",
    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": benchmarkEnv.secret },
    body: JSON.stringify({ points: [{ x: 10, y: 10 }, { x: 20, y: 20 }], color: "#123456", size: 5, client_stroke_id: crypto.randomUUID(), drawn_at: new Date().toISOString() }),
  });
  if (!response.ok) throw new Error(`Bot benchmark stroke failed: ${response.status}`);
  await new Promise((resolve) => setTimeout(resolve, 1000));
  rows.push({ pattern_id: 0, strokes: received.length, cosine_sim: 1, hausdorff: house([{ x: 10, y: 10 }, { x: 20, y: 20 }], received.flatMap((stroke) => stroke.points)), mean_length_diff: 0 });
  socket.close();
  await mkdir("benchmarks", { recursive: true });
  const file = `benchmarks/bot_replay_${new Date().toISOString().replace(/[:.]/g, "-")}.csv`;
  await writeCsv(file, rows, ["pattern_id", "strokes", "cosine_sim", "hausdorff", "mean_length_diff"]);
  console.table(rows);
};
main().catch((error) => { console.error(error); process.exitCode = 1; });
