# Collaborative Drawing Website System — Full Specification Prompt

**Project:** Collaborative Drawing Website System  
**Framework:** Laravel (latest stable)  
**Real-time:** Laravel Reverb + Laravel Echo + Pusher JS protocol  
**Frontend:** Blade, Vanilla JavaScript, HTML5 Canvas, Tailwind CDN  
**Database:** MySQL  
**Queue:** Database driver (no Redis)  
**Admin Access:** Custom URL + IP whitelist + **password prompt**  
**Bot:** Imitation bot that replays **admin-approved** human drawing patterns  
**Code Style:** Minimal — no Livewire, no Vue, no Vite, no third-party UI kits

---

## 1. Overview

Build a web application that allows two users (from different parts of the world) to collaborate on a shared drawing canvas in real time. The system includes:

- A **landing page** with three entry options: enter a session code, start a new session, or play with an AI bot.
- **Real-time drawing synchronization** between two participants using **Laravel Reverb** (self-hosted WebSockets).
- An **imitation bot** that replays human drawing patterns — but **only patterns explicitly approved by the admin**. The bot records real human drawings and lets the admin curate which ones it may imitate.
- **Admin panel** accessible via a **custom URL**, restricted by **IP whitelist**, and protected by a **password prompt**. The admin can manage sessions, players, background images, reports, bans, recorded human drawings, and approved bot patterns.
- **Robust features**: inactivity kick, reporting, IP banning, background image overlay at 30% opacity, multi‑color pens, and session limits (max two IPs per session).

The system must be **simple to use**, **robust**, **secure**, and **built with minimal code**. No additional features beyond those described here should be implemented.

---

## 2. User Roles

| Role       | Description                                                                                                         |
| ---------- | ------------------------------------------------------------------------------------------------------------------- |
| **Guest**  | Visits landing page, can create/join sessions or play with bot.                                                     |
| **Player** | A human participant in a drawing session (max 2 per session).                                                       |
| **Bot**    | Virtual player that draws by replaying admin-approved human patterns. Occupies one player slot.                     |
| **Admin**  | Manages the system via a password-protected, IP-restricted custom URL. Approves which drawings the bot can imitate. |

---

## 3. Functional Requirements

### 3.1 Landing Page

- **Background:**
  - Display a blurred grid of live previews from ongoing sessions (up to 6 sessions).
  - If there are fewer than 3 ongoing sessions, play a background video (e.g., abstract art) instead.
  - The background must be visually appealing and not distract from the three main options.

- **Three Main Options (centered on top of background):**
  1. **Enter Code:**
     - Input field for a session code.
     - On submit, validate code:
       - Session exists and is active.
       - Less than 2 human IPs already in session.
       - Requesting IP is not banned.
     - If valid, join the session.
     - If invalid, show error message.
  2. **Start Session:**
     - Creates a new session with a unique 6‑character alphanumeric code.
     - Displays the code prominently below the button.
     - The creator becomes the first player.
     - Waits for a second player.
     - If no second human joins within **60 seconds**, a bot automatically joins.
     - The creator can share the code.
  3. **Play with Bot:**
     - Immediately creates a session with a bot as the second player.
     - The human is the only human in the session.

- **Ban Check:** If the visitor's IP is banned, all three options are disabled and a message is shown: _"Your IP has been banned from this service."_

- **Privacy Notice:** A small footer note: _"Drawings may be recorded and, if approved by the admin, used anonymously to improve the bot's drawing behaviour."_

### 3.2 Session Management

- **Session Code:** 6 characters, uppercase letters and digits. Must be unique.
- **Max Players:** 2 participants per session. A participant can be human or bot.
  - Human players are identified by IP address.
  - A session can have at most 2 distinct human IPs.
  - If a bot is present, only 1 human IP can join.
  - If 2 humans are present, no bot can join.
- **Session States:** `waiting`, `active`, `ended`.
- **Leaving:**
  - Any player can leave at any time.
  - If a human leaves, their slot becomes empty. A new human with a different IP can join using the code.
  - If both humans leave, the session ends and is deleted after 5 minutes of inactivity.
- **Inactivity Kick:**
  - A human player is considered inactive if no mouse movement or drawing occurs for **3 minutes**.
  - Inactive players are kicked from the session.
  - They can rejoin using the same code if the session is still active and a slot is available.
