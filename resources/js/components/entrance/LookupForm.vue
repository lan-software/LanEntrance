<script setup lang="ts">
import { useDebounceFn } from '@vueuse/core';
import { Info, Search, User, CheckCircle2, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type { AttendeeResult } from '@/types';

const emit = defineEmits<{
    select: [token: string];
}>();

const query = ref('');
const results = ref<AttendeeResult[]>([]);
const loading = ref(false);
const searched = ref(false);

const props = defineProps<{
    searchFn: (query: string) => Promise<AttendeeResult[]>;
}>();

const debouncedSearch = useDebounceFn(async (q: string) => {
    if (q.length < 2) {
        results.value = [];
        searched.value = false;

        return;
    }

    loading.value = true;

    try {
        results.value = await props.searchFn(q);
        searched.value = true;
    } finally {
        loading.value = false;
    }
}, 300);

function onInput() {
    debouncedSearch(query.value);
}

function selectAttendee(token: string) {
    emit('select', token);
}
</script>

<template>
    <div class="space-y-4">
        <div class="relative">
            <Search
                class="absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="query"
                type="search"
                :placeholder="$t('entrance.lookup.searchPlaceholder')"
                class="pl-10"
                @input="onInput"
            />
        </div>

        <!-- Search hint for staff -->
        <div
            v-if="!searched && query.length === 0"
            class="flex items-start gap-2.5 rounded-lg border border-dashed bg-muted/50 p-3"
        >
            <Info class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground" />
            <div class="text-sm text-muted-foreground">
                <p class="font-medium text-foreground">
                    {{ $t('entrance.lookup.searchHintTitle') }}
                </p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    <li>{{ $t('entrance.lookup.searchHintName') }}</li>
                    <li>{{ $t('entrance.lookup.searchHintEmail') }}</li>
                </ul>
                <p class="mt-1.5">
                    {{
                        $t('entrance.lookup.searchHintMin', {
                            link: $t('entrance.lookup.searchHintManualLink'),
                        })
                    }}
                </p>
            </div>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-8">
            <Spinner class="h-6 w-6" />
        </div>

        <div v-else-if="results.length > 0" class="space-y-2">
            <button
                v-for="attendee in results"
                :key="attendee.token"
                type="button"
                class="flex w-full items-center gap-3 rounded-lg border bg-card p-4 text-left transition-colors hover:bg-accent"
                @click="selectAttendee(attendee.token)"
            >
                <User class="h-8 w-8 flex-shrink-0 text-muted-foreground" />
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium">{{ attendee.name }}</p>
                    <p
                        v-if="attendee.email"
                        class="truncate text-xs text-muted-foreground"
                    >
                        {{ attendee.email }}
                    </p>
                    <div
                        class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground"
                    >
                        <span
                            v-if="attendee.ticket_type"
                            class="rounded bg-muted px-1.5 py-0.5 font-medium"
                            >{{ attendee.ticket_type }}</span
                        >
                        <span
                            v-if="attendee.validation_token_suffix"
                            class="font-mono"
                            >···{{ attendee.validation_token_suffix }}</span
                        >
                        <span v-if="attendee.seat">{{
                            $t('entrance.lookup.seat', { seat: attendee.seat })
                        }}</span>
                        <span
                            v-for="addon in attendee.addons ?? []"
                            :key="addon"
                            class="rounded bg-muted px-1.5 py-0.5"
                            >{{ addon }}</span
                        >
                    </div>
                </div>
                <CheckCircle2
                    v-if="attendee.status === 'checked_in'"
                    class="h-5 w-5 flex-shrink-0 text-green-500"
                />
                <XCircle
                    v-else
                    class="h-5 w-5 flex-shrink-0 text-muted-foreground"
                />
            </button>
        </div>

        <p
            v-else-if="searched && query.length >= 2"
            class="py-8 text-center text-muted-foreground"
        >
            {{ $t('entrance.lookup.noResults', { query }) }}
        </p>
    </div>
</template>
