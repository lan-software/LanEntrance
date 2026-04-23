<script setup lang="ts">
import { DoorOpen } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <DoorOpen :class="className" v-bind="$attrs" />
</template>
