<template>
  <div class="p-6 space-y-6">
     <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-800">Digital Signature Central</h1>
      <div v-if="hasCA && isAdmin" class="flex gap-2">
         <button @click="showCreateSessionModal = true" class="btn btn-sm btn-primary">
           <i class="fas fa-file-signature mr-2"></i> New Signing Session
         </button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs tabs-boxed bg-base-100 flex justify-between items-center px-4 py-2 shadow-sm">
        <div class="flex gap-1">
            <button @click="activeTab = 'dashboard'" :class="['tab tab-sm', activeTab === 'dashboard' ? 'tab-active' : '']">Dashboard</button>
            <button @click="activeTab = 'certificates'" :class="['tab tab-sm', activeTab === 'certificates' ? 'tab-active' : '']">Certificates</button>
        </div>
        <div class="flex gap-2">
             <button v-if="isAdmin && !hasCA" @click="showCreateCAModal = true" class="btn btn-xs btn-neutral">Setup Root CA</button>
        </div>
    </div>

    <!-- Dashboard Tab -->
    <div v-if="activeTab === 'dashboard'" class="space-y-6">
        <!-- Alert: No CA -->
        <div v-if="!hasCA" class="alert alert-warning shadow-lg">
          <div class="flex flex-col gap-2">
            <h3 class="font-bold">Root CA Not Found</h3>
            <p class="text-sm">You must configure the Root Certificate Authority (CA) for this tenant before issuing certificates or signing documents.</p>
          </div>
        </div>

    <!-- Stats / Active CA -->
    <div v-if="hasCA && caIncluded" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="stats shadow">
        <div class="stat">
          <div class="stat-title">Root CA</div>
          <div class="stat-value text-primary text-xl truncate" :title="caIncluded.name">{{ caIncluded.name }}</div>
          <div class="stat-desc">Valid until: {{ formatDate(caIncluded.valid_to) }}</div>
        </div>
      </div>
      
      <div class="stats shadow">
        <div class="stat">
          <div class="stat-title">My Certificates</div>
          <div class="stat-value text-secondary">{{ myCertificates.length }}</div>
          <div class="stat-desc">Active X.509 Certs</div>
        </div>
      </div>

       <div class="stats shadow">
        <div class="stat">
          <div class="stat-title">Pending Action</div>
          <div class="stat-value text-warning">{{ pendingSignatures.length }}</div>
          <div class="stat-desc">Documents waiting your signature</div>
        </div>
      </div>
    </div>

    <!-- Pending Signatures List -->
    <div v-if="pendingSignatures.length > 0" class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <h2 class="card-title text-sm uppercase tracking-wider text-gray-500">Documents to Sign</h2>
        <div class="overflow-x-auto">
          <table class="table w-full">
            <thead>
              <tr>
                <th>Document</th>
                <th>Session</th>
                <th>Role</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="sig in pendingSignatures" :key="sig.id">
                <td>
                   <div class="font-bold">{{ sig.document.title }}</div>
                   <div class="text-xs opacity-50">{{ sig.document.filename }}</div>
                </td>
                <td>
                  <div class="badge badge-ghost">{{ sig.signing_session.mode }}</div>
                </td>
                <td>{{ sig.role }}</td>
                <td>
                  <button @click="openSigningModal(sig)" class="btn btn-sm btn-primary">Sign Now</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Signed Documents List -->
    <div v-if="signedDocuments.length > 0" class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <h2 class="card-title text-sm uppercase tracking-wider text-gray-500">Recently Signed Documents</h2>
        <div class="overflow-x-auto">
          <table class="table w-full">
            <thead>
              <tr>
                <th>Document</th>
                <th>Signed At</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="doc in signedDocuments" :key="doc.id">
                <td>
                   <div class="font-bold">{{ doc.title }}</div>
                   <div class="text-xs opacity-50">{{ doc.filename }}</div>
                </td>
                <td>{{ formatDate(doc.signed_at) }}</td>
                <td>
                  <a :href="doc.download_url" class="btn btn-sm btn-secondary" download>
                      <i class="fas fa-download mr-1"></i> Download
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    </div>

    <!-- Certificates Tab (Unified) -->
    <div v-if="activeTab === 'certificates'" class="space-y-6">
        <!-- Section: My Certificate -->
        <div class="card bg-base-100 shadow-xl border-l-4 border-primary">
          <div class="card-body">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="card-title text-primary">My Digital Identity</h2>
                    <p class="text-sm opacity-70">Sertifikat ini digunakan khusus untuk tanda tangan Anda sendiri.</p>
                </div>
                <div v-if="myCertificates.length > 0" class="badge badge-success">Verified</div>
            </div>
            
            <div v-if="myCertificates.length === 0" class="py-6 text-center bg-base-200 rounded-xl mt-4">
                <p class="text-sm opacity-50 mb-3">Anda belum memiliki sertifikat digital.</p>
                <button @click="showIssueCertModal = true" class="btn btn-primary btn-sm">Issue My Certificate</button>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div v-for="cert in [...myCertificates].sort((a,b) => b.id - a.id)" :key="cert.id" class="border p-4 rounded-xl flex justify-between items-center bg-primary/5 border-primary/20">
                    <div>
                        <div class="badge badge-primary badge-outline text-[10px] mb-1">{{ cert.label || 'No Label' }}</div>
                        <div class="font-bold text-gray-800">{{ cert.common_name }}</div>
                        <div class="text-[10px] opacity-60">{{ cert.email }}</div>
                        <div class="text-[9px] mt-2 font-mono">EXP: {{ formatDate(cert.valid_to) }}</div>
                    </div>
                    <div class="text-primary"><i class="fas fa-check-circle fa-lg"></i></div>
                </div>
            </div>
          </div>
        </div>

        <!-- Section: Organization Management (Admin Only) -->
        <div v-if="isAdmin" class="card bg-base-100 shadow-xl">
          <div class="card-body">
            <h2 class="card-title text-sm uppercase tracking-wider text-gray-500">Employee Certificate Directory</h2>
            <p class="text-xs opacity-60">Pantau status sertifikat seluruh karyawan di dalam tenant ini.</p>
            
            <div class="overflow-x-auto mt-4">
              <table class="table table-zebra w-full">
                <thead>
                  <tr class="bg-base-200">
                    <th>User</th>
                    <th>Identity & Label</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="cert in allCertificates" :key="cert.id">
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="avatar placeholder">
                                <div class="bg-neutral text-neutral-content rounded-full w-8">
                                    <span class="text-xs">{{ cert.user_name.charAt(0) }}</span>
                                </div>
                            </div>
                            <div>
                                <div class="font-bold text-xs">{{ cert.user_name }}</div>
                                <div class="text-[10px] opacity-50">{{ cert.email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="text-xs font-bold">{{ cert.common_name }}</div>
                        <div class="text-[10px] text-primary italic">{{ cert.label || 'No Label' }}</div>
                    </td>
                    <td>
                        <div :class="['badge badge-xs', cert.is_revoked ? 'badge-error' : 'badge-success']">
                            {{ cert.is_revoked ? 'Revoked' : 'Active' }}
                        </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
    </div>
    <!-- Modals -->
    <CreateCAModal v-if="showCreateCAModal" @close="showCreateCAModal = false" />
    <IssueCertificateModal v-if="showIssueCertModal" @close="showIssueCertModal = false" />
    <CreateSessionModal v-if="showCreateSessionModal" :available-signers="availableSigners" :templates="templates" @close="showCreateSessionModal = false" />
    
    <SigningModal 
        v-if="selectedSignature" 
        :signature="selectedSignature" 
        :certificates="myCertificates"
        @close="selectedSignature = null" 
    />

  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import dayjs from 'dayjs';
import CreateCAModal from './Partials/CreateCAModal.vue';
import IssueCertificateModal from './Partials/IssueCertificateModal.vue';
import CreateSessionModal from './Partials/CreateSessionModal.vue';
import SigningModal from './Partials/SigningModal.vue';

const props = defineProps({
  hasCA: Boolean,
  caIncluded: {
    type: Object,
    default: null
  },
  myCertificates: {
    type: Array,
    default: () => []
  },
  pendingSignatures: {
    type: Array,
    default: () => []
  },
  signedDocuments: {
    type: Array,
    default: () => []
  },
  availableSigners: {
    type: Array,
    default: () => []
  },
  templates: {
    type: Array,
    default: () => []
  },
  allCertificates: {
    type: Array,
    default: () => []
  },
  isAdmin: Boolean
});

const activeTab = ref('dashboard');
const showCreateCAModal = ref(false);
const showIssueCertModal = ref(false);
const showCreateSessionModal = ref(false);
const selectedSignature = ref(null);

const formatDate = (date) => dayjs(date).format('DD MMM YYYY');

// Check if user has an active certificate
const hasActiveCertificate = computed(() => {
  return props.myCertificates && props.myCertificates.length > 0;
});

const openSigningModal = (sig) => {
    selectedSignature.value = sig;
};
</script>
