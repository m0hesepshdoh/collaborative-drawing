# Collaborative Drawing System

A full-stack collaborative drawing platform built with Laravel, Reverb, and a browser-based canvas interface. The project enables two participants to share a drawing session in real time, supports human moderation and administrative oversight, and includes a bot system that replays curated human-drawn patterns rather than generating arbitrary strokes.

## Abstract

This repository implements a browser-based multi-user drawing system designed to support synchronous visual collaboration with moderation, persistence, and limited automation. The application sits at the intersection of interactive web systems, event-driven communication, and human-in-the-loop curation. Human participants join a session using a six-character code and draw onto a shared canvas. Strokes are recorded and broadcast in near real time to the other participant using Laravel Reverb, while administrative users can inspect sessions, manage reports, restrict abusive IP addresses, and decide which recorded patterns are approved for bot replay.

The system is intentionally minimal in architectural scope: it avoids large frontend frameworks, complex service layers, and unnecessary abstraction. Instead, it relies on Laravel's conventional MVC structure, Eloquent models, queued jobs, and broadcast events to coordinate user activity and session state. This lean architecture makes the project suitable as a reference implementation for real-time browser collaboration, experimental bot behavior, and audit-friendly moderation infrastructure.

## Project Goals

The application is designed to satisfy the following primary objectives:

- provide real-time shared drawing between two human users
- support session lifecycle management, reporting, inactivity cleanup, and bans
- enable a lightweight admin interface for moderation and configuration
- allow a curated imitation bot to replay known human patterns
- collect session data that can be reviewed and approved for future bot behavior
- operate in a minimal, cost-effective, and source-controlled deployment model

## System Overview

The system is organized around a central session model. Each session receives a unique six-character identifier and is associated with a background, a set of players, and a set of recorded strokes. Participants are identified by their client IP address, which is also used for moderation, access control, and session validation. A human may create a new session, join an existing one, or interact with a bot-enabled session. Once a second human participant joins, or when a bot session has been created, the session becomes active and real-time drawing can begin.

The software stack consists of:

- PHP 8.3+ and Laravel 13 for application logic and HTTP routing
- MySQL as the default persistence layer
- Laravel Reverb for event-driven real-time broadcasts
- Laravel Echo in the browser for channel subscription and broadcasting
- Database-driven queue jobs for asynchronous bot behavior
- Blade templates for server-rendered administrative and session views
- HTML5 Canvas and vanilla JavaScript for interactive drawing on the client side

## Functional Description

### 1. Session lifecycle

A drawing session begins when a user creates a session or joins a valid session code. The session moves through states such as waiting, active, and ended, and may be finalized when certain conditions are reached, such as prescribed finish deadlines or explicit completion events. Session records retain metadata about the background, finish state, AI outputs, and timestamps for participation and activity.

The session model is backed by the `sessions` table and includes relationships to:

- players
- recorded strokes
- reports
- background metadata
- AI output information (when enabled)

The `players` table is used to track each participant's IP, join timestamp, last activity time, and whether the player is a human or bot. `left_at` is used to model temporary disconnects, removals, or session exits. These records provide the basis for session validation and moderation logic.

### 2. Real-time drawing synchronization

The application uses a shared-canvas design in which each stroke is generated on the client and submitted to the server. Each incoming stroke is validated, persisted as a recorded stroke, and then broadcast to the other participant(s) via a private Reverb channel. Client-side drawing logic draws on a local canvas using data contained in each broadcast payload.

This architecture keeps the real-time interaction lightweight and avoids excessive server-side rendering. The code treats stroke objects as canonical event payloads containing point sequences, color, size, and a client-side identifier. This makes it possible to de-duplicate or inspect events while retaining a consistent data model for later replay or moderation.

### 3. Moderation, policy, and anti-abuse controls

The project is built around an administrative moderation model rather than a generic user-account system. Participants are not authenticated via app users; instead, session membership is derived from client IP addresses. This is a deliberately narrow trust boundary intended for controlled or local deployments, but it allows the system to enforce session-level access checks, bans, and report-driven interventions.

The application supports:

