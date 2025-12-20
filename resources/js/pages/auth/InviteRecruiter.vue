<template>
    <div>
        <div
            class="min-h-screen flex flex-col items-center justify-center bg-white"
        >
            <div class="mb-6 text-center relative">
                <!-- Logo Nusahire (Text Manual) -->
                <a href="/" class="text-4xl font-extrabold inline-block hover:opacity-80 transition-opacity">
                    <span class="text-[#00852C]">nusa</span><strong class="text-green-800">hire</strong>
                </a>
            </div>

            <div class="mb-8 text-center">
                <div
                    v-if="loading"
                    class="bg-gray-50 border border-gray-200 rounded-lg p-6 max-w-md"
                >
                    <div class="animate-pulse">
                        <div class="flex items-center justify-center mb-3">
                            <div class="w-8 h-8 bg-gray-300 rounded-full"></div>
                        </div>
                        <div class="h-4 bg-gray-300 rounded mb-2"></div>
                        <div class="h-6 bg-gray-300 rounded mb-2"></div>
                        <div class="h-3 bg-gray-300 rounded"></div>
                    </div>
                </div>

                <div
                    v-else-if="error"
                    class="bg-red-50 border border-red-200 rounded-lg p-6 max-w-md"
                >
                    <div class="flex items-center justify-center mb-3">
                        <svg
                            class="w-8 h-8 text-red-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            ></path>
                        </svg>
                    </div>
                    <p class="text-red-700 text-sm">{{ error }}</p>
                </div>

                <div
                    v-else
                    class="bg-green-50 border border-green-200 rounded-lg p-6 max-w-md"
                >
                    <div class="flex items-center justify-center mb-3">
                        <svg
                            class="w-8 h-8 text-green-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            ></path>
                        </svg>
                    </div>
                    <p class="text-gray-700 text-sm mb-2">
                        Anda diundang untuk bergabung sebagai admin di
                    </p>
                    <h2 class="text-xl font-semibold text-green-600">
                        {{ tenant.name }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-2">
                        Silakan login untuk melanjutkan
                    </p>
                </div>
            </div>

            <div
                v-if="!loading && !error"
                class="flex flex-col sm:flex-row gap-4 mb-10 cursor-pointer"
            >
                <button
                    class="cursor-pointer flex items-center gap-2 bg-white border border-[#EDEDED] text-gray-700 px-6 py-3 rounded-md shadow-sm hover:shadow-md transition"
                    @click="loginWithGoogle()"
                >
                    <img
                        src="/images/logo-google.svg"
                        alt="Google"
                        class="w-5 h-5"
                    />
                    <span class="text-[14px] text-[#7A7A7A]">
                        Masuk dengan Google
                        <span
                            v-if="loadingGoogle"
                            class="ml-1 d-flex align-center"
                        >
                            <img
                                src="/images/loading.gif"
                                alt="loading"
                                width="18"
                            />
                        </span>
                    </span>
                </button>

                <button
                    class="cursor-pointer flex items-center gap-2 bg-[#00852C] text-white px-6 py-3 rounded-md shadow-sm hover:bg-green-800 transition"
                    @click="handleNusaworkLogin"
                    :disabled="loadingNusawork"
                >
                    <img
                        src="/images/logo-nusawork-light.svg"
                        alt="logo nusawork"
                        class="w-5 h-5"
                    />
                    <span class="text-[14px] flex items-center">
                        <span v-if="!loadingNusawork"
                            >Masuk dengan Nusawork</span
                        >
                        <span v-else class="flex items-center">
                            <svg
                                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            Memuat...
                        </span>
                    </span>
                </button>
            </div>

            <div
                class="md:absolute md:bottom-20 flex flex-col sm:flex-row gap-6 text-center"
            >
                <div
                    class="flex flex-nowrap items-center bg-[#FBFBFB] rounded-[6px] px-8 py-4"
                >
                    <div>
                        <img
                            src="/images/speed.svg"
                            alt="lowongan nusahire"
                            class="w-5 h-5"
                        />
                    </div>
                    <p class="text-gray-700 text-[14px] pl-2 text-start">
                        Buat lowongan<br />dengan cepat
                    </p>
                </div>

                <div
                    class="flex flex-nowrap items-center bg-[#FBFBFB] rounded-[6px] px-8 py-4"
                >
                    <div>
                        <img
                            src="/images/sorting.svg"
                            alt="sortinglowongan nusahire"
                            class="w-5 h-5"
                        />
                    </div>
                    <p class="text-gray-700 text-[14px] pl-2 text-start">
                        Sorting kandidat<br />secara otomatis
                    </p>
                </div>

                <div
                    class="flex flex-nowrap items-center bg-[#FBFBFB] rounded-[6px] px-8 py-4"
                >
                    <div>
                        <img
                            src="/images/interview.svg"
                            alt="schedule nusahire"
                            class="w-5 h-5"
                        />
                    </div>
                    <p class="text-gray-700 text-[14px] pl-2 text-start">
                        Tentukan jadwal<br />interview
                    </p>
                </div>
            </div>

            <footer
                class="absolute bottom-5 text-center text-[14px] text-gray-500"
            >
                Hiring solution from
                <a
                    class="text-green-700 font-semibold"
                    target="_blank"
                    rel="noopener"
                    href="https://nusawork.com"
                    >Nusawork</a
                >
            </footer>
        </div>

        <div
            class="feedback fixed top-4 right-4 sm:top-5 sm:right-5 z-50 flex items-center cursor-pointer"
            @click="openFeedbackModal"
        >
            <img src="/images/icon-feedback.svg" alt="icon feedback" />
        </div>

        <Modal
            :title="'Masuk dengan Nusawork'"
            :show="showQR"
            :use-close="!isProcessingCallback"
            :use-footer="false"
            @update-show="handleUpdateShow"
            :styleProps="{ maxWidth: '48vh' }"
        >
            <template #body>
                <div class="p-2 flex flex-col items-center">
                    <div v-if="qrStatus === 'loading'" class="py-8">
                        <div class="animate-pulse flex flex-col items-center">
                            <div
                                class="w-48 h-48 bg-gray-200 rounded-lg mb-4"
                            ></div>
                            <p class="text-gray-600">Menyiapkan QR Code...</p>
                        </div>
                    </div>

                    <div
                        v-else-if="
                            qrStatus === 'waiting' ||
                            qrStatus === 'scanned' ||
                            qrStatus === 'processing'
                        "
                        class="space-y-4"
                    >
                        <p class="text-gray-700 text-center text-[14px]">
                            {{ qrMessage }}
                        </p>

                        <div
                            v-if="qrStatus === 'waiting'"
                            class="flex flex-col items-center justify-center"
                        >
                            <div v-if="qrCodeImage" class="qr-code-container">
                                <img
                                    :src="qrCodeDataUrl"
                                    alt="QR Code"
                                    class="w-48 h-48"
                                />
                            </div>

                            <div class="flex justify-center mt-3">
                                <ul class="text-sm space-y-2 list-decimal pl-5">
                                    <li>
                                        Buka <strong>Nusawork</strong> di ponsel
                                        Anda
                                    </li>
                                    <li>
                                        Klik ikon
                                        <strong class="whitespace-nowrap mx-1"
                                            >pindai QR
                                            <img
                                                src="data:image/svg+xml,%3csvg%20width='24'%20height='24'%20viewBox='0%200%2024%2024'%20fill='none'%20xmlns='http://www.w3.org/2000/svg'%3e%3cpath%20d='M16.5%203H21V7.5M7.5%203H3V7.5M3%2016.5V21H7.5M16.5%2021H21V16.5'%20stroke='black'%20stroke-width='2'%20stroke-linejoin='round'/%3e%3cpath%20d='M3%2012H21'%20stroke='black'%20stroke-width='2'/%3e%3cpath%20d='M3%2012H21'%20stroke='black'%20stroke-width='2'%20stroke-linecap='round'/%3e%3crect%20x='8'%20y='8'%20width='8'%20height='2'%20fill='black'/%3e%3crect%20x='8'%20y='14'%20width='8'%20height='2'%20fill='black'/%3e%3c/svg%3e"
                                                width="20"
                                                height="2"
                                                class="inline-block align-middle ml-1"
                                                alt="icon"
                                            />
                                        </strong>
                                        di sudut kanan atas pada menu beranda /
                                        profil
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div v-if="qrStatus === 'scanned'" class="mt-4">
                            <div
                                class="flex items-center justify-center space-x-3 mb-4"
                            >
                                <img
                                    :src="
                                        qrProfile.photo ||
                                        '/images/default-avatar.png'
                                    "
                                    :alt="qrProfile.first_name"
                                    class="w-12 h-12 rounded-full"
                                />
                                <div class="text-left">
                                    <p class="font-medium">
                                        {{ qrProfile.first_name }}
                                        {{ qrProfile.last_name }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        {{ qrProfile.email }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ qrProfile.company.name }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="flex flex-col items-center justify-center my-4"
                            >
                                <img
                                    src="data:image/svg+xml,%3csvg%20width='42'%20height='73'%20viewBox='0%200%2042%2073'%20fill='none'%20xmlns='http://www.w3.org/2000/svg'%3e%3crect%20x='2'%20y='2'%20width='38'%20height='69'%20rx='4.5'%20fill='white'%20stroke='black'%20stroke-width='3'/%3e%3crect%20x='3.5'%20y='39.5'%20width='35'%20height='31'%20rx='4'%20fill='%23D9D9D9'/%3e%3crect%20x='7.5'%20y='61.5'%20width='28'%20height='4'%20fill='%2300852C'/%3e%3crect%20x='7.5'%20y='42.5'%20width='24'%20height='1'%20fill='%237C7C7C'/%3e%3crect%20x='7.5'%20y='44.5'%20width='10'%20height='1'%20fill='%237C7C7C'/%3e%3c/svg%3e"
                                    alt="icon"
                                />
                                <p class="t-black-v2 fw-500 fs-12 mt-3">
                                    Konfirmasi login anda di Nusawork Mobile
                                </p>
                            </div>
                        </div>

                        <div v-if="qrStatus === 'processing'" class="mt-4">
                            <div
                                class="flex flex-col items-center justify-center py-8"
                            >
                                <div
                                    class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mb-4"
                                ></div>
                                <p class="text-gray-700 font-medium">
                                    Memproses login...
                                </p>
                                <p
                                    class="text-gray-500 text-sm mt-2 text-center"
                                >
                                    Mohon tunggu, sistem sedang menyiapkan akun
                                    Anda.<br />
                                    Proses ini mungkin memerlukan beberapa saat
                                    jika ini adalah login pertama kali.
                                </p>
                                <div
                                    class="mt-4 bg-green-50 border border-green-200 rounded-lg p-3"
                                >
                                    <div class="flex items-center">
                                        <svg
                                            class="w-4 h-4 text-green-600 mr-2"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                clip-rule="evenodd"
                                            ></path>
                                        </svg>
                                        <p class="text-green-700 text-xs">
                                            Jangan tutup halaman ini sampai
                                            proses selesai
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else-if="qrStatus === 'error'"
                        class="text-center py-8"
                    >
                        <div class="text-red-500 mb-4">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-12 w-12 mx-auto"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                        </div>
                        <p class="text-gray-700">{{ qrMessage }}</p>
                        <button
                            @click="generateQRCode"
                            class="mt-4 text-green-600 hover:text-green-800"
                        >
                            Coba Lagi
                        </button>
                    </div>
                </div>
            </template>
        </Modal>

        <Modal
            :show="isFeedback"
            :style-props="{
                width: windowWidth < 1080 ? '95%' : '700px',
                height: 'auto',
                maxHeight: '90vh',
                zIndex: 1000,
            }"
            :useClose="false"
            :useFooter="false"
            @update-show="handleModalClose"
        >
            <template #header>
                <div class="flex justify-between items-center w-full pt-4 px-8">
                    <h3 class="text-xl font-bold ml-4">Beri Masukan</h3>
                    <button
                        @click="cancelFeedback"
                        class="p-1 rounded-full hover:bg-gray-100 transition mr-4"
                    >
                        <img
                            src="/images/close.svg"
                            alt="Tutup"
                            class="w-5 h-5"
                        />
                    </button>
                </div>
            </template>

            <template #body>
                <div
                    class="wrapper-body-modal px-6 pb-4 overflow-y-auto max-h-[calc(90vh-100px)]"
                >
                    <div class="w-full mb-3">
                        <img
                            src="/images/feedback-banner.svg"
                            alt="Every feedback is a gift"
                            class="w-full rounded-lg object-cover"
                        />
                    </div>

                    <!-- Input Nama dan Email - Compact -->
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <input
                                type="text"
                                v-model="senderName"
                                placeholder="Nama Anda"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-[#00852C] focus:border-[#00852C]"
                            />
                        </div>
                        <div>
                            <input
                                type="email"
                                v-model="senderEmail"
                                placeholder="Email Anda"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-[#00852C] focus:border-[#00852C]"
                            />
                        </div>
                    </div>

                    <!-- Screenshot Section - Compact -->
                    <div
                        class="mb-3 bg-gray-50 border border-gray-200 rounded-lg p-3"
                    >
                        <div class="flex flex-nowrap gap-2 overflow-x-auto">
                            <template v-if="screenshots.length">
                                <div
                                    v-for="(shot, idx) in screenshots"
                                    :key="idx"
                                    class="relative w-32 h-24 rounded-lg flex items-center justify-center overflow-hidden group border border-gray-200 flex-shrink-0"
                                >
                                    <img
                                        :src="shot"
                                        alt="Screenshot feedback"
                                        class="object-cover w-full h-full rounded-lg"
                                    />
                                    <label
                                        v-if="idx > 0"
                                        class="absolute inset-0 bg-black/50 text-white flex flex-col items-center justify-center gap-1 opacity-0 transition-opacity group-hover:opacity-100 cursor-pointer"
                                    >
                                        <span class="text-xs">Ganti</span>
                                        <input
                                            type="file"
                                            class="hidden"
                                            @change="
                                                (event) =>
                                                    replaceScreenshot(
                                                        event,
                                                        idx
                                                    )
                                            "
                                            accept=".jpg,.jpeg,.png"
                                        />
                                    </label>
                                    <button
                                        @click="removeScreenshot(idx)"
                                        class="absolute top-1 right-1 z-10 p-0 bg-white rounded-full"
                                    >
                                        <img
                                            src="/images/close.svg"
                                            alt="Hapus"
                                            class="w-3 h-3"
                                        />
                                    </button>
                                </div>
                            </template>
                            <label
                                v-if="screenshots.length < 4"
                                class="w-32 h-24 border-2 border-dashed border-gray-300 hover:border-green-500 rounded-lg flex items-center justify-center cursor-pointer transition flex-shrink-0"
                            >
                                <img
                                    src="/images/icon-add.svg"
                                    alt="Tambah"
                                    class="opacity-70 w-6 h-6"
                                />
                                <input
                                    type="file"
                                    class="hidden"
                                    @change="addScreenshotFromFile"
                                    accept=".jpg,.jpeg,.png"
                                />
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
                        <button
                            class="category-btn border px-3 py-1.5 rounded text-sm transition-colors flex-1"
                            :class="{
                                'active-category': selectedCategory === 'Saran',
                            }"
                            @click="selectedCategory = 'Saran'"
                        >
                            Saran
                        </button>
                        <button
                            class="category-btn border px-3 py-1.5 rounded text-sm transition-colors flex-1"
                            :class="{
                                'active-category':
                                    selectedCategory === 'Pujian',
                            }"
                            @click="selectedCategory = 'Pujian'"
                        >
                            Pujian
                        </button>
                        <button
                            class="category-btn border px-3 py-1.5 rounded text-sm transition-colors flex-1"
                            :class="{
                                'active-category':
                                    selectedCategory === 'Keluhan',
                            }"
                            @click="selectedCategory = 'Keluhan'"
                        >
                            Keluhan
                        </button>
                    </div>

                    <!-- Textarea - Compact -->
                    <div class="mb-3">
                        <textarea
                            v-model="feedbackText"
                            placeholder="Tulis masukan di sini"
                            class="w-full border rounded-lg p-3 h-24 text-gray-700 text-sm focus:ring-[#00852C] focus:border-[#00852C]"
                        ></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-2">
                        <button
                            class="btn b-grey-fix fs-14"
                            @click="cancelFeedback"
                        >
                            Batal
                        </button>
                        <button
                            class="btn b-new-green fs-14 flex items-center justify-center gap-2"
                            @click="submitFeedback"
                            :disabled="
                                isSubmitting ||
                                !feedbackText.trim() ||
                                !selectedCategory ||
                                !senderName.trim() ||
                                !senderEmail.trim()
                            "
                        >
                            Kirim
                            <img
                                v-if="isSubmitting"
                                src="/images/loading.gif"
                                alt="Loading..."
                                class="w-5 h-5"
                            />
                        </button>
                    </div>
                </div>
            </template>
        </Modal>

        <Modal
            :show="errorModal.show"
            :title="errorModal.title"
            :use-close="false"
            :style-props="{ maxWidth: '350px', borderRadius: '12px' }"
        >
            <template #header></template>
            <template #body>
                <div class="p-6 text-center flex flex-col items-center gap-4">
                    <div class="icon-rise-bounce mb-2">
                        <img
                            src="/images/warning.svg"
                            alt="Warning"
                            class="w-16 h-16"
                        />
                    </div>
                    <h2 class="font-bold text-lg text-gray-800">Perhatian</h2>
                    <p class="text-gray-600">{{ errorModal.message }}</p>
                </div>
            </template>
            <template #footer>
                <div class="px-6 pb-6 pt-0 flex justify-center w-full">
                    <button
                        class="bg-gray-700 text-white rounded-lg px-6 py-3 w-full font-medium hover-bg-gray-800 transition-colors"
                        @click="errorModal.show = false"
                    >
                        OK
                    </button>
                </div>
            </template>
        </Modal>

        <Modal
            :show="successModal.show"
            :title="successModal.title"
            :use-close="false"
            :style-props="{ maxWidth: '350px', borderRadius: '12px' }"
        >
            <template #header></template>
            <template #body>
                <div class="p-6 text-center flex flex-col items-center gap-4">
                    <div class="icon-rise-bounce mb-2">
                        <img
                            src="/images/success.svg"
                            alt="Success"
                            class="w-16 h-16"
                        />
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
                        @click="successModal.show = false"
                    >
                        OK
                    </button>
                </div>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import Modal from "@/components/modal.vue";
