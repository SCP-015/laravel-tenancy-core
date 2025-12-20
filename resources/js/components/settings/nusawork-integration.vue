<template>
    <div>
        <div class="flex items-center mb-5 mt-3">
            <div>
                <h1
                    class="text-xl font-semibold"
                    :class="{ 'ml-6': collapsed }"
                >
                    Koneksi dengan Nusawork
                </h1>
                <h3 class="text-sm">
                    Pengaturan koneksi antara Nusahire dengan Nusawork
                </h3>
            </div>
        </div>

        <template v-if="firstLoad">
            <div class="flex items-center justify-center h-64">
                <div
                    class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"
                ></div>
            </div>
        </template>

        <template v-else>
            <error-message
                v-if="isErrorInfo"
                :error-data="errorData"
                @getData="getData()"
            ></error-message>

            <template v-else>
                <div
                    v-if="!is_active"
                    class="flex items-center justify-center text-center mt-20"
                >
                    <div>
                        <div>Nusahire belum terhubung dengan Nusawork</div>
                        <button
                            class="mt-3 bg-[#E6F3EA] text-[#00852C] text-[14px] font-medium px-4 py-2 rounded-[8px] cursor-pointer"
                        >
                            Hubungkan
                        </button>
                    </div>
                </div>
                <template v-else>
                    <div
                        class="font-[500] text-[14px] text-[#6D6D6D] mt-6 mb-1"
                    >
                        Karyawan yang diekspor ke Nusawork
                    </div>
                    <default-table
                        :fields="fields"
                        :items="items"
                        :wrapperTableClass="'max-h-[400px] min-h-[300px]'"
                        :total-data="total_data"
                        :is-skeleton="loadingData"
                        :is-loading="firstLoad"
                        :page="current_page"
                        :itemsPerPage="per_page"
                        :error-message="errorData"
                        :rowSkeleton="4"
                        @update-page="handlePageChange"
                        @update-items-per-page="handleItemsPerPageChange"
                        @getData="getData()"
                        @pageClick="getData()"
                        @search="onSearch"
                    >
                        <template #customCell(name)="data">
                            {{
                                data.item.first_name + " " + data.item.last_name
                            }}
                        </template>

                        <template #customCell(expected_salary)="data">
                            Rp.
                            {{
                                data.item.expected_salary
                                    ? formatPrice(data.item.expected_salary)
                                    : "-"
                            }}
                        </template>

                        <template #customCell(notice_period)="data">
                            <span class="capitalize">
                                {{
                                    data.item?.notice_period?.replace(
                                        "_",
                                        " "
                                    ) || `-`
                                }}
                            </span>
                        </template>

                        <template #customCell(file)="data">
                            <div class="flex">
                                <div>
                                    <div
                                        class="text-[#00852C] cursor-pointer mb-2"
                                        @click="
                                            handleDownloadFile(data.item, 'cv')
                                        "
                                    >
                                        Preview CV
                                    </div>

                                    <div
                                        class="text-[#00852C] cursor-pointer"
                                        @click="
                                            handleDownloadFile(
                                                data.item,
                                                'portfolio'
                                            )
                                        "
                                    >
                                        Preview Portofolio
                                    </div>
                                </div>

                                <div class="flex items-center">
                                    <span
                                        v-if="loadingDownload[data.item.id]"
                                        class="ml-1 d-flex align-center"
                                    >
                                        <img
                                            src="/images/loading.gif"
                                            alt="loading"
                                            width="18"
                                        />
                                    </span>
                                </div>
                            </div>
                        </template>

                        <template #customCell(action)="data">
                            <div
                                v-if="data.item.nusawork_reff_id"
                                class="text-[#00852C] text-[14px] cursor-pointer flex flex-nowrap items-center gap-1"
                                @click="viewOnNusawork(data.item)"
                            >
                                <span>Lihat di Nusawork</span>
                                
                                <div
                                    v-if="loadingViewNusawork[data.item.id]"
                                    class="spinner-enhanced animate-spin rounded-full h-4 w-4 border-2 border-gray-200 border-t-green-600"
                                ></div>
                                <img
                                    v-else
                                    src="/images/open-tab.svg"
                                    alt="open-right"
                                    width="12"
                                />
                            </div>
                        </template>
                    </default-table>
                </template>
            </template>
        </template>
    </div>
</template>

<script setup>
import dayjs from "dayjs";
import { ref, computed, watch, onBeforeMount, reactive } from "vue";
import { useMainStore } from "../../stores";
import { capitalize, unslugify } from "../../utils";

const props = defineProps({
    collapsed: {
        type: Boolean,
        default: false,
    },
});