- **Reporting:**
  - A player can report the session (usually reporting the other player).
  - Reporting automatically leaves the session.
  - A report record is created with: session code, reporter IP, reported IP, timestamp.
  - Admin can view all reports and ban the reported IP.

### 3.3 Drawing Canvas

- **Canvas:** Full‑screen HTML5 Canvas.
- **Background Image:**
  - A random image from the admin‑managed background collection is assigned to each session.
  - The image is displayed at **30% opacity** on the canvas.
  - Players draw on top of this image.
- **Tools:**
  - Pen with multiple colors (color picker).
  - Multiple pen sizes (e.g., 2px, 5px, 10px, 20px).
  - Eraser (optional, but recommended).
  - Clear canvas button (clears all strokes, keeps background).
- **Real-time Sync (via Reverb):**
  - Every stroke (mousedown → mousemove → mouseup) is broadcast to the other participant via Laravel Echo (Reverb connection).
  - Use **client events (whisper)** for strokes to avoid needing PHP event classes.
  - Throttle mousemove events to send every 50ms to avoid flooding.
  - On receiving a stroke, draw the line on the local canvas.
- **Touch Support:** Enable drawing on mobile devices using touch events.

### 3.4 Bot Behaviour (Imitation Bot)

- **Trigger:**
  - Joins automatically if no human joins within 60 seconds of session creation.
  - Joins immediately when "Play with Bot" is selected.

- **Drawing Style — Imitation Mode:**
  - The bot **does not invent random drawings by default**. Instead, it **replays approved human drawing patterns**.
  - A "pattern" is a recorded sequence of strokes (points, colors, sizes, timing) captured from a real human session.
  - On each bot action, the bot picks a **random approved pattern** from the database and replays it stroke-by-stroke, respecting the original timing (with slight randomization to feel natural).
  - If multiple patterns exist, rotate through them so the bot's output feels varied.

- **Fallback Mode:**
  - If **no approved patterns exist** in the database (e.g., brand-new deployment), the bot falls back to the original random-stroke behaviour (random lines, colors, sizes, 1–3s delay).

- **Recording Human Drawings (for future bot patterns):**
  - During every human session, the server records each player's strokes into a `recorded_strokes` table (session_id, player_ip, points, color, size, timestamp).
  - These recordings are **not used by the bot** until the admin approves them.

- **Implementation:**
  - `BotDrawJob` queries `bot_patterns` where `approved = true`.
  - If results exist → replay a random pattern.
  - If none exist → generate a random stroke (fallback).
  - Pattern replay dispatches a broadcast event on the same Reverb channel as humans.

- **No Real-Time Learning:**
  - The bot does **not** learn in real time.
  - "Imitation" strictly means replaying recorded stroke sequences that the admin has approved.
  - There is no machine-learning model involved.

### 3.5 Admin Panel

- **Access — Three Layers of Protection:**
  1. **Custom URL:** e.g., `/admin-panel-xyz` (configurable in `.env`).
  2. **IP Whitelist:** Only IPs listed in `config/admin.php` can access (via `AdminIpMiddleware`).
  3. **Password Prompt:** Even from a whitelisted IP, the admin must enter a password to access the panel.
     - Password is stored in `.env` (`ADMIN_PASSWORD`) and hashed at runtime or configured as a hash.
     - A simple login form (`/admin-panel-xyz/login`) collects the password.
     - On success, the admin session is flagged (`session(['admin_authenticated' => true])`).
     - A second middleware (`AdminAuthMiddleware`) checks this flag on every admin route.
     - A "Logout" button clears the flag.
     - **No user accounts, no registration — just one shared password.**

