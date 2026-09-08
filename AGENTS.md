# Contributor Guidance

This repository contains three independent applications. Before changing code, work from the relevant directory and follow its existing formatter, linter, and framework conventions.

## Application boundaries

- `frontend/`: public Next.js application. Use TypeScript, App Router patterns, and Tailwind utilities.
- `admin/`: internal Vite React application. Use TypeScript and Tailwind utilities.
- `backend/`: Laravel API and domain logic. Keep controllers thin, validate request input, and place business rules in focused classes.

Do not import source code across application directories. Share data through explicit Laravel API contracts. Never expose secrets or server-only values through `NEXT_PUBLIC_*` or `VITE_*` variables.

## Before handing off a change

Run the smallest relevant checks, and run all of these when a change crosses application boundaries:

```bash
cd frontend && npm run lint && npm run build
cd admin && npm run lint && npm run build
cd backend && php artisan test
```

Update the root README and the appropriate `.env.example` whenever setup steps, ports, or environment variables change. Do not commit generated build output, dependency directories, local databases, populated `.env` files, or credentials.
