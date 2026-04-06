import { describe, expect, it } from 'vitest';
import { useEntranceState } from './useEntranceState';
import type { DecisionResult } from '@/types';

const validResult: DecisionResult = {
    decision: 'valid',
    message: 'Ticket is valid.',
    validation_id: 'val_test',
    degraded: false,
    override_allowed: false,
};

const degradedResult: DecisionResult = {
    decision: 'valid',
    message: 'ok',
    validation_id: 'val_deg',
    degraded: true,
    override_allowed: false,
};

describe('useEntranceState', () => {
    it('initializes in IDLE state', () => {
        const { state } = useEntranceState();
        expect(state.current).toBe('IDLE');
        expect(state.degraded).toBe(false);
        expect(state.lastResult).toBeNull();
        expect(state.loading).toBe(false);
    });

    it('transitions to READY', () => {
        const { state, transition } = useEntranceState();
        transition('READY');
        expect(state.current).toBe('READY');
    });

    it('transitions to ACTIVE_SCAN', () => {
        const { state, transition } = useEntranceState();
        transition('READY');
        transition('ACTIVE_SCAN');
        expect(state.current).toBe('ACTIVE_SCAN');
    });

    it('transitions to DECISION_DISPLAY with result', () => {
        const { state, transition } = useEntranceState();
        transition('DECISION_DISPLAY', validResult);
        expect(state.current).toBe('DECISION_DISPLAY');
        expect(state.lastResult).toEqual(validResult);
        expect(state.degraded).toBe(false);
    });

    it('sets degraded flag from result', () => {
        const { state, transition } = useEntranceState();
        transition('DECISION_DISPLAY', degradedResult);
        expect(state.degraded).toBe(true);
    });

    it('resets to READY and clears result', () => {
        const { state, transition, resetToReady } = useEntranceState();
        transition('DECISION_DISPLAY', validResult);
        resetToReady();
        expect(state.current).toBe('READY');
        expect(state.lastResult).toBeNull();
        expect(state.loading).toBe(false);
    });

    it('manages loading state', () => {
        const { state, setLoading } = useEntranceState();
        setLoading(true);
        expect(state.loading).toBe(true);
        setLoading(false);
        expect(state.loading).toBe(false);
    });

    it('clears loading on transition', () => {
        const { state, setLoading, transition } = useEntranceState();
        setLoading(true);
        transition('DECISION_DISPLAY', validResult);
        expect(state.loading).toBe(false);
    });

    it('manages degraded state manually', () => {
        const { state, setDegraded } = useEntranceState();
        setDegraded(true);
        expect(state.degraded).toBe(true);
        setDegraded(false);
        expect(state.degraded).toBe(false);
    });
});
