import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import QrScanner from './QrScanner.vue';

// Mock vue-qrcode-reader since it depends on browser APIs
vi.mock('vue-qrcode-reader', () => ({
    QrcodeStream: {
        name: 'QrcodeStream',
        template: '<div class="mock-qrcode-stream"><slot /></div>',
        emits: ['detect', 'camera-on', 'camera-off', 'error'],
        props: ['paused', 'torch', 'constraints', 'formats', 'track'],
    },
}));

function mountScanner() {
    return mount(QrScanner);
}

describe('QrScanner', () => {
    it('renders the camera stream component', () => {
        const wrapper = mountScanner();
        expect(wrapper.find('.mock-qrcode-stream').exists()).toBe(true);
    });

    it('renders scan overlay', () => {
        const wrapper = mountScanner();
        expect(wrapper.find('.rounded-2xl.border-2').exists()).toBe(true);
    });

    it('shows error state on camera error', async () => {
        const wrapper = mountScanner();
        const stream = wrapper.findComponent({ name: 'QrcodeStream' });

        await stream.vm.$emit(
            'error',
            new DOMException('denied', 'NotAllowedError'),
        );
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Camera permission was denied');
    });

    it('shows generic error for unknown error types', async () => {
        const wrapper = mountScanner();
        const stream = wrapper.findComponent({ name: 'QrcodeStream' });

        await stream.vm.$emit('error', new Error('Something broke'));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Camera error: Something broke');
    });

    it('emits decoded event on QR detection', async () => {
        const wrapper = mountScanner();
        const stream = wrapper.findComponent({ name: 'QrcodeStream' });

        await stream.vm.$emit('detect', [{ rawValue: 'test-token-123' }]);

        expect(wrapper.emitted('decoded')).toBeTruthy();
        expect(wrapper.emitted('decoded')![0]).toEqual(['test-token-123']);
    });

    it('emits error event on camera failure', async () => {
        const wrapper = mountScanner();
        const stream = wrapper.findComponent({ name: 'QrcodeStream' });

        await stream.vm.$emit(
            'error',
            new DOMException('no camera', 'NotFoundError'),
        );

        expect(wrapper.emitted('error')).toBeTruthy();
        expect(wrapper.emitted('error')![0][0]).toContain('No camera found');
    });

    it('exposes resume method that unpauses', async () => {
        const wrapper = mountScanner();
        const stream = wrapper.findComponent({ name: 'QrcodeStream' });

        // Trigger detect to pause
        await stream.vm.$emit('detect', [{ rawValue: 'token' }]);

        // Resume
        (wrapper.vm as any).resume();
        await wrapper.vm.$nextTick();

        // Scanner should not show error state
        expect(wrapper.find('.mock-qrcode-stream').exists()).toBe(true);
    });
});