- **Features:**
  1. **Dashboard:**
     - List all ongoing sessions (active and waiting).
     - Columns: Session Code, Status, Players (IPs / Bot), Start Time, Last Activity.
     - Actions: View, Delete, Remove Player.
  2. **Delete Session:** Delete a single session and all related data (players, reports, recordings).
  3. **Delete All Sessions:** Wipe all sessions.
  4. **Remove Player:** Kick a specific player (human or bot) from a session.
     - The player is disconnected via a broadcast event.
     - The slot becomes empty.
  5. **Manage Background Photos:**
     - Upload new images (stored in `storage/app/public/backgrounds`).
     - Delete existing images.
     - List all images with thumbnails.
  6. **Reports:**
     - View all reports: Session Code, Reporter IP, Reported IP, Timestamp.
     - Action: Ban Reported IP (one‑click).
  7. **Ban Management:**
     - List all banned IPs with reason and date.
     - Add ban manually (IP + reason).
     - Remove ban (unban).
  8. **Bot Pattern Management:**
     - View all **recorded strokes** grouped by session, with a thumbnail preview of the drawing.
     - View all **pending bot patterns** (not yet approved).
     - **Approve** a recording → it becomes an approved `bot_pattern` and is used by the bot.
     - **Reject / Delete** a recording → it is discarded and never used.
     - **Delete** an approved pattern → the bot stops using it.
     - Filter by: session code, player IP, date, approval status.
     - Preview the pattern by replaying it on a small canvas in the admin panel.

### 3.6 Ban System

- **Banned IPs** are stored in the `bans` table.
- **Middleware** `CheckBannedIp` is applied to all session‑related routes (create, join, play with bot).
- Banned users see a message: _"Your IP has been banned. Contact support."_
- Banned IPs cannot:
  - Create a session.
  - Join a session.
  - Play with a bot.
- Admin can ban/unban at any time.

---

## 4. Non‑Functional Requirements

- **Minimal-Code Constraint:**
  - Use Blade + Tailwind CDN + vanilla JS.
  - No Livewire, no Vue, no React, no Vite build step.
  - Use Laravel Echo + Pusher JS (Reverb uses the Pusher protocol).
  - Use **client events (whisper)** for drawing strokes to avoid writing PHP event classes for every stroke.
  - Use the **database queue driver** (no Redis).
  - Total code should stay roughly under 2,000 lines. Prioritize working functionality over polish.

- **Robustness:** Handle WebSocket disconnections gracefully. Echo will auto-reconnect. If Reverb is down, the page should show a warning.

- **Security:**
  - CSRF protection on all POST requests.
  - XSS prevention (escape output).
  - IP spoofing prevention: use `request()->ip()`.
  - Admin panel protected by custom URL + IP whitelist + password.
  - Admin password stored in `.env`, never committed.

- **Performance:**
  - Throttle WebSocket events.
  - Optimize canvas rendering (avoid redrawing entire canvas on each stroke).

- **Responsive Design:**
  - Works on desktop, tablet, and mobile.
  - Canvas scales to fit screen while maintaining aspect ratio.

- **Code Quality:**
  - Follow Laravel best practices (MVC, Jobs, Middleware).
  - Validate inline (no Form Request classes needed for minimal scope).
  - Write clean, commented code.

- **Privacy Note:** Players are informed via a footer note that drawings may be recorded and used anonymously to train the bot's imitation behaviour.

---

## 5. Database Schema

| Table              | Columns                                                                                                            |
| ------------------ | ------------------------------------------------------------------------------------------------------------------ |
| `sessions`         | id, code, background_id, status, created_at, updated_at                                                            |
| `players`          | id, session_id, ip_address (nullable for bot), is_bot, last_activity_at, joined_at, left_at                        |
| `backgrounds`      | id, filename, path, created_at                                                                                     |
| `reports`          | id, session_id, reporter_ip, reported_ip, created_at                                                               |
| `bans`             | id, ip_address, reason, created_at                                                                                 |
| `recorded_strokes` | id, session_id, player_ip, points (JSON), color, size, drawn_at, created_at                                        |
| `bot_patterns`     | id, name, source_session_id, source_ip, strokes (JSON), approved (boolean, default false), approved_at, created_at |

**Relationships:**

- Session has many Players.
- Session belongs to Background.
- Session has many Reports.
- Session has many RecordedStrokes.
- BotPattern optionally belongs to a source Session.

**Notes:**

- `recorded_strokes` holds _raw_ captured data from every session.
- `bot_patterns` holds _curated_ patterns that the admin has reviewed and approved.
- When the admin approves a recording, a row is created in `bot_patterns` (or the recording is copied over).

---

## 6. Routes

### Web Routes

