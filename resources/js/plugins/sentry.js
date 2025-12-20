import * as Sentry from "@sentry/browser";

// Export Sentry ke global scope untuk akses dari console
window.Sentry = Sentry;

/**
 * Initialize Sentry untuk error tracking dan user feedback
 * 
 * Fitur:
 * - Automatic error capture
 * - User feedback widget
 * - Performance monitoring
 * - Session replay
 */
export function initSentry() {
    // Init Sentry jika DSN ada (development atau production)
    if (import.meta.env.VITE_SENTRY_DSN) {
        // Siapkan integrations
        const integrations = [];
        
        // Tambah feedback widget jika aktif
        if (import.meta.env.VITE_SENTRY_FEEDBACK_WIDGET === 'true') {
            integrations.push(
                Sentry.feedbackIntegration({
                    colorScheme: "system",
                    triggerLabel: "Report Issue",
                    formTitle: "Report an Issue",
                    submitButtonLabel: "Send Report",
                    cancelButtonLabel: "Cancel",
                    messagePlaceholder: "Describe the issue...",
                    namePlaceholder: "Your name",
                    emailPlaceholder: "Your email",
                })
            );
        }
        
        Sentry.init({
            // DSN dari environment variable
            dsn: import.meta.env.VITE_SENTRY_DSN,
            
            // Environment
            environment: import.meta.env.MODE,
            
            // Release version (optional, bisa dari package.json)
            release: "1.0.0",
            
            // Capture 100% of transactions untuk monitoring
            tracesSampleRate: 1.0,
            
            // Capture 100% of replays
            replaysSessionSampleRate: 1.0,
            replaysOnErrorSampleRate: 1.0,
            
            // Send default PII (user info)
            sendDefaultPii: true,
            
            // Integrations
            integrations: integrations,
            
            // Ignore certain errors
            ignoreErrors: [
                // Browser extensions
                "top.GLOBALS",
                // Random plugins/extensions
                "chrome-extension://",
                "moz-extension://",
            ],
            
            // Denylist URLs yang tidak perlu di-track
            denyUrls: [
                // Browser extensions
                /extensions\//i,
                /^chrome:\/\//i,
                /^moz-extension:\/\//i,
            ],
            
            // Before sending event ke Sentry
            beforeSend(event, hint) {
                // Log untuk debugging
                console.log("Sentry beforeSend:", event, hint);
                // Return event untuk mengirim ke Sentry
                return event;
            },
        });
        
        // Setup global error handler untuk uncaught errors
        window.addEventListener('error', (event) => {
            console.log("Global error caught:", event.error);
            Sentry.captureException(event.error);
        });
        
        // Setup handler untuk unhandled promise rejections
        window.addEventListener('unhandledrejection', (event) => {
            console.log("Unhandled rejection caught:", event.reason);
            Sentry.captureException(event.reason);
        });
    }
}

/**
 * Set user context untuk Sentry
 * Panggil ini setelah user login
 * 
 * @param {Object} user - User object dengan id, email, name
 */
export function setSentryUser(user) {
    if (user) {
        Sentry.setUser({
            id: user.id,
            email: user.email,
            username: user.name,
        });
    }
}

/**
 * Clear user context
 * Panggil ini setelah user logout
 */
export function clearSentryUser() {
    Sentry.setUser(null);
}

/**
 * Set custom context untuk Sentry
 * 
 * @param {string} key - Context key
 * @param {Object} value - Context value
 */
export function setSentryContext(key, value) {
    Sentry.setContext(key, value);
}

/**
 * Capture custom message
 * 
 * @param {string} message - Message to capture
 * @param {string} level - Log level (info, warning, error)
 */
export function captureMessage(message, level = "info") {
    Sentry.captureMessage(message, level);
}

/**
 * Capture custom exception
 * 
 * @param {Error} error - Error object
 */
export function captureException(error) {
    Sentry.captureException(error);
}
