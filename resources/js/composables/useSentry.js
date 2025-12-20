import { setSentryUser, clearSentryUser, setSentryContext, captureMessage, captureException } from "../plugins/sentry.js";

/**
 * Composable untuk menggunakan Sentry di Vue components
 * 
 * Usage:
 * const { setSentryUserInfo, clearSentryUserInfo, setContext, logMessage, logError } = useSentry();
 */
export function useSentry() {
    return {
        /**
         * Set user info ke Sentry setelah login
         * @param {Object} user - User object dengan id, email, name
         */
        setSentryUserInfo(user) {
            setSentryUser(user);
        },

        /**
         * Clear user info dari Sentry setelah logout
         */
        clearSentryUserInfo() {
            clearSentryUser();
        },

        /**
         * Set custom context
         * @param {string} key - Context key
         * @param {Object} value - Context value
         */
        setContext(key, value) {
            setSentryContext(key, value);
        },

        /**
         * Log message ke Sentry
         * @param {string} message - Message to log
         * @param {string} level - Log level (info, warning, error)
         */
        logMessage(message, level = "info") {
            captureMessage(message, level);
        },

        /**
         * Log error ke Sentry
         * @param {Error} error - Error object
         */
        logError(error) {
            captureException(error);
        },
    };
}
