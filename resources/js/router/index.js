import { createRouter, createWebHistory } from "vue-router";
import { authGuard } from "@/middleware/auth";

const routes = [
    {
        path: "/:pathMatch(.*)*",
        name: "not-found",
        component: () => import("../pages/404.vue"),
        meta: {
            title: "404 Not Found",
        },
    },
    {
        path: "/home",
        redirect: "/",
    },
    {
        path: "/setup/portal",
        name: "setup-portal",
        component: () => import("../pages/portal/setup.vue"),
        meta: {
            title: "Nusahire",
            requiresAuth: true,
        },
    },
    {
        path: "/",
        name: "home",
        component: () => import("../pages/portal/index.vue"),
        meta: {
            title: "Nusahire",
            requiresAuth: true,
        },
    },
    {
        path: "/auth/login",
        name: "login",
        component: () => import("../pages/auth/login.vue"),
        meta: {
            title: "Login",
            requiresGuest: true,
        },
    },
    {
        path: "/settings",
        name: "settings",
        component: () => import("../pages/settings/index.vue"),
        meta: {
            title: "Pengaturan Admin",
            requiresAuth: true,
        },
    },
];

const routerInstance = createRouter({
    history: createWebHistory(),
    routes,
});

routerInstance.beforeEach((to, from, next) => {
    document.title = (to.meta.title || "Home") + " - Nusahire by Nusawork";
    authGuard(to, from, next);
});

export default routerInstance;
