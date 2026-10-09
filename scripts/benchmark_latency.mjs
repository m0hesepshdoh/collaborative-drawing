// Measures WebSocket one-way latency and HTTP server round-trip latency.
import { mkdir } from "node:fs/promises";
import { benchmarkEnv, connectSocket, printSummary, statistics, syncClock, writeCsv } from "./benchmark_common.mjs";

const RUNS = 3;
const COUNT = 500;
const LOSS_RATE = 0.1;

const stroke = () => ({
  points: Array.from({ length: 5 }, (_, index) => ({ x: Math.round(Math.random() * 800), y: Math.round(Math.random() * 600), index })),
  color: "#123456",
  size: 5,
  client_stroke_id: crypto.randomUUID(),
});

const main = async () => {
  await syncClock(benchmarkEnv.url);
  const sender = await connectSocket(benchmarkEnv);
  const receiver = await connectSocket(benchmarkEnv);
  const latencies = [];
  const roundTrips = [];
  const pending = new Map();
  receiver.socket.on("message", (raw) => {
    const event = JSON.parse(raw.toString());
    if (event.event !== "client-stroke") return;
    const payload = event.data;
    if (pending.has(payload.id)) {
      const receivedAt = Date.now() - globalThis.clockOffsetMs;
      latencies.push(Math.max(0, receivedAt - payload.sentAt));
      pending.delete(payload.id);
    }
  });

  for (let run = 1; run <= RUNS; run++) {
    for (let index = 0; index < COUNT; index++) {
      if (Math.random() < LOSS_RATE) continue;
      const id = crypto.randomUUID();
      const sentAt = Date.now();
      pending.set(id, true);
      const response = await fetch(`${benchmarkEnv.url}/benchmark/${benchmarkEnv.code}/stroke`, {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-Benchmark-Secret": benchmarkEnv.secret },
        body: JSON.stringify({ id, sentAt, stroke: stroke() }),
      });
      if (!response.ok) throw new Error(`Benchmark stroke broadcast failed: ${response.status}`);
    }
    await new Promise((resolve) => setTimeout(resolve, 100));
    const started = Date.now();
    for (let index = 0; index < COUNT; index++) {
      const response = await fetch(`${benchmarkEnv.url}/session/${benchmarkEnv.code}/record-stroke`, {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": benchmarkEnv.secret },
        body: JSON.stringify({ ...stroke(), client_stroke_id: crypto.randomUUID(), drawn_at: new Date().toISOString() }),
      });
      if (response.ok) roundTrips.push(Date.now() - started);
    }
    console.log(`run ${run}: received=${latencies.length}/${COUNT} websocket messages`);
  }
  sender.socket.close();
  receiver.socket.close();
  await mkdir("benchmarks", { recursive: true });
  const file = `benchmarks/latency_${new Date().toISOString().replace(/[:.]/g, "-")}.csv`;
  await writeCsv(file, latencies.map((latency, index) => ({ run: Math.floor(index / COUNT) + 1, direction: "websocket", latency_ms: latency })), ["run", "direction", "latency_ms"]);
  printSummary("Latency", latencies);
  console.log(`Server round-trip mean=${statistics(roundTrips).mean.toFixed(2)}ms`);
};

main().catch((error) => { console.error(error); process.exitCode = 1; });
