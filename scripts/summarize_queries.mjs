#!/usr/bin/env node
// Converts the JSON-lines query log into a compact, reproducible CSV report.
import { readFileSync, mkdirSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';

const input = resolve(process.argv[2] ?? 'storage/logs/queries.log');
const output = resolve(process.argv[3] ?? 'benchmarks/query_summary.csv');
const records = readFileSync(input, 'utf8').split(/\r?\n/).filter(Boolean).map((line) => JSON.parse(line));
const rows = records.map((record) => ({
  route: record.route,
  queries: Number(record.queries),
  total_ms: Number(record.total_ms),
  slowest_ms: Number(record.slowest_ms),
}));
const csv = ['route,queries,total_ms,slowest_ms', ...rows.map((row) => [row.route, row.queries, row.total_ms, row.slowest_ms].map((value) => `"${String(value).replaceAll('"', '""')}"`).join(','))].join('\n') + '\n';
mkdirSync(dirname(output), { recursive: true });
writeFileSync(output, csv);
const totalQueries = rows.reduce((sum, row) => sum + row.queries, 0);
const totalTime = rows.reduce((sum, row) => sum + row.total_ms, 0);
console.log(`Requests: ${rows.length} | Queries: ${totalQueries} | Total: ${totalTime.toFixed(3)} ms`);
