<script setup lang="ts">
import { Clock, Loader2, XCircle, Trash2 } from 'lucide-vue-next';
import type { QueuedRequest } from '@/composables/useRequestQueue';

defineProps<{
    queue: QueuedRequest[];
}>();

defineEmits<{
    clear: [];
}>();

function formatTime(timestamp: number): string {
    return new Date(timestamp).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <div v-if="queue.length > 0" class="rounded-xl border bg-card p-4">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-muted-foreground">
                {{ $t('entrance.queuedScans.title', { count: queue.length }) }}
            </h3>
            <button
                type="button"
                class="flex items-center gap-1 text-xs text-muted-foreground hover:text-destructive"
                @click="$emit('clear')"
            >
                <Trash2 class="h-3 w-3" />
                {{ $t('entrance.queuedScans.clear') }}
            </button>
        </div>

        <ul class="space-y-2">
            <li
                v-for="item in queue"
                :key="item.id"
                class="flex items-center gap-3 rounded-lg bg-muted/50 px-3 py-2 text-sm"
            >
                <Clock
                    v-if="item.status === 'pending'"
                    class="h-4 w-4 flex-shrink-0 text-amber-500"
                />
                <Loader2
                    v-else-if="item.status === 'retrying'"
                    class="h-4 w-4 flex-shrink-0 animate-spin text-blue-500"
                />
                <XCircle
                    v-else
                    class="h-4 w-4 flex-shrink-0 text-destructive"
                />

                <div class="min-w-0 flex-1">
                    <p class="truncate font-mono text-xs">
                        {{ item.token.slice(0, 16) }}...
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ formatTime(item.timestamp) }}
                        <span v-if="item.retryCount > 0">
                            &middot;
                            {{
                                $t('entrance.queuedScans.retriedCount', {
                                    count: item.retryCount,
                                })
                            }}
                        </span>
                        <span
                            v-if="item.status === 'failed'"
                            class="text-destructive"
                        >
                            &middot; {{ $t('entrance.queuedScans.failed') }}
                        </span>
                    </p>
                </div>
            </li>
        </ul>

        <p class="mt-2 text-center text-xs text-muted-foreground">
            {{ $t('entrance.queuedScans.pendingMessage') }}
        </p>
    </div>
</template>
