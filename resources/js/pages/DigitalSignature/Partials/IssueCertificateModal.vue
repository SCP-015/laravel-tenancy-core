<template>
  <div class="modal modal-open">
    <div class="modal-box">
      <h3 class="font-bold text-lg">Request User Certificate</h3>
      <p class="py-4 text-sm opacity-70">Generate a new X.509 certificate for your user account. You must set a passphrase to protect your private key.</p>
      
      <form @submit.prevent="submit">
        <!-- User Info Readonly -->
        <div class="form-control w-full" v-if="$page.props.auth?.user">
            <label class="label"><span class="label-text">Identity</span></label>
            <input type="text" :value="$page.props.auth.user.name + ' (' + $page.props.auth.user.email + ')'" disabled class="input input-bordered w-full" />
        </div>

        <div class="form-control w-full mt-2">
          <label class="label"><span class="label-text">Certificate Name / Label</span></label>
          <input v-model="form.label" type="text" placeholder="e.g. Finance, Director, Daily" class="input input-bordered w-full" required />
          <label class="label text-xs opacity-50">This name will be used to identify your signature identity in the dropdown.</label>
          <label class="label text-error" v-if="form.errors.label"><span class="label-text-alt">{{ form.errors.label }}</span></label>
        </div>

        <div class="modal-action">
          <button type="button" class="btn" @click="$emit('close')">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="form.processing">Generate Certificate</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3';

const emit = defineEmits(['close']);
const page = usePage();

// Get user ID from the auth prop passed by controller
const userId = page.props.auth?.user?.id || null;

const form = useForm({
  label: '',
  user_id: userId,
});

const submit = async () => {
    // Use an internal hardcoded passphrase hash for automatic signing
    const hashedPassphrase = '48124d404081da40b791ee3617307062211913346b9f2c3d59664687d7f78c89'; // SHA256 of 'internal-convenience-key'
    
    const pathParts = window.location.pathname.split('/');
    const tenantSlug = pathParts[1];
    
    const payload = {
        passphrase_hash: hashedPassphrase,
        label: form.label,
        user_id: form.user_id
    };
    
    form.transform(() => payload).post(`/${tenantSlug}/admin/digital-signature/certificates`, {
        onSuccess: () => {
            form.reset();
            emit('close');
        },
        onError: (errors) => {
            console.error('Certificate request failed:', errors);
        }
    });
};
</script>
