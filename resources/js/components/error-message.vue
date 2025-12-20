<template>
    <div class="w-[100%]">
        <div
            class="flex justify-center w-[100%]"
            :style="usePadding ? 'margin: 80px 0px' : ''"
        >
            <div class="text-center">
                <div class="fs-16 mt-1">
                    <div
                        v-if="
                            props.errorData &&
                            props.errorData.message !== '' &&
                            props.errorData.code >= 500
                        "
                    >
                        {{ "Sorry! there’s a problem on our end." }}
                        <br />
                        {{ "Please try again in a moment" }}
                    </div>

                    <span
                        v-else-if="
                            props.errorData &&
                            props.errorData.message !== '' &&
                            props.errorData.code == 404
                        "
                    >
                        {{ `${"Your request was not found!"}` }}
                    </span>

                    <span
                        v-else-if="
                            props.errorData &&
                            props.errorData.message !== '' &&
                            props.errorData.code == 403
                        "
                    >
                        {{ props.errorData.message }}
                    </span>

                    <span v-else>
                        {{ "There are no data to show" }}
                    </span>
                </div>

                <div
                    v-if="
                        props.errorData &&
                        props.errorData.message !== '' &&
                        props.errorData.code !== 403
                    "
                    class="btn btn-sm btn-soft btn-warning mt-3 text-[14px]"
                    @click="reloadData"
                >
                    {{ "Reload" }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    errorData: {
        type: Object,
        default: () => {},
    },
    messageOnly: {
        type: Boolean,
        default: true,
    },
    usePadding: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(["getData"]);

const reloadData = () => {
    emit("getData");
};
</script>
