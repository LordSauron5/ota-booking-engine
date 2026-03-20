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
const page  = usePage();
const { formatPrice } = useFormatters();
const { buildHeaders } = useHttp();

const isAuthenticated = computed(() => !!page.props.auth?.user);
const showAuthModal   = ref(false);

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

    console.log('Confirming booking with ID:', store.bookingId);
}

</script>

<template>
    <div>
        <h2 class="text-lg font-medium mb-1">Confirm your booking</h2>
        <p class="text-sm text-muted-foreground mb-6">
            You're one step away from completing your reservation.
        </p>

        <!-- Authenticated view -->
        <template v-if="isAuthenticated">
            <div class="flex items-center gap-2 mb-6 text-sm text-muted-foreground">
                <CheckCircle class="w-4 h-4 text-green-500" aria-hidden="true" />
                Signed in as
                <span class="font-medium text-foreground">
                    {{ (page.props.auth as any).user.email }}
                </span>
            </div>

            <div class="border border-border rounded-xl p-4 mb-6">
                <p class="text-sm text-muted-foreground mb-1">Total due</p>
                <p class="text-2xl font-semibold text-foreground">
                    {{ formatPrice(store.totalPrice) }}
                </p>
                <p class="text-xs text-muted-foreground mt-0.5">
                    Includes VAT of {{ formatPrice(store.taxAmount) }}
                </p>
            </div>

            <p v-if="confirmError" role="alert" class="mb-4 text-sm text-destructive">
                {{ confirmError }}
            </p>

            <div class="flex justify-between">
                <Button variant="outline" :disabled="isConfirming" @click="store.prevStep()">
                    Back
                </Button>
                <Button :disabled="isConfirming" aria-label="Confirm and submit booking" @click="confirmBooking">
                    <Loader2 v-if="isConfirming" class="w-4 h-4 mr-2 animate-spin" aria-hidden="true" />
                    {{ isConfirming ? 'Submitting...' : 'Confirm booking' }}
                </Button>
            </div>
        </template>

        <!-- Guest view -->
        <template v-else>
            <div class="border border-border rounded-xl p-6 text-center" role="status">
                <LogIn class="w-8 h-8 mx-auto mb-3 text-muted-foreground" aria-hidden="true" />
                <p class="font-medium text-foreground mb-1">Sign in to complete your booking</p>
                <p class="text-sm text-muted-foreground mb-4">
                    Your selections have been saved. Signing in won't affect them.
                </p>
                <Button @click="showAuthModal = true">Sign in or create account</Button>
            </div>
            <div class="mt-4">
                <Button variant="outline" @click="store.prevStep()">Back</Button>
            </div>
        </template>

        <!-- Auth modal — reuses shared component -->
        <AuthModal v-model:open="showAuthModal" />
    </div>
</template>