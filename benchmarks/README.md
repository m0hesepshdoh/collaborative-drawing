# Performance Benchmark Suite

This suite measures the collaborative drawing application without changing normal application behavior. All benchmark routes are opt-in: the benchmark secret must be configured, and client instrumentation remains disabled unless `window.__MEASURE__` is set to `true`.

## Prerequisites

1. Configure the application and Reverb with a shared `BENCH_SECRET` and `BENCH_CODE`.
2. Create an active session with the benchmark code before running a benchmark.
3. Install Node dependencies with `npm install`.
4. Run the application and Reverb on the URL configured by `BENCH_URL`.
5. Keep the benchmark server and database consistent across each run; use a fresh session for each measurement.

## Commands

```bash
node scripts/benchmark_latency.mjs
node scripts/benchmark_throughput.mjs
node scripts/benchmark_reconnect.mjs
node scripts/benchmark_bot.mjs
node scripts/benchmark_ai.mjs
```

Each script writes a timestamped CSV file to `benchmarks/`. The latency script measures WebSocket delivery and server round-trip behavior; throughput measures delivered strokes and drop rate; reconnect measures controlled disconnect/reconnect and channel re-subscription; bot replay compares the replayed geometry; and AI measures provider request latency and success rate.

The scripts use real Reverb connections and real HTTP requests. Do not treat benchmark output as a production SLA; preserve the raw CSV files with the run metadata and report warm-up, load, and hardware details in the paper.

## Query and test reporting

```bash
MEASURE_QUERIES=true php artisan bench:health
composer test:report
node scripts/summarize_queries.mjs storage/logs/queries.log benchmarks/query_summary.csv
node scripts/aggregate.mjs benchmarks
```

`MEASURE_QUERIES` is local-only. The middleware appends one JSON object per request to `storage/logs/queries.log`; run the query summarizer after an instrumented request. `composer test:report` writes JUnit XML, converts it to CSV, and prints the test totals.

## Health and export

```bash
php artisan bench:health
php artisan route:list --path=measurements
```

The administrator measurement page and CSV export use the persisted client measurements. Health data is written to `storage/logs/health.log` and the `/admin/{admin_path}/health` endpoint returns memory and database-size observations.

## Reproducibility

Record the following with every run:

- CPU model and core count
- Operating system and container/runtime version
- PHP, Node, Laravel, and Reverb versions
- Database engine and version
- Number of concurrent clients and benchmark duration
- Warm-up and sample count
- Raw CSV files and generated JSON/Markdown summaries
