# JT-IS

Laravel 12 + Inertia Vue monitoring app for PT Jasa Tirta Energi.

The core monitoring flow is:

Tender / Pipeline -> Won -> Active Project -> RAB/RAP -> Progress/BAMC -> Cost/Invoice/Payment -> Dashboard warning.

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
```

Run the app:

```bash
composer run dev
```

## Demo Accounts

All seeded demo users use `password`.

- `admin@example.com`
- `operational@example.com`
- `finance@example.com`
- `management@example.com`

## Main Modules

- Dashboard: management summary, warning and critical projects, recent progress.
- Marketing / Pipeline: tender records and won tender conversion to project.
- Projects: project summary, documents, health status, warnings.
- RAB/RAP: budget headers and item totals.
- Progress / BAMC: official progress requires internal and client approval.
- Finance: cost realization and invoices.
- Clients: client records connected to projects and tenders.
- Admin: users, roles, and permissions.

## Project Structure

See [docs/STRUCTURE.md](docs/STRUCTURE.md) for folder layout, route groups, Inertia page names, and where business status logic lives.

## OCR Helpers

OCR is optional and disabled by default. The app remains fully usable with manual input when no OCR provider is configured.

```env
OCR_PROVIDER=none
```

Reviewable OCR helpers are available only in project documents, RAB/RAP, Progress/BAMC, cost realization, and invoice flows. They create draft suggestions that must be reviewed before applying.

See [docs/OCR.md](docs/OCR.md) for provider configuration, fallback behavior, limitations, and safety rules.

## Useful Commands

```bash
php artisan migrate:fresh --seed
php artisan test
npm run types:check
npm run build
vendor/bin/pint --dirty
```

## Notes

Experimental AI extraction and billing test pages are kept for compatibility, but they are not part of the normal monitoring sidebar path.
