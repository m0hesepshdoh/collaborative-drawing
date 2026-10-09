// Measures reconnect and channel re-subscription latency after controlled WebSocket drops.
import { mkdir } from "node:fs/promises";
import { benchmarkEnv, connectSocket, writeCsv } from "./benchmark_common.mjs";

const DROPS = [5, 10, 30];

const main = async () => {
  await mkdir("benchmarks", { recursive: true });
  const rows = [];
  for (const dropSeconds of DROPS) {
    const { socket } = await connectSocket(benchmarkEnv);
    const disconnected = Date.now();
    socket.close();
    await new Promise((resolve) => setTimeout(resolve, dropSeconds * 1000));
    const reconnect = await connectSocket(benchmarkEnv);
    const reconnectMs = Date.now() - disconnected;
    const subscribedAt = Date.now();
    const pong = new Promise((resolve, reject) => {
      const timer = setTimeout(() => reject(new Error("Timed out waiting for channel pong")), 5000);
      reconnect.socket.on("message", (raw) => {
        const event = JSON.parse(raw.toString());
        if (event.event === "pusher:pong") {
          clearTimeout(timer);
          resolve(event.data);
        }
      });
    });
    reconnect.socket.send(JSON.stringify({ event: "pusher:ping", data: { info: "benchmark" } }));
    await pong;
    const resubscribeMs = Date.now() - subscribedAt;
    rows.push({ drop_seconds: dropSeconds, reconnect_ms: reconnectMs, resubscribe_ms: resubscribeMs, success: true });
    reconnect.socket.close();
  }
  const file = `benchmarks/reconnect_${new Date().toISOString().replace(/[:.]/g, "-")}.csv`;
  await writeCsv(file, rows, ["drop_seconds", "reconnect_ms", "resubscribe_ms", "success"]);
  console.table(rows);
  console.log(`Wrote ${file}`);
};

main().catch((error) => { console.error(error); process.exitCode = 1; });
