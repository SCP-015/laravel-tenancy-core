<!-- CustomCellWrapper.vue -->
<template>
    <slot :name="slotName" :item="item">
        <td class="px-6 py-3 whitespace-nowrap" :class="thClass">
            {{ header.label }}
        </td>
    </slot>
</template>

<script setup>
defineProps({
    slotName: String,
    header: Object,
    item: Object,
    thClass: String,
});
</script>