import axios from "axios";
import { useMainStore } from "@/stores/index";
import { toJpeg } from "html-to-image";

// --- Konstanta Ukuran File (Sesuai dengan Backend: 2MB) ---
const MAX_FILE_SIZE_MB = 2;
const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024;

// --- Global Constants ---
const API_BASE_URL_PANEL_NUSAWORK =
    import.meta.env.VITE_BASE_URL_PANEL_NUSAWORK ||
    "https://panel.dev.nusa.work/api";
const mainStore = useMainStore();
const baseURL = window.location.origin;

// --- Props dari controller & State Login yang sudah ada ---
const props = defineProps({
    tenantSlug: String,
    inviteCode: String,
});
const tenant = ref({
    name: "",
    slug: "",
    code: "",
});
const loading = ref(true);
const error = ref(null);
let showQR = ref(false),
    loadingGoogle = ref(false),
    loadingNusawork = ref(false),
    urlGoogleLogin = ref("");
const qrCodeUrl = ref("");
const qrExpired = ref("");
const iframeLoaded = ref(false);
const qrStatus = ref(""); // 'loading', 'waiting', 'scanned', 'confirmed', 'processing', 'error'
const qrMessage = ref("");
const qrProfile = ref(null);
const qrCodeImage = ref("");
const isProcessingCallback = ref(false);
let pollInterval = null;
let timeoutId = null;

