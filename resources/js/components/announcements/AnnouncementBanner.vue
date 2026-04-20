<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type SharedAnnouncement = {
    id: number;
    audience: string | null;
    severity: string | null;
    title: string;
    body: string | null;
    starts_at: string | null;
    ends_at: string | null;
    dismissible: boolean;
};

const page = usePage<{ announcements: SharedAnnouncement[] | null }>();

const dismissed = ref<number[]>([]);

const visible = computed<SharedAnnouncement[]>(() =>
    (page.props.announcements ?? []).filter(
        (a: SharedAnnouncement) => !dismissed.value.includes(a.id),
    ),
);

const classesFor = (severity: string | null): string => {
    switch (severity) {
        case 'emergency':
            return 'border-red-300 bg-red-100 text-red-900 dark:border-red-500/40 dark:bg-red-500/15 dark:text-red-100';
        case 'silent':
            return 'border-slate-300 bg-slate-100 text-slate-900 dark:border-slate-500/40 dark:bg-slate-500/15 dark:text-slate-100';
        default:
            return 'border-sky-300 bg-sky-100 text-sky-900 dark:border-sky-500/40 dark:bg-sky-500/15 dark:text-sky-100';
    }
};

const dismiss = (announcement: SharedAnnouncement): void => {
    dismissed.value = [...dismissed.value, announcement.id];
};
</script>

<template>
    <div v-if="visible.length > 0" class="flex w-full flex-col gap-1">
        <div
            v-for="announcement in visible"
            :key="announcement.id"
            :class="classesFor(announcement.severity)"
            class="flex w-full items-start justify-between gap-3 border-b px-4 py-2 text-sm"
            role="status"
        >
            <div class="flex-1">
                <p class="font-semibold">{{ announcement.title }}</p>
                <p v-if="announcement.body" class="mt-0.5 opacity-90">
                    {{ announcement.body }}
                </p>
            </div>
            <button
                v-if="announcement.dismissible"
                type="button"
                class="shrink-0 rounded px-2 py-1 text-xs font-medium underline-offset-2 hover:underline"
                @click="dismiss(announcement)"
            >
                Dismiss
            </button>
        </div>
    </div>
</template>
