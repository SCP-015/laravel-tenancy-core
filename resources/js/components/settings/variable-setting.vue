<template>
    <div>
        <div class="flex items-center mb-5 mt-3 sm:mt-3">
            <div>
                <h1
                    class="text-[20px] sm:text-[22px] font-semibold"
                    :class="{ 'ml-7': collapsed }"
                >
                    Variabel
                </h1>
                <h3 class="text-[14px]">
                    Mengatur variabel yang terdapat pada kolom Form Pelamar dan
                    Form Kualifikasi
                </h3>
            </div>
        </div>

        <tabs-list
            class="mt-3"
            :currentTab="currentTab"
            :tabs="listTab"
            @update-tab="(newValue) => (currentTab = newValue)"
        ></tabs-list>

        <div class="text-[#222222] font-[500] mt-4">
            {{ listTab?.find((item) => item.id == currentTab)?.name }}
        </div>

        <div class="flex">
            <div class="mt-3">
                <div class="text-[#000000] text-[14px] text-[500] mb-1">
                    {{ selectedItem?.id ? "Ubah" : "Tambah Baru" }}
                </div>
                <div class="flex flex-nowrap">
                    <Input
                        v-model="new_item"
                        :type="'text'"
                        :classWidth="'w-[100%] sm:w-[250px]'"
                    />
                </div>
            </div>

            <div class="mt-3 ml-3" v-if="currentTab == 1">
                <div class="text-[#000000] text-[14px] text-[500] mb-1">
                    Pilih Parent
                </div>
                <v-select
                    v-model="selected_parent"
                    class="w-[100%] my-3 sm:my-0 sm:w-[250px] text-[14px] rounded-md text-[#4A4A4A] focus:outline-none focus:ring-2 focus:ring-blue-500"
                    label="name"
                    :options="parentOptions"
                    :clearable="false"
                    :placeholder="'Pilih Parent'"
                    :reduce="(e) => e.id"
                ></v-select>
            </div>

            <div class="flex items-end mt-3">
                <div class="ml-3 flex flex-nowrap">
                    <button
                        v-if="selectedItem?.id || new_item.trim() != ''"
                        class="bg-[#f2f2f2] text-[14px] px-5 py-3 rounded-md transition me-2 cursor-pointer"
                        @click="cancelSave()"
                    >
                        Cancel
                    </button>
                    <button
                        :disabled="new_item.trim() == '' || new_item.length < 2"
                        class="bg-[#00852C] text-[#fff] text-[14px] px-5 py-3 rounded-md transition cursor-pointer flex flex-nowrap"
                        @click="saveItem()"
                    >
                        {{ selectedItem?.id ? "Simpan" : "Tambah" }}
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
                    </button>
                </div>
            </div>
        </div>

        <div
            class="wrapper-list-item bg-[#FBFBFB] rounded-[6px] px-4 py-3 my-4"
        >
            <template v-if="loading || loadingMove">
                <template v-for="i in 2">
                    <div class="skeleton h-8 mb-3 w-45"></div>
                    <div class="skeleton h-8 mb-3 w-55"></div>
                    <div class="skeleton h-8 mb-3 w-35"></div>
                    <div class="skeleton h-8 mb-3 w-65"></div>
                </template>
            </template>
            <template v-else>
                <error-message
                    v-if="isError"
                    :error-data="errorData"
                    @getData="getData()"
                ></error-message>
                <template v-else>
                    <!-- View khusus Posisi Pekerjaan: gunakan default-table dengan pagination & search -->
                    <template v-if="currentTab === 1">
                        <div class="flex justify-between items-center mb-3">
                            <div class="flex items-center gap-3">
                                <label class="inline-flex items-center text-[13px] text-gray-700">
                                    <input
                                        type="checkbox"
                                        v-model="filterManualJobPositions"
                                        class="mr-2 rounded border-gray-300"
                                    />
                                    <span>Tampilkan hanya posisi yang belum tersinkron ke Nusawork</span>
                                </label>
                            </div>
                            <button
                                type="button"
                                class="bg-[#3A3A3A] text-white px-6 py-2 rounded-md shadow-sm transition hover:bg-gray-800 text-[14px]"
                                @click="openParentMode"
                            >
                                Lihat struktur parent
                            </button>
                        </div>
                        <default-table
                            :fields="fieldsJobPosition"
                            :items="jobPositionItems"
                            :wrapperTableClass="'max-h-[400px]'"
                            :total-data="totalJobPosition"
                            :is-skeleton="loading"
                            :is-loading="loading"
                            :page="currentPageJobPosition"
                            :itemsPerPage="perPageJobPosition"
                            :error-message="errorData"
                            :rowSkeleton="4"
                            @update-page="handleJobPositionPageChange"
                            @update-items-per-page="handleJobPositionPerPageChange"
                            @getData="getData()"
                            @pageClick="getData()"
                            @search="onSearchJobPosition"
                        >
                            <template #customCell(parent)="{ item }">
                                <span class="text-[13px] text-gray-700">
                                    {{
                                        item.id_parent
                                            ? (list.find((p) => p.id === item.id_parent)?.name || '-')
                                            : '-'
                                    }}
                                </span>
                            </template>

                            <template #customCell(children_count)="{ item }">
                                <span class="text-[13px] text-gray-700">
                                    {{ (item.children && item.children.length) || 0 }}
                                </span>
                            </template>

                            <template #customCell(nusawork_name)="{ item }">
                                <span class="text-[13px] text-gray-700">
                                    {{ item.nusawork_name || '-' }}
                                </span>
                            </template>

                            <template #customCell(source)="{ item }">
                                <span class="text-[12px] px-2 py-1 rounded-full border border-gray-300 text-gray-700">
                                    {{ item.nusawork_id ? 'Terhubung ke Nusawork' : 'Belum terhubung ke Nusawork' }}
                                </span>
                            </template>

                            <template #customCell(action)="{ item }">
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        class="text-[12px] text-gray-700 hover:text-gray-900 underline cursor-pointer"
                                        @click="editLevel(item)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="text-[12px] text-red-600 hover:text-red-800 underline cursor-pointer"
                                        @click="initDelete(item)"
                                    >
                                        Hapus
                                    </button>
                                    <button
                                        type="button"
                                        class="text-[12px] text-[#00852C] hover:text-green-700 underline cursor-pointer"
                                        @click="openSyncNusaworkModal(item)"
                                    >
                                        {{ item.nusawork_id ? "Ganti Nusawork" : "Sync Nusawork" }}
                                    </button>
                                </div>
                            </template>
                        </default-table>
                    </template>

                    <!-- View lama (draggable list) untuk tab selain Posisi Pekerjaan -->
                    <template v-else>
                        <draggable
                            :list="list"
                            :value="list"
                            class="item-container bd-left no-wrap"
                            tag="ul"
                            v-bind="dragOptions"
                            @change="changePosition"
                            @start="drag = true"
                            @end="drag = false"
                        >
                            <template v-for="element in list" :key="element.id">
                                <li
                                    :id="`level-${element.id}`"
                                    class="bd-group is-cursor"
                                    :class="{
                                        'cursor-grab': currentTab !== 1,
                                    }"
                                >
                                    <div class="flex items-center">
                                        <span class="gr-wg" />
                                    </div>
                                    <div
                                        :id="`item-${element.id}`"
                                        class="res pointer flex"
                                    >
                                        <img
                                            v-if="currentTab !== 1"
                                            src="/images/unfold_more.svg"
                                            alt="draggable"
                                        />

                                        {{
                                            element.name.length <= 52
                                                ? element.name
                                                : `${element.name.slice(0, 52)}...`
                                        }}

                                        <span class="ps-2 flex items-center gap-2">
                                            <span
                                                v-if="currentTab === 2 || currentTab === 3"
                                                class="text-[11px] text-gray-600"
                                            >
                                                Nusawork:
                                                <span class="font-medium">
                                                    {{ element.nusawork_name || '-' }}
                                                </span>
                                            </span>

                                            <button
                                                v-if="currentTab === 2"
                                                type="button"
                                                class="text-[12px] text-[#00852C] hover:text-green-700 underline cursor-pointer"
                                                @click="openSyncNusaworkJobLevelModal(element)"
                                            >
                                                {{
                                                    element.nusawork_id
                                                        ? 'Ganti Nusawork'
                                                        : 'Sync Nusawork'
                                                }}
                                            </button>

                                            <button
                                                v-if="currentTab === 3"
                                                type="button"
                                                class="text-[12px] text-[#00852C] hover:text-green-700 underline cursor-pointer"
                                                @click="openSyncNusaworkEducationLevelModal(element)"
                                            >
                                                {{
                                                    element.nusawork_id
                                                        ? 'Ganti Nusawork'
                                                        : 'Sync Nusawork'
                                                }}
                                            </button>

                                            <button
                                                class="text-[14px] ml-5 p-0 border-0 cursor-pointer"
                                                @click="editLevel(element)"
                                            >
                                                <img
                                                    src="/images/edit_pin.svg"
                                                    alt="icon"
                                                />
                                            </button>

                                            <button
                                                class="text-[14px] p-0 border-0 cursor-pointer ml-2"
                                                @click="initDelete(element)"
                                            >
                                                <img
                                                    src="/images/trash_pin.svg"
                                                    alt="icon"
                                                    class="me-2"
                                                />
                                            </button>
                                        </span>
                                    </div>
                                </li>
                            </template>
                        </draggable>
                    </template>
                </template>
            </template>
        </div>

        <!-- Confirm Delete -->
        <Modal
            :show="showConfirm"
            :use-close="true"
            :use-footer="true"
            @update-show="(newValue) => (showConfirm = newValue)"
        >
            <template #header>
                <div class="px-6 py-6 text-[18px] font-[600]">
                    Konfirmasi Hapus
                </div>
            </template>
            <template #body>
                <div class="text-[14px] text-[#3A3A3A]">
                    Apakah anda yakin ingin menghapus
                    <span class="font-[600]">{{ selectedDelete?.name }}</span
                    >, dari
                    <span class="font-[600]">
                        {{
                            currentTab == 1
                                ? "Posisi Pekerjaan"
                                : currentTab == 2
                                ? "Tingkat Pekerjaan"
                                : currentTab == 3
                                ? "Edukasi"
                                : "Level Pengalaman"
                        }} </span
                    >?
                </div>
            </template>

            <template #footer>
                <div class="flex justify-content-end">
                    <button
                        class="mr-3 cursor-pointer flex items-center gap-2 bg-[#F6F6F6] text-[#3A3A3A] text-[14px] px-4 py-2 rounded-md transition"
                        @click="cancelDelete()"
                    >
                        {{ "Cancel" }}
                    </button>
                    <button
                        class="cursor-pointer flex items-center gap-2 bg-[#3A3A3A] text-[#fff] text-[14px] px-4 py-2 rounded-md transition"
                        @click="doDelete()"
                    >
                        {{ "Delete" }}
                        <span
                            v-if="loadingDelete"
                            class="ml-1 d-flex align-center"
                        >
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

        <!-- Sinkronisasi Edukasi dengan Nusawork -->
        <Modal
            :show="showSyncNusaworkEducationLevelModal"
            :use-close="true"
            :use-footer="true"
            maxWidth="2xl"
            @update-show="(newValue) => (showSyncNusaworkEducationLevelModal = newValue)"
        >
            <template #header>
                <div class="px-6 py-6 text-[18px] font-[600]">
                    Sinkronkan Edukasi dengan Nusawork
                </div>
            </template>
            <template #body>
                <div class="px-6 pb-4">
                    <div class="text-[14px] text-[#3A3A3A] mb-3">
                        Pilih Education Level dari Nusawork untuk
                        <span class="font-[600]">
                            {{ selectedEducationLevelForSync?.name || '-' }}
                        </span>
                    </div>

                    <div
                        v-if="loadingNusaworkMaster"
                        class="flex items-center gap-2 text-[13px] text-gray-600"
                    >
                        <img
                            src="/images/loading.gif"
                            alt="loading"
                            width="18"
                        />
                        <span>Mengambil data education level dari Nusawork...</span>
                    </div>

                    <div v-else>
                        <v-select
                            v-model="selectedNusaworkEducationLevelId"
                            class="w-full text-[14px] rounded-md text-[#4A4A4A] focus:outline-none focus:ring-2 focus:ring-blue-500"
                            label="value"
                            :options="nusaworkEducationLevels"
                            :clearable="true"
                            :placeholder="'Pilih Education Level Nusawork'"
                            :reduce="(e) => e.id"
                        ></v-select>
                        <p class="mt-2 text-[11px] text-gray-500">
                            Kosongkan pilihan untuk menghapus hubungan edukasi dengan Nusawork.
                        </p>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end px-6 pb-4 gap-3">
                    <button
                        class="mr-3 cursor-pointer flex items-center gap-2 bg-[#F6F6F6] text-[#3A3A3A] text-[14px] px-4 py-2 rounded-md transition"
                        @click="showSyncNusaworkEducationLevelModal = false"
                        :disabled="loadingSyncNusawork"
                    >
                        Batal
                    </button>
                    <button
                        class="cursor-pointer flex items-center gap-2 bg-[#3A3A3A] text-[#fff] text-[14px] px-4 py-2 rounded-md transition"
                        @click="saveSyncNusaworkEducationLevel()"
                        :disabled="loadingSyncNusawork || loadingNusaworkMaster"
                    >
                        <span
                            v-if="loadingSyncNusawork"
                            class="ml-1 d-flex align-center"
                        >
                            <img
                                src="/images/loading.gif"
                                alt="loading"
                                width="18"
                            />
                        </span>
                        <span v-else>Simpan</span>
                    </button>
                </div>
            </template>
        </Modal>

        <!-- Sinkronisasi Tingkat Pekerjaan dengan Nusawork -->
        <Modal
            :show="showSyncNusaworkJobLevelModal"
            :use-close="true"
            :use-footer="true"
            maxWidth="2xl"
            @update-show="(newValue) => (showSyncNusaworkJobLevelModal = newValue)"
        >
            <template #header>
                <div class="px-6 py-6 text-[18px] font-[600]">
                    Sinkronkan Tingkat Pekerjaan dengan Nusawork
                </div>
            </template>
            <template #body>
                <div class="px-6 pb-4">
                    <div class="text-[14px] text-[#3A3A3A] mb-3">
                        Pilih Job Level dari Nusawork untuk
                        <span class="font-[600]">
                            {{ selectedJobLevelForSync?.name || '-' }}
                        </span>
                    </div>

                    <div
                        v-if="loadingNusaworkMaster"
                        class="flex items-center gap-2 text-[13px] text-gray-600"
                    >
                        <img
                            src="/images/loading.gif"
                            alt="loading"
                            width="18"
                        />
                        <span>Mengambil data job level dari Nusawork...</span>
                    </div>

                    <div v-else>
                        <v-select
                            v-model="selectedNusaworkJobLevelId"
                            class="w-full text-[14px] rounded-md text-[#4A4A4A] focus:outline-none focus:ring-2 focus:ring-blue-500"
                            label="name"
                            :options="nusaworkJobLevels"
                            :clearable="true"
                            :placeholder="'Pilih Job Level Nusawork'"
                            :reduce="(e) => e.id"
                        ></v-select>
                        <p class="mt-2 text-[11px] text-gray-500">
                            Kosongkan pilihan untuk menghapus hubungan tingkat pekerjaan dengan Nusawork.
                        </p>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end px-6 pb-4 gap-3">
                    <button
                        class="mr-3 cursor-pointer flex items-center gap-2 bg-[#F6F6F6] text-[#3A3A3A] text-[14px] px-4 py-2 rounded-md transition"
                        @click="showSyncNusaworkJobLevelModal = false"
                        :disabled="loadingSyncNusawork"
                    >
                        Batal
                    </button>
                    <button
                        class="cursor-pointer flex items-center gap-2 bg-[#3A3A3A] text-[#fff] text-[14px] px-4 py-2 rounded-md transition"
                        @click="saveSyncNusaworkJobLevel()"
                        :disabled="loadingSyncNusawork || loadingNusaworkMaster"
                    >
                        <span
                            v-if="loadingSyncNusawork"
                            class="ml-1 d-flex align-center"
                        >
                            <img
                                src="/images/loading.gif"
                                alt="loading"
                                width="18"
                            />
                        </span>
                        <span v-else>Simpan</span>
                    </button>
                </div>
            </template>
        </Modal>

        <!-- Parent Mode: Struktur Posisi Pekerjaan -->
        <Modal
            :show="showParentModal"
            :use-close="true"
            :use-footer="false"
            maxWidth="3xl"
            @update-show="(newValue) => (showParentModal = newValue)"
        >
            <template #header>
                <div class="px-6 py-6 text-[18px] font-[600]">
                    Struktur Posisi Pekerjaan
                </div>
            </template>
            <template #body>
                <div class="px-6 pb-6">
                    <div
                        v-if="loadingParentHierarchy"
                        class="flex justify-center py-8"
                    >
                        <img
                            src="/images/loading.gif"
                            alt="loading"
                            width="24"
                        />
                    </div>
                    <div v-else>
                        <div
                            v-if="!parentJobPositions.length"
                            class="text-[13px] text-gray-600"
                        >
                            Belum ada struktur parent yang dapat ditampilkan.
                        </div>
                        <ul
                            v-else
                            class="space-y-3 max-h-[400px] overflow-y-auto"
                        >
                            <JobPositionTreeNode
                                v-for="parent in parentJobPositions"
                                :key="parent.id"
                                :node="parent"
                                :children-map="jobPositionChildrenMap"
                            />
                        </ul>
                    </div>
                </div>
            </template>
        </Modal>

        <!-- Sinkronisasi Job Position dengan Nusawork -->
        <Modal
            :show="showSyncNusaworkModal"
            :use-close="true"
            :use-footer="true"
            maxWidth="2xl"
            @update-show="(newValue) => (showSyncNusaworkModal = newValue)"
        >
            <template #header>
                <div class="px-6 py-6 text-[18px] font-[600]">
                    Sinkronkan dengan Nusawork
                </div>
            </template>
            <template #body>
                <div class="px-6 pb-4">
                    <div class="text-[14px] text-[#3A3A3A] mb-3">
                        Pilih Job Position dari Nusawork untuk
                        <span class="font-[600]">
                            {{ selectedJobPositionForSync?.name || "-" }}
                        </span>
                    </div>

                    <div v-if="loadingNusaworkMaster" class="flex items-center gap-2 text-[13px] text-gray-600">
                        <img
                            src="/images/loading.gif"
                            alt="loading"
                            width="18"
                        />
                        <span>Mengambil data job position dari Nusawork...</span>
                    </div>

                    <div v-else>
                        <v-select
                            v-model="selectedNusaworkPositionId"
                            class="w-full text-[14px] rounded-md text-[#4A4A4A] focus:outline-none focus:ring-2 focus:ring-blue-500"
                            label="name"
                            :options="nusaworkJobPositions"
                            :clearable="true"
                            :placeholder="'Pilih Job Position Nusawork'"
                            :reduce="(e) => e.id"
                        ></v-select>
                        <p class="mt-2 text-[11px] text-gray-500">
                            Kosongkan pilihan untuk menghapus hubungan dengan Nusawork.
                        </p>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end px-6 pb-4 gap-3">
                    <button
                        class="mr-3 cursor-pointer flex items-center gap-2 bg-[#F6F6F6] text-[#3A3A3A] text-[14px] px-4 py-2 rounded-md transition"
                        @click="showSyncNusaworkModal = false"
                        :disabled="loadingSyncNusawork"
                    >
                        Batal
                    </button>
                    <button
                        class="cursor-pointer flex items-center gap-2 bg-[#3A3A3A] text-[#fff] text-[14px] px-4 py-2 rounded-md transition"
                        @click="saveSyncNusawork()"
                        :disabled="loadingSyncNusawork || loadingNusaworkMaster"
                    >
                        <span v-if="loadingSyncNusawork" class="ml-1 d-flex align-center">
                            <img
                                src="/images/loading.gif"
                                alt="loading"
                                width="18"
                            />
                        </span>
                        <span v-else>Simpan</span>
                    </button>
                </div>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, reactive, defineComponent } from "vue";
