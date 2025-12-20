<template>
    <div class="row">
        <div
            class="col-6 text-center py-3"
            style="background: #ededed; min-height: 200px"
        >
            <button
                v-if="!imageSrc"
                class="fileUpload btn btn-sm btn-bulk me-2"
            >
                <span>{{ "Upload" }} </span>
                <input
                    type="file"
                    class="upload"
                    @change="onFileChange($event)"
                    accept="image/png, image/jpg, image/jpeg"
                />
            </button>

            <div v-if="imageSrc" class="crop-container">
                <img :src="imageSrc" ref="image" class="preview" />
            </div>
        </div>

        <div class="col-6">
            <p class="t-primary fs-14">Rasio Crop</p>
            <div :style="{ opacity: imageSrc ? 1 : 0.5 }">
                <div>
                    <input
                        v-model="selectedRatio"
                        :id="'rasio-1-1'"
                        type="radio"
                        class="align-middle me-2"
                        :value="1"
                        @change="setCropRatio(1, 1)"
                    />
                    <label :for="'rasio-1-1'" class="fs-14 pointer"
                        >{{ "Square" }} 1: 1</label
                    >
                </div>

                <div>
                    <input
                        v-model="selectedRatio"
                        :id="'rasio-3-1'"
                        type="radio"
                        class="align-middle me-2"
                        :value="3"
                        @change="setCropRatio(3, 1)"
                    />
                    <label :for="'rasio-3-1'" class="fs-14 pointer"
                        >{{ "Rectangle" }} 3: 1</label
                    >
                </div>
            </div>
            <canvas ref="canvas" style="display: none"></canvas>
        </div>
    </div>
</template>

<script setup>
import Cropper from "cropperjs";
import "cropperjs/dist/cropper.css";
import { ref } from "vue";

const emit = defineEmits(["updateLogo"]);

const imageSrc = ref(null);
const selectedRatio = ref(1);
const image = ref(null);
const canvas = ref(null);
const croppedImage = ref(null);
// Composable
let cropper = null;

const onFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            imageSrc.value = e.target.result;
            setTimeout(() => {
                if (cropper) cropper.destroy();
                cropper = new Cropper(image.value, {
                    aspectRatio: 1,
                    viewMode: 1,
                });
            }, 100);
        };
        reader.readAsDataURL(file);
    }
    setTimeout(() => {
        cropImage();
    }, 1000);
    event.target.value = "";
};

const setCropRatio = (width, height) => {
    if (cropper) {
        selectedRatio.value = width;
        cropper.setAspectRatio(width / height);
    }
    setTimeout(() => {
        cropImage();
    }, 1000);
};

const cropImage = () => {
    if (cropper) {
        const canvasEl = cropper.getCroppedCanvas();
        croppedImage.value = canvasEl.toDataURL("image/png");
        // let ai = croppedImage.value.replace(/^data:image\/[a-z]+;base64,/, '')
        emit("updateLogo", croppedImage.value);
    }
};

onBeforeUnmount(() => {
    if (cropper) cropper.destroy();
});
</script>

<style>
.btn-bulk {
    border-radius: 6px;
    background-color: #13852d;
    color: #ffffff;
}
.fileUpload {
    position: relative;
    overflow: hidden;
    margin: 10px;
}
.fileUpload input.upload {
    position: absolute;
    top: 0;
    right: 0;
    margin: 0;
    padding: 0;
    font-size: 20px;
    cursor: pointer;
    opacity: 0;
    filter: alpha(opacity=0);
}
.crop-container {
    max-width: 300px;
    max-height: 300px;
    overflow: hidden;
}
.preview {
    max-width: 100%;
    display: block;
}
</style>
