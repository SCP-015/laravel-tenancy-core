<template>
    <div>
        <textarea :id="props.id" class="w-100 is-input"></textarea>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, watch } from "vue";

const props = defineProps({
    id: {
        type: String,
        default: "tiny-editor",
        required: true,
    },
    data: {
        type: String,
        default: "",
    },
});
const emit = defineEmits(["update-content"]);

let content = computed({
    get() {
        return props.data;
    },
    set(newContent) {
        emit("update-content", newContent);
        return newContent;
    },
});

watch(
    content,
    (newValue) => {
        const editor = window?.tinymce?.get(props.id);
        if (editor && editor.getContent() !== newValue) {
            editor.setContent(newValue);
        }
    },
    { deep: true, immediate: true }
);

const initTinyMCE = () => {
    if (window.tinymce) {
        window.tinymce.init({
            selector: `#${props.id}`,
            setup(editor) {
                editor.on("init", () => {
                    editor.setContent(content.value || "");
                });

                editor.on("input change undo redo", () => {
                    content.value = editor.getContent();
                });
            },
            content_style:
                "body { font-family: 'Inter', sans-serif; color: #707070; } p { line-height: 1.5; margin-top: 0; margin-bottom: 0; }",
            menubar: false,
            statusbar: false,
            plugins: "lists link",
            toolbar:
                "undo redo | blocks | underline |" +
                "bold italic backcolor | alignleft aligncenter " +
                "alignright alignjustify | bullist numlist outdent indent | " +
                "link unlink removeformat",
        });
    }
};

onMounted(() => {
    if (window.tinymce) {
        initTinyMCE();
    } else {
        window.addEventListener("tinymceLoaded", initTinyMCE, { once: true });
    }
});

onUnmounted(() => {
    if (window.tinymce) {
        window.tinymce.get(props.id)?.destroy();
    }
});
</script>
