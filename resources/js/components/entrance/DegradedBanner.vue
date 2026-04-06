<script setup lang="ts">
import { AlertTriangle } from 'lucide-vue-next';
</script>

<template>
    <div
        role="alert"
        aria-live="assertive"
        class="fixed top-0 inset-x-0 z-40 flex items-center justify-center gap-2 bg-amber-600 px-4 py-3 text-white shadow-lg"
    >
        <AlertTriangle class="h-5 w-5 flex-shrink-0 animate-pulse" />
        <p class="text-sm font-medium">Reduced connectivity — results may be delayed or unavailable</p>
    </div>
</template>
