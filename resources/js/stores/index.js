import { defineStore } from "pinia";
import { ref, reactive, computed } from "vue";

export const useMainStore = defineStore("main", () => {
    // State
    const state_portal = ref([]);
    const state_profile = ref({});
    const token = ref("");
    const stateNotif = reactive({
        status: false,
        title: "",
        type: "",
        message: "",
    });
    const state_language = ref("");

    const storedLocale = localStorage.getItem("locale");
    state_language.value = storedLocale ? storedLocale : "en";

    const stateShowInfo = reactive({
        show: false,
        title: "",
        type: "",
        sub_title: "",
        message: "",
    });

    // Getters
    const userPortal = computed(() => state_portal);
    const userProfile = computed(() => state_profile);
    const showNotif = computed(() => stateNotif);
    const language = computed(() => state_language);
    const showInfo = computed(() => stateShowInfo);

    // Actions
    function defu(defaults, options) {
        if (!options) return defaults;

        for (const key in defaults) {
            if (!(key in options)) {
                options[key] = defaults[key];
            } else if (
                typeof defaults[key] === "object" &&
                typeof options[key] === "object"
            ) {
                options[key] = defu(defaults[key], options[key]);
            }
        }

        return options;
    }

    const useAPI = async (url, _options, use_auth = false) => {
        const defaults = {
            baseURL: window.location.origin,
            headers: {
                Accept: "application/json",
                "X-Localization": "id",
            },
        };

        const params = defu(_options, defaults);

        if (
            params.body &&
            typeof params.body === "object" &&
            !(params.body instanceof FormData)
        ) {
            params.headers["Content-Type"] =
                params.headers["Content-Type"] || "application/json";
            params.body = JSON.stringify(params.body);
        }

        if (use_auth) {
            if (!token.value) {
                token.value = localStorage.getItem("token");
            }

            if (token.value) {
                params.headers.Authorization = `Bearer ${token.value}`;
            } else {
                console.warn(
                    "Peringatan: useAPI dipanggil dengan use_auth=true tapi tidak ada token."
                );
                throw new Error("Authentication token is missing.");
            }
        }

        try {
            const fullUrl = new URL(url, params.baseURL).href;
            const response = await fetch(fullUrl, params);

            const isJson = response.headers
                .get("content-type")
                ?.includes("application/json");
            const data = isJson ? await response.json() : null;

            // handle unauthenticated
            if (response.status === 401) {
                localStorage.clear();
                window.location.href = "/";
                return {
                    status: response.status,
                    message: "Unauthorized",
                };
            }

            if (response.status === 204) {
                return null;
            }

            if (!response.ok) {
                const error = new Error(data?.message || response.statusText);
                error.status = response.status;
                error.response = data;
                throw error;
            }

            delete data?.status;

            return {
                status: response.status,
                ...data,
            };
        } catch (error) {
            console.error("Gagal melakukan fetch API:", error);
            throw error;
        }
    };

    const getProfile = async () => {
        const portals = JSON.parse(localStorage.getItem("portal") || "[]");
        const slug = portals[0].slug;
        const res = await useAPI(
            `${slug}/api/settings/profile`,
            {
                method: "GET",
                cache: "no-cache",
                key: "get-profile",
            },
            true
        );
        state_profile.value = res;
        return res;
    };

    const getPortal = async (reload = false) => {
        if (state_portal.value.length > 0 && !reload) {
            return {
                status: 200,
                data: state_portal.value,
            };
        }

        // Cek apakah ada selected_portal di localStorage
        let endpoint = "api/portal";
        const selectedPortalStr = localStorage.getItem("selected_portal");

        if (selectedPortalStr) {
            try {
                const selectedPortal = JSON.parse(selectedPortalStr);
                if (selectedPortal && selectedPortal.id) {
                    // Jika ada selected_portal, gunakan endpoint by-id dengan portalId
                    endpoint = `api/portal/by-id/${selectedPortal.id}`;
                }
            } catch (error) {
                console.error(
                    "Error parsing selected_portal from localStorage:",
                    error
                );
                // Jika terjadi error parsing, tetap gunakan endpoint default
            }
        }

        const res = await useAPI(
            endpoint,
            {
                method: "GET",
                cache: "no-cache",
                key: "get-portal",
            },
            true
        );
        state_portal.value = res.data;
        localStorage.setItem("portal", JSON.stringify(res.data));
        return res;
    };

    const getAllPortal = async () => {
        // Langsung fetch semua portal tanpa menyimpan ke state atau localStorage
        const res = await useAPI(
            `api/portal`,
            {
                method: "GET",
                cache: "no-cache",
                key: "get-all-portal",
            },
            true
        );
        return res;
    };

    const login = async (data) => {
        const res = await useAPI("api/auth/nusawork/callback", {
            method: "POST",
            cache: "no-cache",
            key: "login",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(data),
        });

        return res;
    };

    const logout = () => {
        const res = useAPI(
            "api/auth/logout",
            {
                method: "POST",
                cache: "no-cache",
                key: "logout",
            },
            true
        )
            .then(() => {
                // API berhasil, tidak perlu aksi tambahan
            })
            .catch((error) => {
                console.error("Gagal melakukan logout:", error);
                Object.assign(stateNotif, {
                    status: true,
                    title: "Peringatan",
                    type: "warning",
                    message:
                        "API logout gagal, tetapi sesi akan dihapus secara paksa",
                });
            })
            .finally(() => {
                // Paksa logout meskipun API gagal
                state_profile.value = {};
                state_portal.value = [];
                token.value = "";
                localStorage.clear();
                window.location.href = "/";
            });

        return res;
    };

    const getCategory = async () => {
        const res = await useAPI(
            `api/company-categories`,
            {
                method: "GET",
                cache: "no-cache",
                key: "get-company-category",
            },
            true
        );
        return res;
    };

    const getJobLevel = async (id_tenant) => {
        const res = await useAPI(
            `${id_tenant}/api/settings/job-levels`,
            {
                method: "GET",
                cache: "no-cache",
                key: "get-job-level",
            },
            true
        );
        return res;
    };

    const getEducationLevel = async (id_tenant) => {
        const res = await useAPI(
            `${id_tenant}/api/settings/education-levels`,
            {
                method: "GET",
                cache: "no-cache",
                key: "get-education-level",
            },
            true
        );
        return res;
    };

    const getExperienceLevel = async (id_tenant) => {
        const res = await useAPI(
            `${id_tenant}/api/settings/experience-levels`,
            {
                method: "GET",
                cache: "no-cache",
                key: "get-experience-level",
            },
            true
        );
        return res;
    };
    const getGender = async (id_tenant) => {
        const res = await useAPI(
            `${id_tenant}/api/settings/genders`,
            {
                method: "GET",
                cache: "no-cache",
                key: "get-gender",
            },
            true
        );
        return res;
    };

    const getJobPosition = async (id_tenant, query = null) => {
        let url = `${id_tenant}/api/settings/job-positions`;

        if (query) {
            const params = new URLSearchParams();

            if (query.per_page) params.append("per_page", query.per_page);
            if (query.page) params.append("page", query.page);
            if (query.search) params.append("search", query.search);

            const qs = params.toString();
            if (qs) {
                url = `${url}?${qs}`;
            }
        }

        const res = await useAPI(
            url,
            {
                method: "GET",
                cache: "no-cache",
                key: "get-job-position",
            },
            true
        );
        return res;
    };

    const refreshCompanyCode = async (id_tenant) => {
        const res = await useAPI(
            `${id_tenant}/api/settings/refresh-code`,
            {
                method: "POST",
                cache: "no-cache",
                key: "refresh-code",
            },
            true
        );
        return res;
    };

    const getRecruiter = async (id_tenant, query) => {
        let params = `per_page=${query.per_page}&page=${query.page}`;
        if (query.search) params = `${params}&search=${query.search}`;
        const res = await useAPI(
            `${id_tenant}/api/settings/recruiter?${params}`,
            {
                method: "GET",
                cache: "no-cache",
                key: "get-recruiters",
            },
            true
        );
        return res;
    };

    const inviteRecruiter = async (email) => {
        const portals = JSON.parse(localStorage.getItem("portal") || "[]");
        const slug = portals[0].slug;
        const res = await useAPI(
            `${slug}/api/settings/recruiter/invite`,
            {
                method: "POST",
                cache: "no-cache",
                key: "post-recruiters",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ email: email }),
            },
            true
        );
        return res;
    };

    const deleteRecruiter = async (
        id_tenant,
        id_recruiter,
        notifyByEmail = false
    ) => {
        const res = await useAPI(
            `${id_tenant}/api/settings/recruiter/${id_recruiter}`,
            {
                method: "DELETE",
                cache: "no-cache",
                key: "delete-recruiter",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ notify_by_email: notifyByEmail }),
            },
            true
        );
        return res;
    };

    const updateRole = async (id_tenant, id_recruiter, role) => {
        const res = await useAPI(
            `${id_tenant}/api/settings/recruiter/${id_recruiter}/role`,
            {
                method: "PUT",
                cache: "no-cache",
                key: "update-role",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ role: role }),
            },
            true
        );
        return res;
    };

    const getGuestPortal = async (slug) => {
        const res = await useAPI(`${slug}/api/public/portal`, {
            method: "GET",
            key: "get-guest-portal",
        });
        return res;
    };

    return {
        stateNotif,
        showNotif,
        useAPI,
        state_profile,
        userProfile,
        getProfile,
        login,
        logout,
        language,
        state_language,
        state_portal,
        userPortal,
        getPortal,
        getAllPortal,
        getCategory,
        stateShowInfo,
        showInfo,
        getJobLevel,
        getEducationLevel,
        getExperienceLevel,
        getGender,
        getJobPosition,
        token,
        refreshCompanyCode,
        getRecruiter,
        inviteRecruiter,
        deleteRecruiter,
        updateRole,
        getGuestPortal,
    };
});