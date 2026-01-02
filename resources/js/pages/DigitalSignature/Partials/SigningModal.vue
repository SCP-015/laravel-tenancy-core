<template>
  <div class="modal modal-open">
    <div class="modal-box max-w-md overflow-visible">
      <h3 class="font-bold text-lg mb-4 text-gray-800">Sign Document</h3>
      
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                <span class="font-bold text-[10px] uppercase opacity-50 block mb-1">Document</span>
                <span class="font-semibold text-gray-700">{{ signature.document.title }}</span>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                <span class="font-bold text-[10px] uppercase opacity-50 block mb-1">Role</span>
                <span class="font-semibold text-gray-700">{{ signature.role }}</span>
            </div>
        </div>

        <div class="alert alert-info text-xs py-3 border-none bg-blue-50 text-blue-700 shadow-none">
            <i class="fas fa-info-circle mr-2"></i> Pilih identitas sertifikat Anda untuk menandatangani dokumen ini secara aman.
        </div>

        <div v-if="errorMessage" class="alert alert-error text-xs py-2">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ errorMessage }}
        </div>
        
        <form @submit.prevent="submit" id="signForm" class="space-y-6 pt-2">
          <!-- Custom Scrollable Dropdown -->
          <div class="form-control w-full relative">
              <label class="label"><span class="label-text font-bold text-gray-700">Pilih Sertifikat</span></label>
              
              <div class="relative">
                  <button 
                    type="button"
                    @click="isOpen = !isOpen"
                    @blur="setTimeout(() => isOpen = false, 200)"
                    class="btn btn-outline border-gray-300 w-full justify-between bg-white hover:bg-white hover:border-primary text-gray-700 normal-case font-normal px-4 shadow-sm"
                  >
                    <span class="truncate max-w-[300px]">
                        {{ selectedCert ? (selectedCert.label || 'Tanpa Label') : 'Pilih Sertifikat...' }}
                        <span v-if="selectedCert" class="text-[10px] opacity-50 ml-1">({{ selectedCert.common_name }})</span>
                    </span>
                    <i :class="['fas text-xs transition-transform duration-200', isOpen ? 'fa-chevron-up rotate-0' : 'fa-chevron-down']"></i>
                  </button>

                  <div 
                    v-if="isOpen" 
                    class="absolute z-[1000] mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-2xl max-h-48 overflow-y-auto py-1 animate-in fade-in slide-in-from-top-2 duration-200"
                  >
                      <div 
                        v-for="cert in certificates" 
                        :key="cert.id"
                        @click="selectCert(cert.id)"
                        :class="['px-4 py-2.5 hover:bg-primary hover:text-white cursor-pointer text-sm transition-colors flex flex-col', form.certificate_id === cert.id ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-700']"
                      >
                          <span>{{ cert.label || 'Tanpa Label' }}</span>
                          <span class="text-[10px] opacity-60">{{ cert.common_name }}</span>
                      </div>
                      <div v-if="certificates.length === 0" class="px-4 py-3 text-gray-400 italic text-sm text-center">
                          Belum ada sertifikat aktif.
                      </div>
                  </div>
              </div>

              <label class="label text-error text-xs" v-if="form.errors.certificate_id">
                  <span>{{ form.errors.certificate_id }}</span>
              </label>
          </div>
          
          <div class="form-control bg-gray-50 p-4 rounded-xl border border-gray-100">
               <label class="flex items-start gap-3 cursor-pointer group">
                  <input v-model="form.agreement" type="checkbox" required class="checkbox checkbox-primary mt-0.5" />
                  <span class="label-text text-sm text-gray-600 leading-normal group-hover:text-gray-900 transition-colors">
                      Saya mengonfirmasi telah meninjau dokumen ini dan setuju untuk menandatanganinya secara elektronik.
                  </span> 
              </label>
          </div>
        </form>
      </div>

      <div class="modal-action mt-8 flex justify-end gap-3">
        <button type="button" class="btn btn-ghost" @click="$emit('close')">Cancel</button>
        <button type="submit" form="signForm" class="btn btn-primary px-10" :disabled="form.processing || certificates.length === 0">
            <i class="fas fa-file-contract mr-2"></i> Sign Document
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    signature: Object,
    certificates: Array
});

const emit = defineEmits(['close']);
const errorMessage = ref('');
const isOpen = ref(false);

const form = useForm({
  agreement: false,
  certificate_id: props.certificates.length > 0 ? props.certificates[0].id : null,
});

const selectedCert = computed(() => {
    return props.certificates.find(c => c.id === form.certificate_id);
});

const selectCert = (id) => {
    form.certificate_id = id;
    isOpen.value = false;
};

const submit = async () => {
    if (!form.certificate_id) {
        errorMessage.value = 'Mohon pilih identitas sertifikat terlebih dahulu.';
        return;
    }

    const hashedPassphrase = '48124d404081da40b791ee3617307062211913346b9f2c3d59664687d7f78c89'; 

    const pathParts = window.location.pathname.split('/');
    const tenantSlug = pathParts[1];
    
    const payload = {
        passphrase_hash: hashedPassphrase,
        certificate_id: form.certificate_id,
        agreement: form.agreement
    };
    
    form.transform(() => payload).post(`/${tenantSlug}/admin/digital-signature/sign/${props.signature.id}`, {
        onSuccess: () => {
            errorMessage.value = '';
            emit('close');
        },
        onError: (err) => {
            errorMessage.value = err.msg || err.passphrase || 'Gagal menandatangani dokumen.';
            console.error('Signing failed:', err);
        }
    });
};
</script>
