<script setup lang="ts">
import { AlertCircle, Flashlight } from 'lucide-vue-next';
import { ref } from 'vue';
import { QrcodeStream } from 'vue-qrcode-reader';
import type { DetectedBarcode } from 'vue-qrcode-reader';

const emit = defineEmits<{
    decoded: [token: string];
    error: [message: string];
}>();

const paused = ref(false);
const torchActive = ref(false);
const torchSupported = ref(false);
const cameraError = ref<{ name: string; message: string } | null>(null);

const errorMessages: Record<string, string> = {
    NotAllowedError:
        'Camera permission was denied. Please allow camera access in your browser settings.',
    NotFoundError: 'No camera found on this device.',
    NotSupportedError: 'Secure connection (HTTPS) required for camera access.',
    NotReadableError: 'Camera is in use by another application.',
    OverconstrainedError: 'No suitable camera found.',
    StreamApiNotSupportedError:
        'Your browser does not support camera streaming.',
    InsecureContextError: 'Camera requires a secure (HTTPS) connection.',
};

function onDetect(detectedCodes: DetectedBarcode[]) {
    if (detectedCodes.length === 0) {
        return;
    }

    paused.value = true;
    emit('decoded', detectedCodes[0].rawValue);
}

function onCameraOn(capabilities: MediaTrackCapabilities) {
    torchSupported.value = !!capabilities.torch;
}

function onCameraOff() {
    torchSupported.value = false;
}

function onError(error: Error) {
    cameraError.value = {
        name: error.name,
        message: errorMessages[error.name] ?? `Camera error: ${error.message}`,
    };
    emit('error', cameraError.value.message);
}

function trackDetectedCodes(
    codes: DetectedBarcode[],
    ctx: CanvasRenderingContext2D,
) {
    for (const code of codes) {
        const [first, ...rest] = code.cornerPoints;
        ctx.strokeStyle = '#22c55e';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(first.x, first.y);

        for (const point of rest) {
            ctx.lineTo(point.x, point.y);
        }

        ctx.closePath();
        ctx.stroke();
    }
}

function resume() {
    paused.value = false;
}

defineExpose({ resume });
</script>

<template>
    <div class="relative h-full w-full overflow-hidden">
        <div
            v-if="cameraError"
            class="flex h-full flex-col items-center justify-center gap-4 p-6"
        >
            <AlertCircle class="h-12 w-12 text-amber-500" />
            <p class="max-w-sm text-center text-lg text-foreground">
                {{ cameraError.message }}
            </p>
            <p class="text-sm text-muted-foreground">
                Use Manual Lookup instead
            </p>
        </div>

        <QrcodeStream
            v-else
            :paused="paused"
            :torch="torchActive"
            :constraints="{ facingMode: 'environment' }"
            :formats="['qr_code']"
            :track="trackDetectedCodes"
            class="qr-stream"
            @detect="onDetect"
            @camera-on="onCameraOn"
            @camera-off="onCameraOff"
            @error="onError"
        >
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="h-64 w-64 rounded-2xl border-2 border-white/60" />
            </div>

            <button
                v-if="torchSupported"
                type="button"
                class="absolute top-4 right-4 rounded-full bg-black/30 p-2"
                @click="torchActive = !torchActive"
            >
                <Flashlight
                    class="h-6 w-6"
                    :class="torchActive ? 'text-yellow-400' : 'text-white'"
                />
            </button>
        </QrcodeStream>
    </div>
</template>

<style scoped>
.qr-stream,
.qr-stream :deep(video) {
    height: 100%;
    width: 100%;
    object-fit: cover;
}
</style>
