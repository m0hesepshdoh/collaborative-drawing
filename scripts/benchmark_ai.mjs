// Measures AI generation request status and duration against the configured session.
import { mkdir } from "node:fs/promises";
import { benchmarkEnv, writeCsv } from "./benchmark_common.mjs";

const RUNS = 50;
const sample = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB";
const main = async () => {
  await mkdir("benchmarks", { recursive: true });
  const rows = [];
  let successes = 0;
  for (let run = 1; run <= RUNS; run++) {
    const started = performance.now();
    const response = await fetch(`${benchmarkEnv.url}/session/${benchmarkEnv.code}/generate-ai`, {
      method: "POST",
      headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": benchmarkEnv.secret },
      body: JSON.stringify({ image_data_url: sample, prompt: "Benchmark drawing" }),
    });
    const duration = performance.now() - started;
    const status = response.status;
    const success = status === 200;
    successes += success ? 1 : 0;
    rows.push({ run, status, duration_ms: duration, success: success ? "true" : "false" });
  }
  const file = `benchmarks/ai_${new Date().toISOString().replace(/[:.]/g, "-")}.csv`;
  await writeCsv(file, rows, ["run", "status", "duration_ms", "success"]);
  console.log(`Success rate: ${successes}/${RUNS} (${(successes / RUNS * 100).toFixed(2)}%)`);
  console.log(`Mean duration: ${(rows.reduce((sum, row) => sum + row.duration_ms, 0) / RUNS).toFixed(2)}ms`);
  console.log(`Wrote ${file}`);
};
main().catch((error) => { console.error(error); process.exitCode = 1; });
