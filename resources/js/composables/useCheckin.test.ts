import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useCheckin } from './useCheckin';

const mockValidResponse = {
    decision: 'valid',
    message: 'Ticket is valid.',
    validation_id: 'val_test',
    degraded: false,
    override_allowed: false,
    attendee: { name: 'Max Mustermann' },
};

const mockLookupResponse = {
    results: [
        {
            token: 'abc',
            name: 'Max',
            status: 'not_checked_in',
            seat: 'A-1',
            group: null,
        },
    ],
};

function mockFetch(response: unknown, status = 200) {
    return vi.fn().mockResolvedValue({
        ok: status >= 200 && status < 300,
        status,
        json: () => Promise.resolve(response),
    });
}

beforeEach(() => {
    // Provide a CSRF meta tag
    document.head.innerHTML =
        '<meta name="csrf-token" content="test-csrf-token">';
});

describe('useCheckin', () => {
    it('validates a token', async () => {
        global.fetch = mockFetch(mockValidResponse);
        const { validate } = useCheckin();
        const result = await validate('test-token');

        expect(result.decision).toBe('valid');
        expect(result.attendee?.name).toBe('Max Mustermann');

        expect(global.fetch).toHaveBeenCalledWith(
            '/api/entrance/validate',
            expect.objectContaining({
                method: 'POST',
                body: JSON.stringify({ token: 'test-token' }),
            }),
        );
    });

    it('sends checkin request', async () => {
        global.fetch = mockFetch(mockValidResponse);
        const { checkin } = useCheckin();
        await checkin('token', 'val_123');

        expect(global.fetch).toHaveBeenCalledWith(
            '/api/entrance/checkin',
            expect.objectContaining({
                method: 'POST',
                body: JSON.stringify({
                    token: 'token',
                    validation_id: 'val_123',
                }),
            }),
        );
    });

    it('sends verify-checkin request', async () => {
        global.fetch = mockFetch(mockValidResponse);
        const { verifyCheckin } = useCheckin();
        await verifyCheckin('token', 'val_123');

        expect(global.fetch).toHaveBeenCalledWith(
            '/api/entrance/verify-checkin',
            expect.objectContaining({
                method: 'POST',
            }),
        );
    });

    it('sends confirm-payment request with method and amount', async () => {
        global.fetch = mockFetch({ ...mockValidResponse, receipt_sent: true });
        const { confirmPayment } = useCheckin();
        await confirmPayment('token', 'val_123', 'cash', '42.00');

        expect(global.fetch).toHaveBeenCalledWith(
            '/api/entrance/confirm-payment',
            expect.objectContaining({
                method: 'POST',
                body: JSON.stringify({
                    token: 'token',
                    validation_id: 'val_123',
                    payment_method: 'cash',
                    amount: '42.00',
                }),
            }),
        );
    });

    it('sends override request with reason', async () => {
        global.fetch = mockFetch(mockValidResponse);
        const { override } = useCheckin();
        await override('token', 'val_123', 'Group leader confirmed');

        expect(global.fetch).toHaveBeenCalledWith(
            '/api/entrance/override',
            expect.objectContaining({
                method: 'POST',
                body: JSON.stringify({
                    token: 'token',
                    validation_id: 'val_123',
                    reason: 'Group leader confirmed',
                }),
            }),
        );
    });

    it('sends lookup request', async () => {
        global.fetch = mockFetch(mockLookupResponse);
        const { lookup } = useCheckin();
        const results = await lookup('mustermann');

        expect(results).toHaveLength(1);
        expect(results[0].name).toBe('Max');

        expect(global.fetch).toHaveBeenCalledWith(
            '/api/entrance/lookup?q=mustermann',
            expect.objectContaining({ method: 'GET' }),
        );
    });

    it('includes CSRF token in headers', async () => {
        global.fetch = mockFetch(mockValidResponse);
        const { validate } = useCheckin();
        await validate('token');

        const calledHeaders = (global.fetch as ReturnType<typeof vi.fn>).mock
            .calls[0][1].headers;
        expect(calledHeaders['X-CSRF-TOKEN']).toBe('test-csrf-token');
    });
});