import { VueDraggableNext as draggable } from "vue-draggable-next";
import { useMainStore } from "../../stores";
import Modal from "../../components/modal.vue";
const props = defineProps({
    collapsed: {
        type: Boolean,
        default: false,
    },
});
const mainStore = useMainStore();
// State
let listTab = ref([
        {
            id: 1,
            name: "Posisi Pekerjaan",
        },
        {
            id: 2,
            name: "Tingkat Pekerjaan",
        },
        {
            id: 3,
            name: "Edukasi",
        },
        {
            id: 4,
            name: "Level Pengalaman",
        },
    ]),
    currentTab = ref(1),
    new_item = ref(""),
    drag = ref(false),
    list = ref([]),
    loading = ref(false),
    isError = ref(false),
    errorData = reactive({
        code: null,
        message: "",
    }),
    selectedItem = ref(null),
    selected_parent = ref(null),
    loadingSave = ref(false),
    loadingMove = ref(false),
    showConfirm = ref(false),
    loadingDelete = ref(false),
    selectedDelete = ref(null),
    fieldsJobPosition = ref([
        { label: "Nama Posisi", key: "name" },
        { label: "Parent", key: "parent" },
        { label: "Jumlah Anak", key: "children_count" },
        { label: "Nusawork", key: "nusawork_name" },
        { label: "Sumber", key: "source" },
        { label: "Aksi", key: "action" },
    ]),
    currentPageJobPosition = ref(1),
    perPageJobPosition = ref(15),
    totalJobPosition = ref(0),
    searchJobPosition = ref(""),
    filterManualJobPositions = ref(false),
    showParentModal = ref(false),
    loadingParentHierarchy = ref(false),
    jobPositionHierarchy = ref([]),
    showSyncNusaworkModal = ref(false),
    loadingNusaworkMaster = ref(false),
    loadingSyncNusawork = ref(false),
    nusaworkJobPositions = ref([]),
    nusaworkJobLevels = ref([]),
    nusaworkEducationLevels = ref([]),
    selectedJobPositionForSync = ref(null),
    selectedNusaworkPositionId = ref(null),
    showSyncNusaworkJobLevelModal = ref(false),
    selectedJobLevelForSync = ref(null),
    selectedNusaworkJobLevelId = ref(null),
    showSyncNusaworkEducationLevelModal = ref(false),
    selectedEducationLevelForSync = ref(null),
    selectedNusaworkEducationLevelId = ref(null);
