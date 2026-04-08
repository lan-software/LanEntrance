<script setup lang="ts">
import AnnouncementBanner from '@/components/announcements/AnnouncementBanner.vue';
import DemoBanner from '@/components/demo/DemoBanner.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <div>
        <DemoBanner />
        <AnnouncementBanner />
        <AppLayout :breadcrumbs="breadcrumbs">
            <slot />
        </AppLayout>
    </div>
</template>
