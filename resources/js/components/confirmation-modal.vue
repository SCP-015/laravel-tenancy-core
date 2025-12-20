<template>
    <modal
        :show="show"
        @update-show="$emit('close')"
        :style-props="{
            width: '450px',
            maxWidth: '90vw'
        }"
        :title="title"
        :useClose="true"
        :useFooter="false"
        :z-index="zIndex"
    >
        <template #body>
            <div class="p-4">
                <p class="text-[14px] text-gray-700">{{ message }}</p>
                
                <div class="flex items-center justify-end gap-3 mt-6">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 rounded-md text-[14px] font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition"
                    >
                        {{ cancelText || 'Batal' }}
                    </button>
                    <button
                        type="button"
                        @click="$emit('confirm')"
                        class="px-4 py-2 rounded-md text-[14px] font-medium text-white bg-red-600 hover:bg-red-700 transition"
                    >
                        {{ confirmText || 'Konfirmasi' }}
                    </button>
                </div>
            </div>
        </template>
    </modal>
</template>

<script setup>
import Modal from './modal.vue';

defineProps({
    show: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: 'Konfirmasi'
    },
    message: {
        type: String,
        default: ''
    },
    confirmText: {
        type: String,
        default: 'Konfirmasi'
    },
    cancelText: {
        type: String,
        default: 'Batal'
    },
    zIndex: {
        type: Number,
        default: 1002
    }
});

defineEmits(['close', 'confirm']);
</script>
