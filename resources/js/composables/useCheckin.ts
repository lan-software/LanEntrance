import type { AttendeeResult, DecisionResult } from '@/types';

function getCsrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

async function apiRequest<T>(method: string, url: string, body?: Record<string, unknown>): Promise<T> {
    const options: RequestInit = {
        method,
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
    };

    if (body && method !== 'GET') {
        options.body = JSON.stringify(body);
    }

    const response = await fetch(url, options);
    const data = await response.json();

    return data as T;
}

export function useCheckin() {
    async function validate(token: string): Promise<DecisionResult> {
        return apiRequest<DecisionResult>('POST', '/api/entrance/validate', { token });
    }

    async function checkin(token: string, validationId: string): Promise<DecisionResult> {
        return apiRequest<DecisionResult>('POST', '/api/entrance/checkin', {
            token,
            validation_id: validationId,
        });
    }

    async function verifyCheckin(token: string, validationId: string): Promise<DecisionResult> {
        return apiRequest<DecisionResult>('POST', '/api/entrance/verify-checkin', {
            token,
            validation_id: validationId,
        });
    }

    async function confirmPayment(
        token: string,
        validationId: string,
        paymentMethod: string,
        amount: string,
    ): Promise<DecisionResult> {
        return apiRequest<DecisionResult>('POST', '/api/entrance/confirm-payment', {
            token,
            validation_id: validationId,
            payment_method: paymentMethod,
            amount,
        });
    }

    async function override(token: string, validationId: string, reason: string): Promise<DecisionResult> {
        return apiRequest<DecisionResult>('POST', '/api/entrance/override', {
            token,
            validation_id: validationId,
            reason,
        });
    }

    async function lookup(query: string): Promise<AttendeeResult[]> {
        const data = await apiRequest<{ results: AttendeeResult[] }>('GET', `/api/entrance/lookup?q=${encodeURIComponent(query)}`);
        return data.results ?? [];
    }

    return { validate, checkin, verifyCheckin, confirmPayment, override, lookup };
}
