<template>
    <div>
        <div>
            <template v-if="loadingTenant">
                <div class="skeleton h-8 w-[20%] mt-3"></div>
                <div class="skeleton h-4 w-[40%] mt-3"></div>

                <div class="mt-6">
                    <div class="skeleton h-8 w-[30%] mt-3"></div>
                </div>

                <div class="relative">
                    <div class="mt-4">
                        <div class="skeleton h-50 w-[100%] mt-3"></div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="w-[100px] h-[100px] mt-5 relative ml-3 border border-[#E2E2E2] skeleton relative wrapper-photo rounded-sm mb-6"
                        ></div>
                    </div>
                </div>

                <div class="skeleton h-4 w-[15%] mt-6"></div>
                <div class="skeleton h-8 w-[30%] mt-3"></div>

                <div class="skeleton h-4 w-[15%] mt-6"></div>
                <div class="skeleton h-8 w-[30%] mt-3"></div>

                <div class="skeleton h-4 w-[15%] mt-6"></div>
                <div class="mt-4">
                    <div class="skeleton h-30 w-[100%] mt-3"></div>
                </div>
            </template>

            <template v-else>
                <error-message
                    v-if="isError"
                    :error-data="errorData"
                    @getData="getPortal()"
                ></error-message>

                <div v-else>
                    <div class="flex flex-wrap">
                        <h1
                            class="text-[20px] text-[#191C1D] font-[700] flex items-center mt-[10px] mb-4 sm:mt-0 sm:mb-0"
                            :class="{ 'ml-6': collapsed }"
                        >
                            Pengaturan Company
                        </h1>

                        <div class="flex flex-nowrap ml-auto items-center gap-3">
                            <button
                                class="bg-[#3A3A3A] text-white px-6 py-2 rounded-md shadow-sm transition hover:bg-gray-800 text-[14px] inline-flex items-center justify-center"
                                @click="doSave()"
                            >
                                <span class="text-[14px] flex flex-nowrap items-center">
                                    Simpan Perubahan
                                    <span
                                        v-if="loadingSave"
                                        class="ml-1 flex items-center"
                                    >
                                        <img
                                            src="/images/loading.gif"
                                            alt="loading"
                                            width="18"
                                        />
                                    </span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <div
                        class="bg-[#C8C8C8] h-[1px] w-[100%] d-block mt-4"
                    ></div>

                    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4 mt-4">
                        <div class="text-[16px] font-semibold text-gray-900 mb-1">
                            Alamat Company
                        </div>
                        <div class="text-[14px] text-gray-600 mb-3">
                            Alamat ini digunakan sebagai link company Anda.
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="text-[14px] text-gray-700 break-all">
                                {{ baseUrl }}/{{ company.slug || '-' }}
                            </div>
                            <button
                                class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-md shadow-sm transition hover:bg-gray-50 text-[14px] sm:ml-4 shrink-0"
                                @click="openSlugModal()"
                            >
                                Ubah Alamat
                            </button>
                        </div>

                        <!--
                            Catatan:
                            Toggle "Aktifkan pengalihan alamat lama" sementara disembunyikan di Frontend.

                            Jika ingin mengaktifkan kembali di kemudian hari:
                            1) Uncomment blok UI di bawah.
                            2) Kembalikan pengiriman field enable_slug_history_redirect di method doSave().
                        -->
                        <!--
                        <div class="mt-3 flex items-start gap-2">
                            <input
                                id="enable-slug-history-redirect"
                                v-model="company.enable_slug_history_redirect"
                                type="checkbox"
                                class="mt-1 h-4 w-4 rounded border border-gray-300"
                            />
                            <label
                                for="enable-slug-history-redirect"
                                class="text-[13px] text-gray-600"
                            >
                                Aktifkan pengalihan alamat lama (alamat sebelumnya masih bisa diakses dan akan diarahkan otomatis ke alamat baru).
                            </label>
                        </div>
                        -->
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
                        <div class="text-[16px] font-semibold text-gray-900 mb-1">
                            Informasi Company
                        </div>
                        <div class="text-[14px] text-gray-600 mb-3">
                            Perbarui informasi dan tampilan yang muncul di company.
                        </div>

                    <div class="flex items-center space-x-4 mt-3">
                        <span class="text-[#000000] text-[14px] text-[500]"
                            >Warna Tema</span
                        >
                        <div v-for="color in colors" :key="color.name">
                            <label
                                class="w-8 h-8 rounded-full flex items-center justify-center cursor-pointer"
                                :class="[
                                    color.value,
                                    company.theme === color.name
                                        ? 'ring-2 ring-white ring-offset-2'
                                        : '',
                                ]"
                            >
                                <input
                                    type="radio"
                                    class="hidden"
                                    :value="color.name"
                                    v-model="company.theme"
                                />
                                <svg
                                    v-if="company.theme === color.name"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-white"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </label>
                        </div>
                    </div>

                    <!-- Profile Pic & Background -->
                    <div
                        class="bg-[#FBFBFB] aspect-[4/1] w-full block mt-5 relative"
                    >
                        <div
                            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 cursor-pointer"
                        >
                            <label
                                @click="selectedUpload = 'header'"
                                for="uploader"
                                class="rounded-sm bg-[#F6F6F6] px-4 py-2 text-[#626262] text-[10px] flex flex-nowrap hover:bg-[#D9D9D9] transition-all"
                                :class="{
                                    'opacity-25 hover:opacity-100 cursor-pointer':
                                        headerBackground,
                                }"
                            >
                                {{
                                    headerBackground
                                        ? "Ganti Header"
                                        : "Tambah Header"
                                }}
                                <img
                                    class="pl-2"
                                    src="/images/plus-border.svg"
                                    alt="icon plus with border"
                                />
                            </label>
                        </div>

                        <div
                            v-if="headerBackground"
                            :style="`background-image: url(${headerBackground})`"
                            class="background-header"
                        ></div>
                    </div>

                    <div
                        class="w-[100px] h-[100px] mt-5 relative ml-3 border border-[#E2E2E2] bg-[#FBFBFB] relative wrapper-photo rounded-sm mb-6"
                    >
                        <label
                            @click="selectedUpload = 'logo'"
                            for="uploader"
                            class="rounded-sm absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[35px] h-[35px] bg-[#F6F6F6] flex items-center justify-center hover:bg-[#D9D9D9] transition-all cursor-pointer group-hover:block"
                            :class="{
                                'opacity-25 hover:opacity-100 cursor-pointer':
                                    pictureLogo,
                            }"
                        >
                            <img src="/images/camera.svg" alt="camera" />
                        </label>

                        <label
                            v-if="pictureLogo"
                            @click="selectedUpload = 'logo'"
                            for="uploader"
                        >
                            <img
                                :src="pictureLogo"
                                class="logo-company"
                                alt=" profile"
                            />
                        </label>
                    </div>
                    <!-- End -->

                    <!-- Form -->
                    <div class="form-group">
                        <div class="text-[#000000] text-[14px] text-[500] mb-1">
                            Nama Perusahaan
                        </div>
                        <Input
                            v-model="company.name"
                            :type="'text'"
                            :classWidth="'w-[100%] sm:w-[250px]'"
                            :placeholder="'Masukkan nama perusahaan anda'"
                        />
                    </div>

                    <div class="flex flex-wrap">
                        <div class="form-group">
                            <div
                                class="text-[#000000] text-[14px] text-[500] mb-1"
                            >
                                Jumlah Karyawan
                            </div>
                            <v-select
                                v-model="company.margin_employee"
                                class="w-[250px] text-[14px] rounded-md text-[#4A4A4A] focus:outline-none focus:ring-2 focus:ring-blue-500"
                                label="name"
                                :options="listRangeEmployee"
                                :placeholder="'Pilih jumlah karyawan'"
                                :value="listRangeEmployee.id"
                                :reduce="(e) => e.id"
                            ></v-select>
                        </div>

                        <div class="form-group ms-0 sm:ms-4">
                            <div
                                class="text-[#000000] text-[14px] text-[500] mb-1"
                            >
                                Kategori Perusahaan
                            </div>
                            <div
                                class="skeleton h-9 w-[250px]"
                                v-if="loadingCategory"
                            ></div>
                            <v-select
                                v-else
                                v-model="company.category"
                                class="w-[250px] text-[14px] rounded-md text-[#4A4A4A] focus:outline-none focus:ring-2 focus:ring-blue-500"
                                label="name"
                                :options="listCategory"
                                :placeholder="'Pilih Kategori Perusahaan'"
                                :value="listCategory.id"
                                :reduce="(e) => e.id"
                            >
                                <template #list-header>
                                    <li
                                        style="
                                            text-align: end;
                                            margin-top: 7px;
                                            color: #12852d;
                                            font-style: italic;
                                        "
                                    >
                                        <span
                                            class="cursor-pointer font-[600] pe-3 fs-12"
                                            @click="getCategory()"
                                        >
                                            {{ "Reload" }}
                                        </span>
                                    </li>
                                </template>
                            </v-select>
                        </div>
                    </div>

                    <div class="flex flex-wrap">
                        <div class="form-group">
                            <div class="text-[#000000] text-[14px] text-[500] mb-1">
                                LinkedIn
                            </div>
                            <Input
                                v-model="company.linkedin"
                                :type="'text'"
                                :classWidth="'w-[100%] sm:w-[250px]'"
                                :placeholder="'Masukkan URL LinkedIn perusahaan'"
                            />
                        </div>

                        <div class="form-group ms-0 sm:ms-4">
                            <div class="text-[#000000] text-[14px] text-[500] mb-1">
                                Instagram
                            </div>
                            <Input
                                v-model="company.instagram"
                                :type="'text'"
                                :classWidth="'w-[100%] sm:w-[250px]'"
                                :placeholder="'Masukkan username/URL Instagram'"
                            />
                        </div>

                        <div class="form-group ms-0 sm:ms-4">
                            <div class="text-[#000000] text-[14px] text-[500] mb-1">
                                Website Perusahaan
                            </div>
                            <Input
                                v-model="company.website"
                                :type="'text'"
                                :classWidth="'w-[100%] sm:w-[250px]'"
                                :placeholder="'Masukkan URL website perusahaan'"
                            />
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="text-[#000000] text-[14px] text-[500] mb-1">
                            Tentang Kami
                        </div>
                        <TinyEditor
                            :data="company.company_values"
                            @update-content="
                                (newValue) =>
                                    (company.company_values = newValue)
                            "
                        ></TinyEditor>
                    </div>

                    </div>
                    <!-- End -->
                
                <Modal :show="showCropper" :useFooter="true" @update-show="showCropper = $event">
                    <template #body>
                        <div class="flex justify-content-center">
                            <cropper
                                class="cropper"
                                ref="imageCropper"
                                :src="customizeImage"
                                :stencil-props="{
                                    aspectRatio:
                                        selectedUpload == 'header' ? 4 / 1 : 1,
                                }"
                                :resize-image="{
                                    adjustStencil: true,
                                }"
                                :auto-zoom="true"
                                @change="changeImage"
                            />
                        </div>

                        <div class="mt-3 d-flex justify-content-center">
                            <div @click="flip(-90, 0)" class="mx-3 pointer">
                                <i class="bi bi-align-middle"></i>
                            </div>
                            <div @click="flip(0, -90)" class="mx-3 pointer">
                                <i class="bi bi-align-center"></i>
                            </div>
                            <div @click="rotate(-90)" class="mx-3 pointer">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </div>
                            <div @click="rotate(90)" class="mx-3 pointer">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </div>
                        </div>
                    </template>

                    <template #footer>
                        <div class="flex justify-content-end">
                            <button
                                class="mr-3 cursor-pointer flex items-center gap-2 bg-[#F6F6F6] text-[#3A3A3A] text-[14px] px-4 py-2 rounded-md transition"
                                @click="showCropper = false"
                            >
                                {{ "Cancel" }}
                            </button>
                            <button
                                class="cursor-pointer flex items-center gap-2 bg-[#3A3A3A] text-[#fff] text-[14px] px-4 py-2 rounded-md transition"
                                @click="finishFile()"
                            >
                                {{ "Save" }}
                            </button>
                        </div>
                    </template>
                </Modal>

                <confirmation-modal
                    :show="showConfirmSaveSlug"
                    @close="showConfirmSaveSlug = false"
                    @confirm="confirmSaveSlug"
                    title="Ubah Alamat Company?"
                    message="Pastikan alamat company baru sudah benar. Setelah diubah, alamat company lama tidak akan bisa digunakan lagi."
                    confirmText="Ya, Ubah"
                    cancelText="Batal"
                    :z-index="1002"
                />

                <Modal
                    :show="showSlugModal"
                    :useFooter="true"
                    title="Ubah Alamat Company"
                    :styleProps="{ width: '90vw', maxWidth: '900px' }"
                    @update-show="showSlugModal = $event"
                >
                    <template #body>
                        <div class="text-[13px] text-gray-700">
                            <div class="text-[14px] font-semibold text-gray-900 mb-2">
                                Informasi
                            </div>
                            <div class="mb-3">
                                URL company saat ini:
                                <span class="font-semibold">{{ baseUrl }}/{{ company.slug || '-' }}</span>
                            </div>

                            <div class="mb-3">
                                <div class="font-semibold text-gray-900 mb-1">
                                    Kriteria alamat
                                </div>
                                <ul class="list-disc pl-5">
                                    <li>Gunakan huruf kecil (a-z), angka (0-9), dan tanda hubung (-)</li>
                                    <li>Tidak boleh ada spasi</li>
                                    <li>Tidak boleh diawali/diakhiri tanda hubung (-)</li>
                                    <li>Tidak boleh menggunakan: api, admin, auth, setup, session, dev</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <div class="font-semibold text-gray-900 mb-1">
                                    Aturan perubahan
                                </div>
                                <div v-if="isSlugChangeLocked" class="text-[13px] text-red-600">
                                    Alamat company belum bisa diubah. Anda dapat mengubah lagi pada:
                                    <span class="font-semibold">{{ nextSlugChangeAtText }}</span>
                                </div>
                                <div v-else class="text-[13px] text-gray-700">
                                    Jika Anda mengubah alamat company hari ini, Anda dapat mengubah lagi pada:
                                    <span class="font-semibold">{{ nextSlugChangeIfUpdateNowText }}</span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="text-[#000000] text-[14px] text-[500] mb-1">
                                    Alamat Baru
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full">
                                    <div class="text-[14px] text-gray-700 min-w-0 sm:flex-none sm:max-w-[60%] truncate">
                                        {{ baseUrl }}/
                                    </div>
                                    <input
                                        v-model="slugDraft"
                                        type="text"
                                        class="text-[14px] border border-[#BABABA] rounded-md px-3 py-2 text-[#4A4A4A] focus:outline-none bg-white w-full sm:flex-1 sm:min-w-[240px]"
                                        placeholder="media-antar-nusa"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #footer>
                        <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
                            <button
                                class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-md shadow-sm transition hover:bg-gray-50 text-[14px]"
                                @click="showSlugModal = false"
                            >
                                Batal
                            </button>
                            <button
                                :disabled="loadingSaveSlug || isSlugChangeLocked || !isSlugDirty"
                                class="bg-[#3A3A3A] text-white px-6 py-2 rounded-md shadow-sm transition hover:bg-gray-800 text-[14px]"
                                @click="openConfirmSaveSlug()"
                            >
                                Simpan
                                <span v-if="loadingSaveSlug" class="ml-1 d-flex align-center">
                                    <img
                                        src="/images/loading.gif"
                                        alt="loading"
                                        width="18"
                                    />
                                </span>
                            </button>
                        </div>
                    </template>
                </Modal>

                <div class="file-upload" style="display: none">
                    <input
                        id="uploader"
                        type="file"
                        accept="image/*"
                        @change="onFileChange"
                    />
                </div>
                </div>
        </template>
    </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, reactive } from "vue";
