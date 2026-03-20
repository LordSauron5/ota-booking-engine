import { defineStore } from 'pinia';
import { ref } from 'vue';

export type BookingStep = 1 | 2 | 3 | 4 | 5;

export interface Guests {
    adults: number;
    children: number;
}

export const useBookingStore = defineStore('booking', () => {
    // --- Navigation ----------------------------------
    const currentStep = ref<BookingStep>(1);


    // --- Actions ----------------------------------
    function goToStep(step: BookingStep) {
        currentStep.value = step;
    }

    function nextStep() {
        if (currentStep.value < 5) {
            currentStep.value = (currentStep.value + 1) as BookingStep;
        }
    }

    function prevStep() {
        if (currentStep.value > 1) {
            currentStep.value = (currentStep.value - 1) as BookingStep;
        }
    }

    function reset() {
        currentStep.value = 1;
    }

    return {
        currentStep,
        goToStep, nextStep, prevStep, reset,
    };
}, {
    persist: {
        storage: sessionStorage,
    },
});