const qrCodeDataUrl = computed(() => {
    if (!qrCodeImage.value) return "";
    if (
        !qrCodeImage.value.includes("<svg") ||
        !qrCodeImage.value.includes("</svg>")
    ) {
        return "";
    }
    const encodedSvg = encodeURIComponent(qrCodeImage.value);
    return `data:image/svg+xml,${encodedSvg}`;
});

// --- STATE DAN FUNGSI BARU UNTUK FEEDBACK ---
const isFeedback = ref(false);
const isSubmitting = ref(false);
const screenshots = ref([]);
const fileImage = ref([]);
const currentUrl = ref("");
const selectedCategory = ref("");
const feedbackText = ref("");
const senderName = ref("");
const senderEmail = ref("");
const windowWidth = ref(window.innerWidth);

// POPUP KESALAHAN
const errorModal = ref({
    show: false,
    message: "",
});

// POPUP SUKSES
const successModal = ref({
    show: false,
    message: "",
});

// Function to generate a unique filename
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

// Fungsi utilitas untuk konversi Data URI ke Blob
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

// Fungsi untuk membuka modal feedback dan mengambil screenshot
const openFeedbackModal = async () => {
    let originalBackgroundColor = "";

    try {
        currentUrl.value = window.location.href;
        const mainContentElement =
            document.getElementById("main-content") || document.body;

        originalBackgroundColor = mainContentElement.style.backgroundColor;
        mainContentElement.style.backgroundColor = "white";

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

// Fungsi untuk menghapus screenshot
const removeScreenshot = (index) => {
    screenshots.value.splice(index, 1);
    fileImage.value.splice(index, 1);
};

// Fungsi untuk menambah screenshot dari file
const addScreenshotFromFile = (event) => {
    if (screenshots.value.length >= 4) {
        errorModal.value.message =
            "Anda hanya dapat mengunggah maksimal 3 gambar tambahan.";
        errorModal.value.show = true;
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

// Fungsi untuk mengganti screenshot yang sudah ada
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

// Fungsi untuk mengirim feedback
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
        formData.append("tenant_slug", props.tenantSlug);

        fileImage.value.forEach((file, index) => {
            formData.append(`screenshots[${index}]`, file, file.name);
        });

        // Tidak perlu headers 'Authorization' karena ini adalah API publik
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
        // Jika Modal ditutup, panggil reset
        resetFeedbackForm();
    }
};

const onMessageHandler = (event) => {
    handleGoogleLoginResponse(event).catch((err) => {
        console.error("Error in Google login response handler:", err);
    });
};

onMounted(async () => {
    clearUserSession();
    window.addEventListener("message", onMessageHandler);
    await fetchTenantData();
});

onBeforeUnmount(() => {
    window.removeEventListener("message", onMessageHandler);
    if (pollInterval) clearInterval(pollInterval);
    if (timeoutId) clearTimeout(timeoutId);
});

const fetchTenantData = async () => {
    try {
        loading.value = true;
        error.value = null;

        const response = await axios.post("/api/auth/validate-invite", {
            tenant_slug: props.tenantSlug,
            code: props.inviteCode,
        });

        if (response.data.status === "success") {
            tenant.value = response.data.data.tenant;
        } else {
            throw new Error(
                response.data.message || "Gagal memvalidasi undangan"
            );
        }
    } catch (err) {
        console.error("Error fetching tenant data:", err);
        error.value =
            err.response?.data?.message ||
            err.message ||
            "Gagal memuat data undangan";

        // Redirect ke halaman error jika invite tidak valid
        if (err.response?.status === 404) {
            router.visit("/404", {
                replace: true,
                data: {
                    message: "Link undangan tidak valid atau sudah kadaluarsa",
                },
            });
        }
    } finally {
        loading.value = false;
    }
};

const generateQRCode = async () => {
    try {
        // Reset state sebelum generate QR Code
        qrStatus.value = "loading";
        resetQRState();

        // Request QR Code dari API
        const response = await axios.get(
            `${API_BASE_URL_PANEL_NUSAWORK}/companies/login/qrcode`,
            {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-Localization": "id",
                    Accept: "application/json",
                },
            }
        );

        // Validasi response dari API
        if (!response?.data?.qrcode_image) {
            console.error("Format response tidak valid:", response);
            throw new Error("Format response tidak valid");
        }

        // Pastikan URL QR Code lengkap
        let qrImageUrl = response.data.qrcode_image;
        if (qrImageUrl && !qrImageUrl.startsWith("http")) {
            qrImageUrl =
                "https://panel.dev.nusa.work" +
                (qrImageUrl.startsWith("/") ? "" : "/") +
                qrImageUrl;
        }

        // Tambahkan timestamp untuk menghindari cache
        qrImageUrl +=
            (qrImageUrl.includes("?") ? "&" : "?") +
            "t=" +
            new Date().getTime();

        qrCodeUrl.value = qrImageUrl;
        await axios.get(qrImageUrl).then((res) => {
            qrCodeImage.value = res.data;
        });
        qrExpired.value = response.data.expired;

        qrStatus.value = "waiting";
        qrMessage.value = "Scan QR Code dengan aplikasi Nusawork";

        // Mulai polling status QR Code
        startPolling(response.data.qrcode_image);

        // Set timeout untuk expired
        const expiredTime = new Date(response.data.expired).getTime();
        const now = new Date().getTime();
        const timeout = expiredTime - now;

        // Hentikan timeout sebelumnya jika ada
        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            if (qrStatus.value === "waiting") {
                qrStatus.value = "error";
                qrMessage.value =
                    "QR Code telah kadaluarsa, silakan muat ulang";
                if (pollInterval) clearInterval(pollInterval);
            }
        }, timeout);
    } catch (error) {
        console.error("Error generating QR Code:", error);
        qrStatus.value = "error";
        qrMessage.value = "Gagal memuat QR Code, silakan coba lagi";
    }
};