import TinyEditor from "../../components/tiny-editor.vue";
import Modal from "../../components/modal.vue";
import ConfirmationModal from "../../components/confirmation-modal.vue";
import { useMainStore } from "../../stores";
import { router } from "@inertiajs/vue3";

const mainStore = useMainStore();
const baseUrl = window.location.origin;
const portalSlugCooldownDays = ref(60);
const isAppConfigLoaded = ref(false);
const loadingAppConfig = ref(false);
const props = defineProps({
    asComponent: {
        type: Boolean,
        default: false,
    },
    collapsed: {
        type: Boolean,
        default: false,
    },
});

let colors = [
        { name: "#336AFF", value: "bg-[#336AFF]" },
        { name: "#00852C", value: "bg-[#00852C]" },
        { name: "#FFA800", value: "bg-[#FFA800]" },
        { name: "#CD2026", value: "bg-[#CD2026]" },
        { name: "#D9D9D9", value: "bg-[#D9D9D9]" },
    ],
    rawImage = ref(null),
    picture = ref(null),
    company = ref({
        id: null,
        theme: "#336AFF",
        name: "",
        slug: "",
        slug_changed_at: null,
        enable_slug_history_redirect: false,
        code: "",
        margin_employee: 3,
        category: 1,
        company_values: "",
        header_bg: null,
        logo: null,
        linkedin: "",
        instagram: "",
        website: "",
    }),
    listRangeEmployee = ref([
        {
            id: 1,
            name: "1 - 10",
        },
        {
            id: 2,
            name: "11 - 50",
        },
        {
            id: 3,
            name: "51 - 100",
        },
        {
            id: 4,
            name: "101 - 200",
        },
        {
            id: 5,
            name: "201 - 500",
        },
        {
            id: 6,
            name: "501 - 1000",
        },
        {
            id: 7,
            name: "1001 - 2000",
        },
        {
            id: 8,
            name: "2001 - 5000",
        },
        {
            id: 9,
            name: "5000+",
        },
    ]),
    listCategory = ref([]),
    showCropper = ref(false),
    customizeImage = ref(""),
    imageCropper = ref(null),
    selectedUpload = ref(""),
    image = ref(""),
    headerBackground = ref(null),
    pictureLogo = ref(null),
    loadingTenant = ref(true),
    isError = ref(false),
    errorData = reactive({
        code: null,
        message: "",
    }),
    loadingSave = ref(false),
    loadingSaveSlug = ref(false),
    showSlugModal = ref(false),
    showConfirmSaveSlug = ref(false),
    slugDraft = ref(""),
    loadingCategory = ref(true),
    isErrorCategory = ref(false),
    errorCategory = reactive({
        code: null,
        message: "",
    });

