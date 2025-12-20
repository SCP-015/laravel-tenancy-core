<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-white px-4 py-8">
        <div class="mb-6 text-center relative">
            <h1 class="text-4xl font-bold text-blue-600">
                <img
                    src="/images/logo-with-nusawork.svg"
                    alt="logo nusahire"
                />
            </h1>
            <a
                class="text-[12px] text-[#00884F] mt-2 absolute bottom-4 left-26"
                target="_blank"
                rel="noopener"
                href="https://nusawork.com"
            >
                by Nusawork
            </a>
        </div>

        <div class="w-full max-w-4xl bg-[#FBFBFB] border border-[#EDEDED] rounded-lg p-6 shadow-sm">
            <h2 class="text-[18px] sm:text-[24px] font-semibold text-gray-900 mb-2">
                Nusawork Dev Callback Playground
            </h2>
            <p class="text-[14px] text-gray-500 mb-4">
                Halaman ini hanya untuk pengembangan lokal. Gunakan halaman ini untuk
                menguji flow callback Nusawork dengan memasukkan token dan data user
                secara manual.
            </p>

            <form class="space-y-5" @submit.prevent="handleSubmit">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[13px] font-medium text-gray-700">
                            Dev helper: Body JSON (Postman)
                        </label>
                        <button
                            type="button"
                            class="text-[12px] text-gray-600 hover:text-gray-900 underline"
                            @click="applyJsonBody"
                            :disabled="!rawJsonBody.trim()"
                        >
                            Isi form dari JSON
                        </button>
                    </div>
                    <textarea
                        v-model="rawJsonBody"
                        rows="5"
                        class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[12px] font-mono"
                        placeholder='Paste body JSON lengkap dari Postman di sini'
                    ></textarea>
                    <p class="text-[11px] text-gray-500 mt-1">
                        Field <span class="font-mono">token</span>, <span class="font-mono">email</span>, <span class="font-mono">first_name</span>, <span class="font-mono">last_name</span>, <span class="font-mono">photo</span>, <span class="font-mono">company.name</span>, <span class="font-mono">company.address</span>, <span class="font-mono">join_code</span>, <span class="font-mono">force_create_user</span>, dan <span class="font-mono">use_session_flow</span> akan diisi otomatis jika ada di JSON.
                    </p>
                    <div
                        v-if="jsonParseError"
                        class="mt-1 text-[11px] text-red-600"
                    >
                        {{ jsonParseError }}
                    </div>
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-gray-700 mb-1">
                        Token Nusawork (raw)
                    </label>
                    <textarea
                        v-model="token"
                        rows="4"
                        class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[13px] font-mono"
                        placeholder="Tempel token Nusawork di sini"
                    ></textarea>
                    <p class="text-[11px] text-gray-500 mt-1">
                        Token akan diparse di frontend untuk membantu debugging, tetapi validasi utama tetap dilakukan di backend.
                    </p>
                    <div
                        v-if="parsedToken"
                        class="mt-2 bg-white border border-gray-200 rounded p-2 max-h-40 overflow-auto text-left"
                    >
                        <p class="text-[11px] text-gray-500 mb-1">
                            Payload token (parsed, read-only):
                        </p>
                        <pre class="whitespace-pre-wrap break-all text-[11px] text-gray-700">
{{ parsedToken }}
                        </pre>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-1">
                            Email
                        </label>
                        <input
                            v-model="email"
                            type="email"
                            class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[13px]"
                            placeholder="user@example.com"
                        />
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-1">
                            Foto (URL, opsional)
                        </label>
                        <input
                            v-model="photo"
                            type="text"
                            class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[13px]"
                            placeholder="https://..."
                        />
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-1">
                            Nama Depan
                        </label>
                        <input
                            v-model="firstName"
                            type="text"
                            class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[13px]"
                            placeholder="Nama depan"
                        />
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-1">
                            Nama Belakang (opsional)
                        </label>
                        <input
                            v-model="lastName"
                            type="text"
                            class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[13px]"
                            placeholder="Nama belakang"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-1">
                            Nama Perusahaan
                        </label>
                        <input
                            v-model="companyName"
                            type="text"
                            class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[13px]"
                            placeholder="Nama perusahaan"
                        />
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-1">
                            Alamat Perusahaan
                        </label>
                        <input
                            v-model="companyAddress"
                            type="text"
                            class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[13px]"
                            placeholder="Alamat perusahaan"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="block text-[13px] font-medium text-gray-700 mb-1">
                            Join Code (opsional)
                        </label>
                        <input
                            v-model="joinCode"
                            type="text"
                            class="w-full rounded border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 text-[13px]"
                            placeholder="Kode undangan portal (jika ada)"
                        />
                    </div>
                    <div class="flex items-center gap-2 mt-2 sm:mt-0">
                        <input
                            id="force-create-user"
                            v-model="forceCreateUser"
                            type="checkbox"
                            class="h-4 w-4 text-gray-900 border-gray-300 rounded"
                        />
                        <label for="force-create-user" class="text-[13px] text-gray-700">
                            Force create user jika belum ada
                        </label>
                    </div>
                    <div class="flex items-center gap-2 mt-2 sm:mt-0">
                        <input
                            id="use-session-flow"
                            v-model="useSessionFlow"
                            type="checkbox"
                            class="h-4 w-4 text-gray-900 border-gray-300 rounded"
                        />
                        <label for="use-session-flow" class="text-[13px] text-gray-700">
                            Gunakan session flow
                        </label>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 border-t border-gray-200 mt-4">
                    <div class="text-[12px] text-gray-500">
                        Endpoint yang dipanggil: <code class="font-mono">/api/auth/nusawork/callback</code>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-md shadow-sm transition hover:bg-gray-50 text-[14px]"
                            @click="resetForm"
                            :disabled="isSubmitting"
                        >
                            Reset
                        </button>
                        <button
                            type="submit"
                            class="bg-[#3A3A3A] text-white px-6 py-2 rounded-md shadow-sm transition hover:bg-gray-800 text-[14px] flex items-center gap-2 disabled:opacity-60"
                            :disabled="isSubmitting"
                        >
                            <span v-if="!isSubmitting">Kirim Callback &amp; Login</span>
                            <span v-else class="flex items-center">
                                <svg
                                    class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </div>

                <div v-if="errorMessage" class="mt-3 text-[13px] text-red-600">
                    {{ errorMessage }}
                </div>

                <div
                    v-if="rawResponse"
                    class="mt-3 bg-white border border-gray-200 rounded p-3 max-h-64 overflow-auto text-left"
                >
                    <p class="text-[11px] text-gray-500 mb-1">
                        Response dari /api/auth/nusawork/callback (untuk debugging):
                    </p>
                    <pre class="whitespace-pre-wrap break-all text-[11px] text-gray-700">
{{ formattedResponse }}
                    </pre>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useMainStore } from "@/stores/index";

