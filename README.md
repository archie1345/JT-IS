# JT-IS

## Indonesia

### Gambaran Umum
JT-IS adalah prototype aplikasi monitoring proyek berbasis Laravel + Vue. Sistem ini disiapkan untuk membantu tim internal JTE dan client memantau data proyek, anggaran, progres pekerjaan, invoice, pembayaran, dan permintaan dana dalam satu tempat.

Saat ini project ini masih dalam tahap pengembangan awal, jadi beberapa halaman masih berupa prototype UI dan beberapa controller/model masih berupa fondasi CRUD yang nantinya bisa disesuaikan lagi sesuai kebutuhan bisnis.

### Tujuan Sistem
- Menyimpan data client dan user internal.
- Mencatat proyek dan anggota tim yang terlibat.
- Mengelola data tender.
- Menyimpan RAB dan RAP beserta item-itemnya.
- Mencatat progress report dan approval.
- Mencatat biaya proyek, invoice, pembayaran, dan fund request.

### Modul Data
Tabel utama yang sudah disiapkan:
- `clients`
- `users`
- `projects`
- `project_users`
- `tenders`
- `rabs`
- `rab_items`
- `raps`
- `rap_items`
- `progress_reports`
- `progress_approvals`
- `project_costs`
- `invoices`
- `payments`
- `fund_requests`

### Soft Delete
Semua tabel project monitoring sudah menggunakan soft delete.

Artinya:
- Data yang dihapus tidak langsung hilang dari database.
- Sistem hanya mengisi kolom `deleted_at`.
- Data yang sudah dihapus tidak akan tampil pada query normal/controller default.
- Data masih bisa direstore di kemudian hari jika fitur restore ditambahkan.

### Controller
Controller CRUD sudah dibuat untuk semua tabel project monitoring di folder:

`app/Http/Controllers/ProjectMonitoring`

Struktur ini dibuat agar:
- lebih cepat untuk proses development,
- mudah dicek oleh client atau developer,
- mudah dimodifikasi setelah review.

Controller dasar yang dipakai bersama:

`app/Http/Controllers/ProjectMonitoring/TableCrudController.php`

Fungsi utamanya:
- `index()` untuk daftar data
- `store()` untuk simpan data baru
- `show()` untuk detail data
- `update()` untuk ubah data
- `destroy()` untuk soft delete data

### Model
Model Eloquent juga sudah dibuat di folder:

`app/Models`

Setiap model utama sudah disiapkan dengan:
- `fillable`
- `casts`
- relasi dasar antar tabel
- `SoftDeletes`

Contoh model yang sudah tersedia:
- `Client`
- `Project`
- `ProjectUser`
- `Tender`
- `Rab`
- `RabItem`
- `Rap`
- `RapItem`
- `ProgressReport`
- `ProgressApproval`
- `ProjectCost`
- `Invoice`
- `Payment`
- `FundRequest`
- `User`

### Seeder Sementara
Seeder dummy sementara sudah dibuat untuk membantu testing awal data project monitoring.

File:

`database/seeders/TemporaryProjectMonitoringSeeder.php`

Seeder ini hanya untuk kebutuhan development/testing dan masih aman untuk diubah.

### Catatan Penting
- Beberapa halaman frontend masih berupa prototype.
- Route API/resource penuh untuk semua controller belum semuanya dihubungkan.
- Validasi dan business logic masih bisa diperdalam sesuai alur operasional JTE.
- Jika migration sudah pernah dijalankan sebelum perubahan soft delete, sebaiknya dibuat migration baru untuk menambahkan kolom `deleted_at` daripada hanya mengandalkan edit file migration lama.

### Langkah Dasar Menjalankan Project
1. Pastikan database MySQL sudah dibuat dan `.env` sudah sesuai.
2. Jalankan install dependency:

```bash
composer install
npm install
```

3. Jalankan migration dan seeder:

```bash
php artisan migrate --seed
```

4. Jalankan project:

```bash
composer run dev
```

### Status Saat Ini
Project ini sudah memiliki:
- struktur database utama,
- migration,
- temporary seeder,
- controller CRUD dasar,
- model Eloquent,
- beberapa halaman prototype frontend.

Project ini belum final dan memang disiapkan supaya mudah direview lalu dimodifikasi bersama client.

---

## English

### Overview
JT-IS is a Laravel + Vue project monitoring prototype. The system is intended to help the JTE internal team and clients manage project data, budgeting, work progress, invoices, payments, and fund requests in one place.

At the moment, this project is still in an early development stage. Some pages are still UI prototypes, and several controllers/models are currently scaffolded as a clean CRUD foundation that can be adjusted later to match real business requirements.

### System Goals
- Store client and internal user data.
- Manage projects and assigned team members.
- Track tender data.
- Store RAB and RAP data with their line items.
- Record progress reports and approvals.
- Record project costs, invoices, payments, and fund requests.

### Data Modules
The main tables prepared in the system are:
- `clients`
- `users`
- `projects`
- `project_users`
- `tenders`
- `rabs`
- `rab_items`
- `raps`
- `rap_items`
- `progress_reports`
- `progress_approvals`
- `project_costs`
- `invoices`
- `payments`
- `fund_requests`

### Soft Deletes
All project-monitoring tables already use soft deletes.

This means:
- Deleted data is not removed immediately from the database.
- The system only fills the `deleted_at` column.
- Soft-deleted records are hidden from normal queries and default controllers.
- The data can still be restored later if a restore feature is added.

### Controllers
CRUD controllers have been created for all project-monitoring tables in:

`app/Http/Controllers/ProjectMonitoring`

This structure is meant to be:
- fast to continue development,
- easy for the client or developer to review,
- easy to modify after discussion.

Shared base controller:

`app/Http/Controllers/ProjectMonitoring/TableCrudController.php`

Main methods:
- `index()` for listing data
- `store()` for creating data
- `show()` for viewing details
- `update()` for updating data
- `destroy()` for soft deleting data

### Models
Eloquent models have also been created in:

`app/Models`

Each main model already includes:
- `fillable`
- `casts`
- basic table relationships
- `SoftDeletes`

Available models include:
- `Client`
- `Project`
- `ProjectUser`
- `Tender`
- `Rab`
- `RabItem`
- `Rap`
- `RapItem`
- `ProgressReport`
- `ProgressApproval`
- `ProjectCost`
- `Invoice`
- `Payment`
- `FundRequest`
- `User`

### Temporary Seeder
A temporary dummy seeder has been added to help with initial project-monitoring testing.

File:

`database/seeders/TemporaryProjectMonitoringSeeder.php`

This seeder is only for development/testing purposes and can be modified freely.

### Important Notes
- Some frontend pages are still prototypes.
- Full API/resource routing for every controller is not connected yet.
- Validation and business logic can still be expanded based on JTE operational flow.
- If the migrations had already been run before the soft delete update, it is safer to create a new migration that adds `deleted_at` columns instead of relying only on edits to the old migration files.

### Basic Project Setup
1. Make sure the MySQL database exists and `.env` is configured correctly.
2. Install dependencies:

```bash
composer install
npm install
```

3. Run migrations and seeders:

```bash
php artisan migrate --seed
```

4. Run the project:

```bash
composer run dev
```

### Current Status
This project already includes:
- main database structure,
- migrations,
- a temporary seeder,
- base CRUD controllers,
- Eloquent models,
- several frontend prototype pages.

This is not a final product yet. It is intentionally prepared in a way that is easy to review first and modify together with the client afterward.