const handleUpdateShow = (value) => {
    // Jangan tutup modal jika sedang processing callback
    if (isProcessingCallback.value && !value) {
        return;
    }

    showQR.value = value;
    clearInterval(pollInterval);
};

const startPolling = (qrUrl) => {
    // Hentikan polling sebelumnya jika ada
    if (pollInterval) clearInterval(pollInterval);

    pollInterval = setInterval(async () => {
        try {
            const response = await axios.get(qrUrl, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-Localization": "id",
                    Accept: "application/json",
                },
            });

            // Jika QR berhasil di-scan
            if (response.data.title === "Confirmation") {
                qrStatus.value = "scanned";
                qrProfile.value = response.data.data.profile;
                qrMessage.value = response.data.message;
            }

            // Jika login berhasil
            if (response.data.title === "Success" && response.data.data.token) {
                clearInterval(pollInterval);
                clearTimeout(timeoutId);

                // Set status processing sebelum memanggil API callback
                qrStatus.value = "processing";
                qrMessage.value = "Memproses login, mohon tunggu...";
                isProcessingCallback.value = true;

                const token = response.data.data.token || "";
                const profile = response.data.data.profile || {};

                const payload = {
                    token: token,
                    email: profile?.email ?? null,
                    first_name: profile?.first_name ?? null,
                    last_name: profile?.last_name ?? null,
                    photo: profile?.photo ?? null,
                    company: profile?.company ?? null,
                    join_code: props.inviteCode, // Gunakan invite code dari props
                };

                try {
                    const res = await mainStore.login(payload);
                    if (res.token && res.user) {
                        // Langsung login dengan select_tenant jika ada
                        setUserLogin(res.token, res.user, res.select_tenant);
                    } else {
                        qrStatus.value = "error";
                        qrMessage.value =
                            "Gagal melakukan login, silakan coba lagi";
                    }
                } catch (loginError) {
                    // Hentikan polling dan timeout
                    clearInterval(pollInterval);
                    if (timeoutId) clearTimeout(timeoutId);

                    // Tampilkan error di modal QR
                    qrStatus.value = "error";
                    const errorMessage =
                        loginError?.response?.data?.message ||
                        loginError?.message ||
                        "Gagal melakukan login, silakan coba lagi";
                    qrMessage.value = errorMessage;

                    // Log error untuk debugging
                    console.error("Error during Nusawork login:", {
                        message: loginError?.message,
                        response: loginError?.response,
                        config: loginError?.config,
                    });
                } finally {
                    isProcessingCallback.value = false;
                }
            }
        } catch (error) {
            // Hentikan proses polling & timeout yang masih aktif
            clearInterval(pollInterval);
            if (timeoutId) clearTimeout(timeoutId);

            // Update state UI
            qrStatus.value = "error";
            const errorMessage =
                error?.response?.data?.message ||
                error?.message ||
                "Terjadi kesalahan, silakan coba lagi";
            qrMessage.value = errorMessage;

            // Log detail error untuk debugging/monitoring
            console.error("Error polling QR status:", {
                message: error?.message,
                response: error?.response,
                config: error?.config,
            });
        }
    }, 2000); // Poll setiap 2 detik
};

