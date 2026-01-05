<template>
  <div class="modal modal-open">
    <div class="modal-box w-11/12 max-w-3xl">
      <h3 class="font-bold text-lg">New Signing Session</h3>
      
      <form @submit.prevent="submit" class="mt-4 space-y-4">
        
        <!-- File Upload -->
        <div class="form-control">
            <label class="label"><span class="label-text">Upload Document (PDF)</span></label>
            <input type="file" @change="form.file = $event.target.files[0]" class="file-input file-input-bordered w-full" accept=".pdf" required />
            <label class="label text-error" v-if="errors.file"><span class="label-text-alt">{{ errors.file }}</span></label>
        </div>
        
        <div class="form-control">
            <label class="label"><span class="label-text">Session Title</span></label>
            <input v-model="form.title" type="text" placeholder="e.g. Sales Contract Q1" class="input input-bordered" required />
        </div>

        <!-- Mode Selection -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-control">
                <label class="label"><span class="label-text">Signing Mode</span></label>
                <select v-model="form.mode" class="select select-bordered">
                    <option value="parallel">Parallel (Any Order)</option>
                    <option value="sequential">Sequential (Ordered)</option>
                </select>
            </div>
            
            <div class="form-control" v-if="form.mode === 'sequential' && templates && templates.length > 0">
                <label class="label"><span class="label-text text-primary font-bold">Apply Approval Template</span></label>
                <select @change="applyTemplate($event.target.value)" class="select select-bordered select-primary">
                    <option value="">-- Manual Selection --</option>
                    <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
                <label class="label"><span class="label-text-alt opacity-70">Pre-defined workflow steps</span></label>
            </div>
        </div>

        <!-- Signers -->
        <div class="border p-4 rounded-lg bg-base-200">
            <div class="flex justify-between items-center mb-2">
                <h4 class="font-bold text-sm">Signers</h4>
                <button type="button" @click="addSigner" class="btn btn-xs btn-ghost">+ Add Signer</button>
            </div>
            
            <div v-for="(signer, index) in form.signers" :key="index" class="flex gap-2 mb-2 items-end">
                <div class="form-control flex-1">
                    <label class="label text-xs" v-if="index === 0">Signer</label>
                    <select v-model="signer.user_id" class="select select-sm select-bordered" required>
                        <option value="" disabled>Select User</option>
                        <option v-for="user in users" :key="user.id" :value="user.id">
                            {{ user.name }} ({{ user.email }})
                            {{ !user.has_certificate ? '⚠️ No Certificate' : '' }}
                        </option>
                    </select>
                </div>
                <div class="form-control flex-1">
                    <label class="label text-xs" v-if="index === 0">Role</label>
                    <input v-model="signer.role" type="text" placeholder="e.g. Manager" class="input input-sm input-bordered" required />
                </div>
                 <button type="button" @click="removeSigner(index)" class="btn btn-sm btn-square btn-ghost text-error">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
             <p class="text-xs opacity-50 mt-1" v-if="form.mode === 'sequential'">* Signers will be requested in the order listed above.</p>
        </div>

        <div class="modal-action">
          <button type="button" class="btn" @click="$emit('close')" :disabled="processing">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="processing">
            <span v-if="processing" class="loading loading-spinner loading-sm mr-2"></span>
            {{ processing ? 'Creating...' : 'Create Session' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

let mainStore = null;

const emit = defineEmits(['close']);
const props = defineProps({
  availableSigners: {
    type: Array,
    default: () => []
  },
  templates: {
    type: Array,
    default: () => []
  }
});

const users = computed(() => props.availableSigners || []);
const processing = ref(false);
const errors = ref({});

const form = ref({
  file: null,
  title: '',
  mode: 'parallel',
  signers: [
      { user_id: '', role: '', step_order: 1, is_required: true }
  ]
});

onMounted(async () => {
  try {
    const { useMainStore } = await import('../../../stores');
    mainStore = useMainStore();
  } catch (error) {
    console.error('Failed to load mainStore:', error);
  }
});

const addSigner = () => {
    form.value.signers.push({ 
        user_id: '', 
        role: '', 
        step_order: form.value.signers.length + 1,
        is_required: true 
    });
};

const removeSigner = (index) => {
    if (form.value.signers.length > 1) {
        form.value.signers.splice(index, 1);
        form.value.signers.forEach((s, i) => s.step_order = i + 1);
    }
};

const applyTemplate = (templateId) => {
    if (!templateId) return;
    const template = props.templates.find(t => t.id === templateId);
    if (template) {
        const firstSigner = form.value.signers[0];
        
        const templateSigners = template.steps.map((step, idx) => ({
            user_id: step.user_id,
            role: step.role,
            step_order: idx + 2,
            is_required: true
        }));

        form.value.signers = [firstSigner, ...templateSigners];
        form.value.mode = 'sequential'; 
    }
};

const submit = async () => {
    if (!mainStore) {
        console.error('MainStore not initialized');
        errors.value = { general: 'System not ready, please try again' };
        return;
    }

    processing.value = true;
    errors.value = {};

    const pathParts = window.location.pathname.split('/');
    const tenantSlug = pathParts[1];
    
    const formData = new FormData();
    formData.append('file', form.value.file);
    formData.append('title', form.value.title);
    formData.append('mode', form.value.mode);
    
    // Properly format array for Laravel multipart/form-data
    form.value.signers.forEach((signer, index) => {
        formData.append(`signers[${index}][user_id]`, signer.user_id);
        formData.append(`signers[${index}][role]`, signer.role);
        formData.append(`signers[${index}][step_order]`, signer.step_order);
        formData.append(`signers[${index}][is_required]`, signer.is_required ? 1 : 0);
    });

    try {
        const response = await mainStore.useAPI(
            `${tenantSlug}/api/digital-signature/session`,
            {
                method: 'POST',
                body: formData
            },
            true
        );

        if (response.status === 201 || response.status === 200 || response.code === 201 || response.status === 'success') {
            emit('close');
            router.reload({ only: ['pendingSignatures', 'signedDocuments', 'availableSigners'] });
        } else {
            errors.value = response.errors || {};
        }
    } catch (err) {
        console.error('Create session failed:', err);
        errors.value = { general: err.message || 'Failed to create session' };
    } finally {
        processing.value = false;
    }
};
</script>
