<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { DecisionResult } from '@/types';

defineProps<{
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
    if (!isValid.value) {
        return;
    }

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
                <DialogTitle>{{ $t('entrance.override.title') }}</DialogTitle>
                <DialogDescription>
                    {{ $t('entrance.override.description', { attendee: context.attendee?.name ?? $t('entrance.override.fallbackAttendee') }) }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-3 py-4">
                <div
                    v-if="context.group_policy"
                    class="rounded-lg bg-muted p-3 text-sm"
                >
                    <p>{{ context.group_policy.message }}</p>
                    <p class="mt-1 text-muted-foreground">
                        {{ $t('entrance.decision.membersCheckedIn', { checked: context.group_policy.members_checked_in, total: context.group_policy.members_total }) }}
                    </p>
                </div>

                <textarea
                    v-model="reason"
                    rows="3"
                    :placeholder="$t('entrance.override.placeholder')"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                />

                <p
                    v-if="reason.length > 0 && reason.length < 10"
                    class="text-xs text-destructive"
                >
                    {{ $t('entrance.override.charsNeeded', { count: 10 - reason.length }) }}
                </p>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="handleCancel">{{ $t('entrance.override.cancel') }}</Button>
                <Button :disabled="!isValid" @click="handleSubmit"
                    >{{ $t('entrance.override.confirm') }}</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