const handleNusaworkLogin = async () => {
    try {
        loadingNusawork.value = true;
        resetQRState(); // Reset state terlebih dahulu
        showQR.value = true; // Tampilkan modal dulu
        await generateQRCode(); // Baru generate QR
    } catch (error) {
        qrStatus.value = "error";
        const errorMessage =
            error.response?.data?.message ||
            error.message ||
            "Terjadi kesalahan";
        qrMessage.value = "Gagal memuat QR Code: " + errorMessage;
        console.error("Error saat generate QR Code:", {
            message: error.message,
            response: error.response,
            config: error.config,
        });
    } finally {
        loadingNusawork.value = false;
    }
};

const setUserLogin = async (token, user, selectTenant = null) => {
    localStorage.setItem("token", token);
    localStorage.setItem("user", JSON.stringify(user));

    let selectedPortal = null;

    // Jika ada select_tenant dari response API, gunakan itu
    if (selectTenant) {
        selectedPortal = selectTenant;
        localStorage.setItem("selected_portal", JSON.stringify(selectedPortal));

        await mainStore.getPortal(true);

        const slug = selectedPortal.slug || "";
        const pathAdmin = import.meta.env.VITE_PATH_ADMIN ?? "admin";
        router.visit(`/${slug}/${pathAdmin}`);
        return;
    }

    // Jika tidak ada select_tenant, ambil dari getAllPortal seperti biasa
    const res = await mainStore.getAllPortal();

    if (res.status == 200 && res.data.length > 0) {
        // Ambil portal pertama sebagai default
        selectedPortal = res.data[0];
        localStorage.setItem("selected_portal", JSON.stringify(selectedPortal));

        await mainStore.getPortal(true);

        const slug = selectedPortal.slug || "";
        const pathAdmin = import.meta.env.VITE_PATH_ADMIN ?? "admin";
        router.visit(`/${slug}/${pathAdmin}`);
    } else {
        // Redirect ke setup portal dengan context invite
        const params = new URLSearchParams(window.location.search);
        const via = params.get("via") || "invite";
        const provider = params.get("provider") || "nusawork";
        const code = params.get("code") || props.inviteCode;

        if (via && provider && code) {
            router.visit(
                `/setup/portal?via=${via}&provider=${provider}&code=${code}`
            );
        } else {
            router.visit("/setup/portal");
        }
    }
};

