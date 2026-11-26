import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import * as Sentry from '@sentry/vue';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { i18n } from './i18n';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Client-side error reporting is opt-in at build time: an empty
        // VITE_SENTRY_DSN ships Sentry as inert code — no network calls,
        // no scope work. Set both VITE_SENTRY_DSN and SENTRY_LARAVEL_DSN so
        // browser and server errors land in the same project.
        const sentryDsn = String(import.meta.env.VITE_SENTRY_DSN ?? '');
        if (sentryDsn) {
            const sentryEnvironment = String(import.meta.env.VITE_SENTRY_ENVIRONMENT ?? '');

            Sentry.init({
                app,
                dsn: sentryDsn,
                release: `nobat-js@${String(import.meta.env.VITE_APP_VERSION ?? 'dev')}`,
                environment: sentryEnvironment || undefined,
                sendDefaultPii: false,
            });
        }

        app.use(plugin)
            .use(i18n)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Register the service worker in production so the app is installable (PWA).
if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

// This will set light / dark mode on page load...
initializeTheme();
