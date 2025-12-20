<template>
    <div
        v-if="show"
        class="relative z-[999]"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <!-- Backdrop -->
        <div
            class="fixed inset-0 bg-gray-500/75 transition-opacity"
            aria-hidden="true"
        ></div>

        <!-- Modal Content -->
        <div
            class="fixed inset-0 z-100 w-screen overflow-y-auto top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[100%] h-[100%]"
        >
            <div
                @click.self="closeModal"
                class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
            >
                <div
                    @click.stop
                    class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all w-full max-w-lg -top-[10vh] sm:top-0 sm:my-8"
                    :style="{
                        ...styleProps,
                        maxHeight: styleProps?.maxHeight || '90vh',
                        overflowY: styleProps?.overflowY || 'auto',
                    }"
                >
                    <!-- Header -->
                    <slot name="header">
                        <div
                            class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 flex flex-nowrap"
                        >
                            <div>
                                <h3
                                    class="text-xl font-semibold text-gray-900"
                                    id="modal-title"
                                >
                                    {{ title }}
                                </h3>
                            </div>

                            <div
                                v-if="useClose"
                                class="ml-auto align-center cursor-pointer"
                                @click="closeModal()"
                            >
                                <img
                                    src="/images/close.svg"
                                    alt="icon close"
                                />
                            </div>
                        </div>
                    </slot>

                    <!-- Body -->

                    <div class="bg-white px-6 pt-0 pb-4 sm:pb-4">
                        <slot name="body"> </slot>
                    </div>

                    <!-- Footer -->
                    <div
                        v-if="useFooter"
                        class="px-4 py-3 flex justify-end sm:px-6"
                    >
                        <slot name="footer">
                            <button
                                type="button"
                                class="cursor-pointer inline-flex w-full justify-center rounded-md bg-[#F6F6F6] px-3 py-2 text-[14px] font-[500] text-[#3A3A3A] shadow-xs sm:ml-3 sm:w-auto me-3"
                                @click="closeModal()"
                            >
                                Close
                            </button>
                            <button
                                type="button"
                                :disabled="loadingSubmit"
                                class="cursor-pointer mt-3 inline-flex w-full justify-center rounded-md bg-[#3A3A3A] px-3 py-2 text-[14px] font-[500] text-white shadow-xs ring-1 ring-gray-300 ring-inset sm:mt-0 sm:w-auto"
                                @click="submitModal()"
                            >
                                Submit
                                <span
                                    v-if="loadingSubmit"
                                    class="ml-1 d-flex align-center"
                                >
                                    <img
                                        src="/images/loading.gif"
                                        alt="loading"
                                        width="18"
                                    />
                                </span>
                            </button>
                        </slot>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    useFooter: {
        type: Boolean,
        default: true,
    },
    loadingSubmit: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: "",
    },
    useClose: {
        type: Boolean,
        default: true,
    },
    styleProps: {
        type: Object,
        default: () => {},
    },
    zIndex: {
        type: Number,
        default: 999,
    },
});
const emit = defineEmits(["update-show", "submit"]);

const closeModal = () => {
    emit("update-show", false);
};

const submitModal = () => {
    emit("submit");
};
</script>
