<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Loader2, LogIn, CheckCircle } from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';
import AuthModal from '@/components/booking/AuthModal.vue';
import { Button } from '@/components/ui/button';
import { useFormatters } from '@/composables/useFormatters';
import { useHttp } from '@/composables/useHttp';
import { useBookingStore } from '@/stores/bookingStore';

const store = useBookingStore();
const page = usePage();
const { formatPrice } = useFormatters();
const { buildHeaders } = useHttp();

const isAuthenticated = computed(() => !!page.props.auth?.user);
const showAuthModal = ref(false);

onMounted(() => {
    if (!isAuthenticated.value) {
        showAuthModal.value = true;
    }
});

const confirmError = ref<string | null>(null);
const isConfirming = ref(false);

async function confirmBooking() {
    if (!store.bookingId) {
        return;
    }

    isConfirming.value = true;
    confirmError.value = null;

    try {
        const response = await fetch(`/bookings/${store.bookingId}/confirm`, {
            method: 'POST',
            headers: buildHeaders(),
        });

        const data = await response.json();

        if (!response.ok) {
            confirmError.value = data.message ?? 'Something went wrong.';

            return;
        }

        store.nextStep();
    } catch {
        confirmError.value = 'A network error occurred. Please try again.';
    } finally {
        isConfirming.value = false;
    }
}
</script>

<template>
    <div>
        <h2 class="mb-1 text-lg font-medium">Confirm your booking</h2>
        <p class="mb-6 text-sm text-muted-foreground">
            You're one step away from completing your reservation.
        </p>

        <!-- Authenticated view -->
        <template v-if="isAuthenticated">
            <div
                class="mb-6 flex items-center gap-2 text-sm text-muted-foreground"
            >
                <CheckCircle
                    class="h-4 w-4 text-green-500"
                    aria-hidden="true"
                />
                Signed in as
                <span class="font-medium text-foreground">
                    {{ (page.props.auth as any).user.email }}
                </span>
            </div>

            <div class="mb-6 rounded-xl border border-border p-4">
                <p class="mb-1 text-sm text-muted-foreground">Total due</p>
                <p class="text-2xl font-semibold text-foreground">
                    {{ formatPrice(store.totalPrice) }}
                </p>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    Includes VAT of {{ formatPrice(store.taxAmount) }}
                </p>
            </div>

            <p
                v-if="confirmError"
                role="alert"
                class="mb-4 text-sm text-destructive"
            >
                {{ confirmError }}
            </p>

            <div class="flex justify-between">
                <Button
                    variant="outline"
                    :disabled="isConfirming"
                    @click="store.prevStep()"
                >
                    Back
                </Button>
                <Button
                    :disabled="isConfirming"
                    aria-label="Confirm and submit booking"
                    @click="confirmBooking"
                >
                    <Loader2
                        v-if="isConfirming"
                        class="mr-2 h-4 w-4 animate-spin"
                        aria-hidden="true"
                    />
                    {{ isConfirming ? 'Submitting...' : 'Confirm booking' }}
                </Button>
            </div>
        </template>

        <!-- Guest view -->
        <template v-else>
            <div
                class="rounded-xl border border-border p-6 text-center"
                role="status"
            >
                <LogIn
                    class="mx-auto mb-3 h-8 w-8 text-muted-foreground"
                    aria-hidden="true"
                />
                <p class="mb-1 font-medium text-foreground">
                    Sign in to complete your booking
                </p>
                <p class="mb-4 text-sm text-muted-foreground">
                    Your selections have been saved. Signing in won't affect
                    them.
                </p>
                <Button @click="showAuthModal = true"
                    >Sign in or create account</Button
                >
            </div>
            <div class="mt-4">
                <Button variant="outline" @click="store.prevStep()"
                    >Back</Button
                >
            </div>
        </template>

        <!-- Auth modal — reuses shared component -->
        <AuthModal v-model:open="showAuthModal" />
    </div>
</template>
