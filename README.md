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

### Seeding

Domain data is loaded from a local DB snapshot under `database/data/seed/*.json` via `LocalDataSeeder` (no demo/Faker data).

```bash
php artisan db:seed
```

To refresh the snapshot from the current local database:

```bash
php database/scripts/export_seed_snapshot.php
```

Default admin credentials match whatever was in the DB at export time (check `users` / your notes).
