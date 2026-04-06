<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Camera } from 'lucide-vue-next';
import LookupForm from '@/components/entrance/LookupForm.vue';
import DecisionDisplay from '@/components/entrance/DecisionDisplay.vue';
import OverrideModal from '@/components/entrance/OverrideModal.vue';
import DegradedBanner from '@/components/entrance/DegradedBanner.vue';
import { Spinner } from '@/components/ui/spinner';
import { useEntranceState } from '@/composables/useEntranceState';
import { useCheckin } from '@/composables/useCheckin';

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
const { validate, verifyCheckin, confirmPayment, override, lookup } = useCheckin();

transition('READY');

async function onSelect(token: string) {
    setLoading(true);
    transition('ACTIVE_LOOKUP');
    const result = await validate(token);
    transition('DECISION_DISPLAY', result);
}

async function onVerifyCheckin() {
    if (!state.lastResult) return;
    setLoading(true);
    const result = await verifyCheckin(
        state.lastResult.validation_id,
        state.lastResult.validation_id,
    );
    transition('DECISION_DISPLAY', result);
}

async function onConfirmPayment(method: string) {
    if (!state.lastResult?.payment) return;
    setLoading(true);
    const result = await confirmPayment(
        state.lastResult.validation_id,
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
    if (!state.lastResult) return;
    overrideVisible.value = false;
    setLoading(true);
    const result = await override(
        state.lastResult.validation_id,
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
        <div v-if="state.loading" class="flex items-center justify-center py-12">
            <Spinner class="h-8 w-8" />
        </div>

        <LookupForm v-else :search-fn="lookup" @select="onSelect" />

        <div class="mt-4">
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
