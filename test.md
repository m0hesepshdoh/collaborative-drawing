You are working on a Laravel 13 collaborative drawing project (Laravel + Reverb + MySQL + Blade + vanilla JS + HTML5 Canvas). The project has:
- `public/js/canvas.js` — client-side drawing, stroke sync via Echo whisper, heartbeat
- `app/Http/Controllers/SessionController.php` — session create/join/stroke/heartbeat/finish/AI
- `routes/web.php` — all routes
- `app/Jobs/BotDrawJob.php` — bot replay of approved patterns
- `app/Models/` — Session, Player, RecordedStroke, BotPattern, AppSetting
- `tests/Feature/` — PHPUnit 11 feature tests


Your task: **add a complete performance-measurement and benchmarking layer** to this project so the author can produce a systems/performance paper. Do NOT change existing features. Only add instrumentation, a test harness, and export/report tooling. Keep code minimal, commented, and following Laravel conventions.

Implement everything below in order. After each step, run the relevant command and confirm it works before moving to the next.

---

### 1. Client-side latency instrumentation in `public/js/canvas.js`

Add a lightweight, opt-in measurement module that:

1.1. Reads a global flag `window.__MEASURE__` (default `false`). Only log when true.

1.2. Generates a per-tab `clientId` using `crypto.randomUUID()` and stores it in `sessionStorage`.

1.3. Records the following events with `performance.now()`:
- `stroke_send_start` — before `Echo.whisper("stroke", ...)`
- `stroke_send_end` — immediately after the whisper call
- `stroke_receive` — inside `listenForWhisper("stroke", ...)`, with the sender's `clientId` and the sender's `sentAt` timestamp included in the payload
- `heartbeat_send`, `heartbeat_ack`
- `reconnect_start`, `reconnect_end` — hook into Echo's `connecting`, `connected`, `disconnected` events
- `ai_request_start`, `ai_request_end` — around the `generate-ai` fetch

1.4. Include `sentAt: Date.now()` and `senderId: clientId` in every whisper payload so the receiver can compute one-way latency.

1.5. Buffer measurements in memory in an array `window.__measurements__`. Provide `window.dumpMeasurements()` that returns the array as JSON and `window.clearMeasurements()` that resets it.

1.6. Do NOT auto-upload. The author will manually export.

Add clear comments marking every block you add.

---

### 2. Server-side measurement sink

2.1. Create migration `create_measurements_table` with columns:
- `id` (bigint, primary)
- `session_code` (string, 6, indexed)
- `client_id` (string, uuid)
- `event_type` (string, indexed) — e.g. `stroke_send`, `stroke_receive`, `heartbeat`, `reconnect`, `ai_request`
- `value_ms` (float, nullable)
- `meta` (json, nullable)
- `created_at` (timestamp)

2.2. Create model `App\Models\Measurement` with `$fillable` and casts (`meta` => `array`).

2.3. Add a route `POST /session/{code}/measurements` handled by `SessionController@storeMeasurements` that:
- Validates `events` array of `{ event_type, client_id, value_ms, meta }`
- Inserts in bulk inside `DB::transaction`
- Returns `204`

2.4. Add a route `GET /admin-panel-xyz/measurements` (protected by existing admin middleware) handled by `AdminController@measurements` that:
- Returns a Blade view listing measurements grouped by `event_type`
- Provides a **Download CSV** button that hits `GET /admin-panel-xyz/measurements/export`
- `export` streams a CSV with columns: `session_code, client_id, event_type, value_ms, meta_json, created_at`

2.5. In `canvas.js`, add `window.uploadMeasurements(code)` that POSTs buffered events to `/session/{code}/measurements` using `fetch` with CSRF token from the meta tag.

---

### 3. Automated latency benchmark script

3.1. Create `scripts/benchmark_latency.mjs` (Node 20+, no external deps beyond `ws` and `node-fetch` which you may add via `npm i ws node-fetch`).

3.2. The script must:
- Read `BENCH_URL` (e.g. `http://127.0.0.1:8000`), `BENCH_CODE` (session code), `BENCH_SECRET` (a shared token stored in `.env` as `BENCH_SECRET`) from env.
- Open two WebSocket connections to Reverb (use the existing `REVERB_APP_KEY` and channel `private-session.{code}`). Authenticate via `POST /broadcasting/auth`.
- Send N=500 strokes from client A to client B. Each stroke is a small JSON payload with `sentAt` (server-synced via a one-time `Date.now()` handshake) and random 5-point path.
- On client B, record `receivedAt`. Compute `latency = receivedAt - sentAt`.
- Also measure server round-trip by sending an HTTP `POST /session/{code}/record-stroke` and timing the response.
- Repeat for N=500 with 10% packet loss simulated by randomly dropping messages.
- Output CSV to `benchmarks/latency_YYYYMMDD_HHMMSS.csv` with columns: `run, direction, latency_ms`.
- Print summary: mean, median, p95, p99, min, max, stddev.

3.3. Add a `benchmarks/` folder with a `.gitkeep` and a `README.md` explaining how to run it.

3.4. Add `BENCH_SECRET` to `.env.example`.

---

### 4. Throughput benchmark

4.1. Create `scripts/benchmark_throughput.mjs`.

