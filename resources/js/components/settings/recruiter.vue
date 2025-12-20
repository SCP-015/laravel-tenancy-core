<template>
    <div>
        <div class="flex items-center mb-5 mt-3">
            <div>
                <h1
                    class="text-xl font-semibold"
                    :class="{ 'ml-6': collapsed }"
                >
                    Pengaturan Admin
                </h1>
                <h3 class="text-sm">
                    Mengatur siapa saja yang dapat mengakses company
                </h3>
            </div>
        </div>

        <div class="w-[70%]" v-if="isSuperAdmin">
            <div class="font-[500] text-xs text-[#6D6D6D] mt-2 mb-1">
                Kode Company
            </div>
            <div class="flex flex-nowrap">
                <Input
                    v-model="company_code"
                    :type="'text'"
                    :placeholder="'Company Code'"
                    :disabled="true"
                />

                <div
                    @click="!isCopying && copyToClipboard()"
                    class="mx-2 px-3 py-2 flex flex-nowrap border border-[#AFAFAF] rounded-sm cursor-pointer hover:bg-gray-50"
                    :class="{
                        'opacity-50': isCopying,
                        'pointer-events-none': isCopying,
                    }"
                >
                    <div
                        v-if="isCopying"
                        class="w-4 h-4 border-2 border-t-transparent border-blue-500 border-solid rounded-full animate-spin mr-1"
                    ></div>
                    <div v-else class="mr-1 flex items-center">
                        <img
                            src="/images/copy.svg"
                            width="12"
                            alt="icon copy"
                        />
                    </div>
                    <span
                        class="text-sm font-[500] text-[#3A3A3A] flex items-center"
                    >
                        {{ isCopying ? "Menyalin..." : "Copy" }}
                    </span>
                </div>

                <div
                    @click="!isRefreshing && refreshCompanyCode()"
                    class="mx-2 px-3 py-2 flex flex-nowrap border border-[#AFAFAF] rounded-sm cursor-pointer hover:bg-gray-50"
                    :class="{
                        'opacity-50': isRefreshing,
                        'pointer-events-none': isRefreshing,
                    }"
                >
                    <div
                        v-if="isRefreshing"
                        class="w-4 h-4 border-2 border-t-transparent border-blue-500 border-solid rounded-full animate-spin mr-1"
                    ></div>
                    <div v-else class="mr-1 flex items-center">
                        <img
                            src="/images/refresh.svg"
                            width="12"
                            alt="icon refresh"
                        />
                    </div>
                    <span
                        class="text-xs font-[500] text-[#3A3A3A] flex items-center"
                    >
                        {{ isRefreshing ? "Memperbarui..." : "Refresh" }}
                    </span>
                </div>
            </div>
            <div class="font-[400] text-xs mt-2 mb-1 text-[#6D6D6D]">
                Anda dapat membagikan kode company kepada admin di
                perusahaan anda
            </div>
        </div>

        <div class="w-[80%] mt-8" v-if="isSuperAdmin">
            <div class="font-[500] text-xs text-[#6D6D6D] mt-2 mb-1">Email</div>
            <div class="flex flex-nowrap">
                <Input
                    v-model="recruiter"
                    type="email"
                    placeholder="Masukkan email admin"
                    @keyup.enter="inviteRecruiter"
                />

                <button
                    @click="inviteRecruiter"
                    :disabled="isAdding"
                    :class="[
                        'ml-2 cursor-pointer flex items-center gap-2 px-6 py-2 rounded-md shadow-sm transition',
                        isAdding
                            ? 'bg-gray-400'
                            : 'bg-[#3A3A3A] text-white hover:bg-gray-700',
                    ]"
                >
                    <span v-if="isAdding" class="animate-spin mr-1">↻</span>
                    <span class="text-sm text-nowrap">{{
                        isAdding ? "Mengundang..." : "Tambah Baru"
                    }}</span>
                </button>
            </div>
            <div class="font-[400] text-xs mt-2 mb-4 text-[#6D6D6D]">
                Masukkan e-mail Google (gmail) untuk mengirimkan konfirmasi ke
                Admin baru
            </div>
        </div>

        <default-table
            :wrapperTableClass="'max-h-[400px]'"
            :fields="fields.filter((f) => !f.hidden)"
            :items="items"
            :total-data="total_data"
            :is-skeleton="loadingData"
            :is-loading="firstLoad"
            :page="current_page"
            :itemsPerPage="per_page"
            :error-message="errorData"
            :rowSkeleton="4"
            @update-page="handlePageChange"
            @update-items-per-page="handleItemsPerPageChange"
            @getData="getRecruiter()"
            @pageClick="getRecruiter()"
            @search="onSearch"
        >
            <template #customCell(role)="{ item }">
                <div class="flex items-center" v-if="isSuperAdmin && item.id != user.id">
                    <span v-if="!editingRole[item.id]">
                        {{ capitalize(unslugify(item.role, "_")) }}
                        <button 
                            @click="startEditingRole(item)"
                            class="ml-2 text-gray-500 hover:text-gray-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                    </span>
                    <div v-else class="flex items-center">
                        <select
                            v-model="editingRole[item.id]"
                            class="border border-gray-300 rounded px-2 py-1 text-sm"
                            @change="updateUserRole(item.id, editingRole[item.id])"
                        >
                            <option 
                                v-for="role in roles" 
                                :key="role.id" 
                                :value="role.name"
                            >
                                {{ capitalize(unslugify(role.name, '_')) }}
                            </option>
                        </select>
                        <button 
                            @click="cancelEditingRole(item.id)"
                            class="ml-2 text-gray-500 hover:text-gray-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div v-else>
                    {{ capitalize(unslugify(item.role, "_")) }}
                </div>
            </template>
            <template #customCell(name)="{ item }">
                <span>
                    {{ item.name }}
                    <span
                        v-if="item.id == user.id"
                        class="bg-[#A2A2A2] text-[#fff] px-3 py-1 rounded-lg text-xs ml-3"
                    >
                        Anda</span
                    >
                </span>
            </template>

            <template #customCell(join_date)="{ item }">
                <span>
                    {{
                        item.join_date
                            ? dayjs(item.join_date).format("DD MMMM YYYY")
                            : "-"
                    }}
                </span>
            </template>

            <template v-if="isSuperAdmin" #customCell(last_login)="{ item }">
                <span>
                    {{
                        item.last_login
                            ? dayjs(item.last_login).format("DD MMMM YYYY")
                            : "-"
                    }}
                </span>
            </template>

            <template v-if="isSuperAdmin" #customCell(action)="{ item }">
                <div class="relative" v-if="item.id != user.id">
                    <button
                        @click="openDeleteModal(item)"
                        :disabled="isDeleting[item.id]"
                        class="cursor-pointer bg-[#F6F6F6] px-3 py-2 rounded-sm text-xs font-[500] text-[#3A3A3A] hover:bg-gray-200 transition-colors"
                        :class="{ 'opacity-50': isDeleting[item.id] }"
                    >
                        <span
                            v-if="isDeleting[item.id]"
                            class="flex items-center"
                        >
                            <span
                                class="w-3 h-3 border-2 border-t-transparent border-blue-500 border-solid rounded-full animate-spin mr-1"
                            ></span>
                            Menghapus...
                        </span>
                        <span v-else>Hapus Admin</span>
                    </button>
                </div>
            </template>
        </default-table>

        <!-- Modal Konfirmasi Hapus -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        >
            <div class="bg-white rounded-lg w-full max-w-md p-6 mx-4">
                <h3 class="text-lg font-semibold mb-4">
                    Konfirmasi Hapus Admin
                </h3>
                <p class="text-gray-700 mb-4">
                    Apakah Anda yakin ingin menghapus
                    <b>{{ selectedRecruiter?.name }}</b> dari daftar admin?
                </p>

                <div class="mb-4 flex items-center">
                    <input
                        type="checkbox"
                        id="notifyEmail"
                        v-model="notifyByEmail"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    />
                    <label for="notifyEmail" class="ml-2 text-sm text-gray-700">
                        Infokan lewat e-mail
                    </label>
                </div>

                <div class="flex justify-end space-x-3">
                    <button
                        @click="closeDeleteModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                    >
                        Batal
                    </button>
                    <button
                        @click="confirmDelete"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        :disabled="isDeleting[selectedRecruiter?.id]"
                    >
                        <span
                            v-if="isDeleting[selectedRecruiter?.id]"
                            class="flex items-center"
                        >
                            <span
                                class="w-3 h-3 border-2 border-t-transparent border-white border-solid rounded-full animate-spin mr-2"
                            ></span>
                            Menghapus...
                        </span>
                        <span v-else>Ya, Hapus</span>
                    </button>
                </div>
            </div>
        </div>
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
let user = ref({}),
    company_code = ref(""),
    isSuperAdmin = ref(false),
    recruiter = ref(""),
    isAdding = ref(false),
    isCopying = ref(false),
    isRefreshing = ref(false),
    showDeleteConfirm = ref(null),
    isDeleting = ref({}),
    showDeleteModal = ref(false),
    selectedRecruiter = ref(null),
    notifyByEmail = ref(false),
    current_page = ref(1),
    per_page = ref(15),
    search = ref(""),
    total_data = ref(0),
    firstLoad = ref(true),
    errorData = reactive({
        code: null,
        message: "",
    }),
    loadingData = ref(true),
    roles = ref([]),
    editingRole = ref({}),
    fields = ref([
        { label: "ID", key: "id", hidden: true },
        { label: "Nama", key: "name" },
        { label: "Role", key: "role" },
        { label: "Email", key: "email" },
        { label: "Tanggal Bergabung", key: "join_date" },
        { label: "Tanggal Terakhir Login", key: "last_login_at" },
        { label: "", key: "action" },
    ]),
    items = ref([]);

