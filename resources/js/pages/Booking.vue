<script setup lang="ts">
import BookingNav from '@/components/booking/BookingNav.vue';
import StepConfirm from '@/components/booking/StepConfirm.vue';
import StepDates from '@/components/booking/StepDates.vue';
import StepRooms from '@/components/booking/StepRooms.vue';
import StepStatus from '@/components/booking/StepStatus.vue';
import StepSummary from '@/components/booking/StepSummary.vue';

import { useBookingStore } from '@/stores/bookingStore';
import type { Unit } from '@/stores/bookingStore';

defineProps<{
    property: { id: number; name: string; currency: string; tax_rate: number };
    units: Unit[];
}>();

const store = useBookingStore();

const steps = [
    { number: 1, label: 'Dates' },
    { number: 2, label: 'Rooms' },
    { number: 3, label: 'Summary' },
    { number: 4, label: 'Confirm' },
    { number: 5, label: 'Status' },
];

const canNavigateTo = (step: number): boolean =>
    step <= store.currentStep ||
    (step === 2 && store.stepOneValid) ||
    (step === 3 && store.stepOneValid && store.stepTwoValid);
</script>

<template>
    <div class="min-h-screen bg-background">
        <!-- Persistent nav — always visible across all steps -->
        <BookingNav :property-name="property.name" />

        <div class="mx-auto max-w-3xl px-4 py-10">
            <!-- Stepper nav -->
            <nav aria-label="Booking steps" class="mb-10">
                <ol class="relative flex items-center justify-between">
                    <li
                        aria-hidden="true"
                        class="absolute top-4 right-0 left-0 -z-10 h-px bg-border"
                    />
                    <li
                        v-for="step in steps"
                        :key="step.number"
                        class="flex flex-col items-center gap-1"
                    >
                        <button
                            :aria-current="
                                store.currentStep === step.number
                                    ? 'step'
                                    : undefined
                            "
                            :aria-label="`Step ${step.number}: ${step.label}`"
                            :disabled="!canNavigateTo(step.number)"
                            class="flex h-8 w-8 items-center justify-center rounded-full border-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-40"
                            :class="{
                                'border-primary bg-primary text-primary-foreground':
                                    store.currentStep === step.number,
                                'border-primary bg-primary/20 text-primary':
                                    step.number < store.currentStep,
                                'border-border bg-background text-muted-foreground':
                                    step.number > store.currentStep,
                            }"
                            @click="
                                canNavigateTo(step.number) &&
                                store.goToStep(step.number as 1 | 2 | 3 | 4 | 5)
                            "
                        >
                            {{ step.number }}
                        </button>
                        <span
                            class="text-xs"
                            :class="
                                store.currentStep === step.number
                                    ? 'font-medium text-foreground'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ step.label }}
                        </span>
                    </li>
                </ol>
            </nav>

            <!-- Step panels -->
            <div
                role="region"
                :aria-label="`Step ${store.currentStep} of ${steps.length}`"
                class="rounded-xl border border-border bg-card p-6 shadow-sm"
            >
                <Transition name="step" mode="out-in">
                    <StepDates v-if="store.currentStep === 1" key="step-1" />
                    <StepRooms
                        v-else-if="store.currentStep === 2"
                        key="step-2"
                        :units="units"
                    />
                    <StepSummary
                        v-else-if="store.currentStep === 3"
                        key="step-3"
                        :property="property"
                    />
                    <StepConfirm
                        v-else-if="store.currentStep === 4"
                        key="step-4"
                    />
                    <StepStatus
                        v-else-if="store.currentStep === 5"
                        key="step-5"
                    />
                </Transition>
            </div>
        </div>
    </div>
</template>

<style scoped>
.step-enter-active,
.step-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}
.step-enter-from {
    opacity: 0;
    transform: translateX(12px);
}
.step-leave-to {
    opacity: 0;
    transform: translateX(-12px);
}
</style>
