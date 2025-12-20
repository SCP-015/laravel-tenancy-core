---
trigger: always_on
---

# Project Rules - Nusahire (Laravel + Vue + Inertia + Tailwind)
## Frontend Rules
1. Framework frontend: Vue 3 dengan Inertia.js untuk server-side rendering; semua halaman baru WAJIB mengikuti pola ini.
2. Styling frontend: gunakan Tailwind CSS sebagai standar; hindari custom CSS jika memungkinkan, gunakan custom CSS hanya bila utility Tailwind tidak mencukupi.
3. Build tool: gunakan Vite untuk bundling assets dan development server; konfigurasi build harus kompatibel dengan Laravel.
4. Logic Vue WAJIB menggunakan Composition API (`<script setup>` atau composables) untuk komponen baru; hindari Options API untuk kode baru.
5. Logic kompleks di Vue WAJIB dipisahkan ke composables di [resources/js/composables](cci:7://file:///Users/riantopangaribuan/Workspace/php/nusahire/resources/js/composables:0:0-0:0) agar komponent tetap tipis dan mudah diuji.
6. Props di semua komponen Vue WAJIB diberi type hints menggunakan `PropType` (kecuali tipe sederhana yang sudah jelas).
7. Emit events WAJIB menggunakan nama kebab-case yang jelas dan konsisten (misal: `update-page`, `confirm-delete`).
8. Gunakan `v-model` untuk two-way binding pada form inputs; hindari sinkronisasi manual value dan event bila tidak perlu.
9. Hindari direct DOM manipulation di Vue; gunakan reactivity system (ref, reactive, computed, watch) dan refs komponen bila benar-benar diperlukan.
10. Untuk halaman Inertia, gunakan Inertia Form helper untuk form submission dan validation ketika sesuai; tampilkan validation errors dari backend melalui `form.errors`.
11. Implementasikan optimistic updates untuk interaksi kritis yang sering digunakan user, bila aman dilakukan.
12. Selalu tampilkan loading state dan disable tombol ketika form sedang disubmit atau data sedang di-load untuk menghindari double submit.
13. Untuk data fetching biasa, gunakan Inertia sebagai mekanisme utama; hindari `fetch`/`axios` langsung jika bisa dicapai dengan Inertia.
14. Untuk kebutuhan real-time, gunakan WebSockets atau polling terkontrol; hindari polling agresif tanpa batas waktu.
15. Setiap pemanggilan API di frontend WAJIB memiliki error handling yang jelas (tampilkan pesan atau popup-info yang informatif untuk user).
16. Gunakan lazy load untuk Vue components dengan `defineAsyncComponent` atau dynamic imports untuk code splitting di halaman berat.
17. Gunakan lazy loading Inertia untuk menunda load data yang tidak kritis (misal tab yang jarang dibuka).
18. Optimalkan gambar dan assets (format modern seperti WebP) terutama untuk halaman yang diakses publik (portal, landing).
19. Monitor dan optimalkan Core Web Vitals (LCP, FID, CLS) untuk halaman penting; hindari layout shift besar yang mengganggu UX.
20. Style halaman frontend WAJIB mengikuti pattern yang sudah ada agar konsisten di seluruh aplikasi.
21. Header utama halaman admin: `text-[18px] sm:text-[24px] font-semibold` dengan warna teks `text-gray-900`.
22. Subheading/description admin: `text-[14px]` dengan warna `text-gray-500` atau `text-gray-600`.
23. Tombol primary admin: `bg-[#3A3A3A] text-white px-6 py-2 rounded-md shadow-sm transition hover:bg-gray-800 text-[14px]`.
24. Tombol secondary admin: `bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-md shadow-sm transition hover:bg-gray-50 text-[14px]`.
25. Section filter: gunakan wrapper `bg-white border border-gray-200 rounded-lg p-4 mb-4`.
26. Input/select umum: gunakan `rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[14px]`.
27. Teks tabel: gunakan `text-[13px]` untuk konten utama dan `text-[11px]` untuk informasi sekunder.
28. Spacing standar layout: gunakan `mb-4`, `mb-5`, dan `gap-4` untuk menjaga konsistensi jarak antar elemen.
29. Skema warna teks: gunakan `text-gray-900` untuk teks utama, `text-gray-500` untuk teks sekunder, dan `border-gray-200` untuk border.
30. Badge/status: gunakan `rounded-full px-2 py-1 text-[11px]` dengan warna background dan teks sesuai status (success, warning, error, neutral).
31. Hindari arbitrary values seperti `bg-gray-50`, `max-w-7xl`, `py-8` jika tidak konsisten dengan pattern existing; gunakan kelas yang sudah disepakati.
32. Referensi utama style admin: gunakan [/resources/js/pages/lowongan/templates.vue](cci:7://file:///Users/riantopangaribuan/Workspace/php/nusahire/resources/js/pages/lowongan/templates.vue:0:0-0:0) sebagai contoh pattern yang benar.
33. Semua API call di frontend WAJIB menggunakan `mainStore.useAPI()`; DILARANG menggunakan `axios` langsung.
34. Pola dasar pemanggilan API: `mainStore.useAPI('${portalId}/api/endpoint', { method: 'GET' }, true)`.
35. Tenant ID WAJIB selalu diikutsertakan: `const portalId = portal.value[0]?.id`.
36. Portal reference WAJIB: `const portal = computed(() => mainStore.userPortal.value)`.
37. Import wajib untuk API call di Vue: `import { useMainStore } from '../stores'` dan `import { computed } from 'vue'`.
38. Jika `portalId` tidak ditemukan, WAJIB melempar error eksplisit: `throw new Error('Portal ID tidak ditemukan')`.
39. Query string WAJIB dibangun dengan `URLSearchParams()`; hindari membangun URL query dengan string concatenation manual.
40. Parameter ketiga `useAPI()` WAJIB bernilai `true` untuk menyertakan auth token pada setiap request yang membutuhkan autentikasi.
41. Semua composable yang memanggil API WAJIB mengikuti pattern yang sama (lihat `useAuditLogs.js` dan [templates.vue](cci:7://file:///Users/riantopangaribuan/Workspace/php/nusahire/resources/js/pages/lowongan/templates.vue:0:0-0:0) sebagai referensi).
42. DILARANG meng-hardcode tenant ID atau meng-skip tenant context; hal ini akan merusak isolasi data multi-tenant dan menyebabkan error “Gagal memuat data”.
43. WAJIB menggunakan komponen existing sebelum membuat komponen baru (reusable-first).
44. Komponen reusable utama: `default-table`, `modal`, `popup-info`, `confirmation-modal`, dan komponen lain yang sudah ada di [resources/js/components](cci:7://file:///Users/riantopangaribuan/Workspace/php/nusahire/resources/js/components:0:0-0:0).
45. `default-table` menjadi standar untuk semua tampilan tabel di admin; DILARANG membuat `<table>` custom di page jika `default-table` sudah mencukupi.
46. `default-table` digunakan dengan props standar (`fields`, `items`, `wrapperTableClass`, `total-data`, `is-skeleton`, `is-loading`, `page`, `itemsPerPage`) dan event standar (`@update-page`, `@update-items-per-page`, `@search`, `@getData`, `@pageClick` bila perlu).
47. Untuk custom cell/head, gunakan slots `#customCell(fieldKey)` dan `#customHead(fieldKey)` pada `default-table`.
48. Dialog konten (form, detail, konfigurasi) WAJIB menggunakan `modal` component; DILARANG membuat struktur modal manual dengan `fixed inset-0` kecuali untuk overlay khusus.
49. Dialog konfirmasi standar WAJIB menggunakan `confirmation-modal` dengan event `@confirm` dan `@close/@cancel`.
50. Popup informasi global (error, warning, info umum) WAJIB menggunakan `popup-info` yang terhubung ke `mainStore.stateShowInfo`; popup custom per halaman hanya dibuat untuk kasus khusus yang tidak dapat di-handle oleh `popup-info`.