// State
const mainStore = useMainStore();
let current_page = ref(1),
    per_page = ref(15),
    search = ref(""),
    total_data = ref(0),
    firstLoad = ref(true),
    isError = ref(false),
    errorData = reactive({
        code: null,
        message: "",
    }),
    loadingData = ref(true),
    fields = ref([
        { label: "Nama", key: "name" },
        { label: "Notice Period", key: "notice_period" },
        { label: "Gaji yang Diharapkan", key: "expected_salary" },
        { label: "File", key: "file" },
        { label: "Aksi", key: "action" },
    ]),
    items = ref([]),
    is_active = ref(false),
    loadingDownload = reactive({}),
    loadingViewNusawork = reactive({});

const portal = computed(() => mainStore.userPortal.value);

watch(
    portal,
    (val) => {
        if (val.length > 0) {
            getData();
        }
    },
    { deep: true, immediate: true }
);

// methods
function onSearch(searchValue) {
    search.value = searchValue;
    current_page.value = 1;
    getData();
}

const handlePageChange = (page) => {
    current_page.value = page;
    getData();
};

function handleItemsPerPageChange(newValue) {
    per_page.value = newValue;
    current_page.value = 1; // Reset ke halaman pertama
    getData();
}

async function getData() {
    try {
        if (!portal.value.length) return;
        isError.value = false;
        errorData.code = null;
        errorData.message = "";
        loadingData.value = true;
        let idTenant = portal.value[0].id;
        const res = await mainStore.useAPI(
            `${idTenant}/api/exported-candidates?search=${search.value}&page=${current_page.value}&per_page=${per_page.value}`,
            {
                method: "GET",
                cache: "no-cache",
                key: "get-exported-candidates",
            },
            true
        );

        if (res.status == 200) {
            console.log("ress", res);

            items.value = res.data;
            total_data.value = res.meta.total;
            loadingData.value = false;
            firstLoad.value = false;
            is_active.value = res.portal.is_nusawork_integrated;
        }
    } catch (error) {
        isError.value = true;
        errorData.code = error.response?.status || error.status;
        errorData.message = error.response?.data?.message || error.message;
        loadingData.value = false;
        firstLoad.value = false;
    }
}

const formatPrice = (value) => {
    if (value || value === 0) {
        // Konversi ke integer untuk menghilangkan semua angka desimal
        let valueInt = Math.floor(parseFloat(value));

        // Format dengan pemisah ribuan
        let result = valueInt.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        if (result == "NaN") {
            return value;
        } else {
            return result;
        }
    }
    return "";
};

const handleDownloadFile = async (candidate, type) => {
    try {
        if (loadingDownload[candidate.id]) return;
        loadingDownload[candidate.id] = true;
        const res = await downloadFile(
            candidate,
            type == "cv"
                ? candidate.current_resume_path
                : candidate.portfolio_file_path,
            type
        );
        if (res?.status == 200) {
            loadingDownload[candidate.id] = false;
            showPopup({
                status: true,
                title: "Berhasil!",
                type: "success",
                message: "File berhasil dibuka!",
            });
        }
    } catch (err) {
        console.log("error??", err);
        loadingDownload[candidate.id] = false;
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

const downloadFile = async (data, path, type) => {
    try {
        const response = await fetch(path, {
            method: "GET",
        });

        if (!response.ok) throw new Error("Download gagal");

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);

        window.open(url, "_blank");
        setTimeout(() => window.URL.revokeObjectURL(url), 1000);

        return { status: 200 };
    } catch (err) {
        console.error("Something went wrong:", err);
        throw err;
    }
};

const showPopup = (state) => {
    mainStore.stateShowInfo = state;
};

const viewOnNusawork = async (candidate) => {
    try {
        if (loadingViewNusawork[candidate.id]) return;
        loadingViewNusawork[candidate.id] = true;
        
        // Hit API untuk fetch data kandidat dari Nusawork
        const res = await mainStore.useAPI(
            `${portal.value[0].id}/api/integrations/nusawork/candidate/${candidate.nusawork_reff_id}`,
            {
                method: "GET",
                cache: "no-cache",
                key: `view-nusawork-${candidate.id}`,
            },
            true
        );

        if (res.status == 200) {
            loadingViewNusawork[candidate.id] = false;
            
            // Jika success, buka tab baru dengan URL dari response
            if (res.data?.view_on_nusawork_url) {
                window.open(res.data.view_on_nusawork_url, "_blank");
            }
        }
    } catch (err) {
        console.error("Error viewing on Nusawork:", err);
        loadingViewNusawork[candidate.id] = false;
        showPopup({
            status: true,
            title: err.status >= 500 ? "Error" : "Warning",
            type: err.status >= 500 ? "error" : "warning",
            message: err.response?.data?.message || err.message || "Gagal membuka halaman Nusawork",
        });
    }
};
</script>

<style scoped>
/* Untuk menutup dropdown saat klik di luar */
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
