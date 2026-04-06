<script setup lang="ts">
import { ref, computed } from 'vue';
import type { DecisionResult } from '@/types';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    open: boolean;
    context: DecisionResult;
}>();

const emit = defineEmits<{
    submit: [reason: string];
    cancel: [];
}>();

const reason = ref('');
const isValid = computed(() => reason.value.trim().length >= 10);

function handleSubmit() {
    if (!isValid.value) return;
    emit('submit', reason.value.trim());
    reason.value = '';
}

function handleCancel() {
    reason.value = '';
    emit('cancel');
}
</script>

<template>
    <Dialog :open="open" @update:open="(v: boolean) => !v && handleCancel()">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Override</DialogTitle>
                <DialogDescription>
                    Provide a reason for overriding the restriction for
                    <strong>{{ context.attendee?.name ?? 'this attendee' }}</strong>.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-3 py-4">
                <div v-if="context.group_policy" class="rounded-lg bg-muted p-3 text-sm">
                    <p>{{ context.group_policy.message }}</p>
                    <p class="mt-1 text-muted-foreground">
                        {{ context.group_policy.members_checked_in }} of
                        {{ context.group_policy.members_total }} members checked in
                    </p>
                </div>

                <textarea
                    v-model="reason"
                    rows="3"
                    placeholder="Override reason (minimum 10 characters)..."
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />

                <p v-if="reason.length > 0 && reason.length < 10" class="text-xs text-destructive">
                    {{ 10 - reason.length }} more characters needed
                </p>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="handleCancel">Cancel</Button>
                <Button :disabled="!isValid" @click="handleSubmit">Confirm Override</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
