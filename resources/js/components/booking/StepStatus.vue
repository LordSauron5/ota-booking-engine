<script setup lang="ts">
import {
    CheckCircle,
    XCircle,
    Loader2,
    CalendarDays,
    BedDouble,
    Hash,
} from 'lucide-vue-next';

import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useFormatters } from '@/composables/useFormatters';
import { useHttp } from '@/composables/useHttp';
import { useBookingStore } from '@/stores/bookingStore';

const store = useBookingStore();
const { buildHeaders } = useHttp();
const { formatDate, formatPrice } = useFormatters();

type BookingStatus = 'pending' | 'confirmed' | 'failed';

const status = ref<BookingStatus>('pending');
const channelRef = ref<string | null>(null);
const pollError = ref<string | null>(null);
const isRetrying = ref(false);
const consecutiveFailures = ref(0);
let pollInterval: ReturnType<typeof setInterval> | null = null;

// ── Single poll tick ──────────────────────────────────────────────────────
async function poll() {
    if (!store.bookingId) {
        return;
    }

    try {
        const response = await fetch(
            `/api/bookings/${store.bookingId}/status`,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );

        if (!response.ok) {
            stopPolling();

            pollError.value = 'Could not retrieve booking status.';

            return;
        }

        console.log('response status code:', response.status);

        const data = await response.json();

        console.log('response data:', data);

        status.value = data.status;

        if (data.channel_manager_ref) {
            channelRef.value = data.channel_manager_ref;
        }

        // Terminal state — stop polling
        if (data.status === 'confirmed' || data.status === 'failed') {
            stopPolling();
        }

        // Reset failure streak on a successful response
        consecutiveFailures.value = 0;
    } catch {
        consecutiveFailures.value++;

        if (consecutiveFailures.value >= 5) {
            stopPolling();

            pollError.value = 'Lost connection. Please refresh the page.';
        }
    }
}