- report creation when a participant identifies abuse or misconduct
- ban records keyed by IP address
- forced disconnects for banned or removed players
- inactivity-based cleanup and stale-session enforcement
- configurable admin path and IP allow-list
- password-based admin authentication

This design exposes the system to the known risk of IP spoofing when deployed behind untrusted proxies, which is why the project explicitly emphasizes deployment-time proxy control and proper environment configuration.

### 4. Admin panel and governance model

The admin panel is protected by three layers:

1. a configurable path such as `/admin-panel-xyz`
2. an allow-list of permitted IP addresses
3. a password-based session flag

Once authenticated, the admin can inspect active or waiting sessions, delete them, remove players, review reports, manage bans, upload backgrounds, and curate bot training patterns. The admin interface is implemented as a thin set of Laravel controllers and Blade templates, making it straightforward to extend while preserving the minimal code footprint of the project.

Administrative functions include:

- dashboard view for session monitoring
- settings for bot wait time and finish wait time
- session deletion and all-session cleanup
- per-player removal and broadcast kick
- background upload and deletion
- report review and ban management
- pattern approval and rejection

### 5. Bot behavior and imitation model

The bot system is intentionally curatorial rather than generative. Rather than inventing random strokes, the bot selects from a set of approved patterns stored in `bot_patterns` and replays them in sequence. This makes the bot behave more like an imitation agent derived from previously observed human behavior, while preserving an explicit human approval step.

The bot logic is implemented through queued jobs:

- `BotJoinJob` adds the bot after a configured delay if one human remains in a waiting session
- `BotDrawJob` chooses an approved pattern or falls back to synthetic random strokes if no approved patterns exist
- each stroke is persisted as a recorded stroke and broadcast to the active session

Patterns are created from recorded human drawings and only become operational after admin approval. This separation of collection and approval is critical to the system's intended design: it preserves human agency and keeps the imitation process auditable.

## Data Model

The core application tables are centered around session-oriented interaction and moderation.

### Sessions

The `DrawingSession` model stores the canonical session state and lifecycle metadata. Each session has a unique code, a background association, a status field, and optional AI-related values such as `finish_state`, `finished_by_ip`, `finish_deadline_at`, and `ai_image_url`.

It relates to:

- `players`: all participants in the session
- `recordedStrokes`: persistent drawing history for the session
- `reports`: reports attached to the session
- `background`: image metadata used as a visual overlay

### Players

The `Player` model records either human or bot participation. The `ip_address` column allows human players to be identified by their network address while bot rows are allowed to have no address. The `last_activity_at` and `left_at` timestamps support inactivity detection, cleanup, and custom moderation flows.

### Recorded strokes

The `RecordedStroke` model stores the actual drawing actions as JSON arrays of point pairs along with color, stroke size, and timestamp. This historical data supports both session replay and bot pattern approval workflows. The design keeps raw human-drawn traces separate from curated bot patterns, allowing a clear distinction between observed behavior and approved imitation behavior.

### Reports and bans

Reports and bans form the moderation backbone. Reports are created when a participant reports another user within a session, while bans associate a network address with a reason and are enforced at the route-middleware layer.

### App settings

`AppSetting` is a singleton-style configuration record used for application tuning, especially:

- bot wait seconds
- finish wait seconds
- AI generation enablement

This allows the admin panel to persist operational parameters without requiring custom env-only configuration for every runtime decision.

## Architecture and Execution Flow

The system follows a conventional Laravel request lifecycle, with event-driven updates layered over it.

1. A user reaches the landing page and either creates a session, joins by code, or starts a bot session.
2. The controller validates the request, creates or retrieves the session, and creates the corresponding player row.
3. Browser-side canvas interactions collect stroke data and submit them through session routes.
4. The server validates the stroke payload, stores it in `recorded_strokes`, and broadcasts the event to the other participant through the session channel.
5. Administrative actions are handled through authenticated, IP-restricted routes that govern moderate and configuration tasks.
6. Background tasks such as delayed bot join or timed bot draw execution are queued and executed asynchronously.
7. Data from drawing sessions can be reviewed by the admin, approved into bot patterns, and later replayed by the bot.

## Security and Deployment Considerations

The repository is intentionally explicit about its deployment assumptions and known risks. The product is designed for same-origin use and assumes a controlled web application environment. Several key operational considerations matter:

- sensitive values such as `ADMIN_PASSWORD`, `APP_KEY`, and Reverb secrets should stay in the environment
- HTTPS and a reverse proxy should be used before public exposure
- trusted proxy headers should be restricted to actual upstream infrastructure
- IP-based access control is only as safe as the gateway and network configuration
- admin access remains vulnerable to spoofed client addresses if reverse proxy trust is misconfigured

The application also includes a `not.banned` middleware gate for public session routes, which prevents banned addresses from creating or joining sessions.

## Performance Benchmarking

The repository includes a dedicated benchmark suite under `benchmarks/` and a set of Node-based scripts for measuring latency, throughput, reconnect reliability, bot replay behavior, AI response timing, and query cost. These benchmark artifacts are not merely supplementary—they provide empirical validation of the system's real-time performance profile.

The benchmark configuration captures:

- client-side latency across session events
- stroke throughput under load
- reconnect behavior during transient connection loss
- bot replay timing and behavior
- AI-generation latency under finalization workflows
- measurement aggregation and CSV/JUnit export

This component makes the project suitable for both demonstration and experimentation in distributed or low-latency network environments.

## AI Integration

The project includes optional AI enhancement via SenseNova image generation. When AI is enabled in the admin settings and a session has been finalized, the eligible participant may submit canvas data and optionally a prompt to the generate-AI endpoint. The server then calls the SenseNova API, stores the generated image under the public storage path, and records the resulting URL and prompt on the session model.

This capability is designed as an augmentation to the collaborative drawing workflow rather than a general-purpose conversational assistant. It is tightly scoped to image transformation and is subject to feature flags and runtime configuration. The integration is purposely separated from the rest of the drawing logic so it can be disabled without breaking the core collaboration flow.

## Installation and Setup

### Requirements

- PHP 8.3+
- Composer 2
- MySQL 8+
- Laravel-compatible PHP extensions as required by the selected environment
- Optional: SenseNova API access for AI generation

### Basic local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
```

Then configure the following environment values:

- `APP_URL`
- database credentials
- Reverb settings
- `ADMIN_PASSWORD`
- `ADMIN_ALLOWED_IPS`
- `ADMIN_PATH`
- `SENSENOVA_API_KEY` if AI generation is enabled

### Local runtime

Run the application services in separate terminals:

```bash
php artisan serve --host=0.0.0.0 --port=8000
php artisan reverb:start
php artisan queue:work
php artisan schedule:work
```

The queue worker supports delayed bot behavior, while the scheduler ensures stale players and ended sessions are cleaned up according to the app's rules.

## Validation and Testing

The project includes automated tests under `tests/` and uses PHPUnit through Laravel's testing infrastructure. These tests cover key interactions such as admin configuration, session finalization, AI settings, recording and broadcasting behavior, and bot fallback scheduling. The codebase also integrates benchmark scripts that can be used to evaluate latency and reliability under representative usage patterns.

Common validation commands:

```bash
php artisan test
./vendor/bin/pint --test
```

For benchmark exports and detailed evidence collection, the repository provides:

```bash
composer test:report
```

## Repository Structure

The repository is organized around conventional Laravel layout and experimental benchmarking scripts:

- `app/` — controllers, models, jobs, events, and middleware
- `config/` — application configuration including admin settings
- `database/` — migrations, factories, and seeders
- `routes/` — HTTP and broadcast route definitions
- `resources/views/` — Blade views for the public app and admin panel
- `public/` — served web assets and generated static files
- `storage/` — runtime file uploads and generated AI output
- `benchmarks/` — performance scripts and result archives
- `figures/` — plotting and visualization scripts for benchmark analysis
- `scripts/` — data processing and JUnit/export automation

## Summary

This project demonstrates a practical, minimal, and academically interpretable approach to synchronous collaborative drawing in a web environment. It combines real-time communication, event-driven processing, and human-directed moderation in a single Laravel application, while also incorporating a bot system that depends on curated human patterns rather than unrestricted algorithmic generation. The result is a system that is structurally simple, operationally transparent, and well-suited for teaching, experimentation, and benchmarking in the broader domain of collaborative web applications.