// End

// Effects
const portal = computed(() => mainStore.userPortal.value);
const dragOptions = computed(() => {
    return {
        animation: 200,
        group: "description",
        disabled: currentTab.value == 1 || loadingMove.value,
        ghostClass: "ghost",
    };
});
const jobPositionItems = computed(() => {
    if (currentTab.value !== 1) {
        return [];
    }

    return Array.isArray(list.value) ? list.value : [];
});
const jobPositionChildrenMap = computed(() => {
    const map = {};

    if (!Array.isArray(jobPositionHierarchy.value)) {
        return map;
    }

    jobPositionHierarchy.value.forEach((item) => {
        const parentId = item.id_parent || null;
        if (!map[parentId]) {
            map[parentId] = [];
        }
        map[parentId].push(item);
    });

    return map;
});
const parentJobPositions = computed(() => {
    return jobPositionChildrenMap.value[null] || [];
});
const parentOptions = computed(() => {
    if (!Array.isArray(jobPositionHierarchy.value)) {
        return [];
    }

    const currentId = selectedItem.value?.id;

    if (!currentId) {
        return jobPositionHierarchy.value;
    }

    return jobPositionHierarchy.value.filter((item) => item.id !== currentId);
});
watch(currentTab, (newValue) => {
    if (newValue === 1) {
        currentPageJobPosition.value = 1;
        searchJobPosition.value = "";
        loadParentHierarchy();
    }

    try {
        localStorage.setItem("variable-setting-current-tab", String(newValue));
    } catch (e) {
        // ignore storage error
    }

    getData();
});
watch(filterManualJobPositions, () => {
    if (currentTab.value === 1) {
        currentPageJobPosition.value = 1;
        getData();
    }
});
// End

