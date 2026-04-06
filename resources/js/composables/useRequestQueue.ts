import { reactive, ref } from 'vue';

export interface QueuedRequest {
    id: string;
    token: string;
    timestamp: number;
    retryCount: number;
    status: 'pending' | 'retrying' | 'failed';
}

const MAX_RETRIES = 3;
const RETRY_INTERVAL_MS = 10_000; // 10 seconds

export function useRequestQueue() {
    const queue = reactive<QueuedRequest[]>([]);
    const isRetrying = ref(false);
    let retryTimer: ReturnType<typeof setInterval> | null = null;

    function enqueue(token: string): QueuedRequest {
        const item: QueuedRequest = {
            id: `q_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
            token,
            timestamp: Date.now(),
            retryCount: 0,
            status: 'pending',
        };
        queue.push(item);
        startRetryLoop();

        return item;
    }

    function remove(id: string) {
        const idx = queue.findIndex((q) => q.id === id);

        if (idx !== -1) {
            queue.splice(idx, 1);
        }

        if (queue.length === 0) {
            stopRetryLoop();
        }
    }

    function clear() {
        queue.splice(0, queue.length);
        stopRetryLoop();
    }

    /**
     * Attempt to process all pending items.
     * Called by the retry loop and by external consumers when connectivity is restored.
     * @param processFn Async function that attempts the validation. Returns true on success.
     */
    async function retryAll(processFn: (token: string) => Promise<boolean>) {
        if (isRetrying.value) {
return;
}

        isRetrying.value = true;

        const pending = queue.filter(
            (q) => q.status === 'pending' || q.status === 'retrying',
        );

        for (const item of pending) {
            item.status = 'retrying';
            item.retryCount++;

            try {
                const success = await processFn(item.token);

                if (success) {
                    remove(item.id);
                } else if (item.retryCount >= MAX_RETRIES) {
                    item.status = 'failed';
                } else {
                    item.status = 'pending';
                }
            } catch {
                if (item.retryCount >= MAX_RETRIES) {
                    item.status = 'failed';
                } else {
                    item.status = 'pending';
                }
            }
        }

        isRetrying.value = false;

        // Stop loop if nothing left to retry
        if (queue.every((q) => q.status === 'failed') || queue.length === 0) {
            stopRetryLoop();
        }
    }

    function startRetryLoop() {
        if (retryTimer) {
return;
}

        retryTimer = setInterval(() => {
            // Emit event for the consumer to call retryAll with their processFn
            window.dispatchEvent(new CustomEvent('entrance:retry-queue'));
        }, RETRY_INTERVAL_MS);
    }

    function stopRetryLoop() {
        if (retryTimer) {
            clearInterval(retryTimer);
            retryTimer = null;
        }
    }

    return {
        queue,
        isRetrying,
        enqueue,
        remove,
        clear,
        retryAll,
    };
}
