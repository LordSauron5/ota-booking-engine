import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export type BookingStep = 1 | 2 | 3 | 4 | 5;

export interface Guests {
    adults: number;
    children: number;
}

export const useBookingStore = defineStore('booking', () => {
    // --- Navigation ----------------------------------
    const currentStep = ref<BookingStep>(1);

    // --- Step 1 ----------------------------------
    const checkIn  = ref<string | null>(null);
    const checkOut = ref<string | null>(null);

    // --- Computed ----------------------------------
    const nights = computed<number>(() => {
        if (!checkIn.value || !checkOut.value) {
            return 0;
        }

        const diff = new Date(checkOut.value).getTime() - new Date(checkIn.value).getTime();

        return Math.round(diff / (1000 * 60 * 60 * 24));
    });

    // --- Step Validity Guards ----------------------------------
    const stepOneValid = computed<boolean>(() =>
        !!checkIn.value && !!checkOut.value && nights.value > 0
    );

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
        checkIn.value     = null;
        checkOut.value    = null;
    }

    return {
        currentStep, checkIn, checkOut, 
        nights,
        goToStep, nextStep, prevStep, reset,
        stepOneValid,
    };
}, {
    persist: {
        storage: sessionStorage,
    },
});