// methods
const handleGoogleLoginResponse = async (event) => {
    const { access_token, user } = event.data;

    if (access_token && user) {
        return await setUserLogin(access_token, user);
    } else {
        return console.warn("Login Google gagal atau data tidak lengkap");
    }
};

const clearUserSession = () => {
    // Hapus semua data dari localStorage
    localStorage.removeItem("token");
    localStorage.removeItem("user");
    localStorage.removeItem("portal");

    // Hapus semua cookies terkait autentikasi
    const cookiesToClear = [
        "laravel_session",
        "XSRF-TOKEN",
        // Tambahkan nama cookie proxy dari config
        "nusahire_proxy_token", // Sesuaikan dengan config('custom.proxy_key')
    ];

    cookiesToClear.forEach((cookieName) => {
        // Hapus cookie dengan berbagai path dan domain
        document.cookie = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
        document.cookie = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=${window.location.hostname};`;
        document.cookie = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.${window.location.hostname};`;
    });

    console.log("User session cleared for invite flow");
};

const loginWithGoogle = async () => {
    try {
        loadingGoogle.value = true;

        // Gunakan invite code dari props
        let apiUrl = `${baseURL}/api/auth/google?join_code=${encodeURIComponent(
            props.inviteCode
        )}`;

        const res = await fetch(apiUrl, {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
            },
        });

        if (res.status == 200) {
            const data = await res.json();
            urlGoogleLogin.value = data.url;
            if (urlGoogleLogin.value) {
                const width = 500;
                const height = 600;

                // Hitung posisi tengah layar
                const left = window.screenX + (window.outerWidth - width) / 2;
                const top = window.screenY + (window.outerHeight - height) / 2;

                // Buka popup window di posisi tengah
                const popup = window.open(
                    urlGoogleLogin.value,
                    "_blank",
                    `width=${width},height=${height},left=${left},top=${top}`
                );

                if (!popup) {
                    alert("Popup blocked! Izinkan popup di browser Anda.");
                } else {
                    popup.focus();
                }
            } else {
                alert("URL login Google tidak ditemukan di respons backend.");
                console.error(
                    "URL login Google tidak ditemukan di respons backend."
                );
            }
        }
    } catch (err) {
        alert("Unexpected error: " + err);
        console.error("Unexpected error:", err);
    } finally {
        loadingGoogle.value = false;
    }
};

