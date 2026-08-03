# Coolify deploy — basvuru360-admin

Laravel admin panel + `/api/v1` API. Portal (`basvuru360-portal`) is a separate app/repo.

## Prerequisites

- Coolify on your Ubuntu server
- GitHub connected to Coolify (private repo access)
- This repository: `basvuru360-admin` (Dockerfile included)

## 1. Create MySQL (separate Coolify resource)

1. Coolify → **New Resource** → **Database** → **MySQL** (8.x)
2. Note hostname, port, database, username, password
3. Keep MySQL on the same Coolify network as the app (default when linked)

## 2. Create the application

1. Coolify → **New Resource** → **Application** → GitHub → `basvuru360-admin`
2. Build pack: **Dockerfile** (auto-detected)
3. Port: **8080** (serversideup/php listens on 8080; Coolify proxies HTTPS)
4. Persistent volumes (important for uploads):

| Container path | Purpose |
|----------------|---------|
| `/var/www/html/storage/app` | Laravel `public` disk files (başvuru evrakları, etc.) |
| `/var/www/html/storage/logs` | Application logs (optional) |
| `/var/www/html/public/uploads` | Genel / portal sayfa / sertifika görselleri |

5. Domain: leave empty or use Coolify’s temporary URL for now; add a real domain later and set `APP_URL` to match.

## 3. Environment variables

Generate `APP_KEY` locally once:

```bash
php artisan key:generate --show
```

Set in Coolify (adjust DB_* from the MySQL resource):

```env
APP_NAME=Basvuru360
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR-COOLIFY-URL-OR-DOMAIN
APP_KEY=base64:PASTE_HERE

APP_LOCALE=tr
APP_FALLBACK_LOCALE=tr

DB_CONNECTION=mysql
DB_HOST=YOUR_MYSQL_HOST
DB_PORT=3306
DB_DATABASE=YOUR_DB
DB_USERNAME=YOUR_USER
DB_PASSWORD=YOUR_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_CHANNEL=stack
LOG_LEVEL=warning

FILESYSTEM_DISK=local

JWT_ISSUER=basvuru360
# JWT_SECRET=   # optional; defaults from APP_KEY if unset in app
JWT_ACCESS_TTL=60
JWT_REFRESH_TTL=20160

CORS_ALLOWED_ORIGINS=*

SMS_DRIVER=log
EMAIL_DRIVER=log
MAIL_MAILER=log

RUN_MIGRATIONS=true
SSL_MODE=off
```

When the portal domain is known, set e.g.:

```env
CORS_ALLOWED_ORIGINS=https://portal.example.com
```

## 4. Deploy

1. Deploy the application from Coolify
2. First boot runs `migrate --force`, `storage:link`, and config/route/view cache (see `docker/entrypoint.d/99-laravel.sh`)
3. Seed admin user (one-time), via Coolify **Execute Command** / terminal:

```bash
php artisan db:seed --force
```

Default admin (change immediately):

- Email: `admin@basvuru360.test`
- Password: `password`

For production you may prefer a minimal custom seeder or `php artisan tinker` instead of full demo seeders.

## 5. Domain later

1. Coolify → application → Domains → add domain + SSL
2. Update `APP_URL=https://your-domain`
3. Redeploy (or restart) so config cache picks up the new URL

## Health check

```http
GET /api/v1/health
```

## Notes

- No Redis / queue worker required for the current codebase
- Do not commit `.env`; secrets live only in Coolify
- Local Vite (`npm run dev` on 5174) is not used in production; assets are baked in `public/build` during the Docker build
