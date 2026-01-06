<template>
    <div class="wrapper-settings">
        <div class="nav-setting relative" :class="{ collapsed: collapsed }">
            <div class="inner-nav-setting" :class="{ collapsed: collapsed }">
                <div
                    v-for="(item, idx) in nav"
                    :key="idx"
                    class="p-[10px] cursor-pointer"
                    :class="{
                        'border-l-2 border-black': item.id == currentNav,
                    }"
                    @click="handleNav(item)"
                >
                    {{ item.label }}
                </div>
                <div
                    class="cursor-pointer flex justify-between flex-nowrap mt-3"
                    @click="doLogout()"
                >
                    <span class="pl-2">Keluar Akun</span>
                    <img src="/images/logout.svg" alt="icon logout" />
                </div>
            </div>

            <div
                class="chevron-circle"
                @click.prevent="collapsed = !collapsed"
                :class="{ collapsed: collapsed }"
            >
                <div
                    :class="collapsed ? 'chevron-right' : 'chevron-left'"
                ></div>
            </div>
        </div>
        <div class="content-setting" :class="{ collapsed: collapsed }">
            <template v-if="currentNav == 'recruiter'">
                <recruiter :asComponent="true" :collapsed="collapsed"></recruiter>
            </template>

            <template v-else-if="currentNav == 'portal'">
                <portal :asComponent="true" :collapsed="collapsed"></portal>
            </template>

            <template v-else-if="currentNav == 'my-feedback'">
                <feedback :asComponent="true" :collapsed="collapsed"></feedback>
            </template>

            <template v-else-if="currentNav == 'variable'">
                <VariableSetting
                    :asComponent="true"
                    :collapsed="collapsed"
                ></VariableSetting>
            </template>

            <template v-else-if="currentNav == 'audit-logs'">
                <auditLogs :asComponent="true" :collapsed="collapsed"></auditLogs>
            </template>

            <template v-else-if="currentNav == 'default-signers'">
                <defaultSigners :asComponent="true" :collapsed="collapsed"></defaultSigners>
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from "vue";
import recruiter from "../../components/settings/recruiter.vue";
import portal from "../../pages/portal/index.vue";
import feedback from "../../components/feedback.vue"
import VariableSetting from "../../components/settings/variable-setting.vue";
import auditLogs from "./audit-logs.vue";
import defaultSigners from "../../components/settings/default-signers.vue";
import { useMainStore } from "../../stores";
import { router, usePage } from "@inertiajs/vue3";

const mainStore = useMainStore();

// State
let currentNav = ref("portal"),
    nav = ref([
        {
            id: "portal",
            label: "Company",
        },
        {
            id: "recruiter",
            label: "Admin",
        },
        {
            id: "variable",
            label: "Variable",
        },
        {
            id: "audit-logs",
            label: "Riwayat Perubahan",
        },
        {
            id: "default-signers",
            label: "Default Signers",
        },
        {
            id: "my-feedback",
            label: "Feedback Saya",
        },
    ]),
    collapsed = ref(false),
    windowWidth = ref(window.innerWidth);

watch(currentNav, (val) => {
    updateNavQuery(val);
});

watch(windowWidth, () => {
    if (windowWidth.value < 768) {
        collapsed.value = true;
    } else {
        collapsed.value = false;
    }
});

onMounted(() => {
    resizeHandler();
    window.addEventListener("resize", resizeHandler);
    const url = new URL(usePage().url, window.location.origin);
    const navQuery = url.searchParams.get("nav");

    if (navQuery) {
        const allowedNavIds = new Set((nav.value || []).map((item) => item.id));
        if (allowedNavIds.has(navQuery)) {
            currentNav.value = navQuery;
        } else {
            updateNavQuery(currentNav.value);
        }
        // Tidak perlu memanggil updateNavQuery di sini karena akan menyebabkan double mounting
        // Cukup set currentNav saja
    }
});

onUnmounted(() => {
    window.removeEventListener("resize", resizeHandler);
});

// Methods
function updateNavQuery(navValue) {
    // Hindari update jika navValue sama dengan currentNav.value yang sudah ada di URL
    const url = new URL(window.location.href);
    const currentNavParam = url.searchParams.get("nav");

    if (currentNavParam === navValue) {
        // Jika nav parameter sudah sesuai dengan yang diminta, tidak perlu update URL
        return;
    }

    url.searchParams.set("nav", navValue);
    router.visit(url.pathname + url.search, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: [],
    });
}

function resizeHandler() {
    windowWidth.value = window.innerWidth;
}

const handleNav = (item) => {
    currentNav.value = item.id;
    if (windowWidth.value < 768) {
        collapsed.value = true;
    }
};

const doLogout = () => {
    mainStore.logout();
};
</script>

<style scoped>
.wrapper-settings {
    display: flex;
    flex-wrap: nowrap;
}

.nav-setting {
    min-width: 200px;
    width: 20%;
}

.nav-setting.collapsed {
    width: 0;
    min-width: 0;
}

.content-setting.collapsed {
    width: 100%;
}

.inner-nav-setting {
    position: absolute;
    width: 100%;
    height: 90dvh;
    margin-top: -19px;
    margin-left: -29px;
    box-shadow: 3px 0 8px -3px rgba(0, 0, 0, 0.1);
    padding: 30px 20px;
}

.inner-nav-setting.collapsed {
    margin-left: -100%;
    display: none;
}

.content-setting {
    width: 80%;
}

/* Icons */
.chevron-circle {
    width: 25px;
    height: 25px;
    background-color: green;
    border-radius: 50%;
    align-items: center;
    justify-content: center;
    position: absolute;
    top: -13px;
    right: 18px;
}

.chevron-circle.collapsed {
    top: 13px;
    right: -21px;
}

.chevron-right {
    width: 8px;
    height: 8px;
    border-right: 2px solid white;
    border-bottom: 2px solid white;
    transform: rotate(-45deg);
    margin-left: 0.45rem;
    margin-top: 0.5rem;
}

.chevron-left {
    width: 8px;
    height: 8px;
    border-right: 2px solid white;
    border-bottom: 2px solid white;
    transform: rotate(135deg);
    margin-left: 0.55rem;
    margin-top: 0.5rem;
}

@media (max-width: 767px) {
    .chevron-circle {
        display: flex;
    }
}
</style>
