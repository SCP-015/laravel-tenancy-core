<template>
    <div class="p-6">
        <div class="mb-6">
            <h2 class="text-[18px] sm:text-[24px] font-semibold text-gray-900">Default Signers</h2>
            <p class="text-[14px] text-gray-500">Kelola default signer berdasarkan workgroup untuk mode sequential signing.</p>
        </div>

        <!-- Add New Workgroup Form -->
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
            <h3 class="text-[14px] font-semibold text-gray-800 mb-3">Buat Workgroup Baru / Tambah Signer</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[13px] text-gray-600 mb-1">Workgroup</label>
                    <input
                        v-model="form.workgroup"
                        type="text"
                        placeholder="e.g. HR, Finance, Legal"
                        class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[14px]"
                    />
                </div>
                <div>
                    <label class="block text-[13px] text-gray-600 mb-1">User</label>
                    <select
                        v-model="form.user_id"
                        class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[14px]"
                    >
                        <option value="">Pilih User</option>
                        <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                            {{ user.name }} ({{ user.email }})
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-[13px] text-gray-600 mb-1">Urutan</label>
                    <input
                        v-model.number="form.step_order"
                        type="number"
                        min="1"
                        placeholder="1"
                        class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[14px]"
                    />
                </div>
                <div>
                    <label class="block text-[13px] text-gray-600 mb-1">Role/Jabatan</label>
                    <input
                        v-model="form.role"
                        type="text"
                        placeholder="e.g. Manager"
                        class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[14px]"
                    />
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button
                    @click="addSignerFromTop"
                    :disabled="!form.workgroup || !form.user_id || !form.step_order || loading"
                    class="bg-[#3A3A3A] text-white px-6 py-2 rounded-md shadow-sm transition hover:bg-gray-800 text-[14px] disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="loading">Loading...</span>
                    <span v-else>Tambah Signer</span>
                </button>
            </div>
        </div>

        <!-- Error Message -->
        <!-- Action Error Messages (Add/Edit/Delete) -->
        <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-[14px]">
            {{ error }}
        </div>
        <div v-if="actionErrorData" class="mb-4">
            <one-line-error-message :error-data="actionErrorData" @getData="fetchSigners"></one-line-error-message>
        </div>

        <!-- Main Content Area -->
        <div v-if="fetchErrorData">
            <error-message :error-data="fetchErrorData" @getData="fetchSigners"></error-message>
        </div>

        <!-- Skeleton Loader -->
        <div v-else-if="loading && !initialized" class="space-y-4">
            <div class="h-12 bg-gray-200 rounded animate-pulse w-full"></div>
            <div class="h-32 bg-gray-100 rounded animate-pulse w-full"></div>
            <div class="h-32 bg-gray-100 rounded animate-pulse w-full"></div>
        </div>

        <!-- Signers List by Workgroup -->
        <div v-else-if="initialized && signersGrouped.length === 0" class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
            <p class="text-gray-500 text-[14px]">Belum ada default signer. Tambahkan signer baru di atas.</p>
        </div>

        <div v-else v-for="group in signersGrouped" :key="group.workgroup" class="bg-white border border-gray-200 rounded-lg mb-4">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h3 class="text-[14px] font-semibold text-gray-800">{{ group.workgroup }}</h3>
                    <span class="text-[11px] text-gray-500">{{ group.signers.length }} signer(s)</span>
                </div>
                <button 
                    v-if="inlineForm.activeGroup !== group.workgroup"
                    @click="activateInlineAdd(group)"
                    class="text-[12px] bg-white border border-gray-300 px-3 py-1 rounded hover:bg-gray-50 transition shadow-sm text-gray-700"
                >
                    + Tambah Signer
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="text-left px-4 py-2 text-[11px] text-gray-600 font-medium w-16">Urutan</th>
                            <th class="text-left px-4 py-2 text-[11px] text-gray-600 font-medium">Nama</th>
                            <th class="text-left px-4 py-2 text-[11px] text-gray-600 font-medium">Email</th>
                            <th class="text-left px-4 py-2 text-[11px] text-gray-600 font-medium">Role</th>
                            <th class="text-left px-4 py-2 text-[11px] text-gray-600 font-medium w-24">Status</th>
                            <th class="text-center px-4 py-2 text-[11px] text-gray-600 font-medium w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="signer in group.signers" :key="signer.id" class="border-t border-gray-100 hover:bg-gray-50">
                            <!-- EDIT MODE ROW -->
                            <template v-if="editForm.id === signer.id">
                                <td class="px-4 py-3">
                                    <input 
                                        v-model.number="editForm.step_order" 
                                        type="number" 
                                        min="1" 
                                        class="w-full rounded border border-gray-300 p-1 text-[13px]" 
                                    />
                                </td>
                                <td class="px-4 py-3" colspan="2">
                                    <select v-model="editForm.user_id" class="w-full rounded border border-gray-300 p-1 text-[13px]">
                                        <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                            {{ user.name }} ({{ user.email }})
                                        </option>
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <input 
                                        v-model="editForm.role" 
                                        type="text" 
                                        placeholder="Role" 
                                        class="w-full rounded border border-gray-300 p-1 text-[13px]" 
                                    />
                                </td>
                                <td class="px-4 py-3 text-center" colspan="2">
                                    <div class="flex gap-2 justify-center">
                                        <button @click="saveEditSigner" :disabled="loading" class="text-xs bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700">Simpan</button>
                                        <button @click="cancelEdit" class="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded hover:bg-gray-300">Batal</button>
                                    </div>
                                </td>
                            </template>

                            <!-- VIEW MODE ROW -->
                            <template v-else>
                                <td class="px-4 py-3 text-[13px] text-gray-900">{{ signer.step_order }}</td>
                                <td class="px-4 py-3 text-[13px] text-gray-900 font-medium">{{ signer.user_name }}</td>
                                <td class="px-4 py-3 text-[13px] text-gray-600">{{ signer.user_email }}</td>
                                <td class="px-4 py-3 text-[13px] text-gray-600">{{ signer.role || '-' }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['rounded-full px-2 py-1 text-[11px]', signer.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600']">
                                        {{ signer.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="startEdit(signer)" class="text-blue-600 hover:text-blue-800 p-1" title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>
                                        
                                        <!-- Inline Delete Confirmation -->
                                        <div v-if="deleteConfirmationId === signer.id" class="flex items-center gap-1">
                                            <button @click="deleteSigner(signer.id)" class="text-[10px] bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700">Yakin?</button>
                                            <button @click="deleteConfirmationId = null" class="text-[10px] bg-gray-200 text-gray-700 px-2 py-1 rounded hover:bg-gray-300">Batal</button>
                                        </div>
                                        <button v-else @click="deleteConfirmationId = signer.id" class="text-red-600 hover:text-red-800 p-1" title="Hapus">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </template>
                        </tr>

                        <!-- INLINE ADD FORM ROW -->
                        <tr v-if="inlineForm.activeGroup === group.workgroup" class="bg-blue-50 border-t border-blue-100">
                            <td class="px-4 py-3">
                                <input v-model.number="inlineForm.step_order" type="number" min="1" class="w-full rounded border border-gray-300 p-1 text-[13px]"/>
                            </td>
                            <td class="px-4 py-3" colspan="2">
                                <select v-model="inlineForm.user_id" class="w-full rounded border border-gray-300 p-1 text-[13px]">
                                    <option value="">Pilih User</option>
                                    <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                        {{ user.name }} ({{ user.email }})
                                    </option>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <input v-model="inlineForm.role" type="text" placeholder="Role" class="w-full rounded border border-gray-300 p-1 text-[13px]"/>
                            </td>
                            <td class="px-4 py-3 text-center" colspan="2">
                                <div class="flex gap-2 justify-center">
                                    <button @click="saveInlineSigner" :disabled="loading || !inlineForm.user_id" class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700">Simpan</button>
                                    <button @click="cancelInlineAdd" class="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded hover:bg-gray-300">Batal</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useMainStore } from '../../stores';
import ErrorMessage from '../../components/error-message.vue';
import OneLineErrorMessage from '../../components/one-line-error-message.vue';

const mainStore = useMainStore();
const portal = computed(() => mainStore.userPortal.value);

const loading = ref(false);
const initialized = ref(false);
const error = ref(''); // For generic string errors (validation)
const fetchErrorData = ref(null); // For main data fetching errors
const actionErrorData = ref(null); // For action errors (404/500)
const signersData = ref([]);
const availableUsers = ref([]);
const deleteConfirmationId = ref(null);

// Form Atas (Create New Workgroup)
const form = ref({ workgroup: '', user_id: '', step_order: 1, role: '' });

// Inline Add Form State
const inlineForm = ref({ activeGroup: null, user_id: '', step_order: 1, role: '' });

// Inline Edit Form State
const editForm = ref({ id: null, user_id: '', step_order: 1, role: '' });

const signersGrouped = computed(() => signersData.value);

const fetchSigners = async () => {
    if (!portal.value || !portal.value[0]?.id) return;
    loading.value = true;
    fetchErrorData.value = null;
    actionErrorData.value = null; // Clear action errors on reload
    error.value = '';
    try {
        const portalId = portal.value[0]?.id;
        const response = await mainStore.useAPI(`${portalId}/api/default-signers`, { method: 'GET' }, true);
        signersData.value = response.data || [];
    } catch (err) {
        fetchErrorData.value = {
            code: err.response?.status || 500,
            message: err.message || 'Gagal memuat default signers'
        };
    } finally {
        loading.value = false;
        initialized.value = true;
    }
};

const fetchUsers = async () => {
    if (!portal.value || !portal.value[0]?.id) return;
    try {
        const portalId = portal.value[0]?.id;
        const response = await mainStore.useAPI(`${portalId}/api/default-signers/users`, { method: 'GET' }, true);
        availableUsers.value = response.data || [];
    } catch (err) {
        console.error('Error fetching users:', err);
    }
};

const initData = async () => {
    if (portal.value && portal.value.length > 0) {
        await fetchUsers();
        await fetchSigners();
    }
};

const addSignerFromTop = async () => {
    await submitSigner({ ...form.value });
    form.value = { workgroup: '', user_id: '', step_order: 1, role: '' };
};

const activateInlineAdd = (group) => {
    const maxOrder = group.signers.length > 0 ? Math.max(...group.signers.map(s => s.step_order)) : 0;
    inlineForm.value = { activeGroup: group.workgroup, user_id: '', step_order: maxOrder + 1, role: '' };
    editForm.value = { id: null, user_id: '', step_order: 1, role: '' };
    deleteConfirmationId.value = null;
};
const cancelInlineAdd = () => { inlineForm.value = { activeGroup: null, user_id: '', step_order: 1, role: '' }; };
const saveInlineSigner = async () => {
    if (!inlineForm.value.activeGroup || !inlineForm.value.user_id) return;
    await submitSigner({
        workgroup: inlineForm.value.activeGroup,
        user_id: inlineForm.value.user_id,
        step_order: inlineForm.value.step_order,
        role: inlineForm.value.role,
    });
    cancelInlineAdd();
};

// Edit Functions
const startEdit = (signer) => {
    editForm.value = {
        id: signer.id,
        user_id: signer.user_id,
        step_order: signer.step_order,
        role: signer.role || '',
    };
    inlineForm.value = { activeGroup: null, user_id: '', step_order: 1, role: '' };
    deleteConfirmationId.value = null;
    error.value = '';
    actionErrorData.value = null;
    console.log("Starting edit for signer ID:", signer.id);
};
const cancelEdit = () => { editForm.value = { id: null, user_id: '', step_order: 1, role: '' }; };
const saveEditSigner = async () => {
    if (!editForm.value.id) return;
    loading.value = true;
    error.value = '';
    actionErrorData.value = null;
    try {
        const portalId = portal.value[0]?.id;
        if (!portalId) throw new Error('Portal ID tidak ditemukan');
        
        console.log("Saving signer ID:", editForm.value.id);

        await mainStore.useAPI(
            `${portalId}/api/default-signers/${editForm.value.id}`,
            { method: 'PUT', body: {
                user_id: editForm.value.user_id,
                step_order: editForm.value.step_order,
                role: editForm.value.role
            }},
            true
        );
        cancelEdit();
        await fetchSigners();
        mainStore.stateShowInfo.show = true; mainStore.stateShowInfo.type = 'success'; mainStore.stateShowInfo.message = 'Default signer berhasil diperbarui.';
    } catch (err) {
        if (err.response?.status === 404 || err.response?.status >= 500) {
            actionErrorData.value = {
                code: err.response?.status,
                message: err.message
            };
        } else {
             error.value = err.response?.message || err.message || 'Gagal memperbarui signer';
        }
    } finally {
        loading.value = false;
    }
};

const submitSigner = async (payload) => {
    loading.value = true;
    error.value = '';
    actionErrorData.value = null;
    try {
        const portalId = portal.value[0]?.id;
        if (!portalId) throw new Error('Portal ID tidak ditemukan. Harap refresh halaman.');
        await mainStore.useAPI(`${portalId}/api/default-signers`, { method: 'POST', body: payload }, true);
        await fetchSigners();
        mainStore.stateShowInfo.show = true; mainStore.stateShowInfo.type = 'success'; mainStore.stateShowInfo.message = 'Default signer berhasil ditambahkan.';
    } catch (err) {
         if (err.response?.status === 404 || err.response?.status >= 500) {
            actionErrorData.value = {
                code: err.response?.status,
                message: err.message
            };
        } else {
             error.value = err.response?.message || err.message || 'Gagal menambahkan signer';
        }
    } finally {
        loading.value = false;
    }
};

const deleteSigner = async (id) => {
    loading.value = true;
    error.value = '';
    actionErrorData.value = null;
    console.log("Deleting signer ID:", id);

    try {
        const portalId = portal.value[0]?.id;
        if (!portalId) throw new Error('Portal ID tidak ditemukan');
        await mainStore.useAPI(`${portalId}/api/default-signers/${id}`, { method: 'DELETE' }, true);
        await fetchSigners();
        mainStore.stateShowInfo.show = true; mainStore.stateShowInfo.type = 'success'; mainStore.stateShowInfo.message = 'Default signer berhasil dihapus.';
        deleteConfirmationId.value = null;
    } catch (err) {
        if (err.response?.status === 404 || err.response?.status >= 500) {
            actionErrorData.value = {
                code: err.response?.status,
                message: err.message
            };
        } else {
             error.value = err.message || 'Gagal menghapus signer';
        }
    } finally {
        loading.value = false;
    }
};

watch(portal, (newVal) => { if (newVal && newVal.length > 0) initData(); }, { immediate: true });
onMounted(() => {});
</script>
```
