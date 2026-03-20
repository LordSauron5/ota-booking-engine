import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export type BookingStep = 1 | 2 | 3 | 4 | 5;

export interface Guests {
    adults: number;
    children: number;
}

export interface Unit {
    id: number;
    name: string;
    description: string;
    max_guests: number;
    pricing_model: string;
    price: number;
    available_count: number;
    pictures: string[];
}

export const useBookingStore = defineStore(
    'booking',
    () => {
        // --- Navigation ----------------------------------
        const currentStep = ref<BookingStep>(1);

        // --- Step 1 ----------------------------------
        const checkIn = ref<string | null>(null);
        const checkOut = ref<string | null>(null);

        // --- Step 2 ----------------------------------
        const selectedUnit = ref<Unit | null>(null);
        const quantity = ref<number>(1);
        const guests = ref<Guests>({ adults: 1, children: 0 });

        // --- Booking Metadata ----------------------------------
        const bookingId = ref<number | null>(null);
        const reference = ref<string | null>(null);

        // --- Computed ----------------------------------
        const nights = computed<number>(() => {
            if (!checkIn.value || !checkOut.value) {
                return 0;
            }

            const diff =
                new Date(checkOut.value).getTime() -
                new Date(checkIn.value).getTime();

            return Math.round(diff / (1000 * 60 * 60 * 24));
        });

        const basePrice = computed<number>(() => {
            if (!selectedUnit.value || nights.value === 0) {
                return 0;
            }

            return selectedUnit.value.price * quantity.value * nights.value;
        });

        const taxAmount = computed<number>(() => {
            return parseFloat((basePrice.value * 0.15).toFixed(2));
        });

        const totalPrice = computed<number>(() => {
            return parseFloat((basePrice.value + taxAmount.value).toFixed(2));
        });

        const totalGuests = computed<number>(() => {
            return guests.value.adults + guests.value.children;
        });

        // --- Step Validity Guards ----------------------------------
        const stepOneValid = computed<boolean>(
            () => !!checkIn.value && !!checkOut.value && nights.value > 0,
        );

        const stepTwoValid = computed<boolean>(
            () =>
                !!selectedUnit.value &&
                quantity.value >= 1 &&
                guests.value.adults >= 1,
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
            checkIn.value = null;
            checkOut.value = null;
            selectedUnit.value = null;
            quantity.value = 1;
            guests.value = { adults: 1, children: 0 };
            bookingId.value = null;
            reference.value = null;
        }

        return {
            currentStep,
            checkIn,
            checkOut,
            selectedUnit,
            quantity,
            guests,
            bookingId,
            reference,
            nights,
            basePrice,
            taxAmount,
            totalPrice,
            totalGuests,
            goToStep,
            nextStep,
            prevStep,
            reset,
            stepOneValid,
            stepTwoValid,
        };
    },
    {
        persist: {
            storage: sessionStorage,
        },
    },
);
