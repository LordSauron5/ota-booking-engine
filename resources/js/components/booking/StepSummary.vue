<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useBookingStore } from '@/stores/bookingStore';

const store = useBookingStore();

</script>

<template>
    <div>
        <h2 class="text-lg font-medium mb-1">Step 3: Summary</h2>

        <!-- Next button -->
        <div class="mt-8 flex justify-end">
            <Button
                variant="outline"
                aria-label="Back"
                @click="store.prevStep()"
            >
                Back
            </Button>
            <Button
                aria-label="next"
                @click="store.nextStep()"
            >
                Continue
            </Button>
        </div>
    </div>
</template>