const JobPositionTreeNode = defineComponent({
    name: "JobPositionTreeNode",
    props: {
        node: {
            type: Object,
            required: true,
        },
        childrenMap: {
            type: Object,
            required: true,
        },
    },
    setup(props) {
        const children = computed(() => props.childrenMap[props.node.id] || []);

        return {
            children,
        };
    },
    template: `
        <li class="relative">
            <div class="flex items-center">
                <span class="text-[13px] text-gray-900 font-medium">{{ node.name }}</span>
            </div>
            <ul
                v-if="children.length"
                class="mt-1 ml-4 pl-3 border-l border-gray-200 space-y-1"
            >
                <JobPositionTreeNode
                    v-for="child in children"
                    :key="child.id"
                    :node="child"
                    :children-map="childrenMap"
                />
            </ul>
        </li>
    `,
});

// Lifecycle
onMounted(() => {
    let initialTab = 1;

    try {
        const storedTab = localStorage.getItem("variable-setting-current-tab");
        const parsedTab = storedTab ? Number(storedTab) : NaN;
        if ([1, 2, 3, 4].includes(parsedTab)) {
            initialTab = parsedTab;
        }
    } catch (e) {
        initialTab = 1;
    }

    if (initialTab !== currentTab.value) {
        currentTab.value = initialTab;
        // watcher currentTab akan memanggil getData()
    } else {
        getData();
    }

    if (currentTab.value === 1) {
        loadParentHierarchy();
    }
});

