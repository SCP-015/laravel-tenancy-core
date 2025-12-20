<template>
    <div
        class="relative overflow-x-auto"
        :style="isSlide ? tableWrapperStyle : ''"
    >
        <div class="flex items-center mb-4">
            <slot name="buttonGroup"></slot>
            <div class="ml-auto">
                <template v-if="isLoading">
                    <div class="skeleton h-8 w-[200px]"></div>
                </template>
                <Input
                    v-else
                    v-model="search"
                    :type="'text'"
                    :placeholder="'Search...'"
                />
            </div>
        </div>

        <div class="relative">
            <div class="overflow-x-auto">
                <div
                    class="overflow-y-auto min-h-[290px] border border-[#e9e7e7] rounded-[6px]"
                    :class="wrapperTableClass"
                >
                    <table
                        class="w-full text-sm text-left text-gray-600 min-h-[200px]"
                        v-if="isLoading"
                    >
                        <caption class="sr-only">
                            {{ tableCaption || 'Data table' }}
                        </caption>
                        <thead
                            class="text-xs text-gray-700 uppercase bg-gray-100 sticky top-0 z-10"
                        >
                            <tr>
                                <th
                                    v-for="(header, idx) in fields"
                                    :key="idx"
                                    class="px-6 py-3 whitespace-nowrap"
                                >
                                    <div class="skeleton h-4 w-20"></div>
                                </th>
                                <th
                                    v-if="slots.action"
                                    class="px-6 py-3 whitespace-nowrap sticky right-0 bg-gray-100 z-10"
                                >
                                    <div class="skeleton h-4 w-16"></div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, i) in rowSkeleton"
                                :key="`row-${i}`"
                                v-if="isLoading || isSkeleton"
                            >
                                <td
                                    style="padding-top: 3px !important"
                                    v-for="(column, c) in fields"
                                    :key="`column-${c}`"
                                >
                                    <div class="skeleton h-4 w-25"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table
                        v-else
                        class="w-full text-sm text-left text-gray-600"
                        :class="{ 'h-[300px]': paginatedItems.length < 1 }"
                    >
                        <caption class="sr-only">
                            {{ tableCaption || 'Data table' }}
                        </caption>
                        <thead
                            class="text-xs text-gray-700 uppercase bg-gray-100 sticky top-0 z-10"
                        >
                            <tr>
                                <th
                                v-for="(header, idx) in fields"
                                :key="idx"
                                :class="[header.sortable ? 'cursor-pointer hover:bg-gray-200' : '', header.thClass]"
                                @click="handleHeaderClick(header.key)"
                                class="px-6 py-3 whitespace-nowrap transition-colors duration-200"
                            >
                                <div class="flex items-center">
                                    <span>{{ header.label }}</span>
                                    <span v-if="header.sortable" class="ml-2 transition-colors duration-200">
                                        <svg
                                            v-if="sortKey === header.key && sortDirection === 'asc'"
                                            class="w-4 h-4 text-gray-800 transition-colors duration-200"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M5 15l7-7 7 7"
                                            ></path>
                                        </svg>
                                        <svg
                                            v-else-if="sortKey === header.key && sortDirection === 'desc'"
                                            class="w-4 h-4 text-gray-800 transition-colors duration-200"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 9l-7 7-7-7"
                                            ></path>
                                        </svg>
                                        <svg
                                            v-else
                                            class="w-4 h-4 text-gray-400 transition-colors duration-200"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M8 9l4-4 4 4m0 6l-4 4-4-4"
                                            ></path>
                                        </svg>
                                    </span>
                                </div>
                            </th>

                                <th
                                    v-if="slots.action"
                                    class="px-6 py-3 whitespace-nowrap sticky right-0 bg-gray-100 z-10"
                                >
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(row, i) in rowSkeleton"
                                :key="`row-${i}`"
                                v-if="isLoading || isSkeleton"
                            >
                                <td
                                    style="padding-top: 3px !important"
                                    v-for="(column, c) in fields"
                                    :key="`column-${c}`"
                                >
                                    <div class="skeleton h-4 w-25"></div>
                                </td>
                            </tr>

                            <template v-else>
                                <tr
                                    v-if="paginatedItems.length == 0"
                                    class="my-10"
                                >
                                    <td
                                        colspan="100%"
                                        style="
                                            text-align: center;
                                            padding: 20px;
                                        "
                                    >
                                        <div class="fs-16 mt-1">
                                            <span
                                                v-if="
                                                    errorMessage &&
                                                    errorMessage.message !==
                                                        '' &&
                                                    errorMessage.code >= 500
                                                "
                                            >
                                                {{
                                                    "Sorry! there’s a problem on our end."
                                                }}
                                                <br />
                                                {{
                                                    "Please refresh again in a minute"
                                                }}
                                            </span>

                                            <span
                                                v-else-if="
                                                    errorMessage &&
                                                    errorMessage.message !==
                                                        '' &&
                                                    errorMessage.code == 404
                                                "
                                            >
                                                {{
                                                    `${"Your request was not found!"}`
                                                }}
                                            </span>

                                            <span
                                                v-else-if="
                                                    errorMessage &&
                                                    errorMessage.message !==
                                                        '' &&
                                                    errorMessage.code == 403
                                                "
                                            >
                                                {{ errorMessage.message }}
                                            </span>

                                            <span
                                                v-else-if="
                                                    errorMessage &&
                                                    errorMessage.message !==
                                                        '' &&
                                                    errorMessage.code
                                                "
                                            >
                                                {{ errorMessage.message }}
                                            </span>

                                            <span v-else class="text-center">
                                                <div
                                                    class="flex justify-center"
                                                >
                                                    <img
                                                        :src="iconError"
                                                        width="30"
                                                        alt="icon"
                                                    />
                                                </div>

                                                {{
                                                    paginatedItems.length < 1
                                                        ? "No data found"
                                                        : "There are no records matching"
                                                }}
                                            </span>
                                        </div>

                                        <div
                                            v-if="
                                                errorMessage &&
                                                errorMessage.message !== ''
                                            "
                                            class="btn btn-sm b-new-primary t-new-green mt-3 fs-14"
                                            @click="emit('getData')"
                                        >
                                            {{ "Reload" }}
                                        </div>
                                    </td>
                                </tr>

                                <template v-else>
                                    <tr
                                        v-for="(item, idx) in paginatedItems"
                                        :key="item.id"
                                        class="hover:bg-gray-50"
                                        style="border-bottom: 1px solid #ededed"
                                    >
                                        <td
                                            v-for="(header, colIdx) in fields"
                                            :key="colIdx"
                                            class="px-6 py-4 whitespace-nowrap align-middle leading-tight"
                                        >
                                            <slot
                                                :name="`customCell(${header.key})`"
                                                :item="item"
                                                :header="header"
                                            >
                                                {{ item[header.key] }}
                                            </slot>
                                        </td>

                                        <td
                                            v-if="slots.action"
                                            class="px-6 py-4 whitespace-nowrap align-middle leading-tight sticky right-0 bg-white z-10"
                                        >
                                            <slot name="action" :item="item">
                                            </slot>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center p-4">
            <span
                class="skeleton w-[120px] h-4"
                v-if="isLoading && isSkeleton"
            ></span>
            <span class="text-[12px] text-gray-700" v-else>
                Showing
                {{ (totalData || filteredItems.length) === 0 ? 0 : (itemsPerPage === Number.MAX_SAFE_INTEGER ? totalData || totalRows : itemsPerPage * currentPage - itemsPerPage + 1) }}
                to
                {{ (totalData || filteredItems.length) === 0 ? 0 : renderTotalPage() }}
                of
                {{ totalData || filteredItems.length }} entries
            </span>
            <div class="flex items-center space-x-1" v-if="totalData > 0">
                <img
                    v-if="isLoading"
                    src="/images/loading.gif"
                    alt="loading"
                    width="18"
                />
                <button
                    @click="goToPage(1)"
                    :disabled="currentPage === 1 || isLoading || isSkeleton"
                    class="px-2 py-1 cursor-pointer rounded disabled:opacity-50 hover:text-[#13852d]"
                >
                    «
                </button>

                <button
                    @click="goToPage(currentPage - 1)"
                    :disabled="currentPage === 1 || isLoading || isSkeleton"
                    class="px-2 py-1cursor-pointer rounded disabled:opacity-50 hover:text-[#13852d]"
                >
                    ‹
                </button>

                <template v-for="page in visiblePages" :key="page">
                    <button
                        v-if="page !== '...'"
                        @click="goToPage(page)"
                        :class="[
                            'px-3 py-1 text-[12px] cursor-pointer transition-all',
                            page === currentPage
                                ? 'bg-[#3A3A3A] text-white border rounded'
                                : 'bg-white text-gray-700 hover:bg-[#eaecef] hover:rounder',
                        ]"
                        :disabled="isLoading || isSkeleton"
                    >
                        {{ page }}
                    </button>
                    <span v-else class="px-2 py-1">...</span>
                </template>

                <button
                    @click="goToPage(currentPage + 1)"
                    :disabled="currentPage === totalPages"
                    class="px-2 py-1 cursor-pointer rounded disabled:opacity-50 hover:text-[#13852d]"
                >
                    ›
                </button>

                <button
                    @click="goToPage(totalPages)"
                    :disabled="currentPage === totalPages"
                    class="px-2 py-1 cursor-pointer rounded disabled:opacity-50 hover:text-[#13852d]"
                >
                    »
                </button>
            </div>
        </div>

        </div>
