<template>
    <div>
        <!-- Header -->
        <div class="flex flex-wrap sm:flex-nowrap items-center mb-5 mt-5">
            <div>
                <h1 class="text-[18px] sm:text-[24px] font-semibold">
                    Riwayat Perubahan
                </h1>
                <h3 class="text-[14px]">
                    Lihat semua perubahan data yang terjadi di sistem
                </h3>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Model Type Filter -->
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 mb-1">
                        Tipe Model
                    </label>
                    <select
                        v-model="filters.model_type"
                        class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[14px]"
                    >
                        <option value="">Semua Model</option>
                        <option
                            v-for="type in modelTypes"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>
                </div>

                <!-- Event Type Filter -->
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 mb-1">
                        Tipe Event
                    </label>
                    <select
                        v-model="filters.event"
                        class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[14px]"
                    >
                        <option value="">Semua Event</option>
                        <option
                            v-for="event in eventTypes"
                            :key="event.value"
                            :value="event.value"
                        >
                            {{ event.label }}
                        </option>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 mb-1">
                        Dari Tanggal
                    </label>
                    <input
                        v-model="filters.date_from"
                        type="date"
                        class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[14px]"
                    />
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 mb-1">
                        Sampai Tanggal
                    </label>
                    <input
                        v-model="filters.date_to"
                        type="date"
                        class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[14px]"
                    />
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-4 flex gap-3">
                <button
                    type="button"
                    @click="applyFilters"
                    class="cursor-pointer bg-[#3A3A3A] text-white px-6 py-2 rounded-md shadow-sm transition hover:bg-gray-800 text-[14px]"
                >
                    Terapkan Filter
                </button>
                <button
                    type="button"
                    @click="resetFilters"
                    class="cursor-pointer bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-md shadow-sm transition hover:bg-gray-50 text-[14px]"
                >
                    Reset
                </button>
            </div>
        </div>

        <!-- Audit Logs Table using default-table -->
        <default-table
            :fields="fields"
            :items="auditLogs"
            :wrapperTableClass="'max-h-[600px]'"
            :total-data="pagination.total"
            :is-skeleton="loading"
            :is-loading="loading"
            :page="pagination.current_page"
            :itemsPerPage="pagination.per_page"
            :rowSkeleton="5"
            @update-page="goToPage"
            @update-items-per-page="updateItemsPerPage"
            @search="handleSearch"
        >
            <!-- Timestamp -->
            <template #customCell(created_at)="data">
                <div class="text-[13px]">
                    <div class="font-medium text-gray-900">{{ data.item.created_at }}</div>
                    <div class="text-gray-500 text-[11px]">{{ data.item.created_at_human }}</div>
                </div>
            </template>

            <!-- User -->
            <template #customCell(user)="data">
                <div v-if="data.item.user" class="text-[13px]">
                    <div class="font-medium text-gray-900">{{ data.item.user.name }}</div>
                    <div class="text-gray-500 text-[11px]">{{ data.item.user.email }}</div>
                </div>
                <div v-else class="text-[13px] text-gray-500">System</div>
            </template>

            <!-- Model Type -->
            <template #customCell(model_type)="data">
                <div class="text-[13px]">
                    <div class="font-medium text-gray-900">{{ data.item.model_type_label }}</div>
                    <div class="text-gray-500 text-[11px]">ID: {{ data.item.model_id }}</div>
                </div>
            </template>

            <!-- Event -->
            <template #customCell(event)="data">
                <span
                    :class="getEventBadgeClass(data.item.event)"
                    class="px-2 py-1 inline-flex text-[11px] leading-5 font-semibold rounded-full"
                >
                    {{ data.item.event_label }}
                </span>
            </template>

            <!-- Changes Summary -->
            <template #customCell(changes_summary)="data">
                <div class="text-[13px] text-gray-900 line-clamp-2 max-w-md">
                    {{ data.item.changes_summary }}
                </div>
            </template>

            <!-- Actions -->
            <template #customCell(actions)="data">
                <button
                    type="button"
                    @click="showDetail(data.item)"
                    class="text-[13px] text-blue-600 hover:text-blue-900 font-medium"
                >
                    Detail
                </button>
            </template>
        </default-table>

        <!-- Detail Modal -->
        <modal
            :show="showDetailModal"
            @close="closeDetail"
            :maxWidth="'4xl'"
        >
            <template #header>
                <div class="bg-white px-4 pt-5 pb-4 sm:px-6 sm:pt-6 sm:pb-4">
                    <h3 class="text-[18px] font-semibold text-gray-900">
                        Detail Audit Log
                    </h3>
                </div>
            </template>

            <template #body>
                <div v-if="selectedAudit" class="space-y-4 px-2 sm:px-0">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[12px] font-medium text-gray-700">User</label>
                            <p class="mt-1 text-[14px] text-gray-900">
                                {{ selectedAudit.user?.name || 'System' }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-[12px] font-medium text-gray-700">Waktu</label>
                            <p class="mt-1 text-[14px] text-gray-900">{{ selectedAudit.created_at }}</p>
                        </div>
                        <div>
                            <label class="block text-[12px] font-medium text-gray-700">Model</label>
                            <p class="mt-1 text-[14px] text-gray-900">{{ selectedAudit.model_type_label }}</p>
                        </div>
                        <div>
                            <label class="block text-[12px] font-medium text-gray-700">Event</label>
                            <p class="mt-1 text-[14px] text-gray-900">{{ selectedAudit.event_label }}</p>
                        </div>
                    </div>

                    <!-- Old Values -->
                    <div v-if="Object.keys(selectedAudit.old_values || {}).length > 0">
                        <label class="block text-[12px] font-medium text-gray-700 mb-2">Nilai Lama</label>
                        <div class="bg-red-50 rounded-md p-4 border border-red-100">
                            <pre class="text-[11px] text-gray-800 whitespace-pre-wrap overflow-x-auto">{{ JSON.stringify(selectedAudit.old_values, null, 2) }}</pre>
                        </div>
                    </div>

                    <!-- New Values -->
                    <div v-if="Object.keys(selectedAudit.new_values || {}).length > 0">
                        <label class="block text-[12px] font-medium text-gray-700 mb-2">Nilai Baru</label>
                        <div class="bg-green-50 rounded-md p-4 border border-green-100">
                            <pre class="text-[11px] text-gray-800 whitespace-pre-wrap overflow-x-auto">{{ JSON.stringify(selectedAudit.new_values, null, 2) }}</pre>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                        <div>
                            <label class="block text-[12px] font-medium text-gray-700">IP Address</label>
                            <p class="mt-1 text-[14px] text-gray-900">{{ selectedAudit.ip_address }}</p>
                        </div>
                        <div>
                            <label class="block text-[12px] font-medium text-gray-700">URL</label>
                            <p class="mt-1 text-[14px] text-gray-900 truncate">{{ selectedAudit.url }}</p>
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="px-4 py-3 sm:px-6">
                    <button
                        type="button"
                        @click="closeDetail"
                        class="w-full cursor-pointer bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-md shadow-sm transition hover:bg-gray-50 text-[14px]"
                    >
                        Tutup
                    </button>
                </div>
            </template>
        </modal>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useMainStore } from '../../stores';
