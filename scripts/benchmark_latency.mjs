// Measures WebSocket one-way latency and HTTP server round-trip latency.
import { mkdir } from "node:fs/promises";
import { benchmarkEnv, connectSocket, printSummary, statistics, writeCsv } from "./benchmark_common.mjs";

const RUNS = 3;
const COUNT = 500;
const LOSS_RATE = 0.1;

const stroke = () => ({
  points: Array.from({ length: 5 }, (_, index) => ({ x: Math.round(Math.random() * 800), y: Math.round(Math.random() * 600), index })),
  color: "#123456",
  size: 5,
  client_stroke_id: crypto.randomUUID(),
});

const whisper = (socket, channel, event, payload) => {
  socket.send(JSON.stringify({ event: `client-${event}`, channel, data: JSON.stringify(payload) }));
};

const main = async () => {
  const sender = await connectSocket(benchmarkEnv);
  const receiver = await connectSocket(benchmarkEnv);
  if (sender.channel !== receiver.channel || sender.channel !== `private-session.${benchmarkEnv.code}`) {
    throw new Error(`Benchmark clients subscribed to different channels: ${sender.channel}, ${receiver.channel}`);
  }
  console.log(`Both clients subscribed to ${sender.channel}`);

  const latencies = [];
  const roundTrips = [];

  await new Promise((resolve) => setTimeout(resolve, 500));

  for (let run = 1; run <= RUNS; run++) {
    const runId = crypto.randomUUID();
    const pending = new Map();
    const receivedIds = new Set();
    let receivedCount = 0;
    const onMessage = (raw) => {
      const event = JSON.parse(raw.toString());
      if (event.channel !== receiver.channel || event.event !== "client-stroke") return;
      const payload = typeof event.data === "string" ? JSON.parse(event.data) : event.data;
      if (payload.runId !== runId) return;
      if (!pending.has(payload.id)) {
        if (receivedIds.has(payload.id)) console.warn(`Duplicate websocket message ignored (run ${run}, id ${payload.id})`);
        return;
      }

      pending.delete(payload.id);
      receivedIds.add(payload.id);
      const receivedAt = Date.now();
      latencies.push({ run, latency_ms: Math.max(0, receivedAt - payload.sentAt) });
      receivedCount++;
      if (receivedCount > COUNT) throw new Error(`Received more than ${COUNT} websocket messages in run ${run}`);
      if (receivedCount % 50 === 0) console.log(`Received ${receivedCount}/${COUNT} websocket messages (run ${run})`);
    };
    receiver.socket.on("message", onMessage);

    for (let index = 0; index < COUNT; index++) {
      if (Math.random() < LOSS_RATE) continue;
      const id = crypto.randomUUID();
      const sentAt = Date.now();
      pending.set(id, true);
      whisper(sender.socket, sender.channel, "stroke", {
        id,
        runId,
        senderId: sender.socketId,
        sentAt,
        stroke: stroke(),
      });
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
    receiver.socket.off("message", onMessage);
    console.log(`Received ${receivedCount}/${COUNT} websocket messages (run ${run})`);
  }
  sender.socket.close();
  receiver.socket.close();
  await mkdir("benchmarks", { recursive: true });
  const file = `benchmarks/latency_${new Date().toISOString().replace(/[:.]/g, "-")}.csv`;
  await writeCsv(file, latencies.map((sample) => ({ ...sample, direction: "websocket" })), ["run", "direction", "latency_ms"]);
  printSummary("Latency", latencies.map((sample) => sample.latency_ms));
  console.log(`Server round-trip mean=${statistics(roundTrips).mean.toFixed(2)}ms`);
};

main().catch((error) => { console.error(error); process.exitCode = 1; });
