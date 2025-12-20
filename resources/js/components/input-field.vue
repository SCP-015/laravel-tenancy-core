<template>
    <input
        :value="modelValue"
        :type="type"
        class="text-[14px] border border-[#BABABA] rounded-md px-3 py-2 text-[#4A4A4A] focus:outline-none"
        :class="[
            classWidth,
            disabled
                ? 'bg-[#F6F6F6] border-[#C8C8C8] cursor-not-allowed'
                : 'bg-white',
        ]"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        @input="$emit('update:modelValue', $event.target.value)"
        @paste="$emit('paste', $event)"
    />
</template>

<script setup>
defineProps({
    type: {
        type: String,
        default: "text",
    },
    placeholder: {
        type: String,
        default: "",
    },
    modelValue: {
        type: String,
        default: "",
    },
    classWidth: {
        type: String,
        default: "w-[250px]",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    readonly: {
        type: Boolean,
        default: false,
    },
});

defineEmits(["update:modelValue", "paste"]);
</script>
