<template>
  <div class="modal modal-open">
    <div class="modal-box">
      <h3 class="font-bold text-lg">Setup Root CA</h3>
      <p class="py-4 text-sm opacity-70">Initialize the Public Key Infrastructure for this tenant. This will generate a Root Certificate Authority.</p>
      
      <form @submit.prevent="submit">
        <div class="form-control w-full">
          <label class="label"><span class="label-text">Organization Name *</span></label>
          <input v-model="form.organization" type="text" placeholder="e.g. Acme Corp" class="input input-bordered w-full" required />
          <label class="label text-error" v-if="form.errors.organization"><span class="label-text-alt">{{ form.errors.organization }}</span></label>
        </div>
        
        <div class="form-control w-full mt-2">
          <label class="label"><span class="label-text">Common Name *</span></label>
          <input v-model="form.common_name" type="text" placeholder="e.g. Acme Corp Root CA" class="input input-bordered w-full" required />
          <label class="label text-error" v-if="form.errors.common_name"><span class="label-text-alt">{{ form.errors.common_name }}</span></label>
        </div>

        <div class="form-control w-full mt-2">
          <label class="label"><span class="label-text">Country Code *</span></label>
          <input v-model="form.country" type="text" placeholder="e.g. ID" maxlength="2" class="input input-bordered w-full" required />
          <label class="label text-error" v-if="form.errors.country"><span class="label-text-alt">{{ form.errors.country }}</span></label>
        </div>

        <div class="form-control w-full mt-2">
          <label class="label"><span class="label-text">State/Province</span></label>
          <input v-model="form.state" type="text" placeholder="Optional" class="input input-bordered w-full" />
          <label class="label text-error" v-if="form.errors.state"><span class="label-text-alt">{{ form.errors.state }}</span></label>
        </div>

        <div class="form-control w-full mt-2">
          <label class="label"><span class="label-text">City</span></label>
          <input v-model="form.city" type="text" placeholder="Optional" class="input input-bordered w-full" />
          <label class="label text-error" v-if="form.errors.city"><span class="label-text-alt">{{ form.errors.city }}</span></label>
        </div>

        <div class="form-control w-full mt-2">
          <label class="label"><span class="label-text">Certificate Validity</span></label>
          <div class="input input-bordered w-full flex items-center justify-between bg-gray-100">
            <span class="font-semibold">10 Years (3650 days)</span>
            <span class="badge badge-primary">Fixed</span>
          </div>
        </div>

        <div class="modal-action">
          <button type="button" class="btn" @click="$emit('close')">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="form.processing">
            <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
            Create CA
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3';

const emit = defineEmits(['close']);
const page = usePage();

const form = useForm({
  organization: '',
  common_name: '',
  country: '',
  state: '',
  city: '',
  validity_days: 3650,
});

const submit = () => {
    console.log('Submitting CA form:', form.data());
    
    // Get tenant slug from current URL
    const pathParts = window.location.pathname.split('/');
    const tenantSlug = pathParts[1]; // First part after domain is tenant slug
    const url = `/${tenantSlug}/admin/digital-signature/ca`;
    
    console.log('Posting to URL:', url);
    
    form.post(url, {
        onSuccess: (page) => {
            console.log('CA created successfully', page);
            emit('close');
            window.location.reload();
        },
        onError: (errors) => {
            console.error('CA creation failed:', errors);
        },
        onFinish: () => {
            console.log('Request finished');
        }
    });
};
</script>