// Watchers
onMounted(() => {
    getPortal(true);
    getCategory();
    void getAppConfig();
});

// Methods
const showPopup = (state) => {
    mainStore.stateShowInfo = state;
};

const getAppConfig = async () => {
    try {
        if (loadingAppConfig.value || isAppConfigLoaded.value) return;
        loadingAppConfig.value = true;

        const res = await mainStore.useAPI(
            "api/app-config",
            {
                method: "GET",
                cache: "no-cache",
                key: "app-config",
            },
            true
        );

        const days = Number(res?.data?.portal_slug_change_cooldown_days);
        if (!Number.isNaN(days) && days >= 0) {
            portalSlugCooldownDays.value = Math.floor(days);
        }
        isAppConfigLoaded.value = true;
    } catch (err) {
        showPopup({
            status: true,
            title: err.status >= 500 ? "Error" : "Warning",
            type: err.status >= 500 ? "error" : "warning",
            message: err.response?.message || `${err.message}`,
        });
    } finally {
        loadingAppConfig.value = false;
    }
};

const openConfirmSaveSlug = () => {
    if (!isSlugDirty.value) return;
    showConfirmSaveSlug.value = true;
};

const confirmSaveSlug = async () => {
    showConfirmSaveSlug.value = false;
    await doSaveSlug();
};

