<template>
    <div class="form-group">
        <div class="flex flex-nowrap">
            <div class="text-[#6D6D6D] text-[12px] font-[500] mb-1 block">
                <span v-if="labelHtml" v-html="label"></span>
                <span v-else>{{ label }}</span>
            </div>

            <div class="ml-auto">
                <slot name="extra" />
            </div>
        </div>
        <slot />
    </div>
</template>

<script setup>
defineProps({
    label: String,
    labelHtml: {
        type: Boolean,
        default: false,
    },
});
</script>
