<script setup lang="ts">
import {
    AlertTriangle,
    Banknote,
    CircleCheck,
    CircleX,
    ClipboardCheck,
    ClipboardList,
    CreditCard,
    Mail,
    Package,
    ShieldX,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { Decision, DecisionResult } from '@/types';

const props = defineProps<{
    result: DecisionResult;
}>();

defineEmits<{
    dismiss: [];
    override: [];
    verifyCheckin: [];
    confirmPayment: [method: string];
}>();

const selectedMethod = ref<string | null>(null);

const tierConfig = computed(() => {
    const map: Record<
        Decision,
        { class: string; icon: typeof CircleCheck; text: string }
    > = {
        valid: { class: 'bg-green-600', icon: CircleCheck, text: 'Checked In' },
        invalid: { class: 'bg-red-600', icon: CircleX, text: 'Entry Denied' },
        denied_by_policy: {
            class: 'bg-red-600',
            icon: ShieldX,
            text: 'Entry Denied',
        },
        already_checked_in: {
            class: 'bg-orange-600',
            icon: AlertTriangle,
            text: 'Already Checked In',
        },
        override_possible: {
            class: 'bg-orange-600',
            icon: AlertTriangle,
            text: 'Override Available',
        },
        verification_required: {
            class: 'bg-orange-600',
            icon: ClipboardCheck,
            text: 'Verification Required',
        },
        payment_required: {
            class: 'bg-orange-600',
            icon: Banknote,
            text: 'Payment Required',
        },
    };

    return map[props.result.decision];
});

const isRedTier = computed(() =>
    ['invalid', 'denied_by_policy'].includes(props.result.decision),
);
const isActionRequired = computed(() =>
    ['verification_required', 'payment_required'].includes(
        props.result.decision,
    ),
);
</script>

<template>
    <div
        :class="[
            'fixed inset-0 z-50 flex flex-col text-white',
            tierConfig.class,
        ]"
    >
        <!-- TOP: Status header -->
        <div
            class="flex flex-shrink-0 flex-col items-center justify-center px-6 pt-12 pb-6"
        >
            <component
                :is="tierConfig.icon"
                class="mb-4 h-20 w-20"
                :stroke-width="1.5"
            />
            <h1 class="text-center text-3xl font-bold">
                {{ tierConfig.text }}
            </h1>
            <p class="mt-2 max-w-sm text-center text-lg text-white/90">
                {{ result.message }}
            </p>
            <p v-if="result.attendee?.name" class="mt-3 text-xl font-semibold">
                {{ result.attendee.name }}
            </p>
        </div>

        <!-- MIDDLE: Scrollable detail area -->
        <div class="flex-1 space-y-4 overflow-y-auto px-6 pb-4">
            <!-- GREEN: Seating information -->
            <div
                v-if="result.decision === 'valid' && result.seating"
                class="rounded-2xl bg-white/20 p-5 backdrop-blur"
            >
                <h2
                    class="mb-2 text-sm font-semibold tracking-wider text-white/70 uppercase"
                >
                    Seating
                </h2>
                <p class="text-2xl font-bold">{{ result.seating.seat }}</p>
                <p v-if="result.seating.area" class="text-lg text-white/90">
                    {{ result.seating.area }}
                </p>
                <p
                    v-if="result.seating.directions"
                    class="mt-2 leading-relaxed text-white/80"
                >
                    {{ result.seating.directions }}
                </p>
            </div>

            <!-- GREEN: Addon list -->
            <div
                v-if="result.decision === 'valid' && result.addons?.length"
                class="rounded-2xl bg-white/20 p-5 backdrop-blur"
            >
                <h2
                    class="mb-3 text-sm font-semibold tracking-wider text-white/70 uppercase"
                >
                    Ticket Addons
                </h2>
                <ul class="space-y-3">
                    <li
                        v-for="addon in result.addons"
                        :key="addon.name"
                        class="flex items-start gap-3"
                    >
                        <Package
                            class="mt-0.5 h-5 w-5 flex-shrink-0 text-white/80"
                        />
                        <div>
                            <p class="font-medium">{{ addon.name }}</p>
                            <p v-if="addon.info" class="text-sm text-white/70">
                                {{ addon.info }}
                            </p>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- GREEN: Receipt sent notice -->
            <div
                v-if="result.decision === 'valid' && result.receipt_sent"
                class="rounded-2xl bg-white/20 p-4 text-center backdrop-blur"
            >
                <p class="text-white/90">
                    <Mail class="mr-1 inline h-5 w-5" />
                    Receipt sent to attendee's email
                </p>
            </div>

            <!-- ORANGE (verification_required): Verification checklist -->
            <div
                v-if="
                    result.decision === 'verification_required' &&
                    result.verification
                "
                class="rounded-2xl bg-white/20 p-5 backdrop-blur"
            >
                <h2
                    class="mb-3 text-sm font-semibold tracking-wider text-white/70 uppercase"
                >
                    Please Verify
                </h2>
                <ul class="space-y-4">
                    <li
                        v-for="check in result.verification.checks"
                        :key="check.label"
                        class="flex items-start gap-3"
                    >
                        <ClipboardList class="mt-0.5 h-6 w-6 flex-shrink-0" />
                        <div>
                            <p class="text-lg font-semibold">
                                {{ check.label }}
                            </p>
                            <p
                                v-if="check.instruction"
                                class="text-sm text-white/80"
                            >
                                {{ check.instruction }}
                            </p>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- ORANGE (payment_required): Payment collection -->
            <template
                v-if="result.decision === 'payment_required' && result.payment"
            >
                <div
                    class="rounded-2xl bg-white/20 p-5 text-center backdrop-blur"
                >
                    <h2
                        class="mb-2 text-sm font-semibold tracking-wider text-white/70 uppercase"
                    >
                        Amount Due
                    </h2>
                    <p class="text-4xl font-bold">
                        {{ result.payment.amount }}
                        {{ result.payment.currency }}
                    </p>
                </div>

                <div class="rounded-2xl bg-white/20 p-5 backdrop-blur">
                    <h2
                        class="mb-3 text-sm font-semibold tracking-wider text-white/70 uppercase"
                    >
                        Items
                    </h2>
                    <ul class="space-y-2">
                        <li
                            v-for="item in result.payment.items"
                            :key="item.name"
                            class="flex justify-between"
                        >
                            <span>{{ item.name }}</span>
                            <span class="font-semibold"
                                >{{ item.price }}
                                {{ result.payment.currency }}</span
                            >
                        </li>
                    </ul>
                </div>

                <div class="rounded-2xl bg-white/20 p-5 backdrop-blur">
                    <h2
                        class="mb-3 text-sm font-semibold tracking-wider text-white/70 uppercase"
                    >
                        Payment Method
                    </h2>
                    <div class="flex gap-3">
                        <button
                            v-for="method in result.payment.methods"
                            :key="method"
                            type="button"
                            :class="[
                                'flex-1 rounded-xl py-3 text-lg font-semibold transition-all',
                                selectedMethod === method
                                    ? 'bg-white text-orange-600 shadow-lg'
                                    : 'border border-white/30 bg-white/10 text-white',
                            ]"
                            @click="selectedMethod = method"
                        >
                            <Banknote
                                v-if="method === 'cash'"
                                class="mr-2 inline h-5 w-5"
                            />
                            <CreditCard
                                v-if="method === 'card'"
                                class="mr-2 inline h-5 w-5"
                            />
                            {{
                                method === 'cash'
                                    ? 'Cash'
                                    : method === 'card'
                                      ? 'Card'
                                      : method
                            }}
                        </button>
                    </div>
                </div>
            </template>

            <!-- ORANGE (override_possible): Override context -->
            <div
                v-if="
                    result.decision === 'override_possible' &&
                    result.group_policy
                "
                class="rounded-2xl bg-white/20 p-5 backdrop-blur"
            >
                <p>{{ result.group_policy.message }}</p>
                <p class="mt-1 text-sm text-white/70">
                    {{ result.group_policy.members_checked_in }} of
                    {{ result.group_policy.members_total }} members checked in
                </p>
            </div>

            <!-- RED: Denial reason -->
            <div
                v-if="isRedTier && result.group_policy"
                class="rounded-2xl bg-white/20 p-5 backdrop-blur"
            >
                <p>{{ result.group_policy.message }}</p>
            </div>
        </div>

        <!-- BOTTOM: Action buttons -->
        <div class="flex-shrink-0 space-y-3 p-6">
            <p v-if="result.audit_id" class="text-center text-xs text-white/40">
                Ref: {{ result.audit_id }}
            </p>

            <!-- Verification confirm -->
            <button
                v-if="result.decision === 'verification_required'"
                type="button"
                class="w-full rounded-2xl bg-green-600 py-4 text-lg font-bold text-white shadow-lg transition-transform active:scale-[0.98]"
                @click="$emit('verifyCheckin')"
            >
                Confirm &amp; Check In
            </button>

            <!-- Payment confirm -->
            <button
                v-if="result.decision === 'payment_required'"
                type="button"
                :disabled="!selectedMethod"
                :class="[
                    'w-full rounded-2xl py-4 text-lg font-bold shadow-lg transition-all',
                    selectedMethod
                        ? 'bg-green-600 text-white active:scale-[0.98]'
                        : 'cursor-not-allowed bg-white/20 text-white/50',
                ]"
                @click="
                    selectedMethod && $emit('confirmPayment', selectedMethod)
                "
            >
                Confirm Payment &amp; Check In
            </button>

            <!-- Override button -->
            <button
                v-if="
                    (result.decision === 'override_possible' ||
                        result.decision === 'payment_required') &&
                    result.override_allowed
                "
                type="button"
                class="w-full rounded-2xl bg-white py-4 text-lg font-bold text-orange-600 shadow-lg transition-transform active:scale-[0.98]"
                @click="$emit('override')"
            >
                Override
            </button>

            <!-- Next Scan (hidden for payment_required unless override_allowed) -->
            <button
                v-if="
                    result.decision !== 'payment_required' ||
                    result.override_allowed
                "
                type="button"
                :class="[
                    'w-full rounded-2xl py-4 text-lg font-bold transition-transform active:scale-[0.98]',
                    isActionRequired
                        ? 'bg-white/20 text-white'
                        : 'bg-white text-gray-900 shadow-lg',
                ]"
                @click="$emit('dismiss')"
            >
                Next Scan
            </button>
        </div>
    </div>
</template>