4.2. Open N=2 WebSocket clients. Send strokes as fast as possible for 10 seconds. Count:
- Strokes sent
- Strokes received
- Strokes dropped
- Effective throughput (received / 10)

4.3. Repeat for N=4, 8, 16 simulated clients by spawning multiple WS connections against the same session.

4.4. Output CSV `benchmarks/throughput_YYYYMMDD_HHMMSS.csv` with columns: `clients, sent, received, dropped, duration_s, throughput_per_s`.

4.5. Print a table.

---

### 5. Reconnection benchmark

5.1. Create `scripts/benchmark_reconnect.mjs`.

5.2. Open one WebSocket client. Measure time from `disconnected` to `connected` after:
- 5-second simulated drop (close socket, wait 5s, reconnect)
- 10-second drop
- 30-second drop

5.3. After reconnect, verify channel re-subscription by sending a ping and awaiting a pong.

5.4. Output CSV `benchmarks/reconnect_YYYYMMDD_HHMMSS.csv` with columns: `drop_seconds, reconnect_ms, resubscribe_ms, success`.

---

### 6. Bot replay accuracy

6.1. Create `scripts/benchmark_bot.mjs` and an Artisan command `php artisan bench:bot-replay`.

6.2. The Artisan command:
- Picks a random approved `BotPattern`
- Simulates its replay by reading `strokes` JSON
- Compares replay to source by:
  - Cosine similarity of the flattened coordinate vector
  - Hausdorff distance between point clouds
  - Per-stroke length difference
- Writes CSV `benchmarks/bot_replay_YYYYMMDD_HHMMSS.csv` with columns: `pattern_id, strokes, cosine_sim, hausdorff, mean_length_diff`.

6.3. The Node script does the same end-to-end by triggering the bot and observing strokes received over WS.

---

### 7. AI edit success rate

7.1. Create `scripts/benchmark_ai.mjs`.

7.2. Load a sample canvas PNG (base64), send 50 requests to `POST /session/{code}/generate-ai`, record:
- HTTP status
- Response time
- Success (200) / disabled (409) / provider error (502) / validation (422) / server error (500)

7.3. Output CSV `benchmarks/ai_YYYYMMDD_HHMMSS.csv` with columns: `run, status, duration_ms, success`.

7.4. Print success rate and mean duration.

---

### 8. Database and memory measurement

8.1. Enable Laravel query logging in a middleware `MeasureQueries` that only activates when `APP_ENV=local` and `MEASURE_QUERIES=true`. Log per-request:
- Query count
- Total query time (ms)
- Slowest query

8.2. Write results to `storage/logs/queries.log` in JSON lines.

8.3. Create `scripts/summarize_queries.mjs` that reads the log and outputs `benchmarks/db_YYYYMMDD_HHMMSS.csv` with columns: `route, queries, total_ms, slowest_ms`.

8.4. For memory, add a `/admin-panel-xyz/health` JSON endpoint returning:
- `memory_get_peak_usage(true)`
- `memory_get_usage(true)`
- DB size via `SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = DATABASE()`

Log this every 60 seconds via a scheduled task into `storage/logs/health.log`.

---

### 9. PHPUnit test-count reporter

9.1. Add a Composer script `test:report` that runs `php artisan test --log-junit storage/logs/junit.xml` and then a small PHP script `scripts/parse_junit.php` that reads the JUnit XML and outputs `benchmarks/tests_YYYYMMDD_HHMMSS.csv` with columns: `suite, tests, assertions, failures, errors, time_s`.

9.2. Add a summary line printed to stdout:
`Tests: N | Assertions: M | Failures: F | Errors: E | Time: T s`

9.3. Update `README.md` with a "Benchmarks" section listing every command.

---

### 10. Aggregator script

10.1. Create `scripts/aggregate.mjs` that:
- Reads all CSVs in `benchmarks/`
- Produces a single `benchmarks/summary.json` with keys: `latency`, `throughput`, `reconnect`, `bot_replay`, `ai`, `db`, `tests`
- For latency: mean, median, p95, p99, min, max, stddev
- For throughput: max throughput, dropped rate
- For reconnect: mean reconnect time per drop duration
- For bot replay: mean cosine similarity, mean Hausdorff
- For AI: success rate, mean duration
- For tests: totals

10.2. Also produce `benchmarks/summary.md` with one Markdown table per metric, ready to paste into the paper.

---

### 11. Documentation

11.1. Update `README.md` with:
- A "Performance Benchmarking" section listing every script and command
- A "Reproducing Results" section: exact steps to run all benchmarks and produce `benchmarks/summary.md`
- Required `.env` variables: `BENCH_SECRET`, `MEASURE_QUERIES`

11.2. Add `benchmarks/README.md` explaining the CSV schema for each file.

---

### 12. Constraints

- Do NOT modify existing feature behavior.
- Do NOT add Redis, Vite, Livewire, or any frontend framework.
- Do NOT add external JS libraries to `canvas.js` beyond what already exists.
- Keep all new code under ~800 lines total.
- Every new file must have a header comment explaining its purpose.
- Every new function must have a one-line comment.
- After implementation, run:
  - `php artisan migrate`
  - `php artisan test`
  - `node scripts/benchmark_latency.mjs` (with a running Reverb and server)
  - `node scripts/aggregate.mjs`
- Report back with: files created, files modified, test results, and the contents of `benchmarks/summary.md`.