const onIframeLoad = () => {
    console.log("Iframe loaded successfully");
    iframeLoaded.value = true;
};

const resetQRState = () => {
    qrCodeUrl.value = "";
    qrExpired.value = "";
    qrStatus.value = "";
    qrMessage.value = "";
    qrProfile.value = null;
    iframeLoaded.value = false;
    isProcessingCallback.value = false;
    if (pollInterval) clearInterval(pollInterval);
    if (timeoutId) clearTimeout(timeoutId);
};
</script>

<style scoped>
/* Tambahin style tambahan kalau perlu */
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

/* Kustom Animasi Ikon */
@keyframes rise-bounce {
    0% {
        transform: translateY(20px) scale(0.8);
    }
    60% {
        transform: translateY(-5px) scale(1.1);
    }
    100% {
        transform: translateY(0) scale(1);
    }
}
.icon-rise-bounce {
    animation: rise-bounce 0.6s cubic-bezier(0.68, -0.55, 0.27, 1.55) forwards;
}

/* Menonaktifkan fitur resize pada textarea */
textarea {
    resize: none;
    transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

/* Efek saat fokus pada textarea */
textarea:focus {
    outline: none;
    border-color: #00852c; /* Warna hijau */
    box-shadow: 0 0 0 2px rgba(0, 133, 44, 0.2); /* Efek bayangan saat fokus */
}

/* Perbaikan untuk border pratinjau gambar */
.flex-wrap > div.relative.w-40.h-32.border {
    border-width: 2px;
    border-style: solid;
    border-color: #e5e7eb; /* Warna abu-abu default */
}

/* Efek hover pada tombol kategori */
.category-buttons > button {
    border-width: 1px;
    border-radius: 4px;
    color: #374151;
    background-color: #fff;
    border-color: #d1d5db;
    font-weight: 500;
    transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
}

.category-buttons > button:not(.active-category):hover {
    background-color: #f3f4f6;
    border-color: #9ca3af;
}

.category-btn.active-category {
    background-color: #00852c !important;
    color: #fff !important;
    border-color: #00852c !important;
}

/* Tombol Batal */
.btn.b-grey-fix {
    background-color: #f3f4f6;
    color: #374151;
    border-radius: 6px;
    padding: 8px 16px;
    font-size: 14px;
    transition: background-color 0.2s ease-in-out;
}
.btn.b-grey-fix:hover {
    background-color: #e5e7eb;
}

/* Tombol Kirim */
.btn.b-new-green {
    background-color: #00852c;
    color: #fff;
    border-radius: 6px;
    padding: 8px 16px;
    font-size: 14px;
    transition: background-color 0.2s ease-in-out;
}
.btn.b-new-green:hover {
    background-color: #006622;
}
.btn.b-new-green:disabled {
    background-color: #a0a0a0;
    cursor: not-allowed;
    opacity: 0.7;
}

/* Efek tombol Ganti Foto */
.group-hover-opacity-100 {
    opacity: 0;
}
.group:hover .group-hover-opacity-100 {
    opacity: 1;
}

/* Styling untuk tombol OK */
.bg-gray-700 {
    background-color: #374151;
    border: none;
}
.hover-bg-gray-800:hover {
    background-color: #1f2937;
}
</style>
