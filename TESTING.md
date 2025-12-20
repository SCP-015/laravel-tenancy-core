# Panduan Testing Nusahire

Dokumen ini menjelaskan cara menjalankan dan menulis test di Nusahire, baik untuk backend (PHPUnit) maupun frontend (Playwright visual/E2E testing).

---

## 1. Backend Testing (PHPUnit & Paratest)

### 1.1. Cara Menjalankan Test Backend

Semua test backend menggunakan PHPUnit dengan dukungan parallel testing.

- **Menjalankan seluruh test tanpa coverage** (paling sering dipakai):

  ```bash
  composer test
  ```

  Perintah di atas akan:

  - Menjalankan script `test:cleanup` (membersihkan database & storage testing).
  - Menjalankan `php artisan test` secara parallel (`--parallel --processes=3`).

- **Menjalankan test dengan HTML coverage report**:

  ```bash
  composer test:coverage-html
  ```

  Output:

  - Report HTML di folder `coverage-report/`.
  - Buka `coverage-report/index.html` di browser untuk melihat detail coverage (file per file).

> Catatan: Coverage menggunakan ekstensi **pcov**. Pastikan pcov ter-install dan di-enable di environment lokal/CI yang menjalankan coverage.

### 1.2. Struktur Folder Tests

Struktur utama folder `tests/` (disederhanakan):

```text
tests/
├── Feature/
│   ├── Auth/         # Test feature untuk authentication
│   ├── Central/      # Test feature untuk context central (non-tenant)
│   ├── Middleware/   # Test untuk middleware
│   ├── Tenant/       # Test feature untuk context tenant (multi-tenant)
│   └── Web/          # Test untuk web routes
│
├── Unit/
│   ├── Models/       # Unit test untuk model
│   ├── Resources/    # Unit test untuk resource transformer
│   ├── Services/     # Unit test untuk service yang pure/utility
│   └── Requests/     # Unit test untuk form request
│
├── TestCase.php      # Base class untuk test central
└── Feature/
    └── TenantTestCase.php  # Base class untuk test tenant
```

Ringkasan aturan organisasi test:

- **Controller (HTTP endpoint)** → `tests/Feature/...` (bukan Unit).
- **Tenant controller/service** → `tests/Feature/Tenant/...` dan extend `TenantTestCase`.
- **Central controller/service** → `tests/Feature/Central/...` dan extend `TestCase`.
- **Middleware** → `tests/Feature/Middleware/...`.
- **Resource pure (tanpa DB)** → `tests/Unit/Resources/...`.
- **Service pure/utility** → `tests/Unit/Services/...`.

### 1.3. Aturan Penulisan Test Backend

- **Naming method test**:

  ```php
  public function test_{action}_{scenario}_{expected_result}(): void
  ```

  Contoh:

  - `test_index_returns_paginated_audit_logs()`
  - `test_update_stage_handles_service_exception()`

- **Naming file**:

  - `{Nama}ControllerTest.php`
  - `{Nama}ServiceTest.php`
  - `{Nama}ResourceTest.php`

- **Base class**:

  - Central → extend `Tests\TestCase`.
  - Tenant → extend `Tests\Feature\Tenant\TenantTestCase`.

- **Database & Tenancy**:

  - Testing menggunakan SQLite (central & tenant) sesuai konfigurasi di `phpunit.xml`.
  - Untuk test tenant, selalu gunakan helper/trait tenancy (`actingAsTenant()` atau sejenisnya di `TenantTestCase`).

### 1.4. Target Coverage Backend

- Target internal: **coverage tinggi untuk business logic kritis (≥ 80%)**.
- Kondisi saat ini: project Nusahire sudah dijaga agar coverage backend sangat tinggi (mendekati 100%).
- Tambahan kode baru (controller/service) **wajib** ditambah test yang relevan.

> Rekomendasi workflow saat menambah fitur backend:
> 1. Tulis/ubah service + controller.
> 2. Tambahkan/ubah test di folder yang sesuai (Feature/Unit).
> 3. Jalankan `composer test`.
> 4. Jika menyentuh business logic kritis, jalankan `composer test:coverage-html` untuk memastikan coverage tetap terjaga.

## 2. Ringkasan

- **Backend**:
  - Gunakan `composer test` untuk regresi harian.
  - Gunakan `composer test:coverage-html` untuk memantau coverage dan menjaga kualitas kode.

Untuk perubahan fitur baru, usahakan selalu menambah/memperbarui test terkait agar stabilitas aplikasi tetap tinggi.
