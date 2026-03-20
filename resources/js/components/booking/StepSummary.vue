<script setup lang="ts">
import { CalendarDays, BedDouble, Users, Info, Loader2 } from 'lucide-vue-next';

import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useFormatters } from '@/composables/useFormatters';
import { useHttp } from '@/composables/useHttp';
import { useBookingStore } from '@/stores/bookingStore';

defineProps<{
    property: {
        id: number;
        name: string;
        currency: string;
        tax_rate: number;
    };
}>();

const store = useBookingStore();
const { buildHeaders } = useHttp();
const { formatDate, formatPrice } = useFormatters();

// ── Draft submission ──────────────────────────────────────────────────────
const isSubmitting = ref(false);
const errorMessage = ref<string | null>(null);

async function saveDraft() {
    if (!store.selectedUnit) {
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = null;

    try {
        const response = await fetch('/bookings/draft', {
            method: 'POST',
            headers: buildHeaders(),
            body: JSON.stringify({
                unit_id: store.selectedUnit.id,
                check_in: store.checkIn,
                check_out: store.checkOut,
                quantity: store.quantity,
                guests: store.guests,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            errorMessage.value =
                data.message ?? 'Something went wrong. Please try again.';

            return;
        }

        // Persist the booking id so subsequent steps can reference it
        store.bookingId = data.booking_id;
        store.reference = data.reference;
        store.nextStep();
    } catch {
        errorMessage.value = 'A network error occurred. Please try again.';
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <div>
        <h2 class="mb-1 text-lg font-medium">Booking summary</h2>
        <p class="mb-6 text-sm text-muted-foreground">
            Review your details before confirming.
        </p>

        <div class="flex flex-col gap-4">
            <!-- Property -->
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle
                        class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Property
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="font-medium text-foreground">
                        {{ property.name }}
                    </p>
                </CardContent>
            </Card>

            <!-- Dates -->
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle
                        class="flex items-center gap-1.5 text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        <CalendarDays class="h-3.5 w-3.5" aria-hidden="true" />
                        Dates
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <div>
                            <p class="mb-0.5 text-xs text-muted-foreground">
                                Check-in
                            </p>
                            <p class="font-medium text-foreground">
                                {{ formatDate(store.checkIn) }}
                            </p>
                        </div>
                        <div>
                            <p class="mb-0.5 text-xs text-muted-foreground">
                                Check-out
                            </p>
                            <p class="font-medium text-foreground">
                                {{ formatDate(store.checkOut) }}
                            </p>
                        </div>
                        <div>
                            <p class="mb-0.5 text-xs text-muted-foreground">
                                Duration
                            </p>
                            <p class="font-medium text-foreground">
                                {{ store.nights }}
                                {{ store.nights === 1 ? 'night' : 'nights' }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Room -->
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle
                        class="flex items-center gap-1.5 text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        <BedDouble class="h-3.5 w-3.5" aria-hidden="true" />
                        Room
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-start justify-between text-sm">
                        <div>
                            <p class="font-medium text-foreground">
                                {{ store.selectedUnit?.name }}
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                {{ store.quantity }}
                                {{ store.quantity === 1 ? 'room' : 'rooms' }}
                                ×
                                {{
                                    formatPrice(store.selectedUnit?.price ?? 0)
                                }}
                                / night
                            </p>
                        </div>
                        <p class="shrink-0 font-medium text-foreground">
                            {{
                                formatPrice(
                                    (store.selectedUnit?.price ?? 0) *
                                        store.quantity *
                                        store.nights,
                                )
                            }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Guests -->
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle
                        class="flex items-center gap-1.5 text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        <Users class="h-3.5 w-3.5" aria-hidden="true" />
                        Guests
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex gap-4 text-sm">
                        <div>
                            <p class="mb-0.5 text-xs text-muted-foreground">
                                Adults
                            </p>
                            <p class="font-medium text-foreground">
                                {{ store.guests.adults }}
                            </p>
                        </div>
                        <div>
                            <p class="mb-0.5 text-xs text-muted-foreground">
                                Children
                            </p>
                            <p class="font-medium text-foreground">
                                {{ store.guests.children }}
                            </p>
                        </div>
                        <div>
                            <p class="mb-0.5 text-xs text-muted-foreground">
                                Total
                            </p>
                            <p class="font-medium text-foreground">
                                {{ store.totalGuests }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Price breakdown -->
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle
                        class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Price breakdown
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        class="flex flex-col gap-2 text-sm"
                        role="region"
                        aria-label="Price breakdown"
                    >
                        <!-- Base price line -->
                        <div class="flex justify-between text-muted-foreground">
                            <span>
                                {{ store.quantity }}
                                {{ store.quantity === 1 ? 'room' : 'rooms' }}
                                × {{ store.nights }}
                                {{ store.nights === 1 ? 'night' : 'nights' }}
                            </span>
                            <span>{{ formatPrice(store.basePrice) }}</span>
                        </div>

                        <!-- VAT line -->
                        <div class="flex justify-between text-muted-foreground">
                            <span>VAT (15%)</span>
                            <span>{{ formatPrice(store.taxAmount) }}</span>
                        </div>

                        <Separator class="my-1" />

                        <!-- Total with VAT tooltip -->
                        <div
                            class="flex items-center justify-between font-semibold text-foreground"
                        >
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger
                                        class="flex cursor-help items-center gap-1 underline decoration-dotted underline-offset-4"
                                        aria-describedby="vat-tooltip"
                                    >
                                        <span>Total</span>
                                        <Info
                                            class="h-3.5 w-3.5 text-muted-foreground"
                                            aria-hidden="true"
                                        />
                                    </TooltipTrigger>
                                    <TooltipContent
                                        id="vat-tooltip"
                                        side="top"
                                        class="max-w-[200px] text-center text-xs"
                                    >
                                        Includes VAT of
                                        <span class="font-semibold">
                                            {{ formatPrice(store.taxAmount) }}
                                        </span>
                                        at 15%
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                            <span aria-live="polite">{{
                                formatPrice(store.totalPrice)
                            }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Edit links -->
        <div class="mt-4 flex gap-3 text-sm">
            <button
                class="rounded text-primary underline underline-offset-4 transition-colors hover:text-primary/80 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                @click="store.goToStep(1)"
            >
                Edit dates
            </button>
            <span class="text-muted-foreground" aria-hidden="true">·</span>
            <button
                class="rounded text-primary underline underline-offset-4 transition-colors hover:text-primary/80 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                @click="store.goToStep(2)"
            >
                Edit room
            </button>
        </div>

        <!-- Error message -->
        <p
            v-if="errorMessage"
            role="alert"
            class="mt-4 text-sm text-destructive"
        >
            {{ errorMessage }}
        </p>

        <!-- Navigation -->
        <div class="mt-8 flex justify-between">
            <Button
                variant="outline"
                :disabled="isSubmitting"
                aria-label="Back to room selection"
                @click="store.prevStep()"
            >
                Back
            </Button>
            <Button
                :disabled="isSubmitting"
                aria-label="Save draft and continue to confirmation"
                @click="saveDraft"
            >
                <Loader2
                    v-if="isSubmitting"
                    class="mr-2 h-4 w-4 animate-spin"
                    aria-hidden="true"
                />
                {{ isSubmitting ? 'Saving...' : 'Continue' }}
            </Button>
        </div>
    </div>
</template>
