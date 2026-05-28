# Project Structure

JT-IS is organized around the MVP monitoring flow:

Marketing / Pipeline -> Won Tender -> Project -> RAB/RAP -> Progress/BAMC -> Costs/Invoices/Payments -> Dashboard warnings.

## Backend

Main routes live in `routes/web.php` and are grouped by domain:

- Dashboard: `DashboardController`
- Marketing / Pipeline: `ProjectMonitoring\TendersController`
- Projects: `ProjectsPageController`, `ProjectDetailsController`, `ProjectDocumentsController`
- Budget: `RabsPageController`, `RapsPageController`, `RabRapDetailsController`, `ProjectMonitoring\RabsController`, `ProjectMonitoring\RapsController`
- Progress / BAMC: `ProjectMonitoring\ProgressReportsController`
- Finance: `ProjectMonitoring\ProjectCostsController`, `ProjectMonitoring\InvoicesController`
- Clients: `ClientsPageController`, `ClientDetailsController`
- Admin: `Admin\UserManagementController`

Shared CRUD behavior for simple monitoring resources is in:

- `app/Http/Controllers/ProjectMonitoring/CrudResourceController.php`

Project warning/status rules are in:

- `app/Services/ProjectStatusService.php`

Use this service when changing the meaning of `On Track`, `Warning`, or `Critical`.

## Frontend Pages

Inertia pages are grouped by domain under `resources/js/pages`:

- `dashboard/Index.vue`
- `marketing/TendersIndex.vue`
- `projects/Index.vue`, `projects/Show.vue`
- `budget/rabs/Index.vue`, `budget/raps/Index.vue`, `budget/BudgetDetails.vue`
- `progress/Index.vue`
- `finance/costs/Index.vue`, `finance/invoices/Index.vue`, `finance/FinancialDocumentDetails.vue`
- `clients/Index.vue`, `clients/Show.vue`
- `admin/UserManagement.vue`
- `dev/*` for non-MVP experimental pages
- `shared/RecordDetails.vue` for generic record detail screens still used by simple CRUD modules

## Components

- `resources/js/components/ui`: shadcn-style primitive UI components.
- `resources/js/components/layout`: app shell, sidebar, navigation, breadcrumbs, logo, user menu.
- `resources/js/components/shared`: reusable business components such as the spreadsheet-like data table and document upload panel.
- `resources/js/components/admin`, `entity`, `invoice`, `prototype`: domain or legacy helper components retained because they are still used.

## MVP Demo Data

Demo seed data lives in:

- `database/seeders/TemporaryProjectMonitoringSeeder.php`

`php artisan migrate:fresh --seed` should create demo users, clients, tenders, projects, budgets, progress reports, costs, invoices, and payments.

Demo users:

- `admin@example.com / password`
- `operational@example.com / password`
- `finance@example.com / password`
- `management@example.com / password`

## Experimental Code

OCR/AI extraction and billing test routes remain available for compatibility, but they are hidden from the normal MVP sidebar and kept under `resources/js/pages/dev`.
