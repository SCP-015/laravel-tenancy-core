<template>
    <div class="min-h-screen flex flex-col bg-white">

        <!-- Navbar Khusus Landing Page -->
        <header class="bg-white border-b border-gray-100 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-6 sm:px-10 lg:px-20">
                <div class="flex items-center justify-between py-4">
                    
                    <!-- 1. Logo Nusahire -->
                    <a href="/" class="text-2xl font-extrabold flex-shrink-0">
                        <span class="text-green-600">nusa</span>
                        <strong class="text-green-800">hire</strong>
                    </a>

                    <!-- 2. Menu Desktop (Hidden di Mobile) -->
                    <nav class="hidden md:flex items-center gap-8">
                        <a href="#fitur" class="text-[15px] font-medium text-gray-600 hover:text-[#00852C] transition-colors">
                            Fitur
                        </a>
                        
                        <!-- Tombol Desktop -->
                        <a href="/auth/login"
                           class="inline-flex items-center justify-center rounded-lg bg-[#00852C] text-white px-5 py-2 text-[14px] font-semibold shadow-md hover:bg-green-700 transition-colors">
                            Mulai Gratis
                        </a>
                    </nav>

                    <!-- 3. Tombol Hamburger (Hanya Muncul di Mobile) -->
                    <button
                            type="button"
                            @click="toggleMobileMenu"
                            class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none"
                            :aria-expanded="isMobileMenuOpen ? 'true' : 'false'"
                            aria-controls="landing-mobile-menu"
                            aria-label="Buka menu navigasi">
                        <!-- Icon Garis 3 (Menu) -->
                        <svg v-if="!isMobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <!-- Icon Silang (Close) -->
                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- 4. Menu Dropdown Mobile (Muncul jika isMobileMenuOpen = true) -->
                <!-- Transisi halus untuk menu mobile -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <div
                        v-if="isMobileMenuOpen"
                        id="landing-mobile-menu"
                        class="md:hidden py-4 border-t border-gray-100">
                        <div class="flex flex-col gap-4">
                            <a href="#fitur" 
                               @click="isMobileMenuOpen = false"
                               class="text-[15px] font-medium text-gray-600 hover:text-[#00852C] py-2">
                                Fitur
                            </a>
                            
                            <!-- Tombol Versi Mobile (Full Width) -->
                            <a href="/auth/login"
                               class="w-full flex items-center justify-center rounded-lg bg-[#00852C] text-white px-5 py-3 text-[14px] font-semibold shadow-md hover:bg-emerald-700 transition-colors">
                                Mulai Gratis
                            </a>
                        </div>
                    </div>
                </transition>
            </div>
        </header>

        <!-- Hero Section with Gradient Background -->
        <main class="flex-1 flex flex-col bg-[#f7faf4]">
            <!-- Hero Section Dua Kolom -->
            <section class="px-6 sm:px-10 lg:px-20 py-10 sm:py-14">
                <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-10 lg:gap-16 items-center">
                    <!-- Kolom Kiri: Teks -->
                    <div class="text-center md:text-left">
                        <h1
                            class="text-[34px] sm:text-[42px] lg:text-[52px] font-extrabold leading-tight text-[#000000] mb-5 max-w-2xl mx-auto md:mx-0">
                            Rekrut karyawan tanpa kerut, kerja penuh senyum
                        </h1>
                        <p class="text-[15px] sm:text-[16px] text-gray-700 max-w-xl mx-auto md:mx-0 mb-8">
                            Solusi rekrutmen terintegrasi yang memungkinkan Anda
                            mengelola portal karir profesional dan kandidat
                            terbaik, didukung oleh ekosistem Nusawork.
                        </p>

                        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center md:justify-start">
                            <a href="/auth/login"
                                class="inline-flex items-center justify-center rounded-full bg-[#00852C] text-white px-7 py-3 text-[14px] sm:text-[15px] font-semibold shadow-md hover:bg-green-700 transition">
                                Mulai Gratis
                            </a>
                            <a href="#fitur"
                                class="inline-flex items-center justify-center rounded-full border border-gray-300 bg-white text-[#00852C] px-7 py-3 text-[14px] sm:text-[15px] font-semibold shadow-sm hover:border-[#00852C] hover:bg-emerald-50 transition">
                                Lihat Fitur Lengkap
                            </a>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Hero Image (WebP optimized + PNG fallback) -->
                    <div class="mt-8 md:mt-0 w-full flex justify-center">
                        <picture class="w-full max-w-xl">
                            <source
                                srcset="/images/landing-page-mobile.webp 900w"
                                type="image/webp" />
                            <img src="/images/landing-page-optimized-mobile.png"
                                 alt="Preview company Nusahire"
                                 width="900" 
                                 height="799"
                                 fetchpriority="high"
                                 decoding="async"
                                 class="w-full h-auto rounded-lg md:shadow-lg" 
                                 style="display: block; aspect-ratio: 900 / 799;" />
                        </picture>
                    </div>
                </div>
            </section>

            <!-- Section Kedua: Mengapa Memilih Nusahire -->
            <section id="fitur" class="bg-gray-50 py-14 px-6 sm:px-10 lg:px-20">
                <div class="max-w-6xl mx-auto">
                    <div class="text-center mb-10">
                        <h2 class="text-[26px] sm:text-[30px] font-extrabold text-[#00852C] mb-3">
                            Mengapa Memilih Nusahire?
                        </h2>
                        <p class="text-[14px] sm:text-[15px] text-gray-700 max-w-2xl mx-auto">
                            Kami menyediakan semua yang dibutuhkan perusahaan modern untuk
                            mengelola proses rekrutmen.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
                        <!-- Kartu 1 -->
                        <div
                            class="feature-card bg-white rounded-2xl p-6 shadow-[0_12px_30px_rgba(15,23,42,0.12)] cursor-pointer transition-transform duration-300 hover:-translate-y-1">
                            <div class="text-gray-800 mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6M7 4h7l4 4v10a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z" />
                                </svg>
                            </div>
                            <h3 class="text-[16px] sm:text-[17px] font-semibold text-gray-900 mb-2">
                                Buat lowongan mudah
                            </h3>
                            <p class="text-[13px] sm:text-[14px] text-gray-600 leading-relaxed">
                                Buat dan publikasikan lowongan kerja profesional dengan cepat. Cukup isi detail,
                                unggah logo perusahaan Anda, dan langsung akan tayang. Hemat waktu, rekrut lebih cepat.
                            </p>
                        </div>

                        <!-- Kartu 2 -->
                        <div
                            class="feature-card bg-white rounded-2xl p-6 shadow-[0_12px_30px_rgba(15,23,42,0.12)] cursor-pointer transition-transform duration-300 hover:-translate-y-1">
                            <div class="text-gray-800 mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2m18 0v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75M9 11a4 4 0 100-8 4 4 0 000 8z" />
                                </svg>
                            </div>
                            <h3 class="text-[16px] sm:text-[17px] font-semibold text-gray-900 mb-2">
                                Manajemen Kandidat
                            </h3>
                            <p class="text-[13px] sm:text-[14px] text-gray-600 leading-relaxed">
                                Kelola semua kandidat dari satu dashboard. Lacak status setiap pelamar, beri catatan,
                                dan jadwalkan wawancara dengan mudah. Kolaborasi tim jadi lebih efektif.
                            </p>
                        </div>

                        <!-- Kartu 3 -->
                        <div
                            class="feature-card bg-white rounded-2xl p-6 shadow-[0_12px_30px_rgba(15,23,42,0.12)] cursor-pointer transition-transform duration-300 hover:-translate-y-1">
                            <div class="text-gray-800 mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-[16px] sm:text-[17px] font-semibold text-gray-900 mb-2">
                                Integrasi dengan Nusawork
                            </h3>
                            <p class="text-[13px] sm:text-[14px] text-gray-600 leading-relaxed">
                                Tidak perlu lagi menyalin data satu per satu. Dengan satu klik, seluruh informasi
                                kandidat
                                akan otomatis masuk ke sistem Nusawork. Proses akan menjadi lebih cepat dan akurat.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Bottom CTA - wrapped in gray background -->
            <section class="bg-gray-50 py-16 px-4">
                <div class="flex flex-col items-center text-center">
                    <h2 class="text-[24px] sm:text-[30px] font-semibold text-gray-900 mb-6">
                        Saring dan kelola kandidat terbaik
                        <br class="hidden sm:block" />
                        dengan <span class="text-[#00852C]">nusahire</span>
                    </h2>

                    <a href="/auth/login"
                        class="inline-flex items-center justify-center bg-[#00852C] rounded-lg text-white px-7 py-4 text-[14px] font-semibold shadow-md hover:bg-green-700 transition btn-shiny relative overflow-hidden">
                        <span class="relative z-10">Mulai Gratis</span>
                        <span class="shiny-overlay absolute inset-0 rounded-md"></span>
                    </a>

                    <p class="mt-4 text-[14px] text-gray-600">
                        Hiring solution from
                        <a href="https://nusawork.com" target="_blank" rel="noopener noreferrer"
                            class="text-green-700 font-medium transition cursor-pointer">
                            Nusawork
                        </a>
                    </p>
                </div>
            </section>
        </main>

        <Modal :show="isFeedback" :style-props="{
            width: windowWidth < 1080 ? '95%' : '700px',
            height: 'auto',
            maxHeight: '90vh',
            zIndex: 1000,
        }" :useClose="false" :useFooter="false" @update-show="handleModalClose">
            <template #header>
                <div class="flex justify-between items-center w-full pt-4 px-8">
                    <h3 class="text-xl font-bold ml-4">Beri Masukan</h3>
                    <button @click="cancelFeedback" class="p-1 rounded-full hover:bg-gray-100 transition mr-4">
                        <img src="/images/close.svg" alt="Tutup" loading="lazy" decoding="async" class="w-5 h-5" />
                    </button>
                </div>
            </template>

            <template #body>
                <div class="wrapper-body-modal px-6 pb-4 overflow-y-auto max-h-[calc(90vh-100px)]">
                    <div class="w-full mb-3">
                        <img src="/images/feedback-banner.svg" alt="Every feedback is a gift"
                            loading="lazy" decoding="async" class="w-full rounded-lg object-cover" />
                    </div>

                    <!-- Input Nama dan Email - Compact -->
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <input type="text" v-model="senderName" placeholder="Nama Anda"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-[#00852C] focus:border-[#00852C]" />
                        </div>
                        <div>
                            <input type="email" v-model="senderEmail" placeholder="Email Anda"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-[#00852C] focus:border-[#00852C]" />
                        </div>
                    </div>

                    <!-- Screenshot Section - Compact -->
                    <div class="mb-3 bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <div class="flex flex-nowrap gap-2 overflow-x-auto">
                            <template v-if="screenshots.length">
                                <div v-for="(shot, idx) in screenshots" :key="idx"
                                    class="relative w-32 h-24 rounded-lg flex items-center justify-center overflow-hidden group border border-gray-200 flex-shrink-0">
                                    <img :src="shot" alt="Screenshot feedback"
                                        class="object-cover w-full h-full rounded-lg" />
                                    <label v-if="idx > 0"
                                        class="absolute inset-0 bg-black/50 text-white flex flex-col items-center justify-center gap-1 opacity-0 transition-opacity group-hover:opacity-100 cursor-pointer">
                                        <span class="text-xs">Ganti</span>
                                        <input type="file" class="hidden"
                                            @change="(event) => replaceScreenshot(event, idx)"
                                            accept=".jpg,.jpeg,.png" />
                                    </label>
                                    <button @click="removeScreenshot(idx)"
                                        class="absolute top-1 right-1 z-10 p-0 bg-white rounded-full">
                                        <img src="/images/close.svg" alt="Hapus" class="w-3 h-3" />
                                    </button>
                                </div>
                            </template>
                            <label v-if="screenshots.length < 4"
                                class="w-32 h-24 border-2 border-dashed border-gray-300 hover:border-green-500 rounded-lg flex items-center justify-center cursor-pointer transition flex-shrink-0">
                                <img src="/images/icon-add.svg" alt="Tambah" class="opacity-70 w-6 h-6" />
                                <input type="file" class="hidden" @change="addScreenshotFromFile"
                                    accept=".jpg,.jpeg,.png" />
                            </label>
                        </div>
                        <p class="text-xs text-blue-600 mt-2 break-all">
                            <b>{{ currentUrl }}</b>
                        </p>
                    </div>

                    <p class="text-sm text-gray-700 mb-4 text-center">
                        Beritahu kami apa yang Anda lakukan, apa yang
                        terjadi, apa ekspektasi anda, atau apa yang membuat
                        Anda kesulitan.
                    </p>

                    <!-- Category Buttons - Compact -->
                    <div class="category-buttons flex gap-2 mb-3 w-full">
                        <button class="category-btn border px-3 py-1.5 rounded text-sm transition-colors flex-1"
                            :class="{ 'active-category': selectedCategory === 'Saran' }"
                            @click="selectedCategory = 'Saran'">
                            Saran
                        </button>
                        <button class="category-btn border px-3 py-1.5 rounded text-sm transition-colors flex-1"
                            :class="{ 'active-category': selectedCategory === 'Pujian' }"
                            @click="selectedCategory = 'Pujian'">
                            Pujian
                        </button>
                        <button class="category-btn border px-3 py-1.5 rounded text-sm transition-colors flex-1"
                            :class="{ 'active-category': selectedCategory === 'Keluhan' }"
                            @click="selectedCategory = 'Keluhan'">
                            Keluhan
                        </button>
                    </div>

                    <!-- Textarea - Compact -->
                    <div class="mb-3">
                        <textarea v-model="feedbackText" placeholder="Tulis masukan di sini"
                            class="w-full border rounded-lg p-3 h-24 text-gray-700 text-sm focus:ring-[#00852C] focus:border-[#00852C]"></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-2">
                        <button class="btn b-grey-fix fs-14" @click="cancelFeedback">
                            Batal
                        </button>
                        <button class="btn b-new-green fs-14 flex items-center justify-center gap-2"
                            @click="submitFeedback"
                            :disabled="isSubmitting || !feedbackText.trim() || !selectedCategory || !senderName.trim() || !senderEmail.trim()">
                            Kirim
                            <img v-if="isSubmitting" src="/images/loading.gif" alt="Loading..."
                                class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </template>
        </Modal>

        <!-- Error Modal -->
        <Modal :show="errorModal.show" :title="errorModal.title" :use-close="false"
            :style-props="{ maxWidth: '350px', borderRadius: '12px' }">
            <template #header></template>
            <template #body>
                <div class="p-6 text-center flex flex-col items-center gap-4">
                    <div class="icon-rise-bounce mb-2">
                        <img src="/images/warning.svg" alt="Warning" loading="lazy" decoding="async" class="w-16 h-16" />
                    </div>
                    <h2 class="font-bold text-lg text-gray-800">Perhatian</h2>
                    <p class="text-gray-600">{{ errorModal.message }}</p>
                </div>
            </template>
            <template #footer>
                <div class="px-6 pb-6 pt-0 flex justify-center w-full">
                    <button
                        class="bg-gray-700 text-white rounded-lg px-6 py-3 w-full font-medium hover-bg-gray-800 transition-colors"
                        @click="errorModal.show = false">
                        OK
                    </button>
                </div>
            </template>
        </Modal>

        <!-- Success Modal -->
        <Modal :show="successModal.show" :title="successModal.title" :use-close="false"
            :style-props="{ maxWidth: '350px', borderRadius: '12px' }">
            <template #header></template>
            <template #body>
                <div class="p-6 text-center flex flex-col items-center gap-4">
                    <div class="icon-rise-bounce mb-2">
                        <img src="/images/success.svg" alt="Success" loading="lazy" decoding="async" class="w-16 h-16" />
                    </div>
                    <h2 class="font-bold text-lg text-gray-800">
                        Masukan Terkirim
                    </h2>
                    <p class="text-gray-600">{{ successModal.message }}</p>
                </div>
            </template>
            <template #footer>
                <div class="px-6 pb-6 pt-0 flex justify-center w-full">
                    <button
                        class="bg-gray-700 text-white rounded-lg px-6 py-3 w-full font-medium hover-bg-gray-800 transition-colors"
                        @click="successModal.show = false">
                        OK
                    </button>
                </div>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted, defineAsyncComponent } from "vue";

// Lazy load Modal - hanya di-load ketika dibutuhkan
const Modal = defineAsyncComponent(() => import("@/components/modal.vue"));

let toJpegInstance = null;
let axiosInstance = null;

const loadHtmlToImage = async () => {
    if (!toJpegInstance) {
        const mod = await import("html-to-image");
        toJpegInstance = mod.toJpeg;
    }
    return toJpegInstance;
};

const loadAxios = async () => {
    if (!axiosInstance) {
        const mod = await import("axios");
        axiosInstance = mod.default || mod;
    }
    return axiosInstance;
};

// --- Konstanta Ukuran File (Sesuai dengan Backend: 2MB) ---
const MAX_FILE_SIZE_MB = 2;
const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024;

// --- Feedback Modal Logic ---
const windowWidth = ref(window.innerWidth);
const isFeedback = ref(false);
const isSubmitting = ref(false);
const screenshots = ref([]);
const fileImage = ref([]);
const currentUrl = ref("");
const selectedCategory = ref("");
const feedbackText = ref("");
const senderName = ref("");
const senderEmail = ref("");

const errorModal = ref({
    show: false,
    title: "",
    message: "",
});

const successModal = ref({
    show: false,
    title: "",
    message: "",
});

onMounted(() => {
    window.addEventListener("resize", () => {
        windowWidth.value = window.innerWidth;
    });
});

const openFeedbackModal = async () => {
    let originalBackgroundColor = "";

    try {
        currentUrl.value =
            window.location.origin +
            window.location.pathname +
            window.location.search +
            window.location.hash;

        const mainContentElement =
            document.getElementById("main-content") || document.body;

        originalBackgroundColor = mainContentElement.style.backgroundColor;
        mainContentElement.style.backgroundColor = "white";

        const toJpeg = await loadHtmlToImage();
        const jpegDataUrl = await toJpeg(mainContentElement, { quality: 0.95 });
        screenshots.value = [jpegDataUrl];

        const blobFile = dataURItoBlob(jpegDataUrl);
        const uniqueFilename = generateUniqueFilename(
            "screenshot_otomatis.jpeg"
        );
        const file = new File([blobFile], uniqueFilename, {
            type: "image/jpeg",
        });
        fileImage.value = [file];
    } catch (error) {
        console.error("Gagal mengambil screenshot:", error);
        screenshots.value = [];
        fileImage.value = [];
    } finally {
        const mainContentElement =
            document.getElementById("main-content") || document.body;
        mainContentElement.style.backgroundColor = originalBackgroundColor;
    }

    isFeedback.value = true;
};

const removeScreenshot = (index) => {
    screenshots.value.splice(index, 1);
    fileImage.value.splice(index, 1);
};

const addScreenshotFromFile = (event) => {
    if (screenshots.value.length >= 4) {
        alert("Anda hanya dapat mengunggah maksimal 3 gambar tambahan.");
        event.target.value = "";
        return;
    }

    const file = event.target.files[0];
    if (!file) {
        event.target.value = "";
        return;
    }

    if (file.size > MAX_FILE_SIZE_BYTES) {
        errorModal.value.message = `Ukuran file "${file.name}" melebihi batas maksimum ${MAX_FILE_SIZE_MB} MB.`;
        errorModal.value.show = true;
        event.target.value = "";
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        screenshots.value.push(e.target.result);
        const uniqueFilename = generateUniqueFilename(file.name);
        fileImage.value.push(
            new File([file], uniqueFilename, { type: file.type })
        );
        event.target.value = "";
    };
    reader.readAsDataURL(file);
};

const replaceScreenshot = (event, index) => {
    const file = event.target.files[0];
    if (!file) {
        event.target.value = "";
        return;
    }

    if (file.size > MAX_FILE_SIZE_BYTES) {
        errorModal.value.message = `Ukuran file "${file.name}" melebihi batas maksimum ${MAX_FILE_SIZE_MB} MB.`;
        errorModal.value.show = true;
        event.target.value = "";
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        screenshots.value[index] = e.target.result;
        const uniqueFilename = generateUniqueFilename(file.name);
        fileImage.value[index] = new File([file], uniqueFilename, {
            type: file.type,
        });
        event.target.value = "";
    };
    reader.readAsDataURL(file);
};

const submitFeedback = async () => {
    if (!senderName.value.trim()) {
        errorModal.value.message = "Mohon isi Nama Anda.";
        errorModal.value.show = true;
        return;
    }

    if (!senderEmail.value.trim()) {
        errorModal.value.message = "Mohon isi Email Anda.";
        errorModal.value.show = true;
        return;
    }

    if (!selectedCategory.value) {
        errorModal.value.message =
            "Mohon pilih salah satu kategori feedback (Saran, Pujian, atau Keluhan).";
        errorModal.value.show = true;
        return;
    }

    if (!feedbackText.value) {
        errorModal.value.message = "Mohon isi masukan Anda.";
        errorModal.value.show = true;
        return;
    }

    const oversizedFile = fileImage.value.find(
        (file) => file.size > MAX_FILE_SIZE_BYTES
    );

    if (oversizedFile) {
        errorModal.value.message = `Ukuran file "${oversizedFile.name}" melebihi batas maksimum ${MAX_FILE_SIZE_MB} MB. Mohon ganti gambar atau hapus.`;
        errorModal.value.show = true;
        return;
    }

    isSubmitting.value = true;

    try {
        const formData = new FormData();
        formData.append("url", currentUrl.value);
        formData.append("category", selectedCategory.value);
        formData.append("feedback", feedbackText.value);
        formData.append("sender_name", senderName.value);
        formData.append("sender_email", senderEmail.value);

        fileImage.value.forEach((file, index) => {
            formData.append(`screenshots[${index}]`, file, file.name);
        });

        const axios = await loadAxios();
        const response = await axios.post(`/api/feedback-public`, formData);

        if (response.status === 200) {
            isFeedback.value = false;
            successModal.value.message =
                "Terima kasih, masukan Anda sudah kami terima!";
            successModal.value.show = true;
            resetFeedbackForm();
        } else {
            console.error("Gagal mengirim ke backend:", response.data);
            errorModal.value.message =
                "Gagal mengirim feedback. Silakan coba lagi nanti.";
            errorModal.value.show = true;
        }
    } catch (error) {
        console.error("Error jaringan atau server:", error);

        if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            let errorMessage =
                "Masukan Anda tidak valid. Silakan periksa kembali data yang Anda masukkan.";

            if (validationErrors) {
                const firstErrorKey = Object.keys(validationErrors)[0];
                if (firstErrorKey) {
                    errorMessage = validationErrors[firstErrorKey][0];
                }
            }

            errorModal.value.message = errorMessage;
        } else if (error.response) {
            errorModal.value.message = `Terjadi kesalahan server (${error.response.status}). Silakan coba lagi atau hubungi admin.`;
        } else {
            errorModal.value.message =
                "Terjadi kesalahan jaringan. Periksa koneksi internet Anda.";
        }

        errorModal.value.show = true;
    } finally {
        isSubmitting.value = false;
    }
};

