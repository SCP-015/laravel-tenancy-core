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

            <div
                class="feedback fixed top-4 right-4 sm:top-5 sm:right-5 z-50 flex items-center cursor-pointer"
                @click="openFeedbackModal"
            >
                <img src="/images/icon-feedback.svg" alt="icon feedback" />
            </div>

            <template v-if="!selectedOption">
                <div class="mt-2">
                    <div class="flex justify-center mb-3">
                        <img
                            v-if="profile?.avatar"
                            :src="profile?.avatar"
                            class="w-[64px] h-[64px] rounded-full"
                            alt=""
                        />

                        <div
                            v-else
                            class="bg-[#D9D9D9] w-[64px] h-[64px] rounded-full"
                        ></div>
                    </div>
                    <div class="text-[20px] text-[#000000]">
                        Selamat datang, {{ profile?.name }}
                    </div>
                </div>

                <div class="w-50% mt-4">
                    <div
                        class="bg-[#FBFBFB] px-4 py-3 rounded-sm mt-3 cursor-pointer"
                        v-for="(item, index) in options"
                        :key="index"
                        @click="selectedOption = item.id"
                    >
                        <div class="flex flex-nowrap">
                            <div class="mr-4">
                                <div class="font-[500] text-[#000000]">
                                    {{ item.name }}
                                </div>
                                <div
                                    class="font-[400] text-[12px] text-[#7A7A7A]"
                                >
                                    {{ item.desc }}
                                </div>
                            </div>

                            <img
                                class="ml-auto"
                                src="/images/chevron-right.svg"
                                alt="chevron right"
                            />
                        </div>
                    </div>
                </div>
            </template>

            <template v-else>
                <div
                    class="bg-[#FBFBFB] px-4 py-3 rounded-md border border-[#EDEDED] mt-3 w-[380px]"
                >
                    <template v-if="selectedOption == 'new'">
                        <div class="font-[500] text-[#000000]">
                            Buat Portal Baru
                        </div>
                        <div class="text-[#7A7A7A] text-[12px]">
                            Buat portal baru untuk perusahaan Anda
                        </div>

                        <div class="mt-4">
                            <div class="text-[#7A7A7A] text-[12px]">
                                Nama Perusahaan
                            </div>
                            <div class="flex flex-nowrap items-center">
                                <Input
                                    v-model="form.company"
                                    :type="'text'"
                                    :placeholder="'Nama_perusahaan'"
                                    :classWidth="`w-[100%]`"
                                />
                            </div>

                            <div
                                class="flex items-center mr-1 text-[16px] text-[#000000] mt-5"
                            >
                                https://nusahire.com/{{ company_slug }}
                            </div>
                        </div>

                        <div class="flex flex-nowrap mt-4">
                            <div class="flex items-center">
                                <div class="text-[14px]">
                                    Sudah ada?
                                    <span
                                        class="underline text-[#336AFF] font-[500] cursor-pointer"
                                        @click="selectedOption = 'join'"
                                        >Join Company</span
                                    >
                                </div>
                            </div>

                            <button
                                class="ml-auto cursor-pointer flex items-center gap-2 bg-[#3A3A3A] text-white px-6 py-2 rounded-md shadow-sm transition"
                                @click="processData()"
                            >
                                <span class="text-[14px] flex flex-nowrap">
                                    Simpan
                                    <span
                                        v-if="loadingSave"
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
                        </div>
                    </template>
                    <template v-else>
                        <div class="font-[500] text-[#000000]">
                            Join Company yang tersedia
                        </div>
                        <div class="text-[#7A7A7A] text-[12px]">
                            Masuk ke dalam portal yang sudah dibuat sebelumnya
                        </div>

                        <div class="form-group mt-4">
                            <div
                                class="text-[#000000] text-[14px] font-500 mb-1"
                            >
                                Kode Perusahaan
                            </div>
                            <Input
                                :classWidth="`w-[100%]`"
                                v-model="form.code"
                                :type="'text'"
                                :placeholder="'Masukkan kode perusahaan anda'"
                            />
                        </div>

                        <div class="flex flex-nowrap mt-4">
                            <div class="flex items-center">
                                <div class="text-[14px]">
                                    Belum tersedia?
                                    <span
                                        class="underline text-[#336AFF] font-[500] cursor-pointer"
                                        @click="selectedOption = 'new'"
                                        >Buat baru</span
                                    >
                                </div>
                            </div>
                            <button
                                class="ml-auto cursor-pointer flex items-center gap-2 bg-[#3A3A3A] text-white px-6 py-2 rounded-md shadow-sm transition"
                                @click="processData()"
                            >
                                <span class="text-[14px] flex flex-nowrap">
                                    Simpan
                                    <span
                                        v-if="loadingSave"
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
                        </div>
                    </template>
                </div>
            </template>

            <div
                class="bg-[#FBFBFB] px-4 w-[380px] py-3 rounded-sm mt-3 cursor-pointer flex flex-nowrap"
                @click="doLogout()"
            >
                <div class="pl-2">Keluar dari akun ini</div>
                <img
                    src="/images/logout.svg"
                    alt="icon logout"
                    class="ml-auto"
                />
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
            <template #header>
                <div class="flex justify-between items-center w-full pt-4 px-6">
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
                <div class="wrapper-body-modal px-4 pb-4">
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
                                        class="absolute inset-0 bg-black/50 text-white flex flex-col items-center justify-center gap-2 opacity-0 transition-opacity group-hover-opacity-100 cursor-pointer"
                                    >
                                        <span class="text-sm">Ganti Foto</span>
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
                                            class="w-4 h-4"
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
                        Beritahu kami apa yang Anda lakukan, apa yang terjadi,
                        apa ekspektasi anda, atau apa yang membuat Anda
                        kesulitan.
                    </p>
                    
                    <div
                        class="category-buttons flex gap-2 mb-4 w-full justify-between"
                    >
                        <button
                            class="category-btn border px-3 py-1 rounded transition-colors flex-1"
                            :class="{
                                'active-category': selectedCategory === 'Saran',
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

                    <div class="flex items-center justify-end mt-2">
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
            :style-props="{ maxWidth: '350px', borderRadius: '12px' }" >
            <template #header></template>
            <template #body>
                <div class="p-6 text-center flex flex-col items-center gap-4"> <div class="icon-rise-bounce mb-2"> <img src="/images/warning.svg" alt="Warning" class="w-16 h-16"/> </div>
                    <h2 class="font-bold text-lg text-gray-800">Perhatian</h2> <p class="text-gray-600">{{ errorModal.message }}</p> </div>
            </template>
            <template #footer>
                <div class="px-6 pb-6 pt-0 flex justify-center w-full"> <button
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
                        <img src="/images/success.svg" alt="Success" class="w-16 h-16"/>
                    </div>
                    <h2 class="font-bold text-lg text-gray-800">Masukan Terkirim</h2>
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
import { computed, onMounted, ref, watch } from "vue";
import { useMainStore } from "../../stores";
import { router } from "@inertiajs/vue3";
import { toJpeg } from 'html-to-image';
import axios from 'axios';
import Modal from '../../components/modal.vue';
import Input from '../../components/input-field.vue';

