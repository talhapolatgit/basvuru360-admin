# Basvuru360 Admin (Laravel panel + API)

Production deploy: see [DEPLOY.md](DEPLOY.md).

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configure DB_* in .env (MySQL recommended)
php artisan migrate
npm install
npm run dev
```

API / panel (example):

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

Default seeder admin (only after `php artisan db:seed`):

- Email: `admin@basvuru360.test`
- Password: `password`