// ── Polling control ───────────────────────────────────────────────────────
async function startPolling() {
    if (!store.bookingId) {
        return;
    }

    // Await the first poll so stopPolling() has a chance to
    // prevent the interval from being set at all
    await poll();

    // Only set the interval if we're not already in a terminal state
    if (status.value !== 'confirmed' && status.value !== 'failed') {
        pollInterval = setInterval(poll, 3000);
    }
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

onMounted(() => startPolling());
onUnmounted(() => stopPolling());

// ── Retry ─────────────────────────────────────────────────────────────────
async function retryBooking() {
    if (!store.bookingId) {
        return;
    }

    isRetrying.value = true;
    pollError.value = null;
    status.value = 'pending';
    consecutiveFailures.value = 0;

    try {
        const response = await fetch(`/bookings/${store.bookingId}/retry`, {
            method: 'POST',
            headers: buildHeaders(),
        });

        if (!response.ok) {
            const data = await response.json();

            pollError.value = data.message ?? 'Retry failed.';

            status.value = 'failed';

            return;
        }

        startPolling();
    } catch {
        pollError.value = 'A network error occurred. Please try again.';

        status.value = 'failed';
    } finally {
        isRetrying.value = false;
    }
}

// ── Start over ────────────────────────────────────────────────────────────
function startOver() {
    store.reset();
}

const isPending = computed(() => status.value === 'pending');
const isConfirmed = computed(() => status.value === 'confirmed');
const isFailed = computed(() => status.value === 'failed');
</script>

<template>
    <div>
        <!-- ── Pending ─────────────────────────────────────────────────── -->
        <div
            v-if="isPending"
            class="flex flex-col items-center gap-4 py-8 text-center"
            role="status"
            aria-live="polite"
            aria-label="Booking is being processed"
        >
            <Loader2
                class="h-12 w-12 animate-spin text-primary"
                aria-hidden="true"
            />
            <div>
                <h2 class="mb-1 text-lg font-medium text-foreground">
                    Processing your booking
                </h2>
                <p class="max-w-xs text-sm text-muted-foreground">
                    We're confirming your reservation with the property. This
                    usually takes a few seconds.
                </p>
            </div>
            <p class="text-xs text-muted-foreground">
                Reference:
                <span class="font-mono font-medium">{{ store.reference }}</span>
            </p>
        </div>

        <!-- ── Confirmed ──────────────────────────────────────────────── -->
        <div
            v-else-if="isConfirmed"
            class="flex flex-col gap-5"
            role="status"
            aria-live="polite"
            aria-label="Booking confirmed"
        >
            <!-- Success header -->
            <div class="flex flex-col items-center gap-3 py-4 text-center">
                <CheckCircle
                    class="h-12 w-12 text-green-500"
                    aria-hidden="true"
                />
                <div>
                    <h2 class="mb-1 text-lg font-medium text-foreground">
                        Booking confirmed!
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Your reservation has been secured.
                    </p>
                </div>
            </div>

            <!-- Booking details card -->
            <div
                class="divide-y divide-border rounded-xl border border-border text-sm"
            >
                <!-- Reference numbers -->
                <div class="flex flex-col gap-2 p-4">
                    <div class="flex items-center justify-between">
                        <span
                            class="flex items-center gap-1.5 text-muted-foreground"
                        >
                            <Hash class="h-3.5 w-3.5" aria-hidden="true" />
                            Booking reference
                        </span>
                        <span class="font-mono font-medium text-foreground">
                            {{ store.reference }}
                        </span>
                    </div>
                    <div
                        v-if="channelRef"
                        class="flex items-center justify-between"
                    >
                        <span
                            class="flex items-center gap-1.5 text-muted-foreground"
                        >
                            <Hash class="h-3.5 w-3.5" aria-hidden="true" />
                            Channel reference
                        </span>
                        <span class="font-mono font-medium text-foreground">
                            {{ channelRef }}
                        </span>
                    </div>
                </div>

                <!-- Dates -->
                <div class="p-4">
                    <div
                        class="mb-2 flex items-center gap-1.5 text-muted-foreground"
                    >
                        <CalendarDays class="h-3.5 w-3.5" aria-hidden="true" />
                        <span>Dates</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
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
                </div>

                <!-- Room -->
                <div class="p-4">
                    <div
                        class="mb-2 flex items-center gap-1.5 text-muted-foreground"
                    >
                        <BedDouble class="h-3.5 w-3.5" aria-hidden="true" />
                        <span>Room</span>
                    </div>
                    <p class="font-medium text-foreground">
                        {{ store.selectedUnit?.name }}
                    </p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{ store.quantity }}
                        {{ store.quantity === 1 ? 'room' : 'rooms' }} ·
                        {{ store.totalGuests }}
                        {{ store.totalGuests === 1 ? 'guest' : 'guests' }}
                    </p>
                </div>

                <!-- Total -->
                <div class="flex items-center justify-between p-4">
                    <span class="text-muted-foreground">Total paid</span>
                    <span class="font-semibold text-foreground">
                        {{ formatPrice(store.totalPrice) }}
                    </span>
                </div>
            </div>

            <Button variant="outline" class="w-full" @click="startOver">
                Make another booking
            </Button>
        </div>

        <!-- ── Failed ──────────────────────────────────────────────────── -->
        <div
            v-else-if="isFailed"
            class="flex flex-col items-center gap-4 py-8 text-center"
            role="alert"
            aria-live="assertive"
            aria-label="Booking failed"
        >
            <XCircle class="h-12 w-12 text-destructive" aria-hidden="true" />
            <div>
                <h2 class="mb-1 text-lg font-medium text-foreground">
                    Booking unsuccessful
                </h2>
                <p class="max-w-xs text-sm text-muted-foreground">
                    We couldn't confirm your reservation with the property. Your
                    reference is saved — you can retry or start over.
                </p>
            </div>

            <p class="text-xs text-muted-foreground">
                Reference:
                <span class="font-mono font-medium">{{ store.reference }}</span>
            </p>

            <p v-if="pollError" class="text-sm text-destructive">
                {{ pollError }}
            </p>

            <div class="flex gap-3">
                <Button variant="outline" @click="startOver">
                    Start over
                </Button>
                <Button :disabled="isRetrying" @click="retryBooking">
                    <Loader2
                        v-if="isRetrying"
                        class="mr-2 h-4 w-4 animate-spin"
                        aria-hidden="true"
                    />
                    {{ isRetrying ? 'Retrying...' : 'Try again' }}
                </Button>
            </div>
        </div>
    </div>
</template>
