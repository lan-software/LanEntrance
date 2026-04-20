<script setup lang="ts">
import { Keyboard } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const emit = defineEmits<{
    submit: [token: string];
}>();

const token = ref('');
const expanded = ref(false);

function handleSubmit() {
    const trimmed = token.value.trim();

    if (trimmed.length === 0) {
        return;
    }

    emit('submit', trimmed);
    token.value = '';
    expanded.value = false;
}
</script>

<template>
    <div>
        <!-- Collapsed: just a toggle button -->
        <button
            v-if="!expanded"
            type="button"
            class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed bg-card px-4 py-3 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
            @click="expanded = true"
        >
            <Keyboard class="h-4 w-4" />
            Enter token manually
        </button>

        <!-- Expanded: input + submit -->
        <form v-else class="flex gap-2" @submit.prevent="handleSubmit">
            <Input
                v-model="token"
                type="text"
                placeholder="Paste or type ticket token..."
                class="flex-1 font-mono text-sm"
                autofocus
            />
            <Button type="submit" :disabled="token.trim().length === 0">
                Validate
            </Button>
        </form>
    </div>
</template>