// Methods
async function getData() {
    try {
        loading.value = true;
        isError.value = false;
        Object.assign(errorData, { code: null, message: "" });
        list.value = [];

        const portalId =
            portal.value[0]?.id ||
            JSON.parse(localStorage.getItem("portal"))[0]?.id;

        const endpoints = {
            1: { url: "job-positions", key: "get-job-position" },
            2: { url: "job-levels", key: "get-job-level" },
            3: { url: "education-levels", key: "get-education-level" },
            4: { url: "experience-levels", key: "get-experience-level" },
        };

        const { url, key } = endpoints[currentTab.value] || {};
        if (!url) throw new Error("Something went wrong!");

        let endpointUrl = `${portalId}/api/settings/${url}`;

        // Untuk Posisi Pekerjaan, gunakan pagination, search, dan filter sumber di server
        if (currentTab.value === 1) {
            const params = new URLSearchParams();
            params.append("per_page", perPageJobPosition.value);
            params.append("page", currentPageJobPosition.value);
            if (searchJobPosition.value.trim() !== "") {
                params.append("search", searchJobPosition.value.trim());
            }
            if (filterManualJobPositions.value) {
                params.append("source", "manual");
            }

            endpointUrl = `${endpointUrl}?${params.toString()}`;
        }

        const res = await mainStore.useAPI(
            endpointUrl,
            { method: "GET", cache: "no-cache", key },
            true
        );

        if (res?.status === 200) {
            if (currentTab.value === 1 && res.data && res.data.items) {
                list.value = res.data.items;
                const meta = res.data.meta || {};
                totalJobPosition.value = meta.total || list.value.length;
                if (meta.current_page) {
                    currentPageJobPosition.value = meta.current_page;
                }
                if (meta.per_page) {
                    perPageJobPosition.value = meta.per_page;
                }
            } else {
                list.value = res.data;
            }
        }
    } catch (err) {
        console.error("err", err);
        isError.value = true;
        Object.assign(errorData, {
            code: err.response?.status || err.status,
            message: err.response?.data?.message || err.message,
        });
    } finally {
        loading.value = false;
    }
}

