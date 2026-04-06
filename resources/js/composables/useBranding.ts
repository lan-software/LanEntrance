import { usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';

export interface Branding {
    eventName: string | null;
    eventLogo: string | null;
    primaryColor: string | null;
}

export function useBranding() {
    const page = usePage();

    const branding = computed<Branding>(
        () =>
            (page.props.branding as Branding) ?? {
                eventName: null,
                eventLogo: null,
                primaryColor: null,
            },
    );

    // Apply primary color as CSS custom property when set
    watchEffect(() => {
        const color = branding.value.primaryColor;

        if (color) {
            document.documentElement.style.setProperty(
                '--event-primary',
                color,
            );
        } else {
            document.documentElement.style.removeProperty('--event-primary');
        }
    });

    return { branding };
}