import { useAuditLogs } from '@/composables/useAuditLogs';
import DefaultTable from '../../components/default-table.vue';
import Modal from '../../components/modal.vue';

const mainStore = useMainStore();
const portal = computed(() => mainStore.userPortal.value);

const {
    auditLogs,
    modelTypes,
    eventTypes,
    loading,
    error,
    pagination,
    fetchAuditLogs,
    fetchModelTypes,
    fetchEventTypes,
} = useAuditLogs();

const filters = ref({
    model_type: '',
    event: '',
    date_from: '',
    date_to: '',
    search: '',
    page: 1,
    per_page: 15,
});

const showDetailModal = ref(false);
const selectedAudit = ref(null);

// Table fields
const fields = computed(() => [
    { label: 'Waktu', key: 'created_at' },
    { label: 'User', key: 'user' },
    { label: 'Model', key: 'model_type' },
    { label: 'Event', key: 'event' },
    { label: 'Perubahan', key: 'changes_summary' },
    { label: 'Aksi', key: 'actions' },
]);

/**
 * Apply filters dan fetch data
 */
const applyFilters = async () => {
    filters.value.page = 1; // Reset to first page
    await fetchAuditLogs(filters.value);
};

/**
 * Reset filters
 */
const resetFilters = async () => {
    filters.value = {
        model_type: '',
        event: '',
        date_from: '',
        date_to: '',
        search: '',
        page: 1,
        per_page: 15,
    };
    await fetchAuditLogs(filters.value);
};

/**
 * Go to specific page
 */
const goToPage = async (page) => {
    if (page < 1 || page > pagination.value.last_page) return;
    filters.value.page = page;
    await fetchAuditLogs(filters.value);
};

/**
 * Update items per page
 */
const updateItemsPerPage = async (newValue) => {
    console.log('updateItemsPerPage called with:', newValue);
    if (loading.value) {
        console.log('Already loading, skipping...');
        return;
    }
    filters.value.per_page = newValue;
    filters.value.page = 1; // Reset to first page
    await fetchAuditLogs(filters.value);
};

/**
 * Handle search
 */
const handleSearch = async (query) => {
    console.log('handleSearch called with:', query);
    if (loading.value) {
        console.log('Already loading, skipping...');
        return;
    }
    filters.value.search = query;
    filters.value.page = 1; // Reset to first page
    await fetchAuditLogs(filters.value);
};

/**
 * Show detail modal
 */
const showDetail = (audit) => {
    selectedAudit.value = audit;
    showDetailModal.value = true;
};

/**
 * Close detail modal
 */
const closeDetail = () => {
    showDetailModal.value = false;
    selectedAudit.value = null;
};

/**
 * Get badge class for event type
 */
const getEventBadgeClass = (event) => {
    const classes = {
        created: 'bg-green-100 text-green-800',
        updated: 'bg-blue-100 text-blue-800',
        deleted: 'bg-red-100 text-red-800',
        restored: 'bg-yellow-100 text-yellow-800',
    };
    return classes[event] || 'bg-gray-100 text-gray-800';
};

/**
 * Initialize data when portal is ready
 */
const initializeData = async () => {
    try {
        await Promise.all([
            fetchModelTypes(),
            fetchEventTypes(),
            fetchAuditLogs(filters.value),
        ]);
    } catch (err) {
        console.error('Error loading audit logs:', err);
    }
};

// Watch portal changes - load data when portal is ready
// Note: Removed deep: true to prevent triggering on filters changes
watch(
    portal,
    (val) => {
        console.log('Portal watch triggered, portal:', val);
        if (val && val.length > 0) {
            console.log('Portal ready, calling initializeData');
            initializeData();
        }
    },
    { immediate: true }
);
</script>
