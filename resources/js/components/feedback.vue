<template>
    <div class="feedback-page p-6">
        <h1 class="font-bold text-2xl mb-6">Daftar Feedback Saya</h1>

        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 shadow-sm p-4 rounded-lg mb-6">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="flex justify-between items-center mb-1">
                        <h3 class="font-medium text-blue-800">Informasi Feedback</h3>
                        <div class="flex items-center space-x-2">
                            <span v-if="lastUpdateTime" class="text-xs text-gray-500">Terakhir diperbarui: {{ formatLastUpdate(lastUpdateTime) }}</span>
                            <button 
                                @click="refreshData" 
                                class="bg-blue-100 hover:bg-blue-200 text-blue-700 p-1 rounded transition-colors flex items-center"
                                :disabled="loading"
                                :class="{ 'opacity-50 cursor-not-allowed': loading }"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" :class="{ 'animate-spin': loading }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span class="ml-1 text-xs">{{ loading ? 'Memuat...' : 'Refresh' }}</span>
                            </button>
                        </div>
                    </div>
                    <ul class="text-sm text-blue-700 list-disc list-inside space-y-1">
                        <li>Data feedback diambil dari Google Sheets dan mungkin memerlukan waktu untuk dimuat</li>
                        <li>Feedback terbaru tidak langsung muncul di daftar. Sistem akan menyinkronkan data setiap <span class="font-semibold">5–10 menit</span>.</li>
                        <li>Klik tombol "Lihat Detail" untuk melihat informasi lengkap feedback</li>
                    </ul>
                    <div v-if="lastUpdateTime" class="mt-2 text-xs text-gray-500">
                        <span>Terakhir diperbarui: {{ formatLastUpdate(lastUpdateTime) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <Feedback-table
            :fields="fields"
            :items="paginatedAndSortedFeedback"
            :is-loading="loading"
            :error-message="error ? { message: 'Terjadi kesalahan saat memuat feedback.', code: 500 } : null"
            :items-per-page="15"
            :total-data="totalData"
            :row-skeleton="5"
            :searchable="true"
            :sort-key="sortKey"
            :sort-direction="sortDirection"
            @update:search-query="handleSearch"
            @update-page="handlePageChange"
            @sort="handleSort"
            @getData="fetchFeedback(true)"
        >
            <template #customCell(waktu)="data">
                {{ formatDate(data.item.date) }}
            </template>
            
            <template #customCell(jenis)="data">
                <span>
                    {{ data.item.jenis }}
                </span>
            </template>
            
            <template #customCell(deskripsi)="data">
                {{ data.item.deskripsi }}
            </template>
            
            <template #customCell(status)="data">
                <span :class="getStatusClass(data.item.status)">
                    {{ data.item.status }}
                </span>
            </template>
            
            <template #customCell(aksi)="data">
                <button
                    @click="showDetail(data.item)"
                    class="bg-gray-800 text-white py-1 px-4 rounded hover:bg-gray-700 transition-colors"
                >
                    Lihat Detail
                </button>
            </template>
        </Feedback-table>

        <Modal
            :show="showModal"
            :title="'Detail Feedback'"
            :use-close="true"
            @update-show="showModal = false"
            :style-props="{ width: '600px', height: 'auto', zIndex: 1000 }"
        >
            <template #header>
                <div class="px-6 py-2"> 
                    <h3 class="text-xl font-bold">Detail Feedback</h3>
                </div>
            </template>
            <template #body>
                <div v-if="selectedFeedback" class="px-1 pt-2 pb-4 space-y-4">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Kategori</p>
                        <p class="font-medium text-lg" :class="getCategoryClass(selectedFeedback.jenis)">
                            {{ selectedFeedback.jenis }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-sm mb-1">Tanggal</p>
                        <p class="font-medium">
                            {{ formatDate(selectedFeedback.date) }}
                        </p>
                    </div>
                    
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Deskripsi</p>
                        <p class="font-medium">
                            {{ selectedFeedback.deskripsi }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-sm mb-1">Status</p>
                        <p class="font-medium" :class="getStatusClass(selectedFeedback.status)">
                            {{ selectedFeedback.status }}
                        </p>
                    </div>

                    <div v-if="selectedFeedback.attachments && selectedFeedback.attachments.length > 0">
                        <p class="text-gray-500 text-sm mb-2">Lampiran</p>
                        <div class="flex space-x-2">
                            <div v-for="(attachment, index) in selectedFeedback.attachments" :key="index" class="w-20 h-20 overflow-hidden rounded-lg border">
                                <img 
                                    :src="attachment" 
                                    alt="Lampiran" 
                                    class="object-cover w-full h-full cursor-pointer" 
                                    @click="openAttachment(attachment)"
                                />
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="text-gray-500 text-sm mb-1">Tanggapan</p>
                        <div class="bg-gray-100 p-4 rounded-lg">
                            <p v-if="selectedFeedback.response" class="text-gray-700">
                                {{ selectedFeedback.response }}
                            </p>
                            <p v-else class="text-gray-500 italic">
                                Belum ada tanggapan, mohon menunggu
                            </p>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <button
                    @click="showModal = false"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded transition-colors"
                >
                    Tutup
                </button>
            </template>
        </Modal>

        <Modal
            :show="showImageModal"
            :use-close="true"
            @update-show="showImageModal = false"
            :style-props="{ width: 'auto', maxWidth: '90%', height: 'auto', zIndex: 2000 }"
            :use-footer="false"
        >
            <template #body>
                <img :src="selectedImage" alt="Preview Lampiran" class="max-w-full max-h-[80vh] block mx-auto">
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';
import { debounce } from 'lodash';
import axios from 'axios';
import { getTenantName } from '@/utils/url';
import FeedbackTable from '@/components/feedback-table.vue';
import Modal from '@/components/modal.vue';

// State
const allFeedback = ref([]);
const loading = ref(true);
const error = ref(false);
const showModal = ref(false);
const showImageModal = ref(false); 
const selectedFeedback = ref(null);
const selectedImage = ref(''); 
const sortKey = ref(null);
const sortDirection = ref('desc'); 
const current_page = ref(1);
const per_page = ref(15);
const searchQuery = ref('');
const lastUpdateTime = ref(null);

const fields = ref([
    { label: "Waktu", key: "waktu", sortable: true },
    { label: "Jenis", key: "jenis", sortable: true },
    { label: "Deskripsi", key: "deskripsi", sortable: false },
    { label: "Status", key: "status", sortable: true },
    { label: "Aksi", key: "aksi", sortable: false },
]);

const totalData = computed(() => {
    return filteredAndSortedFeedback.value.length;
});

const formattedFeedback = computed(() => {
    return allFeedback.value.map(item => ({
        ...item,
        waktu: item.date,
        jenis: getFeedbackType(item.description),
        deskripsi: getFeedbackDescription(item.description),
        attachments: item.image ? [item.image] : [],
        response: item.note || '',
        status: item.status
    }));
});

const filteredAndSortedFeedback = computed(() => {
    let list = [...formattedFeedback.value];
    if (searchQuery.value) {
        const searchWords = searchQuery.value.toLowerCase().split(' ').filter(word => word.length > 0);
        list = list.filter(item => {
            const itemText = `${item.jenis} ${item.deskripsi} ${item.status}`.toLowerCase();
            return searchWords.every(word => itemText.includes(word));
        });
    }
    if (sortKey.value) {
        list.sort((a, b) => {
            const aValue = a[sortKey.value];
            const bValue = b[sortKey.value];
            let comparison = 0;
            if (aValue < bValue) {
                comparison = -1;
            } else if (aValue > bValue) {
                comparison = 1;
            }
            return sortDirection.value === 'asc' ? comparison : -comparison;
        });
    }
    return list;
});

const paginatedAndSortedFeedback = computed(() => {
    const start = (current_page.value - 1) * per_page.value;
    const end = start + per_page.value;
    return filteredAndSortedFeedback.value.slice(start, end);
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const formattedDate = date.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
    const formattedTime = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false }).replace('.', ':');
    return `${formattedDate}, ${formattedTime}`;
};

const formatLastUpdate = (timestamp) => {
    if (!timestamp) return '';
    const now = Date.now();
    const diff = now - timestamp;
    
    // Kurang dari 1 menit
    if (diff < 60000) {
        return 'Baru saja';
    }
    // Kurang dari 1 jam
    else if (diff < 3600000) {
        const minutes = Math.floor(diff / 60000);
        return `${minutes} menit yang lalu`;
    }
    // Kurang dari 24 jam
    else if (diff < 86400000) {
        const hours = Math.floor(diff / 3600000);
        return `${hours} jam yang lalu`;
    }
    // Lebih dari 24 jam
    else {
        return formatDate(new Date(timestamp));
    }
};

const refreshData = () => {
    // Panggil fetchFeedback dengan parameter refresh=true
    fetchFeedback(true);
};

const getCategoryClass = (category) => {
    const lowerCaseCategory = category.toLowerCase();
    if (lowerCaseCategory.includes('saran')) return 'text-blue-800';
    if (lowerCaseCategory.includes('pujian')) return 'text-green-800';
    if (lowerCaseCategory.includes('keluhan')) return 'text-red-800';
    return 'text-gray-800';
};
const getStatusClass = (status) => {
    const lowerCaseStatus = status.toLowerCase();
    if (lowerCaseStatus.includes('done') || lowerCaseStatus.includes('diterima')) return 'text-green-800';
    if (lowerCaseStatus.includes('ignored')) return 'text-yellow-800';
    if (lowerCaseStatus.includes('new')) return 'text-blue-800';
    return 'text-gray-800';
};
const getFeedbackType = (description) => {
    const parts = description.split(':');
    return parts[0]?.trim() || '';
};
const getFeedbackDescription = (description) => {
    const parts = description.split(':');
    return parts.length > 1 ? parts.slice(1).join(':').trim().replace(/\s*\(.*\)/, '') : '';
};
const showDetail = (item) => {
    selectedFeedback.value = item;
    showModal.value = true;
};
const handlePageChange = (page) => {
    current_page.value = page;
};
const handleSearch = (query) => {
  searchQuery.value = query;
  current_page.value = 1; 
};
const handleSort = (key) => {
    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDirection.value = 'asc';
    }
    current_page.value = 1;
};
const openAttachment = (url) => {
  selectedImage.value = url;
  showImageModal.value = true;
};

const fetchFeedback = async (refresh = false) => {
    loading.value = true;
    error.value = false;

    console.log(refresh ? "Memperbarui data dari API." : "Mengambil data dari API.");
    try {
        const tenantName = getTenantName();
        const token = localStorage.getItem('token');
        const headers = { 'Authorization': `Bearer ${token}` };
        // Tambahkan parameter refresh jika diminta
        const url = refresh ? 
            `/${tenantName}/api/my-feedback?refresh=true` : 
            `/${tenantName}/api/my-feedback`;
        
        const response = await axios.get(url, { headers });
        
        if (response.data && Array.isArray(response.data.data)) {
            allFeedback.value = response.data.data;
            lastUpdateTime.value = Date.now();
        } else {
            allFeedback.value = response.data;
            lastUpdateTime.value = Date.now();
        }
    } catch (err) {
        console.error("Error fetching feedback:", err);
        error.value = true;
    } finally {
        loading.value = false;
    }
};

// Membuat versi debounced dari fetchFeedback untuk menghindari multiple calls
const debouncedFetchFeedback = debounce(() => {
    fetchFeedback();
}, 300);

onMounted(() => {
    debouncedFetchFeedback();
});

onUnmounted(() => {
    // Cleanup debounced function jika diperlukan
    debouncedFetchFeedback.cancel();
});
</script>