const getPortal = async (reload = false) => {
    try {
        loadingTenant.value = true;
        isError.value = false;
        errorData.code = null;
        errorData.message = "";
        const res = await mainStore.getPortal(reload);
        if (res.status == 200) {
            const result = res.data[0] || null;
            if (result) {
                company.value.id = result.id;
                company.value.theme = result.theme_color;
                company.value.name = result.name;
                company.value.slug = result.slug || "";
                company.value.slug_changed_at = result.slug_changed_at || null;
                company.value.enable_slug_history_redirect =
                    !!result.enable_slug_history_redirect;
                company.value.code = result.code;
                company.value.category = result.company_category_id || "";
                company.value.company_values = result.company_values;
                company.value.header_bg = result.header_image;
                company.value.logo = result.profile_image;
                company.value.linkedin = result.linkedin || "";
                company.value.instagram = result.instagram || "";
                company.value.website = result.website || "";

                headerBackground.value = result.header_image;
                pictureLogo.value = result.profile_image;

                if (result.employee_range_start === 5000) {
                    company.value.margin_employee = 9;
                } else {
                    let margin = `${result.employee_range_start} - ${result.employee_range_end}`;
                    let mapMargin = listRangeEmployee.value.find(
                        (item) => item.name === margin
                    );
                    company.value.margin_employee = mapMargin
                        ? mapMargin.id
                        : null;
                }
            }
            loadingTenant.value = false;
        }
    } catch (err) {
        console.log("error??", err);
        loadingTenant.value = false;
        isError.value = true;
        errorData.code = err.response?.status || err.status;
        errorData.message = err.response?.data?.message || err.message;
    }
};

