// Measures AI generation request status and duration against the configured session.
import { mkdir, writeFile } from "node:fs/promises";
import { deflateSync } from "node:zlib";

const RUNS = 50;

// Build a small valid PNG canvas so the provider accepts the benchmark image.
const sampleImage = () => {
  const width = 256;
  const height = 256;
  const stride = width * 4 + 1;
  const pixels = Buffer.alloc(stride * height, 255);
  for (let y = 0; y < height; y++) pixels[y * stride] = 0;
  for (let x = 24; x < width - 24; x++) {
    const centerY = Math.round(height / 2 + 42 * Math.sin(x / 18));
    for (let thickness = -2; thickness <= 2; thickness++) {
      const offset = (centerY + thickness) * stride + 1 + x * 4;
      pixels.fill(30, offset, offset + 3);
    }
  }
  const crc32 = (data) => {
    let crc = 0xffffffff;
    for (const byte of data) {
      crc ^= byte;
      for (let bit = 0; bit < 8; bit++) crc = (crc >>> 1) ^ (0xedb88320 & -(crc & 1));
    }
    return (crc ^ 0xffffffff) >>> 0;
  };
  const chunk = (name, data) => {
    const type = Buffer.from(name);
    const length = Buffer.alloc(4);
    length.writeUInt32BE(data.length);
    const checksum = Buffer.alloc(4);
    checksum.writeUInt32BE(crc32(Buffer.concat([type, data])));
    return Buffer.concat([length, type, data, checksum]);
  };
  const header = Buffer.alloc(13);
  header.writeUInt32BE(width, 0);
  header.writeUInt32BE(height, 4);
  header[8] = 8;
  header[9] = 6;
  return `data:image/png;base64,${Buffer.concat([
    Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]),
    chunk("IHDR", header),
    chunk("IDAT", deflateSync(pixels)),
    chunk("IEND", Buffer.alloc(0)),
  ]).toString("base64")}`;
};

const main = async () => {
  await mkdir("benchmarks", { recursive: true });
  const rows = [];
  if (!process.env.SENSENOVA_API_KEY) {
    console.log("AI benchmark skipped: no SENSENOVA_API_KEY configured");
    const file = `benchmarks/ai_${new Date().toISOString().replace(/[:.]/g, "-")}.csv`;
    await writeFile(file, "run,status,http_status,duration_ms,success\n0,skipped,,0,skipped\n", "utf8");
    console.log(`Wrote ${file}`);
    return;
  }

  const { benchmarkEnv, establishBenchmarkSession, writeCsv } = await import("./benchmark_common.mjs");
  const session = await establishBenchmarkSession({ url: benchmarkEnv.url, code: benchmarkEnv.code });
  const imageDataUrl = sampleImage();
  let successes = 0;
  for (let run = 1; run <= RUNS; run++) {
    const started = performance.now();
    let status = "failed";
    let httpStatus = "";
    let providerStatus = "";
    let success = false;
    try {
      const response = await fetch(`${benchmarkEnv.url}/session/${benchmarkEnv.code}/generate-ai`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": session.csrfToken,
          "X-Benchmark-Secret": benchmarkEnv.secret,
          Cookie: session.cookies,
          Accept: "application/json",
        },
        body: JSON.stringify({ image_data_url: imageDataUrl, prompt: "Benchmark drawing" }),
      });
      const responseBody = await response.text();
      httpStatus = response.status;
      success = response.status === 200;
      status = success ? "success" : "failed";
      if (!success) {
        console.error(`AI request ${run} failed: HTTP ${httpStatus}; body: ${responseBody.slice(0, 200)}`);
        try {
          const payload = JSON.parse(responseBody);
          providerStatus = payload.provider_status ?? "";
          if (payload.provider_error) console.error(`Raw SenseNova provider error: ${payload.provider_error}`);
        } catch {}
      }
    } catch (error) {
      console.error(`AI request ${run} failed: ${error.message.slice(0, 200)}`);
    }
    const duration = performance.now() - started;
    successes += success ? 1 : 0;
    rows.push({ run, status, http_status: httpStatus, provider_status: providerStatus, duration_ms: duration, success: success ? "true" : "false" });
    if (providerStatus === 429) {
      console.error(`SenseNova quota exhausted on request ${run}; stopping remaining attempts.`);
      break;
    }
  }
  const file = `benchmarks/ai_${new Date().toISOString().replace(/[:.]/g, "-")}.csv`;
  await writeCsv(file, rows, ["run", "status", "http_status", "provider_status", "duration_ms", "success"]);
  const attempts = rows.filter((row) => row.status !== "skipped").length;
  console.log(`Success rate: ${successes}/${attempts} (${(successes / Math.max(1, attempts) * 100).toFixed(2)}%)`);
  console.log(`Mean duration: ${(rows.reduce((sum, row) => sum + row.duration_ms, 0) / Math.max(1, rows.length)).toFixed(2)}ms`);
  console.log(`Wrote ${file}`);
};
main().catch((error) => { console.error(error); process.exitCode = 1; });
