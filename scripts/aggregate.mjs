#!/usr/bin/env node
// Aggregates the CSV artifacts produced by the benchmark scripts into a paper-ready summary.
import { existsSync, mkdirSync, readdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';

const root = resolve(process.argv[2] ?? 'benchmarks');
const parseCsv = (file) => {
  const rows = readFileSync(file, 'utf8').trim().split(/\r?\n/);
  if (!rows.length) return [];
  const headers = rows.shift().split(',');
  const parseLine = (line) => {
    const fields = [];
    let current = '';
    let quoted = false;
    for (const character of line) {
      if (character === '"') {
        quoted = !quoted;
      } else if (character === ',' && !quoted) {
        fields.push(current);
        current = '';
      } else {
        current += character;
      }
    }
    fields.push(current);
    return fields;
  };
  return rows.map((line) => Object.fromEntries(headers.map((header, index) => [header, parseLine(line)[index] ?? ''])));
};
const median = (values) => {
  if (!values.length) return 0;
  const sorted = [...values].sort((a, b) => a - b);
  const middle = Math.floor(sorted.length / 2);
  return sorted.length % 2 ? sorted[middle] : (sorted[middle - 1] + sorted[middle]) / 2;
};
const percentile = (values, p) => {
  if (!values.length) return 0;
  const sorted = [...values].sort((a, b) => a - b);
  return sorted[Math.ceil((p / 100) * sorted.length) - 1];
};
const files = existsSync(root) ? readdirSync(root).filter((file) => file.endsWith('.csv')) : [];
const latency = [];
const throughput = [];
const reconnect = [];
const bot = [];
const ai = [];
for (const file of files) {
  const rows = parseCsv(resolve(root, file));
  if (file.startsWith('latency_')) latency.push(...rows.map((row) => ({ latency_ms: Number(row.latency_ms) })));
  else if (file.startsWith('throughput_')) throughput.push(...rows);
  else if (file.startsWith('reconnect_')) reconnect.push(...rows);
  else if (file.startsWith('bot_replay_')) bot.push(...rows);
  else if (file.startsWith('ai_')) ai.push(...rows);
}
const latencyValues = latency.map((row) => row.latency_ms).filter(Number.isFinite);
const throughputValues = throughput.map((row) => Number(row.throughput_per_s)).filter(Number.isFinite);
const summary = {
  generated_at: new Date().toISOString(),
  benchmark_files: files.length,
  latency_samples: latencyValues.length,
  latency_min_ms: latencyValues.length ? Math.min(...latencyValues) : null,
  latency_p50_ms: latencyValues.length ? median(latencyValues) : null,
  latency_p95_ms: latencyValues.length ? percentile(latencyValues, 95) : null,
  latency_max_ms: latencyValues.length ? Math.max(...latencyValues) : null,
  throughput_rows: throughput.length,
  throughput_mean_per_s: throughputValues.length ? throughputValues.reduce((sum, value) => sum + value, 0) / throughputValues.length : null,
  reconnect_rows: reconnect.length,
  bot_rows: bot.length,
  ai_rows: ai.length,
  ai_successes: ai.filter((row) => row.success === 'true').length,
  ai_skipped: ai.filter((row) => row.status === 'skipped' || row.success === 'skipped').length,
  ai_attempted: ai.filter((row) => row.status !== 'skipped' && row.success !== 'skipped').length,
  ai_success_rate: (() => {
    const attempted = ai.filter((row) => row.status !== 'skipped' && row.success !== 'skipped');
    return attempted.length ? attempted.filter((row) => row.success === 'true').length / attempted.length : null;
  })(),
};
const output = resolve(root, '..', 'storage', 'reports');
mkdirSync(output, { recursive: true });
const jsonPath = resolve(output, 'summary.json');
const markdownPath = resolve(output, 'summary.md');
writeFileSync(jsonPath, JSON.stringify(summary, null, 2) + '\n');
writeFileSync(markdownPath, `# Benchmark Summary\n\nGenerated: ${summary.generated_at}\n\n- Benchmark files: ${summary.benchmark_files}\n- Latency samples: ${summary.latency_samples}\n- Minimum latency: ${summary.latency_min_ms ?? 'not available'} ms\n- Median latency: ${summary.latency_p50_ms ?? 'not available'} ms\n- P95 latency: ${summary.latency_p95_ms ?? 'not available'} ms\n- Maximum latency: ${summary.latency_max_ms ?? 'not available'} ms\n- Throughput rows: ${summary.throughput_rows}\n- Mean throughput: ${summary.throughput_mean_per_s ?? 'not available'} strokes/s\n- Reconnect rows: ${summary.reconnect_rows}\n- Bot replay rows: ${summary.bot_rows}\n- AI attempts: ${summary.ai_attempted}\n- AI skipped: ${summary.ai_skipped}\n- AI successes: ${summary.ai_successes}\n- AI success rate: ${summary.ai_success_rate === null ? 'not available' : `${(summary.ai_success_rate * 100).toFixed(2)}%`}\n`);
console.log(`Wrote ${jsonPath} and ${markdownPath}`);