const portal = computed(() => mainStore.userPortal.value);

const getRoles = async () => {
    if (!portal.value.length) return;

    try {
        const idTenant = portal.value[0].id;
        const res = await mainStore.useAPI(
            `${idTenant}/api/settings/roles`,
            {
                method: "GET",
                cache: "no-cache",
                key: "get-roles",
            },
            true
        );

        if (res.status == 200) {
            roles.value = res.data;
        }
    } catch (error) {
        console.error("Gagal mengambil data roles:", error);
        showPopup({
            status: true,
            title: "Gagal!",
            type: "danger",
            message: "Gagal mengambil data roles",
        });
    }
};

const inviteRecruiter = async () => {
    if (!recruiter.value) {
        return;
    }

    // Validasi format email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(recruiter.value)) {
        showPopup({
            status: true,
            title: "Gagal!",
            type: "danger",
            message: "Format email tidak valid",
        });
        return;
    }

    isAdding.value = true;

    try {
        const response = await mainStore.inviteRecruiter(recruiter.value);
        if (response.status === 200) {
            showPopup({
                status: true,
                title: "Berhasil!",
                type: "success",
                message: response.message || "Admin berhasil diundang",
            });
            recruiter.value = ""; // Reset form
        }
    } catch (error) {
        showPopup({
            status: true,
            title: "Gagal!",
            type: "danger",
            message:
                error.response?.message || "Gagal mengundang admin",
        });
    } finally {
        isAdding.value = false;
    }
};