const getCategory = async () => {
    try {
        loadingCategory.value = true;
        isErrorCategory.value = false;
        errorCategory.code = null;
        errorCategory.message = "";
        const res = await mainStore.getCategory();
        if (res.status == 200) {
            loadingCategory.value = false;
            listCategory.value = res.data;
        }
    } catch (err) {
        console.log("error??", err);
        loadingCategory.value = false;
        isErrorCategory.value = true;
        errorCategory.code = err.response?.status || err.status;
        errorCategory.message = err.response?.data?.message || err.message;
    }
};

const onFileChange = async (files) => {
    const file = event.target.files?.[0];
    if (!file) return;
    
    // Validasi: pastikan file adalah File atau Blob
    if (!(file instanceof File) && !(file instanceof Blob)) {
        showPopup({
            status: true,
            type: "warning",
            title: "Warning",
            message: ["File tidak valid. Silakan pilih file yang benar."],
        });
        return;
    }

    customizeImage.value = URL.createObjectURL(file);
    showCropper.value = true;
    event.target.value = "";
};

const finishFile = async () => {
    if (selectedUpload.value === "header") {
        company.value.header_bg = rawImage.value;
    } else {
        company.value.logo = rawImage.value;
    }

    // Validasi: pastikan rawImage.value adalah File atau Blob
    if (rawImage.value && (rawImage.value instanceof File || rawImage.value instanceof Blob)) {
        if (selectedUpload.value === "header") {
            headerBackground.value = URL.createObjectURL(rawImage.value);
        } else {
            pictureLogo.value = URL.createObjectURL(rawImage.value);
        }
    }

    showCropper.value = false;
};