const MAX_FILE_SIZE_MB = 2;
const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024;

const mainStore = useMainStore();
// State
let options = ref([
        {
            id: "new",
            name: "Buat Portal Baru",
            desc: "Buat portal baru untuk perusahaan Anda",
        },
        {
            id: "join",
            name: "Join Company yang tersedia",
            desc: "Masuk ke dalam portal yang sudah dibuat sebelumnya",
        },
    ]),
    selectedOption = ref(""),
    form = ref({
        company: "",
        code: "",
    }),
    loadingSave = ref(false);

// Watcher
const profile = computed(() => {
    const res = JSON.parse(localStorage.getItem("user"));
    return res;
});
const portal = computed(() => mainStore.userPortal.value);

watch(
    portal,
    () => {
        if (portal.value.length > 0) {
            const pathAdmin = import.meta.env.VITE_PATH_ADMIN ?? "admin";
            router.visit(`/${portal.value[0].slug}/${pathAdmin}`);
        }
    },
    { deep: true, immediate: true }
);

const company_slug = computed(() => {
    return form.value.company.toLowerCase().replace(/[\s.]+/g, "-");
});

// --- Feedback Modal State ---
const isFeedback = ref(false);
const isSubmitting = ref(false);
const screenshots = ref([]);
const fileImage = ref([]);
const currentUrl = ref("");
const selectedCategory = ref("");
const feedbackText = ref("");
const windowWidth = ref(window.innerWidth);

// --- Modal Popups State ---
const errorModal = ref({
  show: false,
  message: ''
});
const successModal = ref({
    show: false,
    message: ''
});

// Methods
const showPopup = (state) => {
    mainStore.stateShowInfo = state;
};