const resetFeedbackForm = () => {
    screenshots.value = [];
    fileImage.value = [];
    feedbackText.value = "";
    selectedCategory.value = "";
    senderName.value = "";
    senderEmail.value = "";
};

const cancelFeedback = () => {
    resetFeedbackForm();
    isFeedback.value = false;
};

const handleModalClose = (value) => {
    isFeedback.value = value;
    if (!value) {
        resetFeedbackForm();
    }
};

// --- Utility Functions ---
function dataURItoBlob(dataURI) {
    const byteString = atob(dataURI.split(",")[1]);
    const mimeString = dataURI.split(",")[0].split(":")[1].split(";")[0];
    const ab = new ArrayBuffer(byteString.length);
    const ia = new Uint8Array(ab);
    for (let i = 0; i < byteString.length; i++) {
        ia[i] = byteString.charCodeAt(i);
    }
    return new Blob([ab], { type: mimeString });
}

const generateUniqueFilename = (originalName) => {
    const now = new Date();
    const timestamp = now.getTime();
    const randomArray = new Uint32Array(1);
    crypto.getRandomValues(randomArray);
    const randomNumber = randomArray[0] % 100000;
    const originalExtension = originalName.split(".").pop().toLowerCase();
    const cleanName = originalName
        .substring(0, originalName.lastIndexOf("."))
        .replace(/[^a-zA-Z0-9]/g, "-");
    const finalExtension = ["jpeg", "png", "jpg", "gif"].includes(
        originalExtension
    )
        ? originalExtension
        : "jpeg";
    return `screenshot-${cleanName}-${timestamp}-${randomNumber}.${finalExtension}`;
};

const isMobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

</script>

<style scoped>
/* Hilangkan animasi berat di hero & cards untuk meningkatkan LCP dan stability */

/* Feature cards: tampil statis tanpa animasi scroll */
.feature-card {
    opacity: 1;
    transform: translateY(0);
}

/* Tombol kategori di feedback modal */
.category-btn.active-category {
    background-color: #00852c;
    color: #fff;
    border-color: #00852c;
}

.category-btn {
    border-color: #d1d5db;
}

.category-btn:not(.active-category):hover {
    background-color: #f3f4f6;
    border-color: #9ca3af;
}

/* Tombol di feedback modal */
.btn.b-grey-fix {
    background-color: #f3f4f6;
    color: #374151;
    border-radius: 0.375rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    transition: background-color 0.2s ease-in-out;
}

.btn.b-grey-fix:hover {
    background-color: #e5e7eb;
}

.btn.b-new-green {
    background-color: #00852c;
    color: #fff;
    border-radius: 0.375rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    transition: background-color 0.2s ease-in-out;
}

.btn.b-new-green:hover {
    background-color: #006622;
}

.btn.b-new-green:disabled {
    background-color: #a3a3a3;
    cursor: not-allowed;
    color: #e5e5e5;
}

textarea {
    resize: none;
    transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.wrapper-body-modal .mb-4 {
    margin-bottom: 0.75rem;
}

.fs-14 {
    font-size: 0.875rem;
}
</style>