const changeImage = (src) => {
    const { canvas } = src;
    if (canvas) {
        canvas.toBlob((blob) => {
            rawImage.value = blob;
        }, "image/jpeg");
    }
    customizeImage.value = src.image.src;
};

const flip = (x, y) => {
    imageCropper.value.flip(x, y);
};

const rotate = (angle) => {
    imageCropper.value.rotate(angle);
};

const doSave = async () => {
    try {
        if (loadingSave.value) return;
        loadingSave.value = true;

        let formData = new FormData();
        formData.append("name", company.value.name || "");
        formData.append("code", company.value.code || "");
        // Catatan: enable_slug_history_redirect sengaja tidak dikirim dari FE.
        // Jika fitur diaktifkan lagi, tambahkan kembali formData.append('enable_slug_history_redirect', ...)
        formData.append("theme_color", company.value.theme || "");

        if (typeof company.value.header_bg !== "string") {
            formData.append("header_image", company.value.header_bg || "");
        }
        if (typeof company.value.logo !== "string") {
            formData.append("profile_image", company.value.logo || "");
        }
        formData.append("company_values", company.value.company_values || "");
        formData.append("company_category_id", company.value.category || "");
        formData.append("linkedin", company.value.linkedin || "");
        formData.append("instagram", company.value.instagram || "");
        formData.append("website", company.value.website || "");

        let objRange = listRangeEmployee.value.find(
            (item) => item.id === company.value.margin_employee
        );

        let range = [];
        if (objRange) {
            range = objRange?.name
                .split("-")
                .map((val) => parseInt(val.trim()));
        }

        formData.append(
            "employee_range_start",
            range.length > 0 ? range[0] : ""
        );
        formData.append(
            "employee_range_end",
            range.length > 1 ? range[1] : range[0] || ""
        );

        const res = await mainStore.useAPI(
            `api/portal/${company.value.id}`,
            {
                method: "POST",
                cache: "no-cache",
                key: "save-portal",
                body: formData,
            },
            true
        );

        if (res.status == 200) {
            loadingSave.value = false;

            if (!props.asComponent) {
                router.visit("/");
            } else {
                getPortal(true);
            }

            let showInfo = {
                status: true,
                title: "Success",
                type: "success",
                message: res.message || "Success Update!",
            };
            showPopup(showInfo);
        }
    } catch (err) {
        console.log("err ress", err);
        loadingSave.value = false;
        let showInfo = {
            status: true,
            title: err.status >= 500 ? "Error" : "Warning",
            type: err.status >= 500 ? "error" : "warning",
            message: err.response?.message || `${err.message}`,
        };
        const errors = err.response?.errors;
        if (errors) {
            showInfo.message = errors;
        } else {
            showInfo.message = `${err.message}`;
        }
        showPopup(showInfo);
    }
};

