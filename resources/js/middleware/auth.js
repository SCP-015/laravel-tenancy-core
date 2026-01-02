export function getAuthStatus() {
    const token = localStorage.getItem("token");
    return !!token && token !== "undefined" && token !== "null";
}

export function checkAuthRules(meta, isAuthenticated) {
    if (meta?.requiresAuth && !isAuthenticated) {
        return { redirect: "/auth/login" };
    } else if (meta?.requiresGuest && isAuthenticated) {
        console.log('[DEBUG] User is authenticated, checking redirect logic');

        // Cek apakah ini halaman invite recruiter
        const currentPath = window.location.pathname;
        if (currentPath.includes('/invite-recruiter')) {
            // Untuk invite flow, force clear session dan allow akses
            clearUserSession();
            return null; // Allow akses ke halaman invite
        }

        // Coba ambil portal dari localStorage dengan error handling
        try {
            const portalsString = localStorage.getItem("portal");
            console.log('[DEBUG] Portals from localStorage:', portalsString);

            if (portalsString && portalsString !== 'null' && portalsString !== 'undefined') {
                const portals = JSON.parse(portalsString);
                const pathAdmin = import.meta.env.VITE_PATH_ADMIN ?? "admin";

                if (Array.isArray(portals) && portals.length > 0) {
                    const slug = portals[0]?.slug;
                    if (slug) {
                        console.log('[DEBUG] Redirecting to portal:', slug);
                        return { redirect: `/${slug}/${pathAdmin}` };
                    }
                }
            }
        } catch (e) {
            console.error('[DEBUG] Error parsing portals from localStorage:', e);
            // Jika error, lanjut ke logic berikutnya
        }

        // Check if the URL contains via, provider, and code parameters
        const params = new URLSearchParams(window.location.search);
        const via = params.get("via");
        const provider = params.get("provider");
        const code = params.get("code");

        console.log('[DEBUG] URL params:', { via, provider, code });

        if (via && provider && code) {
            console.log('[DEBUG] Redirecting to setup portal with params');
            return {
                redirect: `/setup/portal?via=${via}&provider=${provider}&code=${code}`
            };
        } else {
            console.log('[DEBUG] Redirecting to setup portal');
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