const getRecruiter = async () => {
    if (!portal.value.length) return;

    loadingData.value = true;
    errorData.code = null;
    errorData.message = "";

    try {
        let idTenant = portal.value[0].id;
        let res = await mainStore.getRecruiter(idTenant, {
            page: current_page.value,
            per_page: per_page.value,
            search: search.value,
        });

        if (res.status == 200) {
            items.value = res.data;
            company_code.value = res.company_code;
            total_data.value = res.meta.total;
            isSuperAdmin.value = res.is_super_admin || false;
        }
    } catch (error) {
        console.error("Gagal mengambil data recruiter:", error);
        items.value = [];
        total_data.value = 0;
        errorData.code = error.response?.status || error.status;
        errorData.message = error.response?.data?.message || error.message;
    } finally {
        loadingData.value = false;
        firstLoad.value = false;
    }
};

const handlePageChange = (page) => {
    current_page.value = page;
    getRecruiter();
};

const onSearch = (searchValue) => {
    search.value = searchValue;
    current_page.value = 1;
    getRecruiter();
};

function handleItemsPerPageChange(newValue) {
    per_page.value = newValue;
    current_page.value = 1; // Reset ke halaman pertama
    getRecruiter();
}

const startEditingRole = (item) => {
    // Inisialisasi editingRole untuk user ini jika belum ada
    if (!editingRole.value[item.id]) {
        editingRole.value[item.id] = item.role;
    }
};

