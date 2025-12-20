export function getAdminRoute(path = "") {
    const base = import.meta.env.VITE_PATH_ADMIN ?? "admin";
    return `/${getTenantName()}/${base}${path}`;
}
export function getTenantName() {
    const segments = window.location.pathname.split("/");
    return segments[1] || "tenancy";
}
