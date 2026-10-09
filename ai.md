> Create a benchmark-only controller and route for clock synchronization.
>
> 1. Create `app/Http/Controllers/BenchController.php` with a method `timestamp(Request $request)` that:
>    - Reads `config('services.bench_secret')` and compares it with `$request->header('X-Bench-Secret')`. Return `403` on mismatch.
>    - Returns JSON `{ "server_time_ms": round(microtime(true) * 1000) }`.
>
> 2. Add to `config/services.php`:
>    ```php
>    'bench_secret' => env('BENCH_SECRET'),
>    ```
>
> 3. Add the route in `routes/web.php`:
>    ```php
>    Route::post('/bench/timestamp', [BenchController::class, 'timestamp']);
>    ```
>
> 4. Exclude `/bench/*` from CSRF in `bootstrap/app.php` by adding the path to `validateCsrfTokens(except: ['bench/*'])`.
>
> 5. Run `php artisan route:list | grep bench` and confirm the route appears.
>
> 6. Run `curl -i -X POST -H "X-Bench-Secret: $BENCH_SECRET" http://127.0.0.1:8000/bench/timestamp` and confirm it returns JSON with `server_time_ms`.
