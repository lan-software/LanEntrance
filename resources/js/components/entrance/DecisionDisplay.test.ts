import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import type { DecisionResult } from '@/types';
import DecisionDisplay from './DecisionDisplay.vue';

function makeResult(overrides: Partial<DecisionResult> = {}): DecisionResult {
    return {
        decision: 'valid',
        message: 'Ticket is valid.',
        validation_id: 'val_test',
        degraded: false,
        override_allowed: false,
        ...overrides,
    };
}

function mountDisplay(result: DecisionResult) {
    return mount(DecisionDisplay, {
        props: { result },
        global: { stubs: { teleport: true } },
    });
}

describe('DecisionDisplay', () => {
    it('renders green overlay for valid decision', () => {
        const wrapper = mountDisplay(makeResult({ decision: 'valid' }));
        expect(wrapper.find('.bg-green-600').exists()).toBe(true);
        expect(wrapper.text()).toContain('Checked In');
    });

    it('renders red overlay for invalid decision', () => {
        const wrapper = mountDisplay(
            makeResult({ decision: 'invalid', message: 'Not found' }),
        );
        expect(wrapper.find('.bg-red-600').exists()).toBe(true);
        expect(wrapper.text()).toContain('Entry Denied');
    });

    it('renders red overlay for denied_by_policy', () => {
        const wrapper = mountDisplay(
            makeResult({ decision: 'denied_by_policy' }),
        );
        expect(wrapper.find('.bg-red-600').exists()).toBe(true);
    });

    it('renders orange overlay for already_checked_in', () => {
        const wrapper = mountDisplay(
            makeResult({ decision: 'already_checked_in' }),
        );
        expect(wrapper.find('.bg-orange-600').exists()).toBe(true);
        expect(wrapper.text()).toContain('Already Checked In');
    });

    it('renders orange overlay for override_possible with override button', () => {
        const wrapper = mountDisplay(
            makeResult({
                decision: 'override_possible',
                override_allowed: true,
            }),
        );
        expect(wrapper.find('.bg-orange-600').exists()).toBe(true);
        expect(wrapper.text()).toContain('Override');
    });

    it('renders orange overlay for verification_required with confirm button', () => {
        const wrapper = mountDisplay(
            makeResult({
                decision: 'verification_required',
                verification: {
                    message: 'Verify student ID',
                    checks: [
                        {
                            label: 'Student ID',
                            instruction: 'Must show valid ID',
                        },
                    ],
                },
            }),
        );
        expect(wrapper.find('.bg-orange-600').exists()).toBe(true);
        expect(wrapper.text()).toContain('Verification Required');
        expect(wrapper.text()).toContain('Student ID');
        expect(wrapper.text()).toContain('Confirm & Check In');
    });

    it('renders orange overlay for payment_required with amount', () => {
        const wrapper = mountDisplay(
            makeResult({
                decision: 'payment_required',
                payment: {
                    amount: '42.00',
                    currency: 'EUR',
                    items: [{ name: 'Weekend Ticket', price: '35.00' }],
                    methods: ['cash', 'card'],
                },
            }),
        );
        expect(wrapper.find('.bg-orange-600').exists()).toBe(true);
        expect(wrapper.text()).toContain('Payment Required');
        expect(wrapper.text()).toContain('42.00 EUR');
        expect(wrapper.text()).toContain('Weekend Ticket');
    });

    it('shows seating info on valid decision', () => {
        const wrapper = mountDisplay(
            makeResult({
                decision: 'valid',
                seating: {
                    seat: 'A-42',
                    area: 'Hall A',
                    directions: 'Turn left',
                },
            }),
        );
        expect(wrapper.text()).toContain('A-42');
        expect(wrapper.text()).toContain('Hall A');
        expect(wrapper.text()).toContain('Turn left');
    });

    it('shows addon list on valid decision', () => {
        const wrapper = mountDisplay(
            makeResult({
                decision: 'valid',
                addons: [
                    { name: 'Pizza Package', info: 'Booth 3' },
                    { name: 'Chair Rental', info: null },
                ],
            }),
        );
        expect(wrapper.text()).toContain('Pizza Package');
        expect(wrapper.text()).toContain('Booth 3');
        expect(wrapper.text()).toContain('Chair Rental');
    });

    it('hides seating section when not provided', () => {
        const wrapper = mountDisplay(
            makeResult({ decision: 'valid', seating: null }),
        );
        expect(wrapper.text()).not.toContain('Seating');
    });

    it('shows receipt notice when receipt_sent is true', () => {
        const wrapper = mountDisplay(
            makeResult({
                decision: 'valid',
                receipt_sent: true,
            }),
        );
        expect(wrapper.text()).toContain("Receipt sent to attendee's email");
    });

    it('hides receipt notice when receipt_sent is false', () => {
        const wrapper = mountDisplay(
            makeResult({ decision: 'valid', receipt_sent: false }),
        );
        expect(wrapper.text()).not.toContain('Receipt sent');
    });

    it('shows audit reference', () => {
        const wrapper = mountDisplay(makeResult({ audit_id: 'aud_test123' }));
        expect(wrapper.text()).toContain('Ref: aud_test123');
    });

    it('hides Next Scan for payment_required when override not allowed', () => {
        const wrapper = mountDisplay(
            makeResult({
                decision: 'payment_required',
                override_allowed: false,
                payment: {
                    amount: '10.00',
                    currency: 'EUR',
                    items: [],
                    methods: ['cash'],
                },
            }),
        );
        expect(wrapper.text()).not.toContain('Next Scan');
    });

    it('shows Next Scan for payment_required when override allowed', () => {
        const wrapper = mountDisplay(
            makeResult({
                decision: 'payment_required',
                override_allowed: true,
                payment: {
                    amount: '10.00',
                    currency: 'EUR',
                    items: [],
                    methods: ['cash'],
                },
            }),
        );
        expect(wrapper.text()).toContain('Next Scan');
    });

    it('emits dismiss on Next Scan click', async () => {
        const wrapper = mountDisplay(makeResult({ decision: 'valid' }));
        const btn = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Next Scan'));
        await btn!.trigger('click');
        expect(wrapper.emitted('dismiss')).toHaveLength(1);
    });

    it('emits verifyCheckin on Confirm & Check In click', async () => {
        const wrapper = mountDisplay(
            makeResult({
                decision: 'verification_required',
                verification: { message: 'Verify', checks: [{ label: 'ID' }] },
            }),
        );
        const btn = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Confirm & Check In'));
        await btn!.trigger('click');
        expect(wrapper.emitted('verifyCheckin')).toHaveLength(1);
    });

    it('emits override on Override click', async () => {
        const wrapper = mountDisplay(
            makeResult({
                decision: 'override_possible',
                override_allowed: true,
            }),
        );
        const btn = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Override'));
        await btn!.trigger('click');
        expect(wrapper.emitted('override')).toHaveLength(1);
    });

    it('covers full viewport with fixed positioning', () => {
        const wrapper = mountDisplay(makeResult());
        const root = wrapper.find('.fixed.inset-0.z-50');
        expect(root.exists()).toBe(true);
    });

    it('displays attendee name when provided', () => {
        const wrapper = mountDisplay(
            makeResult({
                attendee: { name: 'Max Mustermann', group: 'Team Alpha' },
            }),
        );
        expect(wrapper.text()).toContain('Max Mustermann');
    });
});