const saveItem = async () => {
    if (
        loadingSave.value ||
        new_item.value.trim() == "" ||
        new_item.value.length < 2
    )
        return;

    loadingSave.value = true;

    try {
        const portalId =
            portal.value[0]?.id ||
            JSON.parse(localStorage.getItem("portal"))[0].id;

        const tabConfig = {
            1: {
                path: "job-positions",
                key: "job-position",
                extraPayload: { id_parent: selected_parent.value },
            },
            2: { path: "job-levels", key: "job-level" },
            3: { path: "education-levels", key: "education-level" },
            4: { path: "experience-levels", key: "experience-level" },
        };

        const {
            path,
            key,
            extraPayload = {},
        } = tabConfig[currentTab.value] || {};
        if (!path) {
            loadingSave.value = false;
            return;
        }

        const isEdit = Boolean(selectedItem.value?.id);
        const url = `${portalId}/api/settings/${path}${
            isEdit ? `/${selectedItem.value.id}` : ""
        }`;
        const payload = {
            name: new_item.value,
            ...(currentTab.value === 1
                ? extraPayload
                : {
                      index: isEdit
                          ? selectedItem.value.index
                          : list.value.length,
                  }),
        };

        const method = isEdit ? "PUT" : "POST";
        const res = await mainStore.useAPI(
            url,
            {
                method,
                cache: "no-cache",
                key: `${isEdit ? "put" : "post"}-${key}`,
                body: payload,
            },
            true
        );

        if ([200, 201].includes(res?.status)) {
            showPopup({
                status: true,
                title: "Berhasil!",
                type: "success",
                message: res?.message || "Data berhasil disimpan!",
            });
            cancelSave();
            getData();
        }
    } catch (err) {
        const errors =
            err.response?.errors || err.response?.message || err.message;
        showPopup({
            status: true,
            title: err.status >= 500 ? "Error" : "Warning",
            type: err.status >= 500 ? "error" : "warning",
            message: errors,
        });
    } finally {
        loadingSave.value = false;
    }
};

const cancelSave = () => {
    selectedItem.value = null;
    new_item.value = "";
    selected_parent.value = null;
};

const onSearchJobPosition = (value) => {
    searchJobPosition.value = value || "";
    currentPageJobPosition.value = 1;
    if (currentTab.value === 1) {
        getData();
    }
};

const handleJobPositionPageChange = (page) => {
    currentPageJobPosition.value = page;
};

const handleJobPositionPerPageChange = (val) => {
    perPageJobPosition.value = val;
    currentPageJobPosition.value = 1;
    if (currentTab.value === 1) {
        getData();
    }
};

const openParentMode = () => {
    showParentModal.value = true;
    loadParentHierarchy();
};

const openSyncNusaworkModal = (item) => {
    selectedJobPositionForSync.value = item;
    selectedNusaworkPositionId.value = item.nusawork_id || null;

    showSyncNusaworkModal.value = true;

    if (!nusaworkJobPositions.value.length) {
        loadNusaworkMasterData();
    }
};

const openSyncNusaworkJobLevelModal = (item) => {
    selectedJobLevelForSync.value = item;
    selectedNusaworkJobLevelId.value = item.nusawork_id || null;

    showSyncNusaworkJobLevelModal.value = true;

    if (!nusaworkJobLevels.value.length) {
        loadNusaworkMasterData();
    }
};

const openSyncNusaworkEducationLevelModal = (item) => {
    selectedEducationLevelForSync.value = item;
    selectedNusaworkEducationLevelId.value = item.nusawork_id || null;

    showSyncNusaworkEducationLevelModal.value = true;

    if (!nusaworkEducationLevels.value.length) {
        loadNusaworkMasterData();
    }
};

const showPopup = (state) => {
    mainStore.stateShowInfo = state;
};

const loadParentHierarchy = async () => {
    try {
        loadingParentHierarchy.value = true;

        const portalId =
            portal.value[0]?.id ||
            JSON.parse(localStorage.getItem("portal"))[0]?.id;

        const res = await mainStore.useAPI(
            `${portalId}/api/settings/job-positions`,
            {
                method: "GET",
                cache: "no-cache",
                key: "get-job-position-hierarchy",
            },
            true
        );

        if (res?.status === 200 && Array.isArray(res.data)) {
            jobPositionHierarchy.value = res.data;
        } else {
            jobPositionHierarchy.value = [];
        }
    } catch (err) {
        console.error("err", err);
        showPopup({
            status: true,
            title: err.status >= 500 ? "Error" : "Warning",
            type: err.status >= 500 ? "error" : "warning",
            message: err.response?.data?.message || err.message,
        });
    } finally {
        loadingParentHierarchy.value = false;
    }
};

