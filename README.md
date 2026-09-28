# Collaborative Drawing Website

Laravel 13 implementation of the supplied specification: two-person drawing sessions, Laravel Reverb/Echo synchronization, database queue, inactivity removal, reporting/banning, admin-managed backgrounds, human stroke recording, and an admin-approved imitation bot.

## Requirements
- PHP 8.3+
- Composer 2
- MySQL 8+
- PHP extensions normally required by Laravel

## Install
```bash
cp .env.example .env
composer install
php artisan key:generate
# edit .env database, Reverb and admin values
php artisan migrate
php artisan storage:link
```

Upload at least one background through the admin panel, or sessions will simply use a white canvas.

## Run locally
Use four terminals (the scheduler is required for the 3-minute inactivity kick):
```bash
php artisan serve
php artisan serve --host=0.0.0.0 --port=8000
php artisan reverb:start
php artisan queue:work
php artisan schedule:work
```
Open `http://127.0.0.1:8000`.

Admin login defaults to `/admin-panel-xyz/login`; both the path and IP allow-list are controlled by `.env`. Change `ADMIN_PASSWORD` before use.

## Important implementation detail
Echo client events (`whisper`) require a private channel. Players intentionally have no accounts, so `/broadcasting/auth` uses `SessionBroadcastAuthMiddleware` to authenticate the current IP as an active player only for the requested session channel. No player registration system is introduced.

## Schema note
The application drawing-session table is named `sessions` as specified. Laravel's own database-backed HTTP session table is named `http_sessions` to avoid a name collision.

## Spec ambiguity resolved
The supplied route list defines two different DELETE actions on the same `/bot-patterns/{id}` URI (reject recording vs delete approved pattern). That cannot be routed deterministically. This project keeps `DELETE /bot-patterns/{id}` for deleting approved patterns and uses `DELETE /recordings/{id}` to reject recorded drawings.

## Production
Serve through HTTPS, set Reverb to TLS, use a reverse proxy, and supervise `reverb:start`, `queue:work`, and `schedule:work`. Use a long random admin password and a strict IP allow-list.
