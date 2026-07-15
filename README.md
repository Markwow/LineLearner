# 🎭 Line Learner

Rehearse lines with yourself. Paste a script, record the cue character's lines through your device mic, then play them back — one line at a time or the whole scene in order.

## How it works

1. **Create a script.** On the home page, give it a title and paste the script, one line per row as `Speaker: line`:

   ```
   Mark: hi
   Ivan: hey
   Mark: sup
   Ivan: nothin
   ```

2. **Pick a cue character.** On the rehearse page, choose whose lines you want to record from the **Cue character** dropdown (e.g. `Mark`). Only that character's lines get record/play controls; the other lines show as dimmed context.

3. **Record & play.** Each cue line has three buttons:
   - **●** record — tap to start, tap **■** to stop
   - **▶** play back the recording
   - **×** delete it

   A green dot marks a line that's been recorded.

4. **Run the scene.** **▶ Play cue lines in order** plays every recorded cue line back-to-back.

Recordings save automatically and persist across reloads and restarts. You can keep multiple scripts, and edit or delete each one from the section at the bottom of its page.

## Requirements

- PHP 8.2+
- Composer
- SQLite (default) — or MySQL/Postgres if you prefer

## Running locally

```bash
composer install
cp .env.example .env        # if you don't already have a .env
php artisan key:generate
php artisan migrate
php artisan storage:link     # audio is served through this symlink
php artisan serve
```

Then open **http://127.0.0.1:8000**.

> **Use `localhost` / `127.0.0.1`, not a `.test` domain over plain HTTP.** The microphone (`getUserMedia`) only works in a *secure context*. `localhost` is exempt in development; anything else needs HTTPS. The first time you record, the browser will ask for mic permission — allow it.

## Project layout

| Piece | Location |
| --- | --- |
| Script model (parses `Speaker: text`, derives speakers) | [`app/Models/Script.php`](app/Models/Script.php) |
| Recording model | [`app/Models/Recording.php`](app/Models/Recording.php) |
| Script CRUD | [`app/Http/Controllers/ScriptController.php`](app/Http/Controllers/ScriptController.php) |
| Audio upload / delete | [`app/Http/Controllers/RecordingController.php`](app/Http/Controllers/RecordingController.php) |
| Create + list view | [`resources/views/scripts/index.blade.php`](resources/views/scripts/index.blade.php) |
| Rehearse view (`MediaRecorder` JS) | [`resources/views/scripts/show.blade.php`](resources/views/scripts/show.blade.php) |
| Routes | [`routes/web.php`](routes/web.php) |

Audio is stored on the `public` filesystem disk under `storage/app/public/recordings/{script}/` and tracked in the `recordings` table (SQLite by default).

## Deploying to production

Standard Laravel deploy, plus three things that matter **specifically** because this app captures audio to disk:

1. **HTTPS is mandatory.** Without a real TLS certificate the record buttons silently do nothing — the mic never activates outside a secure context. This is the most common thing people miss.

2. **Raise the upload size limits.** Recordings upload as files. PHP and your web server default well below the app's 50 MB cap (`max:51200` in [`RecordingController.php`](app/Http/Controllers/RecordingController.php)), so longer takes fail silently. Keep all three in sync:

   ```ini
   ; php.ini
   upload_max_filesize = 50M
   post_max_size       = 51M
   ```
   ```nginx
   # nginx
   client_max_body_size 51M;
   ```

3. **Audio must live on persistent storage.**
   - Run `php artisan storage:link` on the server.
   - On a single VPS, local disk is fine — just make `storage/` writable by the web user.
   - On **ephemeral or multi-server hosts** (Heroku, some Docker setups, etc.) local files get wiped on redeploy or aren't shared between instances. Move recordings to **S3**: configure an `s3` disk and switch the `Storage::disk('public')` calls plus [`Recording::url()`](app/Models/Recording.php) to use it. The same caveat applies to the SQLite database file — put it on persistent storage or use a managed MySQL/Postgres instance.

### Standard production checklist

- `.env`: `APP_ENV=production`, `APP_DEBUG=false`, a generated `APP_KEY`, correct `APP_URL`
- `composer install --no-dev --optimize-autoloader`
- `php artisan migrate --force`
- `php artisan config:cache route:cache view:cache`
- `php artisan storage:link`
- Point `DB_CONNECTION` at a managed database for anything beyond a single-server setup

## License

MIT.