const loadNusaworkMasterData = async () => {
    try {
        loadingNusaworkMaster.value = true;

        const portalId =
            portal.value[0]?.id ||
            JSON.parse(localStorage.getItem("portal"))[0]?.id;

        const res = await mainStore.useAPI(
            `${portalId}/api/integrations/nusawork/master-data`,
            {
                method: "GET",
                cache: "no-cache",
                key: "get-nusawork-master-data",
            },
            true
        );

        if (res?.status === 200 && res.data) {
            const masterData = res.data || {};
            nusaworkJobPositions.value = masterData.job_position || [];
            nusaworkJobLevels.value = masterData.job_level || [];
            nusaworkEducationLevels.value = masterData.education || [];
        } else {
            nusaworkJobPositions.value = [];
            nusaworkJobLevels.value = [];
            nusaworkEducationLevels.value = [];
        }
    } catch (err) {
        console.error("err", err);
        showPopup({
            status: true,
            title: err.status >= 500 ? "Error" : "Warning",
            type: err.status >= 500 ? "error" : "warning",
            message: err.response?.data?.message || err.message,
        });
        showSyncNusaworkModal.value = false;
        selectedJobPositionForSync.value = null;
        selectedNusaworkPositionId.value = null;
        showSyncNusaworkJobLevelModal.value = false;
        selectedJobLevelForSync.value = null;
        selectedNusaworkJobLevelId.value = null;
        showSyncNusaworkEducationLevelModal.value = false;
        selectedEducationLevelForSync.value = null;
        selectedNusaworkEducationLevelId.value = null;
    } finally {
        loadingNusaworkMaster.value = false;
    }
};

const saveSyncNusaworkJobLevel = async () => {
    if (!selectedJobLevelForSync.value) {
        showSyncNusaworkJobLevelModal.value = false;
        return;
    }

    try {
        loadingSyncNusawork.value = true;

        const portalId =
            portal.value[0]?.id ||
            JSON.parse(localStorage.getItem("portal"))[0]?.id;

        const selectedNusawork =
            nusaworkJobLevels.value.find(
                (item) => item.id === selectedNusaworkJobLevelId.value
            ) || null;

        const url = `${portalId}/api/settings/job-levels/${selectedJobLevelForSync.value.id}`;

        const payload = {
            name: selectedJobLevelForSync.value.name,
            index: selectedJobLevelForSync.value.index,
            nusawork_id: selectedNusaworkJobLevelId.value || null,
            nusawork_name: selectedNusawork ? selectedNusawork.name : null,
        };

        const res = await mainStore.useAPI(
            url,
            {
                method: "PUT",
                cache: "no-cache",
                key: `put-job-level-nusawork-${selectedJobLevelForSync.value.id}`,
                body: payload,
            },
            true
        );

        if ([200, 201].includes(res?.status)) {
            showPopup({
                status: true,
                title: "Berhasil!",
                type: "success",
                message:
                    res?.message ||
                    (selectedNusaworkJobLevelId.value
                        ? "Tingkat pekerjaan berhasil dihubungkan dengan Nusawork."
                        : "Hubungan tingkat pekerjaan dengan Nusawork berhasil dihapus."),
            });

            showSyncNusaworkJobLevelModal.value = false;
            selectedJobLevelForSync.value = null;
            await getData();
        }
    } catch (err) {
        console.error("err", err);
        const errors =
            err.response?.errors || err.response?.message || err.message;
        showPopup({
            status: true,
            title: err.status >= 500 ? "Error" : "Warning",
            type: err.status >= 500 ? "error" : "warning",
            message: errors,
        });
    } finally {
        loadingSyncNusawork.value = false;
    }
};

const saveSyncNusaworkEducationLevel = async () => {
    if (!selectedEducationLevelForSync.value) {
        showSyncNusaworkEducationLevelModal.value = false;
        return;
    }

    try {
        loadingSyncNusawork.value = true;

        const portalId =
            portal.value[0]?.id ||
            JSON.parse(localStorage.getItem("portal"))[0]?.id;

        const selectedNusawork =
            nusaworkEducationLevels.value.find(
                (item) => item.id === selectedNusaworkEducationLevelId.value
            ) || null;

        const url = `${portalId}/api/settings/education-levels/${selectedEducationLevelForSync.value.id}`;

        const payload = {
            name: selectedEducationLevelForSync.value.name,
            index: selectedEducationLevelForSync.value.index,
            nusawork_id: selectedNusaworkEducationLevelId.value || null,
            nusawork_name: selectedNusawork ? selectedNusawork.value : null,
        };

        const res = await mainStore.useAPI(
            url,
            {
                method: "PUT",
                cache: "no-cache",
                key: `put-education-level-nusawork-${selectedEducationLevelForSync.value.id}`,
                body: payload,
            },
            true
        );

        if ([200, 201].includes(res?.status)) {
            showPopup({
                status: true,
                title: "Berhasil!",
                type: "success",
                message:
                    res?.message ||
                    (selectedNusaworkEducationLevelId.value
                        ? "Edukasi berhasil dihubungkan dengan Nusawork."
                        : "Hubungan edukasi dengan Nusawork berhasil dihapus."),
            });

        
            showSyncNusaworkEducationLevelModal.value = false;
            selectedEducationLevelForSync.value = null;
            await getData();
        }
    } catch (err) {
        console.error("err", err);
        const errors =
            err.response?.errors || err.response?.message || err.message;
        showPopup({
            status: true,
            title: err.status >= 500 ? "Error" : "Warning",
            type: err.status >= 500 ? "error" : "warning",
            message: errors,
        });
    } finally {
        loadingSyncNusawork.value = false;
    }
};

