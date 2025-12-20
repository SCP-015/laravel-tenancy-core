export function loadTinymce() {
    if (typeof window !== "undefined") {
        if (!window.tinymce) {
            const script = document.createElement("script");
            script.src = "/tinymce/tinymce.min.js";
            script.onload = () => {
                window.dispatchEvent(new Event("tinymceLoaded"));
            };
            document.head.appendChild(script);
        } else {
            window.dispatchEvent(new Event("tinymceLoaded"));
        }
    }
}
