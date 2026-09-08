# Noure Website

Starter monorepo for the Noure public site, administration UI, and API.

## Architecture

| Directory | Stack | Default URL |
| --- | --- | --- |
| `frontend/` | Next.js, TypeScript, Tailwind CSS | `http://localhost:3000` |
| `admin/` | Vite, React, TypeScript, Tailwind CSS | `http://localhost:5173` |
| `backend/` | Laravel API, MySQL | `http://localhost:8000` |

The browser applications communicate with the Laravel API. Their API base URLs are configured by `NEXT_PUBLIC_API_URL` and `VITE_API_URL` respectively.

## Prerequisites

- Node.js 22+ and npm
- PHP 8.3+, Composer, and the PHP extensions required by Laravel
- MySQL 8+

## Local setup

### Public frontend

```bash
cd frontend
cp .env.example .env.local
npm install
npm run dev
```

### Admin application

```bash
cd admin
cp .env.example .env.local
npm install
npm run dev
```

### Backend API

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
```

Create the `noure` MySQL database, update the `DB_*` values in `backend/.env`, then run:

```bash
php artisan migrate
php artisan serve
```

For a fresh Laravel checkout, `storage/` and `bootstrap/cache/` must be writable. Do not commit any populated `.env` file or credentials.

## Validation

Run checks from each application directory:

```bash
cd frontend && npm run lint && npm run build
cd admin && npm run lint && npm run build
cd backend && php artisan test
```

## Repository conventions

- Keep application-specific dependencies and scripts inside their application directory.
- Put public-site code in `frontend/src`, admin code in `admin/src`, and API/domain code in `backend/app`.
- Expose backend functionality through versionable API routes; do not couple browser apps directly to the database.
- Add new environment variables to the relevant `.env.example` with safe placeholder values.