const processData = async () => {
    try {
        if (loadingSave.value) return;
        loadingSave.value = true;
        let useAuth = true;
        let payload = {
            ...(selectedOption.value == "new"
                ? { name: form.value.company, slug: company_slug.value }
                : { code: form.value.code }),
        };

        const res = await mainStore.useAPI(
            selectedOption.value == "new" ? "api/portal" : "api/portal/join",
            {
                method: "POST",
                cache: "no-cache",
                key: "handle-portal",
                body: payload,
            },
            useAuth
        );

        if (res.status == 200 || res.status == 201) {
            loadingSave.value = false;
            const pathAdmin = import.meta.env.VITE_PATH_ADMIN ?? "admin";
            window.location.href = `/${res.portal.slug}/${pathAdmin}`;
        }
    } catch (err) {
        console.log("err---", err);
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
        }

        showPopup(showInfo);
    }
};

const doLogout = () => {
    mainStore.logout();
};

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const code = urlParams.get("code");
    if (code) {
        form.value.code = code;
    }
});

// --- Feedback Functions ---
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
    const originalExtension = originalName.split('.').pop().toLowerCase();
    const cleanName = originalName.substring(0, originalName.lastIndexOf('.')).replace(/[^a-zA-Z0-9]/g, '-');
    const finalExtension = ['jpeg', 'png', 'jpg', 'gif'].includes(originalExtension) ? originalExtension : 'jpeg';
    return `screenshot-${cleanName}-${timestamp}-${randomNumber}.${finalExtension}`;
};

const openFeedbackModal = async () => {
    let originalBackgroundColor = '';
    try {
        currentUrl.value = window.location.href;
        const mainContentElement = document.body;
        originalBackgroundColor = mainContentElement.style.backgroundColor;
        mainContentElement.style.backgroundColor = 'white';
        
        const jpegDataUrl = await toJpeg(mainContentElement, { quality: 0.95 });
        screenshots.value = [jpegDataUrl];
        
        const blobFile = dataURItoBlob(jpegDataUrl);
        const uniqueFilename = generateUniqueFilename('screenshot_otomatis.jpeg');
        const file = new File([blobFile], uniqueFilename, { type: 'image/jpeg' });
        fileImage.value = [file];
        
    } catch (error) {
        console.error("Gagal mengambil screenshot:", error);
        screenshots.value = [];
        fileImage.value = [];
    } finally {
        const mainContentElement = document.body;
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
        errorModal.value.message = "Anda hanya dapat mengunggah maksimal 3 gambar tambahan.";
        errorModal.value.show = true;
        event.target.value = '';
        return;
    }
    const file = event.target.files[0];
    if (!file) {
        event.target.value = '';
        return;
    }

    if (file.size > MAX_FILE_SIZE_BYTES) {
        errorModal.value.message = `Ukuran file "${file.name}" melebihi batas maksimum ${MAX_FILE_SIZE_MB} MB.`;
        errorModal.value.show = true;
        event.target.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        screenshots.value.push(e.target.result);
        const uniqueFilename = generateUniqueFilename(file.name);
        fileImage.value.push(new File([file], uniqueFilename, { type: file.type }));
        event.target.value = '';
    };
    reader.readAsDataURL(file);
};

const replaceScreenshot = (event, index) => {
    const file = event.target.files[0];
    if (!file) {
        event.target.value = '';
        return;
    }

    if (file.size > MAX_FILE_SIZE_BYTES) {
        errorModal.value.message = `Ukuran file "${file.name}" melebihi batas maksimum ${MAX_FILE_SIZE_MB} MB.`;
        errorModal.value.show = true;
        event.target.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        screenshots.value[index] = e.target.result;
        const uniqueFilename = generateUniqueFilename(file.name);
        fileImage.value[index] = new File([file], uniqueFilename, { type: file.type });
        event.target.value = '';
    };
    reader.readAsDataURL(file);
};