```php
// Landing
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Session
Route::post('/session/create', [SessionController::class, 'create'])->name('session.create');
Route::post('/session/join', [SessionController::class, 'join'])->name('session.join');
Route::get('/session/{code}', [SessionController::class, 'show'])->name('session.show');
Route::post('/session/{code}/leave', [SessionController::class, 'leave'])->name('session.leave');
Route::post('/session/{code}/report', [SessionController::class, 'report'])->name('session.report');
Route::post('/session/{code}/clear', [SessionController::class, 'clear'])->name('session.clear');
Route::post('/session/{code}/record-stroke', [SessionController::class, 'recordStroke'])->name('session.recordStroke');

// Admin login (IP-restricted, but NOT password-gated — this is the password entry point)
Route::middleware(['admin.ip'])->prefix('admin-panel-xyz')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});

// Admin panel (IP-restricted + password-gated)
Route::middleware(['admin.ip', 'admin.auth'])->prefix('admin-panel-xyz')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::delete('/session/{id}', [AdminController::class, 'deleteSession'])->name('admin.session.delete');
    Route::delete('/sessions/all', [AdminController::class, 'deleteAllSessions'])->name('admin.sessions.deleteAll');
    Route::delete('/session/{id}/player/{playerId}', [AdminController::class, 'removePlayer'])->name('admin.player.remove');
    Route::resource('backgrounds', BackgroundController::class);
    Route::get('/reports', [AdminController::class, 'reports'])->name('admin.reports');
    Route::post('/ban', [AdminController::class, 'ban'])->name('admin.ban');
    Route::delete('/ban/{id}', [AdminController::class, 'unban'])->name('admin.unban');

    // Bot pattern management
    Route::get('/bot-patterns', [BotPatternController::class, 'index'])->name('admin.botPatterns.index');
    Route::get('/bot-patterns/{id}/preview', [BotPatternController::class, 'preview'])->name('admin.botPatterns.preview');
    Route::post('/bot-patterns/{id}/approve', [BotPatternController::class, 'approve'])->name('admin.botPatterns.approve');
    Route::delete('/bot-patterns/{id}/reject', [BotPatternController::class, 'reject'])->name('admin.botPatterns.reject');
    Route::delete('/bot-patterns/{id}', [BotPatternController::class, 'destroy'])->name('admin.botPatterns.destroy');
    Route::get('/recordings', [BotPatternController::class, 'recordings'])->name('admin.recordings.index');
});
```

### Broadcasting Channels

```php
// routes/channels.php
Broadcast::channel('session.{code}', function ($user, $code) {
    // Authorize if user is a player in the session (by IP) or bot
    // Return true if authorized
});
```

**Note:** Drawing strokes use **client events (whisper)** on the `session.{code}` channel. Because they use `whisper()`, they bypass the server entirely and don't need PHP event classes. Only join/leave/kick events need server-side broadcasting.

### Events (minimal — only for lifecycle, not strokes)

- `PlayerJoined`
- `PlayerLeft`
- `PlayerKicked`
- `BotJoined`
- `CanvasCleared`

**Strokes are NOT events** — they use Echo client whisper events.

---

## 7. Implementation Details

### 7.1 Reverb Setup

Install via:

```bash
php artisan install:broadcasting
```

Choose Reverb. This creates `config/reverb.php`, adds credentials to `.env`, and installs Echo + Pusher JS.

`.env` essentials:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=drawing-app
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

