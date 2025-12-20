<template>
    <Modal :show="show" @close="handleCancel">
        <template #header>
            <div class="px-4 py-3 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 leading-5">
                            Pilih Company
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5 leading-4">
                            Pilih company yang ingin Anda akses
                        </p>
                    </div>
                </div>
            </div>
        </template>
        <template #body>
            <div class="px-4 py-4">
                <!-- Warning Banner -->
                <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-start space-x-2">
                        <svg class="w-4 h-4 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <p class="text-xs font-medium text-yellow-800">Peringatan</p>
                            <p class="text-xs text-yellow-700 mt-0.5">Jangan refresh halaman atau tutup browser saat memilih company. Proses login akan dibatalkan.</p>
                        </div>
                    </div>
                </div>
                
                <p class="text-sm text-gray-600 mb-4 text-center">
                    Anda memiliki akses ke <span class="font-medium text-green-600">{{ portals.length }}</span> company. 
                    Silakan pilih company yang ingin Anda akses:
                </p>
                
                <div class="grid gap-2 max-h-64 overflow-y-auto">
                    <div 
                        v-for="portal in portals" 
                        :key="portal.id"
                        class="group relative border rounded-lg p-3 cursor-pointer transition-all duration-200 hover:shadow-md"
                        :class="{
                            'border-green-500 bg-green-50 shadow-sm': selectedPortal?.id === portal.id,
                            'border-gray-200 hover:border-gray-300 hover:bg-gray-50': selectedPortal?.id !== portal.id
                        }"
                        @click="selectPortal(portal)"
                    >
                        <!-- Selection Indicator -->
                        <div class="absolute top-3 right-3">
                            <div 
                                class="w-4 h-4 rounded-full border transition-all duration-200 flex items-center justify-center"
                                :class="{
                                    'border-green-500 bg-green-500': selectedPortal?.id === portal.id,
                                    'border-gray-300 group-hover:border-gray-400': selectedPortal?.id !== portal.id
                                }"
                            >
                                <svg 
                                    v-if="selectedPortal?.id === portal.id"
                                    class="w-2.5 h-2.5 text-white" 
                                    fill="currentColor" 
                                    viewBox="0 0 20 20"
                                >
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Portal Content -->
                        <div class="pr-6">
                            <div class="flex items-start space-x-3">
                                <!-- Portal Icon -->
                                <div class="flex-shrink-0">
                                    <div 
                                        class="w-8 h-8 rounded-md flex items-center justify-center text-white font-medium text-sm"
                                        :class="{
                                            'bg-gradient-to-br from-green-500 to-green-600': selectedPortal?.id === portal.id,
                                            'bg-gradient-to-br from-gray-400 to-gray-500': selectedPortal?.id !== portal.id
                                        }"
                                    >
                                        {{ portal.name ? portal.name.charAt(0).toUpperCase() : 'P' }}
                                    </div>
                                </div>
                                
                                <!-- Portal Info -->
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-gray-900 truncate">
                                        {{ portal.name }}
                                    </h4>
                                    <p class="text-xs text-gray-500 mt-0.5 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                        </svg>
                                        {{ portal.slug }}
                                    </p>
                                    <p v-if="portal.description" class="text-xs text-gray-600 mt-1 line-clamp-1">
                                        {{ portal.description }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Hover Effect -->
                        <div 
                            class="absolute inset-0 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                            :class="{
                                'bg-gradient-to-r from-green-500/5 to-green-600/5': selectedPortal?.id !== portal.id
                            }"
                        ></div>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <div class="px-4 py-3 border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <div class="flex-1 min-w-0 mr-4">
                        <p class="text-xs text-gray-500 truncate">
                            <span v-if="selectedPortal">Company terpilih: <span class="font-medium">{{ selectedPortal.name }}</span></span>
                            <span v-else>Pilih salah satu company untuk melanjutkan</span>
                        </p>
                    </div>
                    <div class="flex-shrink-0 flex space-x-2">
                        <button
                            @click="handleCancel"
                            :disabled="loadingCancel"
                            class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-gray-500 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="!loadingCancel">Batal</span>
                            <span v-else class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-1.5 h-3 w-3 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Logout...
                            </span>
                        </button>
                        <button
                            @click="handleConfirm"
                            :disabled="!selectedPortal"
                            class="px-4 py-1.5 text-xs font-medium text-white bg-gradient-to-r from-green-600 to-green-700 border border-transparent rounded-md hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-green-600 disabled:hover:to-green-700 transition-all duration-200 shadow-sm"
                        >
                            <span class="flex items-center">
                                Masuk ke Company
                                <svg class="w-3 h-3 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup>
import { ref } from 'vue';
import Modal from '@/components/modal.vue';

// Props
const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    portals: {
        type: Array,
        default: () => []
    }
});

// Emits
const emit = defineEmits(['close', 'confirm', 'cancel']);

// Reactive variables
const selectedPortal = ref(null);
const loadingCancel = ref(false);

// Methods
const selectPortal = (portal) => {
    selectedPortal.value = portal;
};

const handleConfirm = () => {
    if (selectedPortal.value) {
        emit('confirm', selectedPortal.value);
        // Reset selection after confirm
        selectedPortal.value = null;
    }
};

const handleCancel = () => {
    loadingCancel.value = true;
    emit('cancel');
    // Reset selection after cancel
    selectedPortal.value = null;
    loadingCancel.value = false;
};
</script>

<style scoped>
/* Portal Selection Modal Styles */
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Custom scrollbar for portal list */
.max-h-64::-webkit-scrollbar {
    width: 4px;
}

.max-h-64::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 2px;
}

.max-h-64::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.max-h-64::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Enhanced hover effects */
.group:hover .group-hover\:border-gray-400 {
    border-color: #9ca3af;
}

/* Smooth transitions for all interactive elements */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Loading animation */
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
</style>
