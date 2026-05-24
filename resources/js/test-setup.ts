import { config } from '@vue/test-utils';
import i18n from '@/i18n';

// Install the application's vue-i18n instance globally for all component
// tests. Components rely on useI18n()/$t(), which throws
// "Need to install with `app.use` function" when the plugin is not registered
// on the mounting app. Registering the real i18n instance (rather than a stub
// that echoes keys) means tests can assert against the actual translated
// strings, matching production rendering.
config.global.plugins.push(i18n);
