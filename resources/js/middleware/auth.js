export function getAuthStatus() {
    const token = localStorage.getItem("token");
    return !!token && token !== "undefined" && token !== "null";
}

export function checkAuthRules(meta, isAuthenticated) {
    if (meta?.requiresAuth && !isAuthenticated) {
        return { redirect: "/auth/login" };
    } else if (meta?.requiresGuest && isAuthenticated) {
        // Cek apakah ini halaman invite recruiter
        const currentPath = window.location.pathname;
        if (currentPath.includes('/invite-recruiter')) {
            // Untuk invite flow, force clear session dan allow akses
            clearUserSession();
            return null; // Allow akses ke halaman invite
        }

        const portals = localStorage.getItem("portal");
        const pathAdmin = import.meta.env.VITE_PATH_ADMIN ?? "admin";
        const slug = JSON.parse(portals)[0]?.slug ?? null;
        if (slug) {
            return { redirect: `/${slug}/${pathAdmin}` };
        }

        // Check if the URL contains via, provider, and code parameters
        const params = new URLSearchParams(window.location.search);
        const via = params.get("via");
        const provider = params.get("provider");
        const code = params.get("code");
        if (via && provider && code) {
            return {
                redirect: `/setup/portal?via=${via}&provider=${provider}&code=${code}`
            };
        } else {
            return { redirect: "/setup/portal" };
        }
    }
    return null;
}

function clearUserSession() {
    // Hapus semua data dari localStorage
    localStorage.removeItem("token");
    localStorage.removeItem("user");
    localStorage.removeItem("portal");

    // Hapus semua cookies terkait autentikasi
    const cookiesToClear = [
        'laravel_session',
        'XSRF-TOKEN',
        'nusahire_proxy_token',
    ];

    cookiesToClear.forEach(cookieName => {
        document.cookie = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
        document.cookie = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=${window.location.hostname};`;
        document.cookie = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.${window.location.hostname};`;
    });

    console.log("User session cleared for invite flow");
}