const doSaveSlug = async () => {
    try {
        if (loadingSaveSlug.value) return;
        loadingSaveSlug.value = true;

        const res = await mainStore.useAPI(
            `api/portal/${company.value.id}/slug`,
            {
                method: "POST",
                cache: "no-cache",
                key: "save-portal-slug",
                body: {
                    slug: slugDraft.value || "",
                },
            },
            true
        );

        if (res.status == 200) {
            loadingSaveSlug.value = false;

            if (res.portal?.slug) {
                company.value.slug = res.portal.slug;
            }
            if (res.portal?.slug_changed_at) {
                company.value.slug_changed_at = res.portal.slug_changed_at;
            }
            showSlugModal.value = false;

            const newSlug = res.portal?.slug;
            const currentSlug = window.location.pathname.split("/")[1] || "";
            if (newSlug && currentSlug && newSlug !== currentSlug) {
                try {
                    const portals = JSON.parse(localStorage.getItem("portal") || "[]");
                    const updatedPortals = Array.isArray(portals)
                        ? portals.map((p) =>
                              p?.id === company.value.id
                                  ? { ...p, slug: newSlug, name: res.portal?.name ?? p?.name }
                                  : p
                          )
                        : portals;
                    localStorage.setItem("portal", JSON.stringify(updatedPortals));

                    const selectedPortalStr = localStorage.getItem("selected_portal");
                    if (selectedPortalStr) {
                        try {
                            const selectedPortal = JSON.parse(selectedPortalStr);
                            if (selectedPortal?.id === company.value.id) {
                                localStorage.setItem(
                                    "selected_portal",
                                    JSON.stringify({ ...selectedPortal, slug: newSlug })
                                );
                            }
                        } catch (e) {
                            void e;
                        }
                    }
                } catch (e) {
                    void e;
                }

                const pathAdmin = import.meta.env.VITE_PATH_ADMIN ?? "admin";
                window.location.href = `/${newSlug}/${pathAdmin}/settings?nav=portal`;
                return;
            }

            let showInfo = {
                status: true,
                title: "Success",
                type: "success",
                message: res.message || "Success Update!",
            };
            showPopup(showInfo);
        }
    } catch (err) {
        console.log("err ress", err);
        loadingSaveSlug.value = false;
        let showInfo = {
            status: true,
            title: err.status >= 500 ? "Error" : "Warning",
            type: err.status >= 500 ? "error" : "warning",
            message: err.response?.message || `${err.message}`,
        };
        const errors = err.response?.errors;
        if (errors) {
            showInfo.message = errors;
        } else {
            showInfo.message = `${err.message}`;
        }
        showPopup(showInfo);
    }
};

const openSlugModal = async () => {
    await getAppConfig();
    slugDraft.value = company.value.slug || "";
    showSlugModal.value = true;
};

const nextSlugChangeAt = computed(() => {
    if (!company.value.slug_changed_at) return null;
    const base = new Date(company.value.slug_changed_at);
    if (Number.isNaN(base.getTime())) return null;
    const d = new Date(base.getTime());
    d.setDate(d.getDate() + portalSlugCooldownDays.value);
    return d;
});

const isSlugChangeLocked = computed(() => {
    if (!nextSlugChangeAt.value) return false;
    return new Date() < nextSlugChangeAt.value;
});

const nextSlugChangeAtText = computed(() => {
    if (!nextSlugChangeAt.value) return "";
    const dateText = nextSlugChangeAt.value.toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "long",
        year: "numeric",
    });
    return `${dateText} (${portalSlugCooldownDays.value} hari)`;
});

const nextSlugChangeIfUpdateNowText = computed(() => {
    const d = new Date();
    d.setDate(d.getDate() + portalSlugCooldownDays.value);
    const dateText = d.toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "long",
        year: "numeric",
    });
    return `${dateText} (${portalSlugCooldownDays.value} hari)`;
});

const isSlugDirty = computed(() => {
    const next = (slugDraft.value ?? "").trim();
    const current = (company.value.slug ?? "").trim();
    return next !== current;
});
</script>

<style scoped>
.wrapper-photo {
    margin-top: -60px;
}

.background-header {
    width: 100%;
    height: 100%;
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
}

.logo-company {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
</style>
