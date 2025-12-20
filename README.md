## Arsitektur Aplikasi

Project ini dibangun dengan arsitektur multi-tenant menggunakan Laravel 12 dan package `stancl/tenancy`. Berikut adalah gambaran arsitekturnya:

![Arsitektur Diagram](/public/images/architecture.png)


### Keterangan Arsitektur:

1. **Central**
   - Menangani autentikasi utama
   - Halaman Portal/Tenant Public
   - Database terpusat untuk data global

2. **Tenant**
   - Setiap tenant memiliki database terpisah
   - Data terisolasi antar tenant
   - Dapat dikustomisasi per tenant

3. **Alur Request**:
   - Request masuk ke aplikasi
   - Middleware tenancy mengidentifikasi path tenant
   - Request diarahkan ke database tenant yang sesuai
   - Response dikembalikan ke client

4. **Komponen Utama**:
   - **Tenant**: Entitas yang mewakili perusahaan/organisasi
   - **Path**: slug/path yang mengidentifikasi tenant
   - **Database**: Terpisah untuk setiap tenant
   - **Filesystem**: Penyimpanan file yang terisolasi per tenant

## Keamanan

- Setiap tenant memiliki database terpisah
- Data antar tenant terisolasi sepenuhnya
- Autentikasi terpusat dengan konteks tenant
- Middleware untuk validasi akses tenant
- Enkripsi data sensitif

## Requirements

-   Laravel 12.0
-   PHP 8.4
-   Composer
-   NPM (Node.js)
-   MySQL

## Instalasi

1. Clone repositori ini:
    ```bash
    git clone git@github.com:riantopangaribuan/laravel-tenancy-core.git project
    ```
2. Masuk ke direktori proyek:
    ```bash
    cd project
    ```
3. Instalasi Otomatis:
    ```bash
    composer run setup
    ```
4. Jalankan server lokal:
    ```bash
    composer run dev
    ```

## Penggunaan

Setelah instalasi selesai, Anda dapat mengakses aplikasi di `http://localhost:8000`.

### Dokumentasi API Central

Dokumentasi API Central dapat ditemukan di `http://localhost:8000/docs/api`.

### Dokumentasi API Tenant

Dokumentasi API Tenant dapat ditemukan di `http://localhost:8000/docs/api/tenant`.

### Dokumentasi Package Tenancy

Dokumentasi Package Tenancy dapat ditemukan di `https://tenancyforlaravel.com/docs/v3/introduction`.

### Dokumentasi Package Scramble

Dokumentasi Package Scramble dapat ditemukan di `https://scramble.dedoc.co`.

## Testing

Ringkasan singkat cara menjalankan testing di Project ini:

- **Backend (PHPUnit)**
    - `composer test` menjalankan seluruh test backend tanpa coverage.
    - `composer test:coverage-html` generate HTML coverage report di `coverage-report/`.

Detail lengkap aturan dan panduan penulisan test dapat dilihat di file [`TESTING.md`](./TESTING.md).

## Kontribusi

Jika Anda ingin berkontribusi pada proyek ini, silakan buka issue atau pull request di repositori ini.

## Lisensi

Project ini adalah milik perusahaan Nusanet. Penggunaan, distribusi, atau modifikasi kode ini tanpa izin resmi dari Nusanet dilarang.
