---
trigger: always_on
---

# Global Windsurf Rules - Berlaku untuk Semua Project

## Backend Rules
1. Bahasa komunikasi: semua respons, penjelasan, dan komentar kode dari AI menggunakan Bahasa Indonesia.
2. Commit messages: WAJIB menggunakan format Conventional Commits dalam Bahasa Indonesia dengan pola `tipe(konteks): deskripsi singkat` (contoh: `fitur(auth): menambahkan endpoint login pengguna`).
3. Arsitektur backend: terapkan pola "Thin Controllers, Fat Services, Skinny Models" untuk semua framework (Laravel, Node, dll.).
4. Dependency Injection: WAJIB menggunakan Constructor Dependency Injection untuk semua dependensi; JANGAN pernah membuat instance service langsung dengan `new NamaService()`.
5. Single Responsibility: setiap class/service harus fokus pada satu tanggung jawab; pecah service besar menjadi beberapa service kecil bila perlu.
6. Sonar & code quality backend: semua kode backend harus mematuhi standar Sonar (hindari critical bugs, major code smells, dan kompleksitas berlebihan).
7. Keamanan backend: hindari SQL injection, XSS lewat backend, insecure deserialization, dan vulnerability umum lain; selalu validasi dan sanitasi input.
8. Stabilitas backend: hindari null pointer, unreachable code, variabel tidak terpakai; jaga code coverage minimal 80% untuk business logic kritis.
9. Sonar warnings: JANGAN di-ignore; perbaiki atau dokumentasikan alasan jelas jika terpaksa di-ignore.
10. Sentry backend: integrasikan Sentry (atau tool sejenis) untuk production (dan opsional staging); capture semua exception penting dengan context yang cukup (user, request, tenant, dsb.) tanpa menyimpan data sensitif.
11. Sentry data protection backend: JANGAN pernah mengirim password, token, atau PII ke Sentry; gunakan hook seperti `before_send` untuk mem-filter atau masking data.
12. Monitoring backend: pantau dashboard Sentry dan tools observability lain secara berkala; prioritaskan perbaikan error yang sering muncul atau berdampak besar.

## Frontend Rules
1. Bahasa & komunikasi FE: teks yang ditulis AI di komentar atau dokumentasi FE tetap menggunakan Bahasa Indonesia; konten yang terlihat user mengikuti aturan i18n per project.
2. Sonar & kualitas FE: semua kode frontend (Vue, React, JS, TS, dll.) harus mematuhi standar Sonar; hindari code smells, duplikasi, dan kompleksitas berlebihan di komponen.
3. Keamanan FE: hindari XSS di sisi frontend (misalnya penggunaan `v-html` atau `dangerouslySetInnerHTML` tanpa sanitasi); JANGAN pernah mengekspos secret atau credential di kode frontend.
4. Error handling FE: setiap pemanggilan API harus memiliki error handling yang jelas (tampilkan pesan atau popup), jangan swallow error secara diam-diam.
5. Sentry frontend: gunakan Sentry (atau tool sejenis) untuk menangkap error runtime FE di production; pastikan source maps tersedia agar stack trace terbaca.
6. Sentry data protection FE: sama seperti backend, jangan kirim password/token/PII ke Sentry; masking atau hapus field sensitif sebelum dikirim.
7. UX konsisten: ikuti design system / style guide per project (warna, spacing, typography, komponen reusable); hindari styling ad-hoc yang tidak sesuai pedoman.
8. Reusability FE: sebelum membuat komponen baru, cek dulu komponen reusable yang sudah ada; utamakan extend/improve existing component daripada membuat duplikasi.
9. Performance FE: perhatikan ukuran bundle (code splitting bila perlu), hindari rerender berat yang tidak perlu, dan optimalkan interaksi yang sering digunakan user.
10. Testing FE: untuk behavior kritis (form besar, flow penting, dsb.), sediakan minimal smoke test (unit/integration/e2e) agar regresi mudah terdeteksi.