ADMIN_PATH=admin-panel-xyz
ADMIN_PASSWORD=changeme
ADMIN_ALLOWED_IPS=127.0.0.1
```

**Important:** Reverb must be running during development:

```bash
php artisan reverb:start
```

Run alongside:

```bash
php artisan serve
php artisan queue:work
```

### 7.2 Landing Page Background

- Fetch ongoing sessions (status = `active` or `waiting`).
- For each session, get its background image URL.
- Display a blurred grid of these images (CSS `filter: blur(10px)`).
- If count < 3, show a `<video>` element with a looping abstract video.
- Overlay a semi‑transparent dark layer for readability.

### 7.3 Session Creation & Joining

- **Create:**
  - Generate unique code: `strtoupper(Str::random(6))`.
  - Pick random background from `backgrounds` table.
  - Create session with status `waiting`.
  - Create player record for creator (IP, last_activity_at = now).
  - Broadcast `PlayerJoined` on channel `session.{code}`.
  - Redirect to `/session/{code}`.
  - Dispatch `BotJoinJob` with 60‑second delay.
- **Join:**
  - Validate code exists, session is active/waiting, and less than 2 human IPs.
  - Check if IP is banned.
  - If valid, create player record, broadcast `PlayerJoined`, redirect.
- **Bot Join:**
  - If session still has only 1 human after 60s, create bot player (is_bot = true).
  - Broadcast `BotJoined`.
  - Start `BotDrawJob` loop.

### 7.4 Canvas Real‑time Sync (via Reverb)

- **Client‑side (canvas.js):**
  - Use `canvas.getContext('2d')`.
  - On `mousedown`, start a new stroke. Store points array.
  - On `mousemove`, add point to array, draw line locally.
  - Throttle sending: every 50ms.
  - On `mouseup`, send final stroke **via Echo whisper**:
    ```js
    Echo.channel("session." + code).whisper("stroke", { points, color, size });
    ```
  - Also POST the stroke to `/session/{code}/record-stroke` for the admin's approval queue.
  - Listen for other player's strokes:
    ```js
    Echo.channel("session." + code).listenForWhisper("stroke", (data) => {
      // redraw on canvas
    });
    ```
- **Server‑side:** Strokes do NOT touch the server. Only recordings do (for admin approval).

### 7.5 Inactivity Kick

- **Client‑side:** Send heartbeat every 60 seconds with current timestamp.
- **Server‑side:**
  - Update `last_activity_at` on each heartbeat.
  - Scheduled task (`php artisan schedule:run`) every minute:
    - Find players with `last_activity_at < now()->subMinutes(3)`.
    - Broadcast `PlayerKicked`, delete player record.
    - If session has 0 players, mark as `ended`.

### 7.6 Reporting & Banning

- **Report:**
  - Player clicks "Report & Leave".
  - POST to `/session/{code}/report` with reported IP.
  - Create report record.
  - Remove player from session.
  - Broadcast `PlayerLeft`.
- **Admin Ban:**
  - From reports list, click "Ban IP".
  - Insert into `bans` table.
  - Middleware blocks future access.

### 7.7 Admin Access — Three Layers

**Layer 1: `AdminIpMiddleware`**

```php
// app/Http/Middleware/AdminIpMiddleware.php
public function handle($request, Closure $next)
{
    $allowed = config('admin.allowed_ips');
    if (!in_array($request->ip(), $allowed)) {
        abort(403, 'Unauthorized');
    }
    return $next($request);
}
```

**Layer 2: `AdminAuthMiddleware`**

```php
// app/Http/Middleware/AdminAuthMiddleware.php
public function handle($request, Closure $next)
{
    if (!session('admin_authenticated')) {
        return redirect()->route('admin.login');
    }
    return $next($request);
}
```

**Layer 3: Password Login**

```php
// app/Http/Controllers/AdminAuthController.php
public function showLogin() {
    return view('admin.login');
}

public function login(Request $r) {
    $r->validate(['password' => 'required|string']);
    if (!hash_equals(config('admin.password'), $r->password)) {
        return back()->with('error', 'Wrong password.');
    }
    session(['admin_authenticated' => true]);
    return redirect()->route('admin.dashboard');
}

