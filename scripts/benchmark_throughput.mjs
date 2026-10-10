// Measures aggregate stroke throughput and drop rate for increasing WebSocket client counts.
import { mkdir } from "node:fs/promises";
import { benchmarkEnv, connectSocket, writeCsv } from "./benchmark_common.mjs";

const DURATION_SECONDS = 10;
const CLIENT_COUNTS = [2, 4, 8, 16];

const stroke = () => ({
  points: [{ x: 1, y: 1 }, { x: 2, y: 2 }],
  color: "#123456",
  size: 5,
  client_stroke_id: crypto.randomUUID(),
});

const main = async () => {
  await mkdir("benchmarks", { recursive: true });
  const rows = [];
  for (const clients of CLIENT_COUNTS) {
    const connections = await Promise.all(Array.from({ length: clients }, () => connectSocket(benchmarkEnv)));
    const expectedChannel = `private-session.${benchmarkEnv.code}`;
    if (connections.some(({ channel }) => channel !== expectedChannel)) {
      throw new Error(`Benchmark clients did not subscribe to ${expectedChannel}`);
    }
    let sent = 0;
    let received = 0;
    let dropped = 0;
    const clientReceived = connections.map(() => 0);
    connections.forEach(({ socket, channel }, clientIndex) => {
      socket.on("message", (raw) => {
        const event = JSON.parse(raw.toString());
        if (event.channel !== channel || event.event !== "client-stroke") return;
        received++;
        clientReceived[clientIndex]++;
      });
      socket.on("error", () => { dropped++; });
    });
    await new Promise((resolve) => setTimeout(resolve, 250));
    const start = Date.now();
    const interval = setInterval(async () => {
      const id = crypto.randomUUID();
      sent++;
      const response = await fetch(`${benchmarkEnv.url}/benchmark/${benchmarkEnv.code}/stroke`, {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-Benchmark-Secret": benchmarkEnv.secret },
        body: JSON.stringify({ id, sent_at: Date.now(), stroke: stroke() }),
      });
      if (!response.ok) {
        dropped++;
        if (dropped === 1) console.warn(`Benchmark stroke rejected: ${response.status} ${await response.text()}`);
      }
    }, 50);
    await new Promise((resolve) => setTimeout(resolve, DURATION_SECONDS * 1000));
    clearInterval(interval);
    await new Promise((resolve) => setTimeout(resolve, 100));
    const elapsed = (Date.now() - start) / 1000;
    rows.push({ clients, sent, received, dropped, duration_s: elapsed, throughput_per_s: received / elapsed });
    console.table(clientReceived.map((count, index) => ({ client: index + 1, received: count })));
    connections.forEach(({ socket }) => socket.close());
  }
  const file = `benchmarks/throughput_${new Date().toISOString().replace(/[:.]/g, "-")}.csv`;
  await writeCsv(file, rows, ["clients", "sent", "received", "dropped", "duration_s", "throughput_per_s"]);
  console.table(rows);
  console.log(`Wrote ${file}`);
};

main().catch((error) => { console.error(error); process.exitCode = 1; });