</template>

<script setup>
import { computed, ref, useSlots, onMounted, onUnmounted, watch } from "vue";

const iconError = "/images/icon-error.svg";

const slots = useSlots();

const props = defineProps({
    items: {
        type: Array,
        required: true,
    },
    fields: {
        type: Array,
        required: true,
    },
    itemsPerPage: {
        type: Number,
        default: 10,
    },
    wrapperTableClass: {
        type: String,
        default: "max-h-[600px]",
    },
    isSlide: {
        type: Boolean,
        default: false,
    },
    isSkeleton: {
        type: Boolean,
        default: false,
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
    page: {
        type: Number,
        default: 1,
    },
    errorMessage: {
        type: Object,
        default: () => {},
    },
    rowSkeleton: {
        type: Number,
        default: 3,
    },
    totalData: {
        type: Number,
        default: 0,
    },
    sortKey: {
        type: String,
        default: null,
    },
    sortDirection: {
        type: String,
        default: 'asc',
    },
    // Menambahkan prop baru untuk pencarian
    searchQuery: {
        type: String,
        default: ''
    },
    tableCaption: {
        type: String,
        default: 'Data table'
    }
});

// Menambahkan emit untuk pembaruan pencarian
const emit = defineEmits(["update-page", "pageClick", "search", "getData", "sort", "update:searchQuery"]);

// Mengubah 'search' menjadi computed property yang terhubung dengan prop 'searchQuery'
const search = computed({
  get: () => props.searchQuery,
  set: (value) => {
    emit('update:searchQuery', value);
  }
});

const currentPage = ref(1);
const windowWidth = ref(window.innerWidth);

const updateWidth = () => {
    windowWidth.value = window.innerWidth;
};

// Effects
watch(
    () => props.page,
    (val) => {
        currentPage.value = val;
    },
    { deep: true, immediate: true }
);

// Hapus computed property filteredItems karena filtering sekarang dilakukan di komponen induk
const filteredItems = computed(() => props.items);

const totalPages = computed(() => {
    return Math.ceil(
        (props.totalData != 0 ? props.totalData : filteredItems.value.length) /
            props.itemsPerPage
    );
});
const start = computed(() => (currentPage.value - 1) * props.itemsPerPage);
const end = computed(() =>
    Math.min(start.value + props.itemsPerPage, filteredItems.value.length)
);
const paginatedItems = computed(() => filteredItems.value);
const visiblePages = computed(() => {
    const pages = [];
    const total = totalPages.value;
    const current = currentPage.value;

    if (total <= 7) {
        // Show all pages if few
        for (let i = 1; i <= total; i++) pages.push(i);
    } else {
        if (current <= 4) {
            // Start region
            pages.push(1, 2, 3, 4, 5, "...", total);
        } else if (current >= total - 3) {
            // End region
            pages.push(
                1,
                "...",
                total - 4,
                total - 3,
                total - 2,
                total - 1,
                total
            );
        } else {
            // Middle region
            pages.push(
                1,
                "...",
                current - 1,
                current,
                current + 1,
                "...",
                total
            );
        }
    }

    return pages;
});

const tableWrapperStyle = computed(() => {
    const maxWidth = windowWidth.value - 280;
    return {
        maxWidth: maxWidth + "px",
    };
});

// Hapus watch 'search' yang lama dan onSearch/doSearch
// Karena sekarang menggunakan v-model kustom

// Lifecycle
onMounted(() => {
    window.addEventListener("resize", updateWidth);
});

onUnmounted(() => {
    window.removeEventListener("resize", updateWidth);
});

// methods
const renderTotalPage = () => {
    if (props.itemsPerPage === Number.MAX_SAFE_INTEGER) {
        return props.totalData || filteredItems.value.length;
    } else {
        if (
            props.itemsPerPage >
                (props.totalData || filteredItems.value.length) ||
            props.itemsPerPage * currentPage.value >
                (props.totalData || filteredItems.value.length)
        ) {
            return props.totalData || filteredItems.value.length;
        } else {
            return props.itemsPerPage * currentPage.value;
        }
    }
};

function goToPage(page) {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
        emit("update-page", page);
    }

    if (props.totalData) emit("pageClick");
}

const handleHeaderClick = (key) => {
    const field = props.fields.find(f => f.key === key);
    if (field && field.sortable) {
        emit('sort', key);
    }
};
</script>

<style scoped>
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}
</style>
