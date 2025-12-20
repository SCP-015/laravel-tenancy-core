<template>
    <div id="popup">
        <Modal
            :show="optionInfo.status"
            :style-props="{
                width: '400px',
                height: 'auto',
                zIndex: '9999999',
            }"
            :use-close="false"
            :use-footer="false"
            @update-show="closeInfo"
        >
            <template #header>
                <div></div>
            </template>
            <template #body>
                <div class="mt-3 w-[100%]">
                    <div class="flex justify-center w-[100%]">
                        <img :src="icon" alt="Icon Alert" />
                    </div>
                    <div class="mt-3 text-[20px] font-[700] text-center">
                        {{ optionInfo.title ?? defaultTitle }}!
                    </div>
                    <div
                        class="text-[14px] text-center"
                        v-if="typeof optionInfo.message === 'string'"
                    >
                        {{ optionInfo.message }}
                    </div>
                    <div
                        class="text-[14px] text-center"
                        v-if="typeof optionInfo.message === 'object'"
                    >
                        <template v-if="optionInfo?.message?.reason">
                            <div class="font-[bold] text-[red] mt-2">
                                {{ t("Reason") }}:
                            </div>
                            <div>
                                {{ optionInfo?.message?.reason }}
                            </div>
                        </template>

                        <div
                            class="text-[14px] text-center mt-3"
                            v-if="optionInfo.message?.description"
                        >
                            {{ optionInfo.message?.description }}
                        </div>

                        <div v-else class="text-left mt-3">
                            <div>
                                {{
                                    optionInfo.sub_title ||
                                    `${"To proceed, please check and adjust the input from the field below:"}`
                                }}
                            </div>

                            <div
                                class="mt-1 text-[14px] text-[#6D6D6D]"
                                v-for="(err, key) in optionInfo.message"
                                :key="key"
                            >
                                -
                                {{ typeof err === "string" ? err : err[0] }}
                            </div>
                        </div>
                    </div>
                    <div
                        v-else-if="
                            (Array.isArray(optionInfo.message) ||
                                typeof optionInfo.message !== 'string') &&
                            optionInfo.message.length > 0
                        "
                        class="mt-4"
                    >
                        <div>
                            {{
                                optionInfo.sub_title ||
                                `${"To proceed, please check and adjust the input from the field below:"}`
                            }}
                        </div>
                        <div
                            class="mt-1 text-[14px] text-[#6D6D6D]"
                            v-for="(err, key) in optionInfo.message"
                            :key="key"
                        >
                            -
                            {{ typeof err === "string" ? err : err[0] }}
                        </div>
                    </div>
                </div>

                <div class="flex justify-center mt-5">
                    <button
                        class="btn text-[14px] w-20"
                        :class="
                            optionInfo.type == 'success'
                                ? 'b-new-green'
                                : 'b-black-foundation text-white'
                        "
                        @click="closeInfo"
                    >
                        Ok
                    </button>
                </div>
            </template>

            <template #footer>
                <div></div>
            </template>
        </Modal>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useMainStore } from "../stores";
import Modal from "../components/modal.vue";

const iconSuccess = "/images/success.svg";
const iconError = "/images/error.svg";
const iconWarning = "/images/warning.svg";
const iconInfo = "/images/info-alert.svg";

const props = defineProps({
    option: {
        type: Object,
        default: {
            status: false,
            title: "",
            type: "",
            message: "",
            additionalButton: {
                show: false,
                message: "",
                routeLink: "",
            },
        },
    },
});

const emit = defineEmits(["close"]);
const mainStore = useMainStore();

const optionInfo = computed(() => mainStore.stateShowInfo);
const icon = computed(() => {
    if (optionInfo.value.type === "success") return iconSuccess;
    else if (optionInfo.value.type === "error") return iconError;
    else if (optionInfo.value.type === "warning") return iconWarning;
    else return iconInfo;
});

const defaultTitle = computed(() => {
    if (optionInfo.value.type === "success") return "Success";
    else if (optionInfo.value.type === "error") return "Error";
    else if (optionInfo.value.type === "warning") return "Warning";
});

// ❌ REMOVED: Watch tidak diperlukan karena computed sudah reactive
// Watch dengan deep: true bisa menyebabkan infinite loop
// watch(
//     optionInfo,
//     (val) => {
//         // console.log("val--->>.", val);
//     },
//     { deep: true, immediate: true }
// );

// methods

const closeInfo = () => {
    let state = {
        status: false,
        title: "",
        type: "",
        sub_title: "",
        message: "",
    };

    mainStore.stateShowInfo = state;
};

const goTo = () => {
    const routeData = `${window.location.protocol}//${window.location.host}/${optionInfo.value.additionalButton.routeLink}`;
    window.open(routeData, "_blank");
};
</script>

<style scoped>
#popup .modal-mask {
    z-index: 2000 !important;
}

.b-line-black {
    border: 1px solid #3a3a3a;
}
</style>
