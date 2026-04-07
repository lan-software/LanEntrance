<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Camera } from 'lucide-vue-next';
import { ref } from 'vue';
import DecisionDisplay from '@/components/entrance/DecisionDisplay.vue';
import DegradedBanner from '@/components/entrance/DegradedBanner.vue';
import LookupForm from '@/components/entrance/LookupForm.vue';
import OverrideModal from '@/components/entrance/OverrideModal.vue';
import TokenEntry from '@/components/entrance/TokenEntry.vue';
import { Spinner } from '@/components/ui/spinner';
import { useCheckin } from '@/composables/useCheckin';
import { useEntranceState } from '@/composables/useEntranceState';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Entrance', href: '/entrance' },
            { title: 'Lookup', href: '/entrance/lookup' },
        ],
    },
});

const overrideVisible = ref(false);

const { state, transition, setLoading, resetToReady } = useEntranceState();
const { validate, verifyCheckin, confirmPayment, override, lookup } =
    useCheckin();

transition('READY');

async function onSelect(token: string) {
    setLoading(true);
    transition('ACTIVE_LOOKUP');
    const result = await validate(token);
    transition('DECISION_DISPLAY', result, token);
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
</script>

<template>
    <Head title="Manual Lookup" />

    <DegradedBanner v-if="state.degraded" />

    <div class="mx-auto max-w-lg p-4">
        <div
            v-if="state.loading"
            class="flex items-center justify-center py-12"
        >
            <Spinner class="h-8 w-8" />
        </div>

        <LookupForm v-else :search-fn="lookup" @select="onSelect" />

        <div class="mt-4 space-y-3">
            <TokenEntry @submit="onSelect" />

            <Link
                href="/entrance"
                class="flex w-full items-center justify-center gap-2 rounded-xl border bg-card px-4 py-3 text-sm font-medium transition-colors hover:bg-accent"
            >
                <Camera class="h-4 w-4" />
                Back to Scanner
            </Link>
        </div>
    </div>

    <DecisionDisplay
        v-if="state.current === 'DECISION_DISPLAY' && state.lastResult"
        :result="state.lastResult"
        @dismiss="resetToReady"
        @override="showOverride"
        @verify-checkin="onVerifyCheckin"
        @confirm-payment="onConfirmPayment"
    />

    <OverrideModal
        v-if="state.lastResult"
        :open="overrideVisible"
        :context="state.lastResult"
        @submit="onOverrideSubmit"
        @cancel="overrideVisible = false"
    />
</template>
