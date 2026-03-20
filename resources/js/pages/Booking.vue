<script setup lang="ts">

import StepConfirm from '@/components/booking/StepConfirm.vue';
import StepDates   from '@/components/booking/StepDates.vue';
import StepRooms   from '@/components/booking/StepRooms.vue';
import StepStatus  from '@/components/booking/StepStatus.vue';
import StepSummary from '@/components/booking/StepSummary.vue';

import { useBookingStore } from '@/stores/bookingStore';
import type { Unit } from '@/stores/bookingStore';

const props = defineProps<{
    property: { id: number; name: string; currency: string; tax_rate: number };
    units: Unit[];
}>();

const store = useBookingStore();

const steps = [
    { number: 1, label: 'Dates'   },
    { number: 2, label: 'Rooms'   },
    { number: 3, label: 'Summary' },
    { number: 4, label: 'Confirm' },
    { number: 5, label: 'Status'  },
];

const canNavigateTo = (step: number): boolean =>
    step <= store.currentStep || (step === 2 && store.stepOneValid);
</script>

<template>
    <div class="min-h-screen bg-background">

        <div class="max-w-3xl mx-auto py-10 px-4">

            <!-- Stepper nav -->
            <nav aria-label="Booking steps" class="mb-10">
                <ol class="flex items-center justify-between relative">
                    <li
                        aria-hidden="true"
                        class="absolute top-4 left-0 right-0 h-px bg-border -z-10"
                    />
                    <li
                        v-for="step in steps"
                        :key="step.number"
                        class="flex flex-col items-center gap-1"
                    >
                        <button
                            :aria-current="store.currentStep === step.number ? 'step' : undefined"
                            :aria-label="`Step ${step.number}: ${step.label}`"
                            :disabled="!canNavigateTo(step.number)"
                            class="w-8 h-8 rounded-full border-2 text-sm font-medium flex items-center justify-center transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="{
                                'bg-primary border-primary text-primary-foreground':
                                    store.currentStep === step.number,
                                'bg-primary/20 border-primary text-primary':
                                    step.number < store.currentStep,
                                'bg-background border-border text-muted-foreground':
                                    step.number > store.currentStep,
                            }"
                            @click="canNavigateTo(step.number) && store.goToStep(step.number as 1|2|3|4|5)"
                        >
                            {{ step.number }}
                        </button>
                        <span
                            class="text-xs"
                            :class="store.currentStep === step.number
                                ? 'text-foreground font-medium'
                                : 'text-muted-foreground'"
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
                class="bg-card border border-border rounded-xl p-6 shadow-sm"
            >
                <Transition name="step" mode="out-in">
                    <StepDates
                        v-if="store.currentStep === 1"
                        key="step-1"
                    />
                    <StepRooms
                        v-else-if="store.currentStep === 2"
                        key="step-2"
                        :units="units"
                    />
                    <StepSummary
                        v-else-if="store.currentStep === 3"
                        key="step-3"
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
    transition: opacity 0.2s ease, transform 0.2s ease;
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