<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-12">
        <div class="flex justify-center mb-6">
          <div class="p-4 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl shadow-xl">
            <i class="fas fa-certificate fa-3x"></i>
          </div>
        </div>
        <h1 class="text-4xl font-extrabold text-gray-900 mb-2">Signature Verification</h1>
        <p class="text-lg text-gray-600">Upload a signed document to verify its authenticity</p>
      </div>

      <!-- Upload Card -->
      <div v-if="!uploadResult" class="bg-white shadow-2xl rounded-2xl p-8 mb-8">
        <div class="mb-6">
          <h2 class="text-2xl font-bold text-gray-800 mb-2">Upload Signed Document</h2>
          <p class="text-sm text-gray-500">Drag and drop your PDF file or click to browse</p>
        </div>

        <form @submit.prevent="submitFile">
          <!-- Dropzone -->
          <div 
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            :class="[
              'border-4 border-dashed rounded-xl p-12 text-center transition-all duration-200',
              isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-blue-400'
            ]"
          >
            <input 
              ref="fileInput"
              type="file" 
              @change="handleFileSelect" 
              accept=".pdf"
              class="hidden"
            />
            
            <div v-if="!selectedFile">
              <i class="fas fa-cloud-upload-alt text-6xl text-gray-400 mb-4"></i>
              <p class="text-xl font-semibold text-gray-700 mb-2">Drop your PDF here</p>
              <p class="text-sm text-gray-500 mb-4">or</p>
              <button 
                type="button"
                @click="$refs.fileInput.click()" 
                class="btn btn-primary btn-lg"
              >
                <i class="fas fa-folder-open mr-2"></i>
                Browse Files
              </button>
              <p class="text-xs text-gray-400 mt-4">Only PDF files (Max 10MB)</p>
            </div>

            <div v-else class="space-y-4">
              <i class="fas fa-file-pdf text-6xl text-red-500"></i>
              <div>
                <p class="text-lg font-semibold text-gray-800">{{ selectedFile.name }}</p>
                <p class="text-sm text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
              </div>
              <button 
                type="button"
                @click="clearFile" 
                class="btn btn-sm btn-ghost text-red-600"
              >
                <i class="fas fa-times mr-1"></i> Remove
              </button>
            </div>
          </div>

          <!-- Error Display -->
          <div v-if="errorMessage" class="alert alert-error mt-4">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ errorMessage }}
          </div>

          <!-- Submit Button -->
          <div class="mt-6 flex justify-center">
            <button 
              type="submit" 
              :disabled="!selectedFile || isVerifying"
              class="btn btn-primary btn-lg px-8"
            >
              <i class="fas fa-shield-alt mr-2"></i>
              <span v-if="!isVerifying">Verify Signature</span>
              <span v-else>
                <span class="loading loading-spinner loading-sm mr-2"></span>
                Verifying...
              </span>
            </button>
          </div>
        </form>
      </div>

      <!-- Verification Result -->
      <div v-if="uploadResult" class="bg-white shadow-2xl rounded-2xl overflow-hidden">
        <!-- Status Banner -->
        <div :class="[
          'p-6 text-center',
          uploadResult.success ? 'bg-gradient-to-r from-green-500 to-green-600' : 'bg-gradient-to-r from-red-500 to-red-600'
        ]">
          <i :class="[
            'text-6xl text-white mb-3',
            uploadResult.success ? 'fas fa-check-circle' : 'fas fa-times-circle'
          ]"></i>
          <h2 class="text-3xl font-bold text-white">
            {{ uploadResult.message }}
          </h2>
          <p class="text-white text-opacity-90 mt-2">
            {{ uploadResult.description }}
          </p>
        </div>

        <!-- Document Details (if exists) -->
        <div v-if="uploadResult.document" class="p-6 border-b border-gray-200">
          <h3 class="text-xl font-bold text-gray-800 mb-4">Document Information</h3>
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <dt class="text-sm font-medium text-gray-500">Title</dt>
              <dd class="mt-1 text-base font-semibold text-gray-900">{{ uploadResult.document.title }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">Filename</dt>
              <dd class="mt-1 text-base text-gray-900">{{ uploadResult.document.filename }}</dd>
            </div>
            <div class="md:col-span-2">
              <dt class="text-sm font-medium text-gray-500">SHA-256 Hash</dt>
              <dd class="mt-1 text-xs font-mono bg-gray-100 p-3 rounded break-all">{{ uploadResult.document.current_hash }}</dd>
            </div>
          </dl>
        </div>

        <!-- Filename only (if document not found) -->
        <div v-if="!uploadResult.document && uploadResult.filename" class="p-6 border-b border-gray-200">
          <h3 class="text-xl font-bold text-gray-800 mb-4">Uploaded File</h3>
          <p class="text-gray-600"><i class="fas fa-file-pdf mr-2 text-red-500"></i>{{ uploadResult.filename }}</p>
        </div>

        <!-- Signatures -->
        <div v-if="uploadResult.signatures && uploadResult.signatures.length > 0" class="p-6">
          <h3 class="text-xl font-bold text-gray-800 mb-4">Digital Signatures ({{ uploadResult.signatures.length }})</h3>
          <div class="space-y-4">
            <div 
              v-for="sig in uploadResult.signatures" 
              :key="sig.serial"
              class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200"
            >
              <div class="flex-shrink-0 h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-signature text-blue-600 text-xl"></i>
              </div>
              <div class="flex-1">
                <p class="font-bold text-gray-900">{{ sig.signer }}</p>
                <p class="text-sm text-gray-600 mt-1">
                  <i class="fas fa-clock mr-1"></i> Signed: {{ formatDate(sig.signed_at) }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                  <i class="fas fa-fingerprint mr-1"></i> Certificate S/N: {{ sig.serial }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                  <i class="fas fa-network-wired mr-1"></i> IP: {{ sig.ip }}
                </p>
              </div>
              <div class="flex-shrink-0">
                <span class="badge badge-success gap-1">
                  <i class="fas fa-check"></i> Valid
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="p-6 bg-gray-50 text-center">
          <button @click="reset" class="btn btn-outline btn-primary">
            <i class="fas fa-redo mr-2"></i> Verify Another Document
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import dayjs from 'dayjs';

const props = defineProps({
  uploadResult: {
    type: Object,
    default: null
  }
});

const selectedFile = ref(null);
const isDragging = ref(false);
const isVerifying = ref(false);
const errorMessage = ref('');
const uploadResult = ref(props.uploadResult);
const fileInput = ref(null);

onMounted(() => {
  // If we have uploadResult from props, display it
  if (props.uploadResult) {
    uploadResult.value = props.uploadResult;
  }
});

const handleFileSelect = (event) => {
  const file = event.target.files[0];
  if (file && file.type === 'application/pdf') {
    selectedFile.value = file;
    errorMessage.value = '';
  } else {
    errorMessage.value = 'Please select a valid PDF file';
  }
};

const handleDrop = (event) => {
  isDragging.value = false;
  const file = event.dataTransfer.files[0];
  if (file && file.type === 'application/pdf') {
    selectedFile.value = file;
    errorMessage.value = '';
  } else {
    errorMessage.value = 'Please drop a valid PDF file';
  }
};

const clearFile = () => {
  selectedFile.value = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const submitFile = () => {
  if (!selectedFile.value) return;
  
  isVerifying.value = true;
  errorMessage.value = '';
  
  const formData = new FormData();
  formData.append('file', selectedFile.value);
  
  const pathParts = window.location.pathname.split('/');
  const tenantSlug = pathParts[1];
  
  router.post(`/${tenantSlug}/admin/digital-signature/verify-file`, formData, {
    forceFormData: true,
    preserveState: false,
    preserveScroll: true,
    onSuccess: () => {
      isVerifying.value = false;
    },
    onError: (errors) => {
      errorMessage.value = errors.msg || errors.file || 'Verification failed. Please try again.';
      isVerifying.value = false;
    }
  });
};

const reset = () => {
  uploadResult.value = null;
  clearFile();
  errorMessage.value = '';
  
  // Navigate back to verify page to clear state
  const pathParts = window.location.pathname.split('/');
  const tenantSlug = pathParts[1];
  router.visit(`/${tenantSlug}/admin/digital-signature/verify-signature`, {
    preserveState: false
  });
};

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
};

const formatDate = (date) => dayjs(date).format('DD MMM YYYY, HH:mm:ss');
</script>
