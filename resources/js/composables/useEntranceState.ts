import { reactive } from 'vue';
import type { DecisionResult, EntranceState, EntranceStateName } from '@/types';

export function useEntranceState() {
    const state = reactive<EntranceState>({
        current: 'IDLE',
        degraded: false,
        lastToken: null,
        lastResult: null,
        loading: false,
    });

    function transition(
        to: EntranceStateName,
        result?: DecisionResult,
        token?: string,
    ) {
        state.current = to;

        if (token !== undefined) {
            state.lastToken = token;
        }

        if (result !== undefined) {
            state.lastResult = result;
            state.degraded = result.degraded;
        }

        state.loading = false;
    }

    function setLoading(loading: boolean) {
        state.loading = loading;
    }

    function setDegraded(degraded: boolean) {
        state.degraded = degraded;
    }

    function resetToReady() {
        state.current = 'READY';
        state.lastToken = null;
        state.lastResult = null;
        state.loading = false;
    }

    return { state, transition, setLoading, setDegraded, resetToReady };
}
