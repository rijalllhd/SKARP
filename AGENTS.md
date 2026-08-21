# AGENTS.md

## Stack
Laravel 11 (Docker: PHP 8.4, requires ^8.2) + MariaDB + Firebase Realtime DB + Fonnte WhatsApp API. Assets built with Vite; Tailwind/Chart.js loaded via CDN.

## Architecture facts that are not obvious from filenames
- **Sensor data is NOT in MariaDB.** It lives in Firebase Realtime DB at path `SKARP/SensorData/` (`config/firebase_client.php` → `sensor_path`). The dashboard reads it via the Firebase JS SDK (client) and `kreait/laravel-firebase` (server-side recap/alerts). MariaDB only stores users/cache/jobs/sessions.
- **Scheduling lives in `routes/console.php`** (Laravel 11 moved it out of `app/Console/Kernel.php`, which is now just a stub). `sensor:check-alerts` runs `everyTenSeconds()` and sends WhatsApp when amonia/THI cross `config/sensor_alerts.php` thresholds with a cooldown; `sensor:send-daily-recap` runs daily at 20:00. All schedules use `->timezone('Asia/Jakarta')` — keep `APP_TIMEZONE=Asia/Jakarta` in sync.

## Commands
- Install deps: `composer install`. (Dockerfile runs `composer install --no-dev --no-scripts`, so for local dev you need the full `composer install` to get `laravel/pint`.)
- Local run: `cp .env.example .env && php artisan key:generate`, then `php artisan serve` (web) + `npm install && npm run dev` (Vite assets).
- Format PHP: `./vendor/bin/pint`. This is the only dev tool configured — there is no eslint/phpcs.
- Custom artisan commands: `whatsapp:test {message?}`, `sensor:send-daily-recap`, `sensor:check-alerts`.

## Testing
- No test suite is set up. `phpunit.xml` exists, but `require-dev` lists only `laravel/pint` and there is no `tests/` directory. Do not assume `composer test` / phpunit works; add `phpunit` to `require-dev` and create `tests/` before relying on it.

## Docker / Deploy (Dockploy, Docker Compose)
- `docker-compose.yml` builds 5 services: `web` (nginx), `app` (php-fpm), `scheduler`, `queue`, `database` (MariaDB 11.4). Volumes: `laravel_storage` (shared by app/scheduler/queue) and `mariadb_data` (DB; deleting it destroys data).
- **Migrations/cache run ONLY in the `app` container.** `docker/entrypoint.sh` checks `CONTAINER_ROLE` (defaults to `app`); `scheduler`/`queue` never run `migrate`. Don't add migrate steps to those roles.
- Entrypoint order on `app`: `package:discover` → `migrate --force` → `storage:link` → `optimize:clear` → `config:cache`. Because config is cached, env changes need a redeploy (or `php artisan optimize:clear`).
- **Firebase service-account JSON must be uploaded manually** to `/var/www/html/storage/app/firebase-credentials.json` on the `laravel_storage` volume (via the deploy UI/terminal), not via env. `FIREBASE_CREDENTIALS` defaults to that path. Never commit it.
- Only `web` is publicly exposed (port 80). Do not publish host ports for the other services. Logs go to stderr.

## Secrets / security
- `POST /api/recap` (`routes/api.php`) is protected only by a `?secret=` query param equal to `RECAP_SECRET`. Treat `RECAP_SECRET` and `FONNTE_TOKEN` as secrets; never log them.
- Never commit `.env` or `firebase-credentials.json` (gitignored and dockerignored).
