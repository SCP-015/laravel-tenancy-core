<template>
    <div class="min-h-screen bg-white">
        <div class="w-full">
            <div class="relative">
                <div class="w-full h-[220px] bg-gray-100 overflow-hidden">
                    <img
                        v-if="company?.header_image"
                        :src="company.header_image"
                        alt="Header"
                        class="w-full h-full object-cover"
                    />
                </div>

                <div class="default-container">
                    <div class="relative -mt-[48px] flex items-end gap-4">
                        <div
                            class="w-[96px] h-[96px] rounded-lg bg-white border border-gray-200 overflow-hidden flex items-center justify-center"
                        >
                            <img
                                v-if="company?.profile_image"
                                :src="company.profile_image"
                                alt="Logo"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="text-gray-400 text-[12px]">
                                Logo
                            </div>
                        </div>

                        <div class="pb-2 flex-1 min-w-0">
                            <div class="text-[22px] sm:text-[28px] font-semibold text-gray-900 truncate">
                                {{ company?.name || 'Company' }}
                            </div>
                            <div class="text-[13px] text-gray-500 break-all">
                                {{ baseUrl }}/{{ company?.slug || '-' }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-lg p-4">
                            <div class="text-[16px] font-semibold text-gray-900 mb-1">
                                Tentang Company
                            </div>
                            <div class="text-[14px] text-gray-600">
                                <div v-if="company?.company_values" class="whitespace-pre-line">
                                    {{ company.company_values }}
                                </div>
                                <div v-else>
                                    Informasi company belum tersedia.
                                </div>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <div class="text-[16px] font-semibold text-gray-900 mb-1">
                                Informasi
                            </div>
                            <div class="text-[14px] text-gray-600 space-y-2">
                                <div v-if="company?.company_category?.name">
                                    <div class="text-[13px] text-gray-500">Kategori</div>
                                    <div class="text-gray-900">{{ company.company_category.name }}</div>
                                </div>

                                <div v-if="company?.employee_range_start || company?.employee_range_end">
                                    <div class="text-[13px] text-gray-500">Jumlah Karyawan</div>
                                    <div class="text-gray-900">
                                        {{ company.employee_range_start || '-' }} - {{ company.employee_range_end || '-' }}
                                    </div>
                                </div>

                                <div v-if="company?.website">
                                    <div class="text-[13px] text-gray-500">Website</div>
                                    <a
                                        class="text-[#00852C] break-all hover:underline"
                                        :href="normalizeUrl(company.website)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {{ company.website }}
                                    </a>
                                </div>

                                <div v-if="company?.linkedin">
                                    <div class="text-[13px] text-gray-500">LinkedIn</div>
                                    <a
                                        class="text-[#00852C] break-all hover:underline"
                                        :href="normalizeUrl(company.linkedin)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {{ company.linkedin }}
                                    </a>
                                </div>

                                <div v-if="company?.instagram">
                                    <div class="text-[13px] text-gray-500">Instagram</div>
                                    <a
                                        class="text-[#00852C] break-all hover:underline"
                                        :href="normalizeUrl(company.instagram)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {{ company.instagram }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pb-10">
                        <div class="text-[12px] text-gray-400">
                            Hiring solution from Nusawork
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const company = computed(() => page.props?.company)
const baseUrl = computed(() => window.location.origin)

const normalizeUrl = (value) => {
    if (!value) {
        return ''
    }

    if (value.startsWith('http://') || value.startsWith('https://')) {
        return value
    }

    return `https://${value}`
}
</script>
