<template>
    <div class="default-container py-4 shadow-navbar w-auto">
        <div class="flex flex-nowrap items-center">
            <div class="mr-8 flex items-center">
                <Link :href="getAdminRoute()" class="text-xl font-extrabold inline-block hover:opacity-80 transition-opacity leading-none">
                    <span class="text-[#00852C]">nusa</span><strong class="text-green-800">hire</strong>
                </Link>
            </div>
            <div class="desktop-only flex flex-nowrap w-[100%]">
                <template v-for="(menu, idx) in menus" :key="idx">
                    <Link
                        :href="menu.route"
                        class="item-menu text-[14px] py-2 mx-1 px-3 hover:font-medium transition-all"
                        :class="{
                            'text-[#000000] font-medium': isActiveRoute(
                                menu.route,
                                menu.key
                            ),
                        }"
                        :style="
                            isActiveRoute(menu.route, menu.key)
                                ? { borderBottom: '3px solid #000000' }
                                : {}
                        "
                    >
                        {{ menu.name }}
                    </Link>
                </template>

                <div class="ml-auto desktop-only flex align-center gap-3">
                    <a
                        :href="companyPreviewUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="bg-white border border-gray-300 text-gray-700 px-4 py-0 h-[32px] rounded-full shadow-sm transition hover:bg-gray-50 text-[13px] inline-flex items-center justify-center"
                    >
                        Preview Company
                    </a>
                    <div class="relative" v-if="availablePortals.length > 1">
                        <button
                            @click="togglePortalDropdown"
                            class="portal-selector-btn flex items-center text-sm gap-2 cursor-pointer rounded-full border-[1.5px] border-[#00852C] text-[#00852C] px-3 py-0 h-[32px] hover:text-[#256539] hover:border-[#256539] transition"
                        >
                            <span class="truncate max-w-32">{{
                                currentPortal?.name || "Pilih Company"
                            }}</span>
                            <svg
                                class="w-5 h-5 transition-transform"
                                :class="{ 'rotate-180': showPortalDropdown }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                ></path>
                            </svg>
                        </button>

                        <div
                            v-if="showPortalDropdown"
                            class="portal-dropdown absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
                        >
                            <div class="py-2">
                                <div
                                    class="px-3 py-2 text-xs font-medium text-gray-500 border-b border-gray-100"
                                >
                                    Switch Company ({{
                                        availablePortals.length
                                    }})
                                </div>
                                <div class="max-h-60 overflow-y-auto">
                                    <button
                                        v-for="portal in availablePortals"
                                        :key="portal.id"
                                        @click="switchPortal(portal)"
                                        class="w-full text-left px-3 py-2 hover:bg-gray-50 transition-colors flex items-center gap-3"
                                        :class="{
                                            'bg-green-50 border-r-2 border-green-500':
                                                currentPortal?.id === portal.id,
                                        }"
                                    >
                                        <div
                                            class="w-8 h-8 rounded-md bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white font-medium text-sm flex-shrink-0"
                                        >
                                            {{
                                                portal.name
                                                    ? portal.name
                                                          .charAt(0)
                                                          .toUpperCase()
                                                    : "P"
                                            }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div
                                                class="font-medium text-sm text-gray-900 truncate"
                                            >
                                                {{ portal.name }}
                                            </div>
                                            <div
                                                class="text-xs text-gray-500 truncate"
                                            >
                                                {{ portal.slug }}
                                            </div>
                                        </div>
                                        <div
                                            v-if="
                                                currentPortal?.id === portal.id
                                            "
                                            class="text-green-600"
                                        >
                                            <svg
                                                class="w-4 h-4"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="ml-4 hamburger"
                @click="toggleMenu"
                :class="{ active: isActiveMenu }"
            >
                <span></span>
                <span></span>
                <span></span>
            </div>

            <div
                class="ml-4 feedback flex align-center cursor-pointer"
                @click="openFeedbackModal"
            >
                <img
                    src="/images/icon-feedback.svg"
                    alt="icon feedback"
                />
            </div>

            <Modal
                :show="isFeedback"
                :style-props="{
                    width: windowWidth < 1080 ? '95%' : '700px',
                    height: 'auto',
                    zIndex: 1000,
                }"
                :useClose="false"
                :useFooter="false"
                @update-show="handleModalClose"
            >
                <template #header> </template>
                <template #body>
                    <div class="wrapper-body-modal px-4 pt-1 pb-1">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">
                                Beri Masukan
                            </h3>
                            <button
                                @click="cancelFeedback"
                                class="p-1 rounded-full hover:bg-gray-100 transition -mr-2"
                            >
                                <img
                                    src="/images/close.svg"
                                    alt="Tutup"
                                    class="w-5 h-5"
                                />
                            </button>
                        </div>
                        <div class="w-full mb-4">
                            <img
                                src="/images/feedback-banner.svg"
                                alt="Every feedback is a gift"
                                class="w-full rounded-lg object-cover"
                            />
                        </div>

                        <div
                            class="mb-4 bg-gray-50 border border-gray-200 rounded-lg p-4"
                        >
                            <div
                                class="flex flex-nowrap gap-2 overflow-x-auto py-2"
                            >
                                <template v-if="screenshots.length">
                                    <div
                                        v-for="(shot, idx) in screenshots"
                                        :key="idx"
                                        class="relative w-40 h-32 rounded-lg flex items-center justify-center overflow-hidden group border border-gray-200"
                                    >
                                        <img
                                            :src="shot"
                                            alt="Screenshot feedback"
                                            class="object-cover w-full h-full rounded-lg"
                                        />

                                        <label
                                            v-if="idx > 0"
                                            class="absolute inset-0 bg-black/50 text-white flex flex-col items-center justify-center gap-2 opacity-0 transition-opacity group-hover:opacity-100 cursor-pointer"
                                        >
                                            <span class="text-sm"
                                                >Ganti Foto</span
                                            >
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
                                            class="absolute top-1 right-1 z-10 p-0 bg-transparent rounded-full"
                                        >
                                            <img
                                                src="/images/close.svg"
                                                alt="Hapus"
                                                class="w-4 h-3"
                                            />
                                        </button>
                                    </div>
                                </template>

                                <label
                                    v-if="screenshots.length < 4"
                                    class="w-40 h-32 border-2 border-dashed border-gray-300 hover:border-green-500 rounded-lg flex items-center justify-center cursor-pointer transition"
                                >
                                    <img
                                        src="/images/icon-add.svg"
                                        alt="Tambah"
                                        class="opacity-70"
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

                        <div
                            class="category-buttons flex gap-2 mb-4 w-full justify-between"
                        >
                            <button
                                class="category-btn border px-3 py-1 rounded transition-colors flex-1"
                                :class="{
                                    'active-category':
                                        selectedCategory === 'Saran',
                                }"
                                @click="selectedCategory = 'Saran'"
                            >
                                Saran
                            </button>
                            <button
                                class="category-btn border px-3 py-1 rounded transition-colors flex-1"
                                :class="{
                                    'active-category':
                                        selectedCategory === 'Pujian',
                                }"
                                @click="selectedCategory = 'Pujian'"
                            >
                                Pujian
                            </button>
                            <button
                                class="category-btn border px-3 py-1 rounded transition-colors flex-1"
                                :class="{
                                    'active-category':
                                        selectedCategory === 'Keluhan',
                                }"
                                @click="selectedCategory = 'Keluhan'"
                            >
                                Keluhan
                            </button>
                        </div>

                        <div class="mb-2">
                            <textarea
                                v-model="feedbackText"
                                placeholder="Tulis masukan di sini"
                                class="w-full border rounded-lg p-3 h-28 text-gray-700 text-sm focus:ring-[#00852C] focus:border-[#00852C]"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-between mt-2">
                            <button
                                class="btn-feedback-history"
                                @click="viewMyFeedback"
                            >
                                <span class="hidden sm:inline">Lihat Feedback Saya</span>
                                <span class="sm:hidden">Lihat Feed...</span>
                                <img
                                    src="/images/history.svg"
                                    alt="Riwayat Feedback"
                                    class="w-6 h-6 ml-2"
                                    style="
                                        filter: invert(30%) sepia(0%)
                                            saturate(0%) hue-rotate(0deg)
                                            brightness(50%) contrast(100%);
                                    "
                                />
                            </button>

                            <div class="flex gap-2">
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
                                        !selectedCategory
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
                    </div>
                </template>
            </Modal>

            <Modal
                :show="errorModal.show"
                :title="errorModal.title"
                :use-close="false"
                :style-props="{ maxWidth: '350px', borderRadius: '12px' }"
            >
                <template #header> </template>
                <template #body>
                    <div
                        class="p-6 text-center flex flex-col items-center gap-4"
                    >
                        <div class="icon-rise-bounce mb-2">
                            <img
                                src="/images/warning.svg"
                                alt="Warning"
                                class="w-16 h-16"
                            />
                        </div>
                        <h2 class="font-bold text-lg text-gray-800">
                            Perhatian
                        </h2>
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
                    <div
                        class="p-6 text-center flex flex-col items-center gap-4"
                    >
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

            <PortalSelection
                :show="showPortalSelectionModal"
                :portals="availablePortals"
                @confirm="handlePortalSelectionConfirm"
                @cancel="handlePortalSelectionCancel"
            />

            <div
                v-if="isLoadingSwitchPortal"
                class="fixed inset-0 z-[9999] flex items-center justify-center"
            >
                <div
                    class="absolute inset-0 bg-black/20 backdrop-blur-sm"
                ></div>

                <div
                    class="loading-overlay relative z-10 bg-white rounded-xl shadow-2xl p-8 mx-4 max-w-sm w-full"
                >
                    <div class="text-center">
                        <div
                            class="inline-flex items-center justify-center w-16 h-16 mb-4"
                        >
                            <div
                                class="spinner-enhanced animate-spin rounded-full h-16 w-16 border-4 border-gray-200 border-t-green-600"
                            ></div>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            Beralih Company
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Sedang menyiapkan data company baru...
                        </p>

                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div
                                class="progress-bar-animated bg-green-600 h-2 rounded-full"
                                style="width: 70%"
                            ></div>
                        </div>

                        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-center gap-2">
                                <div
                                    class="w-6 h-6 rounded bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white font-medium text-xs"
                                >
                                    {{
                                        currentPortal?.name
                                            ? currentPortal.name
                                                  .charAt(0)
                                                  .toUpperCase()
                                            : "P"
                                    }}
                                </div>
                                <span
                                    class="text-sm font-medium text-gray-700"
                                    >{{ currentPortal?.name }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mobile-menu" :class="{ show: isActiveMenu }">
            <template v-for="(menu, idx) in menus" :key="idx">
                <Link
                    :href="menu.route"
                    class="item-menu text-[14px] py-2 mx-1 px-3 hover:font-medium transition-all"
                    :class="{
                        'text-[#000000] font-medium': isActiveRoute(
                            menu.route,
                            menu.key
                        ),
                    }"
                    @click="isActiveMenu = false"
                >
                    <div>
                        {{ menu.name }}
                    </div>
                </Link>
            </template>

            <a
                :href="companyPreviewUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="item-menu text-[14px] py-2 mx-1 px-3 hover:font-medium transition-all block"
                @click="isActiveMenu = false"
            >
                <div>Preview Company</div>
            </a>

            <div class="border-t border-gray-200 mt-4 pt-4">
                <div v-if="availablePortals.length > 1" class="mb-3">
                    <div
                        class="px-3 py-2 text-xs font-medium text-gray-500 uppercase tracking-wide"
                    >
                        Switch Company
                    </div>
                    <div class="space-y-1">
                        <button
                            v-for="portal in availablePortals"
                            :key="portal.id"
                            @click="switchPortalMobile(portal)"
                            class="w-full text-left px-3 py-3 hover:bg-gray-50 transition-colors flex items-center gap-3 rounded-md mx-1"
                            :class="{
                                'bg-green-50 border border-green-200':
                                    currentPortal?.id === portal.id,
                            }"
                        >
                            <div
                                class="w-8 h-8 rounded-md bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white font-medium text-sm flex-shrink-0"
                            >
                                {{
                                    portal.name
                                        ? portal.name.charAt(0).toUpperCase()
                                        : "P"
                                }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div
                                    class="font-medium text-sm text-gray-900 truncate"
                                >
                                    {{ portal.name }}
                                </div>
                                <div class="text-xs text-gray-500 truncate">
                                    {{ portal.slug }}
                                </div>
                            </div>
                            <div
                                v-if="currentPortal?.id === portal.id"
                                class="text-green-600"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { toJpeg } from "html-to-image";
import { Link, usePage, router } from "@inertiajs/vue3";
import { getAdminRoute, getTenantName } from "@/utils/url";
import { useMainStore } from "@/stores/index";
import Modal from "./modal.vue";
import PortalSelection from "./PortalSelection.vue";
import axios from "axios";

// --- Konstanta Ukuran File (Sesuai dengan Backend: 2MB) ---
const MAX_FILE_SIZE_MB = 2;
const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024;

// --- Fungsi utilitas ---
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

// Function to generate a unique filename
const generateUniqueFilename = (originalName) => {
    const now = new Date();
    const timestamp = now.getTime();

    // Gunakan crypto.getRandomValues() untuk random number yang lebih aman
    const randomArray = new Uint32Array(1);
    crypto.getRandomValues(randomArray);
    const randomNumber = randomArray[0] % 100000; // Batasi ke 5 digit

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

const page = usePage();
const windowWidth = ref(window.innerWidth);
const mainStore = useMainStore();

// Portal Selection
const availablePortals = ref([]);
const currentPortal = ref(null);
const showPortalDropdown = ref(false);
const showPortalSelectionModal = ref(false);
const isLoadingSwitchPortal = ref(false);

const companyPreviewUrl = computed(() => {
    const portalSlug = currentPortal.value?.slug || getTenantName();
    return `/${portalSlug}`;
});

// Helper function to get admin route for current portal
const getAdminRouteForPortal = (path = "") => {
    const base = import.meta.env.VITE_PATH_ADMIN ?? "admin";
    const portalSlug = currentPortal.value?.slug || getTenantName();
    return `/${portalSlug}/${base}${path}`;
};

// Menu - computed to be reactive to portal changes
const menus = computed(() => [
    {
        route: getAdminRouteForPortal(""),
        key: "dashboard",
        name: "Dashboard",
    },
    {
        route: getAdminRouteForPortal("/settings"),
        key: "settings",
        name: "Pengaturan",
    },
]);

const isActiveRoute = (path, key) => {
    const currentPath = page.url;
    const parentMenuKey = page.props.value?.meta?.parent_menu;
    return currentPath === path || parentMenuKey === key;
};

// Hamburger
const isActiveMenu = ref(false);
const toggleMenu = () => {
    isActiveMenu.value = !isActiveMenu.value;
};

// Feedback modal
const isFeedback = ref(false);
const isSubmitting = ref(false);
const screenshots = ref([]);
const fileImage = ref([]);
const currentUrl = ref("");
const selectedCategory = ref("");
const feedbackText = ref("");

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

// Portal Management Functions
const loadAvailablePortals = async () => {
    try {
        const res = await mainStore.getAllPortal();
        if (res.status === 200 && res.data.length > 0) {
            availablePortals.value = res.data;

            // Set current portal from localStorage or current URL
            const selectedPortal = JSON.parse(
                localStorage.getItem("selected_portal") || "null"
            );
            const currentSlug = getTenantName();

            if (selectedPortal) {
                currentPortal.value =
                    availablePortals.value.find(
                        (p) => p.id === selectedPortal.id
                    ) || availablePortals.value[0];
            } else {
                // Try to find portal by current URL slug
                const portalBySlug = availablePortals.value.find(
                    (p) => p.slug === currentSlug
                );
                currentPortal.value = portalBySlug || availablePortals.value[0];

                // Update localStorage with current portal
                if (currentPortal.value) {
                    localStorage.setItem(
                        "selected_portal",
                        JSON.stringify(currentPortal.value)
                    );
                }
            }
        }
    } catch (error) {
        console.error("Error loading available portals:", error);
    }
};

const togglePortalDropdown = () => {
    showPortalDropdown.value = !showPortalDropdown.value;
};

const switchPortal = async (portal) => {
    if (currentPortal.value?.id === portal.id) {
        showPortalDropdown.value = false;
        return;
    }

    try {
        // Show loading overlay
        isLoadingSwitchPortal.value = true;

        // Close dropdown immediately
        showPortalDropdown.value = false;

        // Small delay to show loading effect (reduced from 300ms to 150ms)
        await new Promise((resolve) => setTimeout(resolve, 150));

        // Update localStorage
        localStorage.setItem("selected_portal", JSON.stringify(portal));

        // Update current portal
        currentPortal.value = portal;

        // Update store state
        await mainStore.getPortal(true);

        // Hide loading before redirect
        isLoadingSwitchPortal.value = false;

        // Redirect to new portal immediately
        const pathAdmin = import.meta.env.VITE_PATH_ADMIN ?? "admin";
        router.visit(`/${portal.slug}/${pathAdmin}`);
    } catch (error) {
        console.error("Error switching company:", error);
        isLoadingSwitchPortal.value = false;
        errorModal.value.message = "Gagal beralih company. Silakan coba lagi.";
        errorModal.value.show = true;
    }
};

const switchPortalMobile = async (portal) => {
    // Close mobile menu first
    isActiveMenu.value = false;

    // Use the same logic as desktop
    await switchPortal(portal);
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    const dropdown = event.target.closest(".relative");
    if (!dropdown && showPortalDropdown.value) {
        showPortalDropdown.value = false;
    }
};

// Portal Selection Modal Handlers
const handlePortalSelectionConfirm = async (selectedPortal) => {
    await switchPortal(selectedPortal);
    showPortalSelectionModal.value = false;
};

const handlePortalSelectionCancel = () => {
    showPortalSelectionModal.value = false;
};

// Initialize on mount
onMounted(() => {
    loadAvailablePortals();
    document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
});

const openFeedbackModal = async () => {
    let originalBackgroundColor = "";

    try {
        currentUrl.value =
            window.location.origin +
            window.location.pathname +
            window.location.search +
            window.location.hash;

        // Ambil elemen yang ingin di-screenshot
        const mainContentElement =
            document.getElementById("main-content") || document.body;

        // Simpan gaya asli dan paksa background menjadi putih
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
        // Kembalikan gaya background ke semula, terlepas dari hasilnya
        const mainContentElement =
            document.getElementById("main-content") || document.body;
        mainContentElement.style.backgroundColor = originalBackgroundColor;
    }

    // Tampilkan modal setelah screenshot diambil
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

        fileImage.value.forEach((file, index) => {
            formData.append(`screenshots[${index}]`, file, file.name);
        });

        const res = localStorage.getItem("token");
        const headers = {
            Authorization: `Bearer ${res}`,
        };
        const portals = JSON.parse(localStorage.getItem("portal") || "[]");
        const tenantId = portals.length > 0 ? portals[0].id : null;

        if (!tenantId) {
            errorModal.value.message = "Tenant ID tidak ditemukan.";
            errorModal.value.show = true;
            return;
        }

        const response = await axios.post(
            `/${tenantId}/api/feedback`,
            formData,
            { headers }
        );

        if (response.status === 200) {
            isFeedback.value = false;
            successModal.value.message =
                "Terima kasih, masukan Anda sudah kami terima!";
            successModal.value.show = true;
            resetFeedbackForm(); // Menggunakan fungsi reset baru
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

// KODE BARU: Fungsi untuk mereset dan membatalkan
const resetFeedbackForm = () => {
    screenshots.value = [];
    fileImage.value = [];
    feedbackText.value = "";
    selectedCategory.value = "";
};

// KOREKSI UTAMA: Fungsi yang menutup modal dan mereset state.
const cancelFeedback = () => {
    resetFeedbackForm();
    isFeedback.value = false;
};

// KOREKSI UTAMA: Handler penutupan modal (ketika klik 'X' atau backdrop)
const handleModalClose = (value) => {
    isFeedback.value = value;
    if (!value) {
        // Jika Modal ditutup, panggil reset
        resetFeedbackForm();
    }
};

const viewMyFeedback = () => {
    const tenantSlug = getTenantName();
    const adminPath = import.meta.env.VITE_PATH_ADMIN ?? "admin";
    const url = `/${tenantSlug}/${adminPath}/settings`;

    router.visit(url, {
        data: { nav: "my-feedback" },
        onSuccess: () => {
            isFeedback.value = false;
        },
    });
};
</script>

<style scoped>
/* Your existing styles */
.wrapper-body-modal h1 {
    margin: 0;
}
.shadow-navbar {
    box-shadow: 0px 2px 8px 2px #00000014;
    -webkit-box-shadow: 0px 2px 8px 2px #00000014;
    -moz-box-shadow: 0px 2px 8px 2px #00000014;
}
.desktop-only {
    display: flex;
}
.hamburger {
    width: 25px;
    height: 18px;
    margin-top: 12px;
    margin-left: 18px;
    display: none;
    flex-direction: column;
    justify-content: space-between;
    cursor: pointer;
}
.hamburger span {
    display: block;
    height: 3px;
    background: #888888;
    border-radius: 3px;
    transition: 0.3s ease;
}
.hamburger.active span:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}
.hamburger.active span:nth-child(2) {
    opacity: 0;
}
.hamburger.active span:nth-child(3) {
    transform: rotate(-45deg) translate(6px, -6px);
}
.mobile-menu {
    display: block;
    position: absolute;
    left: -100%;
    top: 9%;
    width: 100%;
    height: 100dvh;
    background: #fff;
    z-index: 99;
    padding: 20px;
    transition: 0.3s ease;
}
.mobile-menu.show {
    left: 0;
}
@media (max-width: 991px) {
    .desktop-only {
        display: none;
    }
    .feedback {
        margin-left: auto;
    }
    .hamburger {
        display: flex;
    }

    .portal-dropdown {
        width: 280px;
        right: -20px;
    }

    /* Mobile portal actions styling */
    .mobile-menu {
        max-height: 100vh;
        overflow-y: auto;
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

.btn-feedback-history {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px; /* Menggunakan padding yang sama dengan Batal/Kirim */
    background-color: #f3f3f3;
    color: #374151;
    border-radius: 6px; /* Menyeragamkan radius dengan Batal/Kirim */
    font-size: 14px; /* MENYAMAKAN UKURAN FONT */
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.btn-feedback-history:hover {
    background-color: #e5e5e5;
}

.btn-feedback-history img {
    /* Menyesuaikan ukuran ikon agar tetap proporsional dengan font 14px */
    width: 18px;
    height: 18px;
    margin-left: 6px;
    /* Tambahan filter lainnya tetap dipertahankan dari template */
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

/* Portal Dropdown Styles */
.rotate-180 {
    transform: rotate(180deg);
}

/* Dropdown animation */
.portal-dropdown-enter-active,
.portal-dropdown-leave-active {
    transition: all 0.2s ease;
}

.portal-dropdown-enter-from,
.portal-dropdown-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* Custom scrollbar for portal dropdown */
.max-h-60::-webkit-scrollbar {
    width: 4px;
}

.max-h-60::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 2px;
}

.max-h-60::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.max-h-60::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Portal button hover effects */
.portal-selector-btn {
    transition: all 0.2s ease;
}

.portal-selector-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 133, 44, 0.15);
}

/* Loading Overlay Styles */
.backdrop-blur-sm {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

/* Loading Animation */
@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.loading-overlay {
    animation: fadeInScale 0.3s ease-out;
}

/* Progress Bar Animation */
@keyframes progressPulse {
    0%,
    100% {
        width: 70%;
    }
    50% {
        width: 85%;
    }
}

.progress-bar-animated {
    animation: progressPulse 2s ease-in-out infinite;
}

/* Spinner Animation Enhancement */
.spinner-enhanced {
    animation: spin 1s linear infinite, pulse 2s ease-in-out infinite alternate;
}

@keyframes pulse {
    from {
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
    }
    to {
        box-shadow: 0 0 0 10px rgba(34, 197, 94, 0);
    }
}

/* Media Query untuk responsifitas */
@media (max-width: 991px) {
    .portal-dropdown {
        width: 280px;
        right: -20px;
    }
}

@media (max-width: 480px) {
    .portal-dropdown {
        width: 90vw;
        right: -50px;
    }

    .max-w-32 {
        max-width: 6rem;
    }

    .btn-feedback-history {
        /* Memastikan padding/font tetap 14px/8px di mobile */
        padding: 8px 12px;
        font-size: 14px;
    }

    .btn-feedback-history img {
        width: 16px;
        height: 16px;
    }

    /* Tombol Batal/Kirim juga kompak (seperti sebelumnya) */
    .btn.b-grey-fix,
    .btn.b-new-green {
        padding: 8px 12px;
        font-size: 14px; /* Disamakan ke 14px di mobile */
    }
}
</style>