const submitFeedback = async () => {
    if (!selectedCategory.value) {
        errorModal.value.message = 'Mohon pilih salah satu kategori feedback (Saran, Pujian, atau Keluhan).';
        errorModal.value.show = true;
        return;
    }
    
    if (!feedbackText.value) {
        errorModal.value.message = 'Mohon isi masukan Anda.';
        errorModal.value.show = true;
        return;
    }

    const oversizedFile = fileImage.value.find(file => file.size > MAX_FILE_SIZE_BYTES);

    if (oversizedFile) {
        errorModal.value.message = `Ukuran file "${oversizedFile.name}" melebihi batas maksimum ${MAX_FILE_SIZE_MB} MB. Mohon ganti gambar atau hapus.`;
        errorModal.value.show = true;
        return;
    }

    isSubmitting.value = true;
    try {
        const formData = new FormData();
        const loggedInUser = JSON.parse(localStorage.getItem("user"));
        const loggedInToken = localStorage.getItem("token");
        
        if (!loggedInUser || !loggedInToken) {
            errorModal.value.message = 'Sesi tidak valid, mohon login kembali.';
            errorModal.value.show = true;
            return;
        }

        formData.append('url', currentUrl.value);
        formData.append('category', selectedCategory.value);
        formData.append('feedback', feedbackText.value);

        fileImage.value.forEach((file, index) => {
            formData.append(`screenshots[${index}]`, file, file.name);
        });

        const headers = {
            'Authorization': `Bearer ${loggedInToken}`
        };
        
        const response = await axios.post(`/api/feedback`, formData, { headers });
        console.log(response)

        if (response.status === 200) {
            isFeedback.value = false;
            successModal.value.message = "Terima kasih, masukan Anda sudah kami terima!";
            successModal.value.show = true;
            resetFeedbackForm();
        } else {
            console.error("Gagal mengirim ke backend:", response.data);
            errorModal.value.message = 'Gagal mengirim feedback. Silakan coba lagi nanti.';
            errorModal.value.show = true;
        }
    } catch (error) {
        console.error("Error jaringan atau server:", error);

        if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            let errorMessage = 'Masukan Anda tidak valid. Silakan periksa kembali data yang Anda masukkan.';

            if (validationErrors) {
                const firstErrorKey = Object.keys(validationErrors)[0];
                if (firstErrorKey) {
                    errorMessage = validationErrors[firstErrorKey][0];
                }
            }

            errorModal.value.message = errorMessage; 
        }
        
        else if (error.response) {
            errorModal.value.message = `Terjadi kesalahan server (${error.response.status}). Silakan coba lagi atau hubungi admin.`;
        } else {
            errorModal.value.message = 'Terjadi kesalahan jaringan. Periksa koneksi internet Anda.';
        }

        errorModal.value.show = true;
    } finally {
        isSubmitting.value = false;
    }
};

const resetFeedbackForm = () => {
    screenshots.value = [];
    fileImage.value = [];
    feedbackText.value = '';
    selectedCategory.value = '';
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
</script>

<style scoped>
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

textarea {
    resize: none;
    transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

textarea:focus {
    outline: none;
    border-color: #00852C;
    box-shadow: 0 0 0 2px rgba(0, 133, 44, 0.2);
}

.flex-wrap > div.relative.w-40.h-32.border {
    border-width: 2px;
    border-style: solid;
    border-color: #e5e7eb;
}

.flex.gap-2.mb-4 > button {
    border-width: 1px;
    border-color: #d1d5db;
    transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
}

.flex.gap-2.mb-4 > button:not(.active-category):hover {
    background-color: #f3f4f6;
    border-color: #9ca3af;
}

.category-btn.active-category {
    background-color: #00852C;
    color: #fff;
    border-color: #00852C;
}

.group-hover-opacity-100 {
    opacity: 0;
}
.group:hover .group-hover-opacity-100 {
    opacity: 1;
}

.bg-gray-700 {
    background-color: #374151;
    border: none;
}
.hover-bg-gray-800:hover {
    background-color: #1f2937;
}

@media (max-width: 480px) {
    .flex.gap-2.mb-4 {
        flex-direction: row;
        flex-wrap: nowrap;
        justify-content: space-between;
    }
    
    .flex.gap-2.mb-4 > button {
        width: auto;
        flex-grow: 1;
        margin-bottom: 0;
    }

    .flex.gap-2.mb-4 > button {
        font-size: 12px;
    }
}

@media (max-width: 991px) {
    .flex.gap-2.flex-wrap {
        flex-direction: row;
        overflow-x: auto;
        white-space: nowrap;
    }

    .flex-wrap > div.relative {
        min-width: 160px;
        margin-right: 8px;
        flex-shrink: 0;
    }
}

.modal-content {
        height: auto;
        width: 100vw;
        max-width: 100%;
        max-height: 100%;
        border-radius: 0;
        top: 0;
        left: 0;
        transform: none;
        padding: 1rem;
    }

    .modal-dialog {
        margin: 0;
        width: 100%;
        height: 100%;
    }

    .modal-body {
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .modal-footer {
        width: 100%;
        position: sticky;
        bottom: 0;
        background-color: white;
    }

    .wrapper-body-modal h1 {
    margin: 0;
}
</style>