const cancelEditingRole = (userId) => {
    delete editingRole.value[userId];
};

const updateUserRole = async (userId, newRole) => {
    try {
        const idTenant = portal.value[0].id;
        const res = await mainStore.updateRole(idTenant, userId, newRole);
        
        if (res.status === 200) {
            showPopup({
                status: true,
                title: "Berhasil!",
                type: "success",
                message: "Role admin berhasil diupdate",
            });
            
            // Hapus mode edit setelah berhasil update
            delete editingRole.value[userId];
            
            // Refresh data recruiter
            await getRecruiter();
        }
    } catch (error) {
        console.error("Gagal mengupdate role admin:", error);
        showPopup({
            status: true,
            title: "Gagal!",
            type: "danger",
            message: error.message || "Gagal mengupdate role admin",
        });
    }
};

const openDeleteModal = (recruiter) => {
    selectedRecruiter.value = recruiter;
    notifyByEmail.value = false; // Reset checkbox
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedRecruiter.value = null;
};

const confirmDelete = async () => {
    if (!selectedRecruiter.value) return;

    const id = selectedRecruiter.value.id;
    await deleteRecruiter(id, notifyByEmail.value);
    closeDeleteModal();
};

const deleteRecruiter = async (id, notifyByEmail) => {
    if (isDeleting.value[id]) return;

    isDeleting.value = { ...isDeleting.value, [id]: true };
    showDeleteConfirm.value = null;

    try {
        const idTenant = portal.value[0].id;
        const res = await mainStore.deleteRecruiter(
            idTenant,
            id,
            notifyByEmail
        );

        if (res.status == 200) {
            showPopup({
                status: true,
                title: "Berhasil!",
                type: "success",
                message: "Admin berhasil dihapus",
            });
            // Refresh data recruiter
            await getRecruiter();
        }
    } catch (error) {
        console.error("Gagal menghapus admin:", error);
        showPopup({
            status: true,
            title: "Gagal!",
            type: "danger",
            message: error.message,
        });
    } finally {
        isDeleting.value = { ...isDeleting.value, [id]: false };
    }
};

const copyToClipboard = async () => {
    if (isCopying.value) return;
    isCopying.value = true;
    try {
        await navigator.clipboard.writeText(company_code.value);
        showPopup({
            status: true,
            title: "Berhasil!",
            type: "success",
            message: `Kode company ${company_code.value} berhasil disalin!`,
        });
    } catch (err) {
        console.error("Gagal menyalin teks: ", err);
        showPopup({
            status: true,
            title: "Gagal!",
            type: "danger",
            message: `Gagal menyalin kode company ${company_code.value}!`,
        });
    } finally {
        isCopying.value = false;
    }
};

const refreshCompanyCode = async () => {
    if (isRefreshing.value) return;
    isRefreshing.value = true;
    try {
        let idTenant = portal.value[0].id;
        let res = await mainStore.refreshCompanyCode(idTenant);
        if (res.status == 200) {
            company_code.value = res.code;
            showPopup({
                status: true,
                title: "Berhasil!",
                type: "success",
                message: `Kode company ${company_code.value} berhasil diperbarui!`,
            });
        }
    } catch (error) {
        console.error("Gagal memperbarui kode:", error);
        showPopup({
            status: true,
            title: "Gagal!",
            type: "danger",
            message: "Gagal memperbarui kode company",
        });
    } finally {
        isRefreshing.value = false;
    }
};
const showPopup = (state) => {
    mainStore.stateShowInfo = state;
};

onBeforeMount(async () => {
    const profile = await mainStore.getProfile();
    if (profile.status == 200) {
        user.value = profile.user;
    }
});

watch(
    portal,
    (val) => {
        if (val.length > 0) {
            getRecruiter();
            getRoles();
        }
    },
    { deep: true, immediate: true }
);
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
