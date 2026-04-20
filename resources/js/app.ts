import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import DemoShell from '@/components/demo/DemoShell.vue';
import { initializeTheme } from '@/composables/useAppearance';
import i18n, { type AvailableLocale } from '@/i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return [DemoShell];
            case name.startsWith('auth/'):
                return [DemoShell, AuthLayout];
            case name.startsWith('settings/'):
                return [DemoShell, AppLayout, SettingsLayout];
            default:
                return [DemoShell, AppLayout];
        }
    },
    setup({ el, App, props, plugin }) {
        const shared = props.initialPage.props as {
            locale?: AvailableLocale;
            auth?: { user?: { locale?: AvailableLocale } };
        };
        const locale = shared.auth?.user?.locale ?? shared.locale;
        if (locale && i18n.global.availableLocales.includes(locale)) {
            i18n.global.locale.value = locale;
        }

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
