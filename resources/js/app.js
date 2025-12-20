import { createApp, h } from "vue";
import { createPinia } from "pinia";
import { createInertiaApp, router as inertiaRouter } from "@inertiajs/vue3";
import "../css/app.css";

import { loadTinymce } from "./plugins/tinymceLoader.js";

// Minimal components for landing (always loaded)
import PopupInfo from "./components/popup-info.vue";

import AppLayout from "./App.vue";

import { getAuthStatus, checkAuthRules } from "./middleware/auth";

let sentryLoaderPromise = null;

const loadSentry = async () => {
    if (!sentryLoaderPromise) {
        sentryLoaderPromise = import("./plugins/sentry.js");
    }
    return sentryLoaderPromise;
};

createInertiaApp({
    resolve: async (name) => {
        // Lazy load pages - hanya load page yang dibutuhkan
        const pages = import.meta.glob([
            "./pages/**/*.vue",
            "!./pages/lowongan/**",
            "!./pages/kandidat/**",
            "!./pages/interview/**",
            "!./pages/portal/lowongan.vue",
            "!./pages/portal/form_lamaran.vue",
        ]);
        const page = pages[`./pages/${name}.vue`];

        if (!page) {
            const errorPage = await pages["./pages/errors/404.vue"]?.();
            return errorPage?.default || (() => null);
        }
        
        const loadedPage = await page();
        const pageComponent = loadedPage.default;

        // Landing page tidak perlu AppLayout
        if (pageComponent && !pageComponent.layout && !name.includes("landing-page")) {
            pageComponent.layout = AppLayout;
        }

        return pageComponent;
    },
    setup({ el, App, props, plugin }) {
        const initialPageMeta = props?.initialPage?.props?.meta;

        const isAuthenticated = getAuthStatus();
        const initialRedirectAction = checkAuthRules(
            initialPageMeta,
            isAuthenticated
        );

        const app = createApp({ render: () => h(App, props) });

        const initialComponent = props?.initialPage?.component || "";
        const isLandingPage = initialComponent.includes("landing-page");

        // Lazy load heavy libraries untuk admin pages only
        if (!isLandingPage) {
            loadTinymce();
            loadSentry().then((mod) => {
                if (mod && typeof mod.initSentry === "function") {
                    mod.initSentry();
                }
            });
            
            // Lazy load vue-select
            import("vue-select").then((mod) => {
                app.component("v-select", mod.default);
            });
            import("vue-select/dist/vue-select.css");
            
            // Lazy load v-calendar
            import("v-calendar").then((mod) => {
                app.component("VCalendar", mod.Calendar);
                app.component("v-date-picker", mod.DatePicker);
            });
            import("v-calendar/style.css");
            
            // Lazy load cropper
            import("vue-advanced-cropper").then((mod) => {
                app.component("cropper", mod.Cropper);
            });
            import("vue-advanced-cropper/dist/style.css");
            
            // Lazy load vue-tel-input
            import("vue3-tel-input").then((mod) => {
                app.component("VueTelInput", mod.VueTelInput);
            });
            import("vue3-tel-input/dist/vue3-tel-input.css");
        }
        if (initialRedirectAction?.redirect) {
            window.location.href = initialRedirectAction.redirect;
            return;
        }

        // Register minimal components (always available)
        app.component("popup-info", PopupInfo);
        
        // Admin components (lazy loaded for non-landing pages only)
        if (!isLandingPage) {
            import("./components/input-field.vue").then((mod) => {
                app.component("Input", mod.default);
            });
            import("./components/default-table.vue").then((mod) => {
                app.component("default-table", mod.default);
            });
            import("./components/tabs.vue").then((mod) => {
                app.component("tabs-list", mod.default);
            });
            import("./components/error-message.vue").then((mod) => {
                app.component("error-message", mod.default);
            });
            import("./components/one-line-error-message.vue").then((mod) => {
                app.component("one-line-error-message", mod.default);
            });
        }
        
        // Heavy libraries registered lazily above for non-landing pages

        const pinia = createPinia();
        app.use(plugin);
        app.use(pinia);
        app.mount(el);
    },
    progress: {
        color: "#29d",
        showSpinner: true,
    },
    title: (title) => `${title} - Nusahire`,
    onError: (errors) => {
        if (errors.response?.status === 404) {
            Inertia.visit("/errors/404");
        }
        if (errors.response?.status === 500) {
            Inertia.visit("/errors/500");
        }
    },
});

inertiaRouter.on("success", (event) => {
    const pageMeta = event.detail.page.props.meta;
    const isAuthenticated = getAuthStatus();
    const navigationRedirectAction = checkAuthRules(pageMeta, isAuthenticated);

    if (navigationRedirectAction?.redirect) {
        inertiaRouter.visit(navigationRedirectAction.redirect);
    }
});
