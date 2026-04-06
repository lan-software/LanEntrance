<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    AlertCircle,
    BarChart3,
    Banknote,
    CheckCircle2,
    Clock,
    Users,
    XCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Entrance', href: '/entrance' },
            { title: 'Analytics', href: '/entrance/analytics' },
        ],
    },
});

interface EntranceStats {
    total_scans: number;
    checked_in: number;
    denied: number;
    overrides: number;
    payments_collected: number;
    payment_total: string;
    payment_currency: string;
    avg_checkin_time_ms: number;
    scans_per_hour: { hour: string; count: number }[];
    error?: boolean;
    message?: string;
}

const props = defineProps<{
    stats: EntranceStats;
}>();

const hasError = computed(() => props.stats.error === true);

const avgTimeFormatted = computed(() => {
    const ms = props.stats.avg_checkin_time_ms;

    if (!ms || ms <= 0) {
return '—';
}

    return ms < 1000 ? `${ms}ms` : `${(ms / 1000).toFixed(1)}s`;
});
</script>

<template>
    <Head title="Entrance Analytics" />

    <div class="mx-auto max-w-4xl space-y-6 p-4">
        <!-- Error state -->
        <div
            v-if="hasError"
            class="flex items-center gap-3 rounded-lg border border-destructive/50 bg-destructive/10 p-4"
        >
            <AlertCircle class="h-5 w-5 text-destructive" />
            <p class="text-sm text-destructive">
                {{ stats.message ?? 'Unable to load analytics.' }}
            </p>
        </div>

        <template v-else>
            <!-- Stat cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Total Scans</CardTitle
                        >
                        <BarChart3 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.total_scans }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Checked In</CardTitle
                        >
                        <CheckCircle2 class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.checked_in }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Denied</CardTitle
                        >
                        <XCircle class="h-4 w-4 text-destructive" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.denied }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Overrides</CardTitle
                        >
                        <Users class="h-4 w-4 text-orange-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.overrides }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Payments Collected</CardTitle
                        >
                        <Banknote class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.payments_collected }}
                        </div>
                        <p
                            v-if="stats.payment_total"
                            class="text-xs text-muted-foreground"
                        >
                            {{ stats.payment_total }}
                            {{ stats.payment_currency }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Avg Check-in Time</CardTitle
                        >
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ avgTimeFormatted }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Hourly throughput -->
            <Card v-if="stats.scans_per_hour?.length">
                <CardHeader>
                    <CardTitle class="text-sm font-medium"
                        >Scans per Hour</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    <div class="flex items-end gap-1" style="height: 120px">
                        <div
                            v-for="bar in stats.scans_per_hour"
                            :key="bar.hour"
                            class="group relative flex flex-1 flex-col items-center"
                        >
                            <div
                                class="w-full rounded-t bg-primary transition-colors group-hover:bg-primary/80"
                                :style="{
                                    height: `${Math.max(4, (bar.count / Math.max(...stats.scans_per_hour.map((b) => b.count))) * 100)}%`,
                                }"
                            />
                            <span
                                class="mt-1 text-[10px] text-muted-foreground"
                            >
                                {{ bar.hour }}
                            </span>
                            <span
                                class="absolute -top-5 hidden text-xs font-medium group-hover:block"
                            >
                                {{ bar.count }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </template>
    </div>
</template>
