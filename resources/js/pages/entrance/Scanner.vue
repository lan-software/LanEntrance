<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';
import DecisionDisplay from '@/components/entrance/DecisionDisplay.vue';
import DegradedBanner from '@/components/entrance/DegradedBanner.vue';
import OverrideModal from '@/components/entrance/OverrideModal.vue';
import QrScanner from '@/components/entrance/QrScanner.vue';
import QueuedScans from '@/components/entrance/QueuedScans.vue';
import TokenEntry from '@/components/entrance/TokenEntry.vue';
import { Spinner } from '@/components/ui/spinner';
import { useCheckin } from '@/composables/useCheckin';
import { useEntranceState } from '@/composables/useEntranceState';
import { useRequestQueue } from '@/composables/useRequestQueue';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Entrance', href: '/entrance' }],
    },
});

const scannerRef = ref<InstanceType<typeof QrScanner> | null>(null);
const overrideVisible = ref(false);

const { state, transition, setLoading, resetToReady } = useEntranceState();
const { validate, checkin, verifyCheckin, confirmPayment, override } =
    useCheckin();
const { queue, enqueue, clear: clearQueue, retryAll } = useRequestQueue();

// Start in READY state
transition('READY');

async function onQrDecoded(token: string) {
    setLoading(true);
    const result = await validate(token);

    // If degraded, offer to queue the scan
    if (result.degraded) {
        enqueue(token);
        setLoading(false);
        scannerRef.value?.resume();

        return;
    }

    transition('DECISION_DISPLAY', result, token);
}

function onScanError() {
    // Camera error — scanner component shows its own error UI.
}

async function onCheckin() {
    if (!state.lastToken || !state.lastResult) {
        return;
    }

    setLoading(true);
    const result = await checkin(
        state.lastToken,
        state.lastResult.validation_id,
    );
    transition('DECISION_DISPLAY', result);
}

async function onVerifyCheckin() {
    if (!state.lastToken || !state.lastResult) {
        return;
    }

    setLoading(true);
    const result = await verifyCheckin(
        state.lastToken,
        state.lastResult.validation_id,
    );
    transition('DECISION_DISPLAY', result);
}

async function onConfirmPayment(method: string) {
    if (!state.lastToken || !state.lastResult?.payment) {
        return;
    }

    setLoading(true);
    const result = await confirmPayment(
        state.lastToken,
        state.lastResult.validation_id,
        method,
        state.lastResult.payment.amount,
    );
    transition('DECISION_DISPLAY', result);
}

function showOverride() {
    overrideVisible.value = true;
}

async function onOverrideSubmit(reason: string) {
    if (!state.lastToken || !state.lastResult) {
        return;
    }

    overrideVisible.value = false;
    setLoading(true);
    const result = await override(
        state.lastToken,
        state.lastResult.validation_id,
        reason,
    );
    transition('DECISION_DISPLAY', result);
}

function resetAndResume() {
    resetToReady();
    scannerRef.value?.resume();
}

// Queue retry handler — listens for retry timer events
async function handleRetry() {
    await retryAll(async (token: string) => {
        const result = await validate(token);

        if (!result.degraded && result.decision !== 'error') {
            // Connectivity restored — show the result
            transition('DECISION_DISPLAY', result, token);

            return true;
        }

        return false;
    });
}

onMounted(() => {
    window.addEventListener('entrance:retry-queue', handleRetry);
});

onUnmounted(() => {
    window.removeEventListener('entrance:retry-queue', handleRetry);
});
</script>

<template>
    <Head title="Entrance Scanner" />

    <DegradedBanner v-if="state.degraded" />

    <div class="relative flex h-[calc(100dvh-4rem)] flex-col">
        <!-- Loading overlay -->
        <div
            v-if="state.loading"
            class="absolute inset-0 z-40 flex items-center justify-center bg-black/50"
        >
            <Spinner class="h-12 w-12 text-white" />
        </div>

        <!-- Scanner viewport -->
        <div class="min-h-0 flex-1 overflow-hidden">
            <QrScanner
                ref="scannerRef"
                @decoded="onQrDecoded"
                @error="onScanError"
            />
        </div>

        <!-- Bottom bar: queued scans + token entry + manual lookup -->
        <div class="flex-shrink-0 space-y-3 border-t bg-background p-4">
            <QueuedScans :queue="queue" @clear="clearQueue" />

            <TokenEntry @submit="onQrDecoded" />

            <Link
                href="/entrance/lookup"
                class="flex w-full items-center justify-center gap-2 rounded-xl border bg-card px-4 py-3 text-sm font-medium transition-colors hover:bg-accent"
            >
                <Search class="h-4 w-4" />
                Manual Lookup
            </Link>
        </div>
    </div>

    <!-- Decision overlay -->
    <DecisionDisplay
        v-if="state.current === 'DECISION_DISPLAY' && state.lastResult"
        :result="state.lastResult"
        @dismiss="resetAndResume"
        @override="showOverride"
        @checkin="onCheckin"
        @verify-checkin="onVerifyCheckin"
        @confirm-payment="onConfirmPayment"
    />

    <!-- Override modal -->
    <OverrideModal
        v-if="state.lastResult"
        :open="overrideVisible"
        :context="state.lastResult"
        @submit="onOverrideSubmit"
        @cancel="overrideVisible = false"
    />
</template>
