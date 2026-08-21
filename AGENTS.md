# AGENTS.md

## Stack
Laravel 11 (Docker: PHP 8.4, requires `^8.2`) + Firebase Realtime DB + Fonnte WhatsApp API. **No database** — sessions/cache use the `file` driver and the queue uses `sync`. Blade UI; Tailwind/Chart.js/Firebase JS are loaded via **CDN**, not built by Vite.

## Architecture facts that are not obvious from filenames
- **Sensor data is NOT in a database.** It lives in Firebase Realtime DB at `config('firebase_client.sensor_path')` (default `SKARP/SensorData/`). The dashboard reads it client-side via the Firebase JS SDK, and server-side recap/alerts use `kreait/laravel-firebase`. There is no MariaDB/MySQL — `CACHE_STORE`/`SESSION_DRIVER` are `file` and `QUEUE_CONNECTION` is `sync`, so `php artisan serve` works with only Firebase + Fonnte configured.
- **Scheduling lives in `routes/console.php`** (Laravel 11 removed `app/Console/Kernel.php`). `sensor:check-alerts` runs `everyTenSeconds()`; `sensor:send-daily-recap` runs daily at 20:00. Both hardcode `->timezone('Asia/Jakarta')`, so keep `APP_TIMEZONE=Asia/Jakarta` in sync for local `schedule:work`.

## Commands
- Deps: `composer install` (full, to get `laravel/pint`). The Docker image runs `composer install --no-dev --no-scripts`, so `pint` is absent there.
- Local web: `cp .env.example .env && php artisan key:generate`, then `php artisan serve`.
- **Frontend does not need a build step.** `npm run dev`/`npm run build` (Vite) are effectively unused — views load CSS/JS from CDN. Don't waste time building assets.
- Format PHP only: `./vendor/bin/pint`. There is no eslint/phpcs/phpstan configured.
- Custom artisan commands: `whatsapp:test {message?}`, `sensor:send-daily-recap`, `sensor:check-alerts`.

## Testing
- No test suite exists. `phpunit.xml` is present but there is no `tests/` directory and `phpunit` is not in `require-dev`. Do not assume `composer test` / phpunit works; add `phpunit` to `require-dev` and create `tests/` first.

## Docker / Deploy (Dockploy, Docker Compose)
- `docker-compose.yml` builds 4 services: `web` (nginx), `app` (php-fpm), `scheduler`, `queue`. No database service. Volume: `laravel_storage` (shared by app/scheduler/queue).
- **No migrations.** `docker/entrypoint.sh` (only on `CONTAINER_ROLE=app`) runs `package:discover` → `storage:link` → `optimize:clear` → `config:cache`. Because config is cached, **env changes need a redeploy (or `php artisan optimize:clear`)** to take effect.
- **Firebase service-account JSON must be uploaded manually** to `/var/www/html/storage/app/firebase-credentials.json` on the `laravel_storage` volume (deploy UI/terminal), not via env. `FIREBASE_CREDENTIALS` defaults to that path. Never commit it.
- Only `web` is publicly exposed (port 80). Do not publish host ports for the other services. Logs go to stderr.

## Secrets / security
- `POST /api/recap` (`routes/api.php`) is protected only by a `?secret=` query param equal to `RECAP_SECRET`. Treat `RECAP_SECRET` and `FONNTE_TOKEN` as secrets; never log them.
- Never commit `.env` or `firebase-credentials.json` (gitignored and dockerignored).
