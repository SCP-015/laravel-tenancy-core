<template>
    <div>
        <navbar v-if="!hideComponent" />

        <div :class="!hideComponent ? 'default-container pt-5' : ''">
            <transition name="fade" mode="out-in">
                <!-- Bungkus slot dengan div sebagai root untuk transisi -->
                <div>
                    <slot />
                </div>
            </transition>
        </div>

        <popup-info></popup-info>
    </div>
</template>

<script setup>
import { computed, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import navbar from "./components/navbar.vue";
import { useMainStore } from "./stores";
import { getAuthStatus } from "./middleware/auth";

// Mengambil nama route dari Laravel via Inertia
const page = usePage();
const mainStore = useMainStore();

const hideComponent = computed(() => {
    const name = page.component || "";

    return [
        "not-found",
        "auth/login",
        "auth/InviteRecruiter",
        "portal/setup",
        "portal",
        "errors/404",
        "landing-page",
    ].some((c) => name.includes(c));
});

onMounted(() => {
    const isAuthenticated = getAuthStatus();
    if (isAuthenticated) {
        mainStore.getPortal();
    }
});
</script>

<style>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.no-scroll {
    overflow: hidden;
}
</style>