defineOptions({
    layout: {
        name: "DevNusaworkCallbackLayout",
        setup(props, { slots }) {
            return () => (slots.default ? slots.default() : null);
        },
    },
});

const mainStore = useMainStore();

const token = ref("");
const email = ref("");
const firstName = ref("");
const lastName = ref("");
const photo = ref("");
const companyName = ref("");
const companyAddress = ref("");
const joinCode = ref("");
const forceCreateUser = ref(true);
const useSessionFlow = ref(false);

const isSubmitting = ref(false);
const errorMessage = ref("");
const rawResponse = ref(null);

const rawJsonBody = ref("");
const jsonParseError = ref("");

const decodeBase64Url = (input) => {
    try {
        let base64 = input.replace(/-/g, "+").replace(/_/g, "/");
        const pad = base64.length % 4;
        if (pad) {
            base64 += "=".repeat(4 - pad);
        }
        return atob(base64);
    } catch (e) {
        console.error("Gagal decode base64 token:", e);
        return null;
    }
};

const parseJwtPayload = (rawToken) => {
    if (!rawToken) {
        return null;
    }

    const parts = rawToken.split(".");
    if (parts.length < 2) {
        return null;
    }

    const decoded = decodeBase64Url(parts[1]);
    if (!decoded) {
        return null;
    }

    try {
        return JSON.parse(decoded);
    } catch (e) {
        console.error("Gagal parse payload token:", e);
        return null;
    }
};

const parsedToken = computed(() => {
    const payload = parseJwtPayload(token.value.trim());
    if (!payload) {
        return "";
    }

    try {
        return JSON.stringify(payload, null, 2);
    } catch (e) {
        return "";
    }
});

const formattedResponse = computed(() => {
    if (!rawResponse.value) {
        return "";
    }

    try {
        return JSON.stringify(rawResponse.value, null, 2);
    } catch (e) {
        return String(rawResponse.value);
    }
});

const resetForm = () => {
    token.value = "";
    email.value = "";
    firstName.value = "";
    lastName.value = "";
    photo.value = "";
    companyName.value = "";
    companyAddress.value = "";
    joinCode.value = "";
    forceCreateUser.value = true;
    useSessionFlow.value = false;
    errorMessage.value = "";
    rawResponse.value = null;
    rawJsonBody.value = "";
    jsonParseError.value = "";
};

