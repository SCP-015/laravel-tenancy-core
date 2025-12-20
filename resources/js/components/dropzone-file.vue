<template>
    <div>
        <!-- Dropzone -->
        <div v-if="fileURL" class="files is-box-shadow">
            <div class="file-item">
                <div>
                    <div class="flex justify-center">
                        <img
                            src="/images/pdf.svg"
                            width="50"
                            height="50"
                            v-if="acceptFile === '.pdf'"
                            alt="icon"
                        />
                        <img
                            :src="fileURL"
                            v-else
                            :id="`dropzoneFile`"
                            style="width: 70px; height: 70px; object-fit: cover"
                            alt="icon"
                        />
                    </div>
                    <div
                        v-if="image.name"
                        class="text-[12px] text-[#6D6D6D] mt-1"
                    >
                        {{ image.name }}
                    </div>
                    <div
                        class="delete-file flex justify-center mt-3"
                        @click="removeFile()"
                    >
                        {{ "Delete" }}
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="dropzone" v-bind="getRootProps()">
            <div
                class="border"
                :class="{
                    isDragActive,
                }"
            >
                <input v-bind="getInputProps()" />
                <div class="dropzone-custom-content text-center">
                    <div
                        class="mt-3 btn bg-[#4A4A4A] text-[#fff] text-[14px]"
                        v-if="!isDragActive"
                    >
                        <img src="/images/upload-light.svg" alt="icon" />
                        {{ "Choose File" }}
                    </div>

                    <div class="text-[12px] text-[#6D6D6D] mt-5">
                        {{
                            "Choose a file or drop it here... (File Format : PDF)"
                        }}.
                    </div>
                </div>
            </div>
        </div>
        <!-- End Dropzone -->
    </div>
</template>

<script setup>
import { ref, reactive, watch } from "vue";
import { useDropzone } from "vue3-dropzone";
import { useMainStore } from "../stores";

// Composable
const props = defineProps({
    file: {
        type: [File, String],
        default: "",
    },
    defaultImage: {
        type: String,
        default: "",
    },
    acceptFile: {
        type: String,
        default: ".jpg, .jpeg, .png",
    },
});
const emit = defineEmits(["update-file"]);

const mainStore = useMainStore();

// State
const fileURL = ref("");
const image = ref("");
const options = reactive({
    multiple: false,
    maxSize: 999999999999999,
    onDrop,
    accept: props.acceptFile,
});
const files = ref([]);
// End

// Watchers
watch(
    () => props.file,
    (val) => {
        if (val) {
            image.value = val;
            // Validasi: hanya panggil createObjectURL jika val adalah File atau Blob
            if (val instanceof File || val instanceof Blob) {
                fileURL.value = URL.createObjectURL(val);
            } else if (typeof val === 'string') {
                // Jika val adalah string (URL), gunakan langsung
                fileURL.value = val;
            }
        }
    },
    { deep: true, immediate: true }
);

watch(
    () => props.defaultImage,
    (val) => {
        if (val) image.value = val;
    },
    { deep: true, immediate: true }
);

watch(
    () => [fileURL, image],
    () => {
        emit("update-file", {
            image: image.value,
            fileURL: fileURL.value,
        });
    },
    { deep: true, immediate: true }
);
// end

// methods
const { getRootProps, getInputProps, isDragActive, ...rest } =
    useDropzone(options);

function onDrop(acceptFiles, rejectReasons) {
    if (rejectReasons.length > 0) {
        let message = [];
        for (const reason of rejectReasons) {
            // Error ukuran
            if (reason.errors[0].message.includes("bytes")) {
                let result = reason.errors[0].message.replace(
                    /\d+ bytes/,
                    (match) => {
                        // ini untuk ngubah bytes ke dalam format 'X MB'
                        let bytes = parseInt(match);
                        let megabytes = (bytes / (1024 * 1024)).toFixed(2);
                        return `${megabytes} MB!`;
                    }
                );

                result = `${reason.file.name} ${result.split("File")[1]}`;
                reason.errors[0].message = result;
            } else {
                // Error file type
                reason.errors[0].message = `${reason.file.name} ${
                    reason.errors[0].message.split("File")[1]
                }`;
            }
            message.push(reason.errors[0].message);
        }

        showPopup({
            status: true,
            type: "warning",
            title: "Warning",
            message: message,
        });
    }
    
    // Validasi: pastikan acceptFiles[0] ada dan valid
    if (!acceptFiles || acceptFiles.length === 0 || !acceptFiles[0]) {
        return;
    }
    
    const file = acceptFiles[0];
    
    // Validasi: pastikan file adalah File atau Blob
    if (!(file instanceof File) && !(file instanceof Blob)) {
        showPopup({
            status: true,
            type: "warning",
            title: "Warning",
            message: ["File tidak valid. Silakan pilih file yang benar."],
        });
        return;
    }
    
    image.value = file;
    fileURL.value = URL.createObjectURL(file);
}

const showPopup = (state) => {
    mainStore.stateShowInfo = state;
};

const removeFile = () => {
    // Revoke object URL untuk free memory dan mencegah memory leak
    if (fileURL.value && fileURL.value.startsWith('blob:')) {
        URL.revokeObjectURL(fileURL.value);
    }
    
    emit("update-file", {
        image: "",
        fileURL: "",
    });
    image.value = "";
    fileURL.value = "";
};
// End
</script>

<style scoped>
#customdropzone {
    padding: 0px 10px;
    border: dashed 1px #4a4a4a;
}

.dropzone,
.files {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    font-size: 12px;
    line-height: 1.5;
}

.border {
    border: 2px dashed #ccc;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    transition: all 0.3s ease;
    background: #fff;
}

.border .isDragActive {
    border: 2px dashed #ffb300;
    background: rgb(255 167 18 / 20%);
}

.file-item {
    display: flex;
    justify-content: center;
    border-radius: 8px;
    background: rgb(243 244 246);
    padding: 15px;
    margin-top: 10px;
}

.file-item:first-child {
    margin-top: 0;
}

.file-item .delete-file {
    display: flex;
    background: red;
    color: #fff;
    padding: 5px 10px;
    border-radius: 8px;
    cursor: pointer;
}
</style>

<style>
.dropzone .dz-message {
    text-align: center;
    margin: 0;
    /* position: absolute;
  height: 100%;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -30%); */
}

.dropzone-wrapper {
    border: 1px dashed #00852c;
    background: #f6f6f6;
    border-radius: 8px;
    min-height: 155px;
    height: auto;
    position: relative;
}

.img-wrapper {
    display: inline-block;
}

.info {
    font-size: 12px;
    color: #a2a2a2;
}
</style>
