<template>
    <div>
        <input
            ref="phoneInput"
            type="tel"
            class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
        />
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import intlTelInput from "intl-tel-input";
import "intl-tel-input/build/css/intlTelInput.css";

const phoneInput = ref(null);
let iti = null;

const emit = defineEmits(["update:modelValue"]);
const props = defineProps({
    modelValue: String,
});

onMounted(() => {
    iti = intlTelInput(phoneInput.value, {
        initialCountry: "id",
        utilsScript:
            "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js",
    });

    phoneInput.value.addEventListener("input", () => {
        if (iti?.isValidNumber()) {
            emit("update:modelValue", iti.getNumber());
        } else {
            emit("update:modelValue", "");
        }
    });

    if (props.modelValue) {
        iti.setNumber(props.modelValue);
    }
});

onBeforeUnmount(() => {
    if (iti) {
        iti.destroy();
    }
});
</script>
<style>
.iti {
    width: 100%;
}
</style>
