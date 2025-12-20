<template>
    <div class="w-100 d-flex flex-nowrap">
        <div class="d-flex align-items-center me-2">
            <div class="fs-14 mt-1">
                <div
                    v-if="
                        errorData &&
                        errorData.message !== '' &&
                        errorData.code >= 500
                    "
                >
                    {{ "Sorry! there’s a problem on our end." }}
                    <br />
                    {{ "Please try again in a moment" }}
                </div>

                <span
                    v-else-if="
                        errorData &&
                        errorData.message !== '' &&
                        errorData.code == 404
                    "
                >
                    {{ `${"Your request was not found!"}` }}
                </span>

                <span v-else>
                    {{ "There are no data to show" }}
                </span>
            </div>
        </div>

        <div class="d-flex align-items-center">
            <div
                class="btn btn-sm b-new-primary t-new-green fs-14"
                @click="emit('getData')"
            >
                <i class="bi bi-arrow-counterclockwise"></i>
                <span class="fs-12 ps-1">Reload</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps({
    errorData: {
        type: Object,
        default: () => {},
    },
});

const emit = defineEmits(["getData"]);

// Composable
</script>
