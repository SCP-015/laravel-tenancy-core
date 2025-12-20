<template>
    <div
        class="relative overflow-x-auto"
        :style="isSlide ? tableWrapperStyle : ''"
    >
        <!-- Search Bar -->
        <div class="flex items-center mb-4">
            <slot name="buttonGroup"></slot>
            <div class="ml-auto flex items-center space-x-4">
                <!-- Items per page selector -->
                <select 
                    v-if="!isLoading && !isSkeleton"
                    v-model="selectedItemsPerPage"
                    @change="handleItemsPerPageChange"
                    class="text-[14px] border border-[#BABABA] rounded-md px-3 py-2 text-[#4A4A4A] focus:outline-none bg-white"
                >
                    <option v-for="option in itemsPerPageOptions" :key="option" :value="option">
                        {{ option }}
                    </option>
                </select>
                
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
                        <thead
                            class="text-xs text-gray-700 uppercase bg-gray-100 sticky top-0 z-10"
                        >
                            <tr>
                                <template v-for="(header, idx) in fields" :key="idx">
                                    <slot 
                                        :name="`customHead(${header.key})`" 
                                        :header="header"
                                    >
                                        <th class="px-6 py-3 whitespace-nowrap" :class="header.thClass">
                                            {{ header.label }}
                                        </th>
                                    </slot>
                                </template>

                                <!-- Sticky Action Header -->
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
                                                    t(
                                                        "Please refresh again in a minute"
                                                    )
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
                                                <!-- Default cell rendering -->
                                                {{ item[header.key] }}
                                            </slot>
                                        </td>

                                        <!-- Sticky Action Cell -->
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

        <!-- Pagination -->
        <div class="flex justify-between items-center p-4">
            <span
                class="skeleton w-[120px] h-4"
                v-if="isLoading && isSkeleton"
            ></span>
            <span class="text-[12px] text-gray-700" v-else>
                Showing
                {{
                    selectedItemsPerPage === Number.MAX_SAFE_INTEGER
                        ? totalData || totalRows
                        : selectedItemsPerPage * currentPage - selectedItemsPerPage + 1
                }}
                to {{ renderTotalPage() }} of
                {{ totalData || filteredItems.length }} entries
            </span>
            <div class="flex items-center space-x-1" v-if="totalData > 0">
                <!-- loading pagination -->
                <img
                    v-if="isLoading"
                    src="/images/loading.gif"
                    alt="loading"
                    width="18"
                />
                <!-- end loading pagination -->

                <!-- First Page -->
                <button
                    @click="goToPage(1)"
                    :disabled="currentPage === 1 || isLoading || isSkeleton"
                    class="px-2 py-1 cursor-pointer rounded disabled:opacity-50 hover:text-[#13852d]"
                >
                    «
                </button>

                <!-- Prev -->
                <button
                    @click="goToPage(currentPage - 1)"
                    :disabled="currentPage === 1 || isLoading || isSkeleton"
                    class="px-2 py-1cursor-pointer rounded disabled:opacity-50 hover:text-[#13852d]"
                >
                    ‹
                </button>

                <!-- Page Numbers -->
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

                <!-- Next -->
                <button
                    @click="goToPage(currentPage + 1)"
                    :disabled="currentPage === totalPages"
                    class="px-2 py-1 cursor-pointer rounded disabled:opacity-50 hover:text-[#13852d]"
                >
                    ›
                </button>

                <!-- Last Page -->
                <button
                    @click="goToPage(totalPages)"
                    :disabled="currentPage === totalPages"
                    class="px-2 py-1 cursor-pointer rounded disabled:opacity-50 hover:text-[#13852d]"
                >
                    »
                </button>
            </div>
        </div>

        <!-- End Pagination -->
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
});

const emit = defineEmits(["update-page", "pageClick", "search", "getData", "update-items-per-page"]);

// State
const search = ref("");
const currentPage = ref(1);
const windowWidth = ref(window.innerWidth);
const selectedItemsPerPage = ref(props.itemsPerPage || 15);
const itemsPerPageOptions = ref([15, 30, 50, 100]);
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

watch(
    () => props.itemsPerPage,
    (val) => {
        selectedItemsPerPage.value = val;
    },
    { deep: true, immediate: true }
);
const filteredItems = computed(() => {
    if (selectedItemsPerPage.value !== 0) {
        return props.items;
    }

    if (!search.value) return props.items;
    return props.items.filter((item) =>
        Object.values(item).some((val) =>
            String(val).toLowerCase().includes(search.value.toLowerCase())
        )
    );
});
const totalPages = computed(() => {
    return Math.ceil(
        (props.totalData != 0 ? props.totalData : filteredItems.value.length) /
            selectedItemsPerPage.value
    );
});
const start = computed(() => (currentPage.value - 1) * selectedItemsPerPage.value);
const end = computed(() =>
    Math.min(start.value + selectedItemsPerPage.value, filteredItems.value.length)
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

watch(search, (val) => {
    setTimeout(() => {
        onSearch(val);
    }, 500);
});

// Lifecycle
onMounted(() => {
    window.addEventListener("resize", updateWidth);
});

onUnmounted(() => {
    window.removeEventListener("resize", updateWidth);
});

// methods
const renderTotalPage = () => {
    if (selectedItemsPerPage.value === Number.MAX_SAFE_INTEGER) {
        return props.totalData || filteredItems.value.length;
    } else {
        if (
            selectedItemsPerPage.value >
                (props.totalData || filteredItems.value.length) ||
            selectedItemsPerPage.value * currentPage.value >
                (props.totalData || filteredItems.value.length)
        ) {
            return props.totalData || filteredItems.value.length;
        } else {
            return selectedItemsPerPage.value * currentPage.value;
        }
    }
};

const handleItemsPerPageChange = () => {
    // Reset ke halaman pertama ketika mengubah items per page
    currentPage.value = 1;
    emit("update-page", 1);
    emit("update-items-per-page", selectedItemsPerPage.value);
};

const onSearch = (val) => {
    doSearch(val);
};

const debounce = (func, delay) => {
    let timerId;
    return (...args) => {
        clearTimeout(timerId);
        timerId = setTimeout(() => {
            func.apply(this, args);
        }, delay);
    };
};

const doSearch = debounce((search_item) => {
    if (props.currentPage !== 1 || !props.totalData) {
        currentPage.value = 1;
        emit("update-page", currentPage.value);
    }
    emit("search", search_item);
}, 500);

const gomycell = (key) => {
    return `cell(${key})`;
};

const gomyhead = (key) => {
    return `head(${key})`;
};

function goToPage(page) {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
        emit("update-page", page);
    }

    if (props.totalData) emit("pageClick");
}
</script>