const saveSyncNusawork = async () => {
    if (!selectedJobPositionForSync.value) {
        showSyncNusaworkModal.value = false;
        return;
    }

    try {
        loadingSyncNusawork.value = true;

        const portalId =
            portal.value[0]?.id ||
            JSON.parse(localStorage.getItem("portal"))[0]?.id;

        const selectedNusawork =
            nusaworkJobPositions.value.find(
                (item) => item.id === selectedNusaworkPositionId.value
            ) || null;

        const url = `${portalId}/api/settings/job-positions/${selectedJobPositionForSync.value.id}`;

        const payload = {
            name: selectedJobPositionForSync.value.name,
            nusawork_id: selectedNusaworkPositionId.value || null,
            nusawork_name: selectedNusawork ? selectedNusawork.name : null,
        };

        const res = await mainStore.useAPI(
            url,
            {
                method: "PUT",
                cache: "no-cache",
                key: `put-job-position-nusawork-${selectedJobPositionForSync.value.id}`,
                body: payload,
            },
            true
        );

        if ([200, 201].includes(res?.status)) {
            showPopup({
                status: true,
                title: "Berhasil!",
                type: "success",
                message:
                    res?.message ||
                    (selectedNusaworkPositionId.value
                        ? "Job position berhasil dihubungkan dengan Nusawork."
                        : "Hubungan dengan Nusawork berhasil dihapus."),
            });

            showSyncNusaworkModal.value = false;
            selectedJobPositionForSync.value = null;
            await getData();
        }
    } catch (err) {
        console.error("err", err);
        const errors =
            err.response?.errors || err.response?.message || err.message;
        showPopup({
            status: true,
            title: err.status >= 500 ? "Error" : "Warning",
            type: err.status >= 500 ? "error" : "warning",
            message: errors,
        });
    } finally {
        loadingSyncNusawork.value = false;
    }
};

const editLevel = (item) => {
    console.log("item", item);
    new_item.value = item.name;
    selectedItem.value = item;
    selected_parent.value = item.id_parent ?? null;
};

const changePosition = async (event) => {
    if (currentTab.value === 1 || loadingMove.value) return;

    const element = event?.moved?.element;
    if (!element?.id) {
        showPopup({
            status: true,
            title: "Warning",
            type: "warning",
            message: "Something went wrong with the data!",
        });
        return;
    }

    try {
        loadingMove.value = true;

        const portalId =
            portal.value[0]?.id ||
            JSON.parse(localStorage.getItem("portal"))[0]?.id;

        const endpoints = {
            2: { url: "job-levels", key: "job-level" },
            3: { url: "education-levels", key: "education-level" },
            4: { url: "experience-levels", key: "experience-level" },
        };

        const config = endpoints[currentTab.value];
        if (!config) throw new Error("Something went wrong!");

        const res = await mainStore.useAPI(
            `${portalId}/api/settings/${config.url}/${element.id}`,
            {
                method: "PUT",
                cache: "no-cache",
                key: `${config.key}-${element.id}`,
                body: { name: element.name, index: event.moved.newIndex },
            },
            true
        );

        if ([200, 201].includes(res?.status)) {
            showPopup({
                status: true,
                title: "Berhasil!",
                type: "success",
                message: res?.message || "Data berhasil disimpan!",
            });
            
            // Force clear list untuk hindari conflict dengan optimistic update dari draggable
            list.value = [];
            
            // Delay kecil untuk beri waktu backend commit perubahan
            await new Promise(resolve => setTimeout(resolve, 200));
            
            // Fetch data terbaru dari server
            await getData();
        }
    } catch (err) {
        console.error("err", err);
        showPopup({
            status: true,
            title: err.status >= 500 ? "Error" : "Warning",
            type: err.status >= 500 ? "error" : "warning",
            message: err.response?.message || err.message,
        });
        
        // Revert perubahan UI karena API gagal
        // Draggable sudah optimistically mengubah list, jadi kita perlu refresh dari server
        list.value = [];
        await getData();
    } finally {
        loadingMove.value = false;
    }
};

const initDelete = (item) => {
    console.log("item", item);
    selectedDelete.value = item;
    showConfirm.value = true;
};

const cancelDelete = () => {
    showConfirm.value = false;
    selectedDelete.value = null;
};

const doDelete = async () => {
    if (loadingDelete.value) return;
    loadingDelete.value = true;

    try {
        const portalId =
            portal.value[0]?.id ||
            JSON.parse(localStorage.getItem("portal"))[0]?.id;

        const endpoints = {
            1: { url: "job-positions", key: "job-position" },
            2: { url: "job-levels", key: "job-level" },
            3: { url: "education-levels", key: "education-level" },
            4: { url: "experience-levels", key: "experience-level" },
        };

        const config = endpoints[currentTab.value];
        if (!config) throw new Error("Something went wrong!");

        const res = await mainStore.useAPI(
            `${portalId}/api/settings/${config.url}/${selectedDelete.value.id}`,
            {
                method: "DELETE",
                cache: "no-cache",
                key: `${config.key}-${selectedDelete.value.id}`,
            },
            true
        );

        if ([200, 201].includes(res?.status)) {
            showPopup({
                status: true,
                title: "Berhasil!",
                type: "success",
                message: res?.message || "Data berhasil dihapus!",
            });
            getData();
            cancelDelete();
        }
    } catch (err) {
        console.error("err", err);
        showPopup({
            status: true,
            title: err.status >= 500 ? "Error" : "Warning",
            type: err.status >= 500 ? "error" : "warning",
            message: err.response?.message || err.message,
        });
    } finally {
        loadingDelete.value = false;
    }
};
</script>

<style scoped>
.bd-left {
    border-left: 1px solid #07a536;
}

.bd-group {
    margin: 15px 0px;
    display: flex;
    flex-wrap: nowrap;
}

.gr-wg {
    display: inline-block;
    border-top: 1px solid #07a536;
    width: 10px;
    color: #07a536;
}

.item-container {
    max-width: 20rem;
    margin: 0;
}
.item {
    padding: 1rem;
    border: solid black 1px;
    background-color: #fefefe;
}

.res {
    height: auto;
    border-radius: 6px;
    background-color: #f1f1f1;
    padding: 6px 7px;
    display: flex;
    flex-wrap: wrap;
}
</style>
