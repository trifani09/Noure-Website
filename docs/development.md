# Development notes

## Service layout

Run each application in its own terminal. The frontend and admin are independent browser applications; Laravel is the only application that connects to MySQL.

| Service | Development command | Port |
| --- | --- | --- |
| Storefront | `cd frontend && npm run dev` | 3000 |
| Admin | `cd admin && npm run dev` | 5173 |
| API | `cd backend && php artisan serve` | 8000 |

If a port is already occupied, pass a framework-specific port option and update local API/CORS configuration as needed.

## Environment files

Copy the example file in each application before local development:

- `frontend/.env.example` to `frontend/.env.local`
- `admin/.env.example` to `admin/.env.local`
- `backend/.env.example` to `backend/.env`

The committed files contain safe development defaults only. Real passwords and deployment configuration belong outside Git.

## Database

Create an empty MySQL database named `noure`, set its credentials in `backend/.env`, and run `php artisan migrate`. Schema changes should be expressed as Laravel migrations rather than manual database changes.

## Dependency installation

Use `npm install` in both JavaScript applications and `composer install` in the backend. Each application owns its own lockfile; keep those lockfiles committed for reproducible installs.

## Scope of this foundation

This initial structure intentionally contains only framework starter code. Product catalog, cart, checkout, authentication, orders, and CMS behavior are not part of the foundation.
# Scheduler

Run Laravel's scheduler in every deployed environment so unpaid order reservations expire and return to available stock:

```bash
php artisan schedule:work
```

`ORDER_RESERVATION_MINUTES` controls the timeout and defaults to `30`. The scheduled `orders:expire-reservations` command runs every minute, is idempotent, marks stale unpaid orders and payments expired, and releases reservations without changing `on_hand`.
