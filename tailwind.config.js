import obfuscate from "tailwindcss-obfuscate";

export default {
    content: [
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.{js,vue}",
    ],
    theme: {nd: {
            fontFamily: {
                sans: [
                    "Instrument Sans",
                    "ui-sans-serif",
                    "system-ui",
                    "sans-serif",
                    "Apple Color Emoji",
                    "Segoe UI Emoji",
                    "Segoe UI Symbol",
                    "Noto Color Emoji",
                ],
            },
        },
        screens: {
            "max-mdx": { max: "991px" },
        },
    },
    plugins: [
        obfuscate({
            classPrefix: "ns-", // semua class akan diubah jadi kayak 'ns-abc123' setelah build
        }),
    ],
};
