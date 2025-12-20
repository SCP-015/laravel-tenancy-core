---
trigger: manual
---

# Project Rules - Nusahire (Laravel + Vue + Inertia + Tailwind)
## Backend Rules
1. Framework backend: Laravel; semua kode HARUS mengikuti praktik terbaik Laravel dan pola arsitektur yang disepakati.
2. Arsitektur multi-tenant: gunakan Laravel Tenancy; production DB PostgreSQL, development MySQL, testing SQLite; semua migrasi dan query HARUS kompatibel ketiga database.
3. Migration & query: gunakan Eloquent dan query builder Laravel; hindari syntax SQL spesifik vendor; pastikan migrasi berjalan di PostgreSQL dan SQLite tanpa modifikasi.
4. Konfigurasi testing: phpunit.xml wajib menggunakan `sqlite_landlord` dan `sqlite_tenant`; `config/database.php` harus memiliki konfigurasi lengkap untuk landlord dan tenant.
5. Tenancy context di tests: sebelum operasi tenant, WAJIB memanggil `actingAsTenant()` atau `tenancy()->initialize()` sesuai helper yang tersedia.
6. Database seeding: seeder harus idempotent dan berjalan di PostgreSQL & SQLite tanpa perubahan; hindari data ganda atau konflik constraint.
7. Performance query: hindari N+1 queries terutama di tenant context; gunakan eager loading `with()` secara eksplisit.
8. Controller hanya menerima request, memanggil Form Request untuk validasi, memanggil SATU method Service, lalu mengembalikan response.
9. DILARANG menempatkan logika bisnis di dalam Controller; semua proses multi-langkah dipindah ke Service.
10. Untuk halaman web, gunakan `return inertia('ComponentName', ['data' => $data])` sebagai pola standar Inertia response.
11. Endpoint API WAJIB mengembalikan JSON dengan struktur konsisten (misal: status, message, data, errors) dan tidak bercampur dengan HTML.
12. Model hanya berisi definisi relasi, atribut (`fillable`, `casts`), scopes, dan accessor/mutator sederhana.
13. DILARANG menempatkan logika bisnis kompleks di dalam Model; use case dan proses multi-step tetap di Service.
14. Semua logika bisnis, proses multi-langkah, dan integrasi API eksternal HARUS ditempatkan di Service class.
15. Service harus mengembalikan data yang siap dipakai frontend (DTO/array terstruktur), bukan objek Eloquent mentah bila tidak diperlukan.
16. Semua validasi request WAJIB menggunakan Form Request; JANGAN gunakan `$request->validate()` langsung di Controller.
17. Proses background seperti email dan notifikasi WAJIB menggunakan Events & Listeners atau Jobs (queue) sesuai kebutuhan skalabilitas.
18. Aturan i18n berlaku untuk Controller, Service, dan Vue components; semua pesan user-facing di backend HARUS melalui helper `__('...')`.
19. Pesan respons pengguna di Controller/Service HARUS ditulis dalam Bahasa Inggris di dalam `__('...')`; terjemahan Indonesia ditambahkan ke `resources/lang/id.json`.
20. Di file `.vue`, gunakan helper frontend `trans()` atau `trans_choice()` untuk i18n; JANGAN menggunakan `__('...')` langsung di `.vue`.
21. DILARANG menggunakan MD5 untuk operasi hash yang terkait security atau cache key data sensitif.
22. Untuk cache key dan grouping identifiers (baik sensitif maupun non-sensitif) gunakan `hash('sha256', $value)` sebagai standar.
23. Konteks sensitif termasuk: cache key untuk public key, authentication tokens, user data, dan informasi rahasia lain; WAJIB menggunakan SHA256 atau lebih kuat.
24. Jika MD5 harus digunakan (kasus langka), WAJIB ada dokumentasi jelas (komentar) yang menjelaskan alasan dan risiko; pertimbangkan anotasi `@codeCoverageIgnore` bila relevan.
25. Semua model dengan operasi POST, PUT/PATCH, DELETE WAJIB menggunakan Auditable trait untuk mencatat perubahan data.
26. Model yang WAJIB auditable termasuk: User, JobVacancy, QuestionTemplate, JobVacancyQuestion, dan model lain yang menyimpan data kritis bisnis.
27. Gunakan `auditInclude` untuk menentukan field yang di-track dan `auditExclude` untuk mengecualikan field auto-managed (created_at, updated_at, order, usage_count) kecuali ada kebutuhan bisnis khusus.
28. Method `generateTags()` pada model auditable WAJIB diimplementasikan agar audit logs dapat difilter berdasarkan tipe model atau konteks lain.
29. Operasi auto-counter seperti `incrementUsage` dan `decrementUsage` dianjurkan men-disable auditing sementara menggunakan `disableAuditing()` untuk menghindari noise berlebihan.
30. Semua endpoint create/update/delete WAJIB memicu audit log yang menyimpan `user_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, dan `url`.
31. Untuk bulk operations (attach/detach), pastikan setiap record tercatat secara individual di audit log, bukan hanya satu event global.
32. Unit/feature tests WAJIB men-cover skenario audit logging penting untuk memastikan event dan data audit tercatat dengan benar.
33. Struktur test WAJIB diorganisir berdasarkan domain/context: Auth, Central, Middleware, Tenant, Web, Unit, dan domain lain bila diperlukan.
34. Test untuk Tenant Controllers HARUS ditempatkan di `tests/Feature/Tenant/*ControllerTest.php` dengan penamaan yang konsisten.
35. Performance backend perlu mempertimbangkan beban multi-tenant; hindari operasi berat tanpa batching atau queue di context tenant.