public function logout() {
    session()->forget('admin_authenticated');
    return redirect()->route('admin.login');
}
```

**Config:**

```php
// config/admin.php
return [
    'path' => env('ADMIN_PATH', 'admin-panel-xyz'),
    'password' => env('ADMIN_PASSWORD', 'changeme'),
    'allowed_ips' => explode(',', env('ADMIN_ALLOWED_IPS', '127.0.0.1')),
];
```

**Route prefix:** Use `config('admin.path')` dynamically:

```php
$adminPath = config('admin.path');
Route::middleware(['admin.ip'])->prefix($adminPath)->group(function () {
    // login routes
});
```

### 7.8 Bot Drawing Job (Imitation Logic)

```php
// app/Jobs/BotDrawJob.php
public function handle()
{
    $session = Session::find($this->sessionId);
    if (!$session || $session->status !== 'active') return;

    $bot = $session->players()->where('is_bot', true)->first();
    if (!$bot) return;

    // 1. Try to use an approved bot pattern
    $pattern = BotPattern::where('approved', true)->inRandomOrder()->first();

    if ($pattern) {
        $strokes = json_decode($pattern->strokes, true);
        foreach ($strokes as $stroke) {
            broadcast(new StrokeDrawnForBot(
                $session->code,
                $stroke['points'],
                $stroke['color'],
                $stroke['size']
            ))->toOthers();

            usleep(rand(200, 800) * 1000);
        }
    } else {
        // 2. Fallback: random stroke
        $points = [];
        $startX = rand(0, 800);
        $startY = rand(0, 600);
        for ($i = 0; $i < 10; $i++) {
            $points[] = ['x' => $startX + rand(-50, 50), 'y' => $startY + rand(-50, 50)];
        }
        $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
        $size = rand(2, 10);

        broadcast(new StrokeDrawnForBot($session->code, $points, $color, $size))->toOthers();
    }

    self::dispatch($this->sessionId)->delay(now()->addSeconds(rand(1, 3)));
}
```

**Note:** The bot uses a server-side broadcast because it has no browser to `whisper()`. Human strokes use `whisper()`. Both arrive on the same Reverb channel.

### 7.9 Recording Human Strokes

- **Client-side:** Every stroke (mousedown → mouseup) is POSTed to `/session/{code}/record-stroke` with points, color, size, timestamp.
- **Server-side:** `SessionController@recordStroke` stores the stroke in `recorded_strokes`.
- **Admin workflow:**
  1. Admin opens `/admin-panel-xyz/recordings` and sees all recorded sessions.
  2. Admin clicks "Preview" to see the drawing replayed on a mini canvas.
  3. Admin clicks "Approve" → a `bot_pattern` row is created with `approved = true`.
  4. Admin clicks "Reject" → the recording is deleted.
- **Grouping:** Recordings are grouped by `session_id` + `player_ip` so the admin reviews each player's full drawing, not individual strokes.

---

## 8. File Structure (Laravel)

```
app/
├── Events/
│   ├── PlayerJoined.php
│   ├── PlayerLeft.php
│   ├── PlayerKicked.php
│   ├── BotJoined.php
│   ├── CanvasCleared.php
│   └── StrokeDrawnForBot.php
├── Http/
│   ├── Controllers/
│   │   ├── LandingController.php
│   │   ├── SessionController.php
│   │   ├── AdminController.php
│   │   ├── AdminAuthController.php
│   │   ├── BackgroundController.php
│   │   └── BotPatternController.php
│   └── Middleware/
│       ├── AdminIpMiddleware.php
│       ├── AdminAuthMiddleware.php
│       └── CheckBannedIp.php
├── Jobs/
│   ├── BotJoinJob.php
│   ├── BotDrawJob.php
│   └── RecordStrokeJob.php
├── Models/
│   ├── Session.php
│   ├── Player.php
│   ├── Background.php
│   ├── Report.php
│   ├── Ban.php
│   ├── RecordedStroke.php
│   └── BotPattern.php
resources/
├── views/
│   ├── landing.blade.php
│   ├── session.blade.php
│   └── admin/
│       ├── login.blade.php
│       ├── dashboard.blade.php
│       ├── backgrounds.blade.php
│       ├── reports.blade.php
│       ├── bans.blade.php
│       └── bot-patterns/
│           ├── index.blade.php
│           ├── preview.blade.php
│           └── recordings.blade.php
├── js/
│   ├── echo.js
│   └── canvas.js
routes/
├── web.php
└── channels.php
config/
├── admin.php
└── reverb.php
```

---

## 9. Step‑by‑Step Build Plan

1. **Setup Laravel + Reverb**
   - `composer create-project laravel/laravel drawing-app`.
   - `php artisan install:broadcasting` → choose Reverb.
   - Configure `.env` (DB, Reverb, admin path, admin password, admin IPs).
   - Install Tailwind via CDN (no npm build).

2. **Database Migrations & Models**
   - Create migrations for sessions, players, backgrounds, reports, bans, recorded_strokes, bot_patterns.
   - Define relationships in models.

3. **Landing Page**
   - Create `LandingController`.
   - Build `landing.blade.php` with three options and dynamic background.

4. **Session Logic**
   - Implement create, join, leave, report, clear.
   - Add code generation and validation.
   - Implement max 2 IPs check.

5. **Drawing Canvas**
   - Build `session.blade.php` with canvas.
   - Write `canvas.js` for drawing, whisper sync, and recording POST.
   - Set up Echo listeners.

6. **Stroke Recording**
   - Add `recordStroke` endpoint.
   - Save strokes to `recorded_strokes`.
   - Group by session + player IP.

7. **Bot Integration**
   - Create `BotJoinJob` and `BotDrawJob`.
   - Dispatch bot join after 60s.
   - Implement imitation logic using approved patterns.
   - Implement random fallback.

8. **Inactivity & Kick**
   - Add heartbeat JS.
   - Schedule task to kick inactive players.
   - Broadcast `PlayerKicked`.

9. **Reporting & Banning**
   - Add report button.
   - Create report record.
   - Middleware `CheckBannedIp`.

10. **Admin Panel**
    - Create `AdminIpMiddleware` + `AdminAuthMiddleware`.
    - Build login page (password only).
    - Build admin dashboard, backgrounds, reports, bans.
    - Build **Bot Pattern Management** page (recordings list, preview, approve, reject, delete).
    - Implement actions: delete session, delete all, remove player, ban/unban.

11. **Testing & Polish**
    - Run all three processes: `serve`, `reverb:start`, `queue:work`.
    - Test with two browsers (different IPs if possible).
    - Test bot fallback and imitation.
    - Test pattern approval workflow.
    - Test inactivity kick.
    - Test admin password login + IP restriction.
    - Ensure responsive design.

---

## 10. Scope Limitations

**Do NOT implement any features not listed here.**  
This includes:

- User registration/login for players (only the admin has a password).
- Chat.
- Saving finished drawings for download (only admin-approved internal recordings).
- More than 2 players per session.
- Layers, shapes, text tools.
- Social sharing.
- Payment.
- Real-time machine learning for the bot (the bot only replays admin-approved patterns).
- Multi-admin accounts (one shared password only).

The system should be **exactly** as described. Keep it simple, robust, and focused.

---

## 11. Testing Checklist

- [ ] Reverb starts without errors (`php artisan reverb:start`).
- [ ] Queue worker runs (`php artisan queue:work`).
- [ ] Landing page shows blurred background or video.
- [ ] "Enter Code" works with valid/invalid codes.
- [ ] "Start Session" generates code and waits for second player.
- [ ] Bot joins after 60s if no human joins.
- [ ] "Play with Bot" creates session with bot immediately.
- [ ] Two humans can join same session (max 2 IPs).
- [ ] Third IP is denied.
- [ ] Banned IP is denied all options.
- [ ] Drawing syncs in real time via Reverb.
- [ ] Pen colors and sizes work.
- [ ] Clear canvas works for both.
- [ ] Background image is at 30% opacity.
- [ ] Inactive player is kicked after 3 minutes.
- [ ] Report button creates report and leaves.
- [ ] Admin login page appears at `/admin-panel-xyz/login`.
- [ ] Wrong password is rejected.
- [ ] Correct password grants access.
- [ ] Non-whitelisted IP cannot even see the login page.
- [ ] Admin can delete session, delete all, remove player.
- [ ] Admin can upload/delete background photos.
- [ ] Admin can view reports and ban IP.
- [ ] Admin can unban IP.
- [ ] Admin can log out.
- [ ] Human strokes are recorded into `recorded_strokes` during sessions.
- [ ] Admin can view recorded sessions with previews.
- [ ] Admin can approve a recording → it becomes a `bot_pattern`.
- [ ] Admin can reject/delete a recording.
- [ ] Admin can delete an approved bot pattern.
- [ ] Bot replays an approved pattern when one exists.
- [ ] Bot falls back to random drawing when no approved pattern exists.
- [ ] Bot rotates through multiple approved patterns (varied output).
- [ ] Mobile touch drawing works.

---

## 12. Final Notes

- **Run three processes in development:**
  ```
  php artisan serve
  php artisan reverb:start
  php artisan queue:work
  ```
- In production, use **Supervisor** to keep Reverb and the queue worker alive.
- Use the **database queue driver** (`QUEUE_CONNECTION=database`) — no Redis needed.
- Ensure all events are broadcast on the correct Reverb channel.
- Keep the UI clean and minimal (Tailwind CDN is enough).
- Write clear comments in code.
- Document all `.env` variables in `.env.example`.
- Add the privacy notice on the landing page footer.
- Admin password should be **long and random** in production; store only in `.env`, never in code.