const applyJsonBody = () => {
    jsonParseError.value = "";

    if (!rawJsonBody.value.trim()) {
        return;
    }

    let data;
    try {
        data = JSON.parse(rawJsonBody.value);
    } catch (e) {
        jsonParseError.value = `Body JSON tidak valid: ${e.message}`;
        return;
    }

    if (typeof data !== "object" || data === null) {
        jsonParseError.value = "Body JSON harus berupa object.";
        return;
    }

    if (typeof data.token === "string") {
        token.value = data.token;
    }

    if (typeof data.email === "string") {
        email.value = data.email;
    }

    const firstNameSource = data.first_name || data.firstName;
    if (typeof firstNameSource === "string") {
        firstName.value = firstNameSource;
    }

    const lastNameSource = data.last_name || data.lastName;
    if (typeof lastNameSource === "string") {
        lastName.value = lastNameSource;
    }

    const photoSource = data.photo || data.avatar;
    if (typeof photoSource === "string") {
        photo.value = photoSource;
    }

    if (data.company && typeof data.company === "object") {
        if (typeof data.company.name === "string") {
            companyName.value = data.company.name;
        }
        if (typeof data.company.address === "string") {
            companyAddress.value = data.company.address;
        }
    }

    const joinCodeSource = data.join_code || data.joinCode;
    if (typeof joinCodeSource === "string") {
        joinCode.value = joinCodeSource;
    }

    if (Object.prototype.hasOwnProperty.call(data, "force_create_user")) {
        forceCreateUser.value = !!data.force_create_user;
    }

    if (Object.prototype.hasOwnProperty.call(data, "use_session_flow")) {
        useSessionFlow.value = !!data.use_session_flow;
    }
};

const handleSubmit = async () => {
    errorMessage.value = "";
    rawResponse.value = null;

    if (!token.value.trim()) {
        errorMessage.value = "Token Nusawork tidak boleh kosong.";
        return;
    }

    if (!email.value.trim()) {
        errorMessage.value = "Email tidak boleh kosong.";
        return;
    }

    if (!firstName.value.trim()) {
        errorMessage.value = "Nama depan tidak boleh kosong.";
        return;
    }

    if (!companyName.value.trim()) {
        errorMessage.value = "Nama perusahaan tidak boleh kosong.";
        return;
    }

    if (!companyAddress.value.trim()) {
        errorMessage.value = "Alamat perusahaan tidak boleh kosong.";
        return;
    }

    const payload = {
        token: token.value.trim(),
        email: email.value.trim(),
        first_name: firstName.value.trim(),
        last_name: lastName.value.trim() || null,
        photo: photo.value.trim() || null,
        company: {
            name: companyName.value.trim(),
            address: companyAddress.value.trim(),
        },
        join_code: joinCode.value.trim() || null,
        force_create_user: !!forceCreateUser.value,
        use_session_flow: !!useSessionFlow.value,
    };

    try {
        isSubmitting.value = true;
        const res = await mainStore.login(payload);

        rawResponse.value = res;

        if (res.session_id && res.redirect_url) {
            // Session flow: redirect ke URL yang diberikan backend
            window.location.href = res.redirect_url;
            return;
        }

        if (res.token && res.user) {
            await setUserLogin(res.token, res.user, res.select_tenant || null);
        } else {
            errorMessage.value =
                "Response tidak berisi token dan user. Cek payload dan log backend.";
        }
    } catch (err) {
        console.error("Error saat memanggil /api/auth/nusawork/callback:", err);
        const responseMessage = err.response?.message || err.response?.data?.message;
        errorMessage.value =
            responseMessage || err.message || "Terjadi kesalahan saat memproses callback.";
    } finally {
        isSubmitting.value = false;
    }
};

const setUserLogin = async (accessToken, user, selectTenant = null) => {
    localStorage.setItem("token", accessToken);
    localStorage.setItem("user", JSON.stringify(user));

    let selectedPortal = null;

    if (selectTenant) {
        selectedPortal = selectTenant;
        localStorage.setItem(
            "selected_portal",
            JSON.stringify(selectedPortal)
        );

        await mainStore.getPortal(true);

        const slug = selectedPortal.slug || "";
        const pathAdmin = import.meta.env.VITE_PATH_ADMIN ?? "admin";
        router.visit(`/${slug}/${pathAdmin}`);
        return;
    }

    const res = await mainStore.getAllPortal();

    if (res.status === 200 && Array.isArray(res.data) && res.data.length > 0) {
        selectedPortal = res.data[0];
        localStorage.setItem(
            "selected_portal",
            JSON.stringify(selectedPortal)
        );

        await mainStore.getPortal(true);

        const slug = selectedPortal.slug || "";
        const pathAdmin = import.meta.env.VITE_PATH_ADMIN ?? "admin";
        router.visit(`/${slug}/${pathAdmin}`);
    } else {
        router.visit("/setup/portal");
    }
};
</script>

<style scoped>
code {
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    background-color: #f3f4f6;
}
</style>
