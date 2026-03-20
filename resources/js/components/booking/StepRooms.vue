<script setup lang="ts">
import {
    Users,
    ChevronLeft,
    ChevronRight,
    Minus,
    Plus,
    BedDouble,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useFormatters } from '@/composables/useFormatters';
import { useBookingStore } from '@/stores/bookingStore';
import type { Unit } from '@/stores/bookingStore';

defineProps<{ units: Unit[] }>();
const store = useBookingStore();

const { formatPrice } = useFormatters();

// ── Image carousel per unit ───────────────────────────────────────────────
const activeImageIndex = ref<Record<number, number>>({});

function getImageIndex(unitId: number): number {
    return activeImageIndex.value[unitId] ?? 0;
}

function prevImage(unitId: number, total: number) {
    const current = getImageIndex(unitId);
    activeImageIndex.value[unitId] = current === 0 ? total - 1 : current - 1;
}

function nextImage(unitId: number, total: number) {
    const current = getImageIndex(unitId);
    activeImageIndex.value[unitId] = current === total - 1 ? 0 : current + 1;
}

// ── Availability ──────────────────────────────────────────────────────────
function isUnitSelectable(unit: Unit): boolean {
    return unit.available_count > 0;
}

// ── Selection ─────────────────────────────────────────────────────────────
function selectUnit(unit: Unit) {
    if (!isUnitSelectable(unit)) {
        return;
    }

    store.selectedUnit = unit;
    store.quantity     = 1;
    store.guests       = { adults: 1, children: 0 };
}

const isSelected = (unit: Unit) => store.selectedUnit?.id === unit.id;

// ── Quantity ──────────────────────────────────────────────────────────────
const maxQuantity = computed(() => store.selectedUnit?.available_count ?? 1);

function decrementQuantity() {
    if (store.quantity > 1) {
        store.quantity--;
    }
}

function incrementQuantity() {
    if (store.quantity < maxQuantity.value) {
        store.quantity++;
    }
}

// ── Guests ────────────────────────────────────────────────────────────────
const maxGuests = computed(() =>
    (store.selectedUnit?.max_guests ?? 1) * store.quantity
);

function decrementAdults() {
    if (store.guests.adults > 1) {
        store.guests.adults--;
    }
}

function incrementAdults() {
    if (store.totalGuests < maxGuests.value) {
        store.guests.adults++;
    }
}

function decrementChildren() {
    if (store.guests.children > 0) {
        store.guests.children--;
    }
}

function incrementChildren() {
    if (store.totalGuests < maxGuests.value) {
        store.guests.children++;
    }
}

// ── Formatting is provided by the `useFormatters` composable
</script>

<template>
    <div>
        <h2 class="text-lg font-medium mb-1">Choose a room</h2>
        <p class="text-sm text-muted-foreground mb-6">
            Select a room type for your
            <span class="font-medium text-foreground">{{ store.nights }}-night</span>
            stay.
        </p>

        <!-- Two column layout on md+, stacked on mobile -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">

            <!-- ── Left: Room cards ──────────────────────────────────── -->
            <div
                class="flex flex-col gap-3"
                role="list"
                aria-label="Available room types"
            >
                <article
                    v-for="unit in units"
                    :key="unit.id"
                    role="listitem"
                    class="border rounded-xl overflow-hidden transition-all duration-200 cursor-pointer"
                    :class="[
                        isSelected(unit)
                            ? 'border-primary ring-2 ring-primary/20'
                            : 'border-border hover:border-primary/40',
                        !isUnitSelectable(unit)
                            ? 'opacity-50 cursor-not-allowed'
                            : 'cursor-pointer',
                    ]"
                    :aria-selected="isSelected(unit)"
                    @click="selectUnit(unit)"
                >
                    <!-- Image carousel -->
                    <div class="relative h-40 bg-muted overflow-hidden">
                        <img
                            :src="unit.pictures[getImageIndex(unit.id)]"
                            :alt="`${unit.name} — image ${getImageIndex(unit.id) + 1} of ${unit.pictures.length}`"
                            class="w-full h-full object-cover"
                        />

                        <template v-if="unit.pictures.length > 1">
                            <button
                                class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white rounded-full w-6 h-6 flex items-center justify-center transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                :aria-label="`Previous image for ${unit.name}`"
                                @click.stop="prevImage(unit.id, unit.pictures.length)"
                            >
                                <ChevronLeft class="w-3.5 h-3.5" aria-hidden="true" />
                            </button>
                            <button
                                class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white rounded-full w-6 h-6 flex items-center justify-center transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                :aria-label="`Next image for ${unit.name}`"
                                @click.stop="nextImage(unit.id, unit.pictures.length)"
                            >
                                <ChevronRight class="w-3.5 h-3.5" aria-hidden="true" />
                            </button>

                            <div
                                class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1"
                                aria-hidden="true"
                            >
                                <span
                                    v-for="(_, i) in unit.pictures"
                                    :key="i"
                                    class="w-1.5 h-1.5 rounded-full transition-colors"
                                    :class="i === getImageIndex(unit.id)
                                        ? 'bg-white'
                                        : 'bg-white/50'"
                                />
                            </div>
                        </template>

                        <!-- Selected badge -->
                        <div
                            v-if="isSelected(unit)"
                            class="absolute top-2 right-2"
                            aria-hidden="true"
                        >
                            <Badge class="text-xs">Selected</Badge>
                        </div>

                        <!-- Unavailable overlay -->
                        <div
                            v-if="!isUnitSelectable(unit)"
                            class="absolute inset-0 bg-background/70 flex items-center justify-center"
                            aria-hidden="true"
                        >
                            <Badge variant="secondary">Unavailable</Badge>
                        </div>
                    </div>

                    <!-- Card body — compact -->
                    <div class="p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="font-medium text-foreground text-sm">
                                    {{ unit.name }}
                                </h3>
                                <p class="text-xs text-muted-foreground mt-0.5 line-clamp-2">
                                    {{ unit.description }}
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-semibold text-foreground text-sm">
                                    {{ formatPrice(unit.price) }}
                                </p>
                                <p class="text-xs text-muted-foreground">/ night</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-1 text-xs text-muted-foreground mt-2">
                            <Users class="w-3 h-3" aria-hidden="true" />
                            <span>Max {{ unit.max_guests }}</span>
                            <span aria-hidden="true">·</span>
                            <span>{{ unit.available_count }} available</span>
                        </div>
                    </div>
                </article>
            </div>

            <!-- ── Right: Configuration panel ───────────────────────── -->
            <div class="md:sticky md:top-24">
                <!-- Empty state -->
                <div
                    v-if="!store.selectedUnit"
                    class="border border-dashed border-border rounded-xl p-8 flex flex-col items-center justify-center text-center gap-3"
                    aria-label="No room selected"
                >
                    <BedDouble
                        class="w-8 h-8 text-muted-foreground/50"
                        aria-hidden="true"
                    />
                    <div>
                        <p class="text-sm font-medium text-foreground">
                            Select a room
                        </p>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            Choose a room on the left to configure your stay
                        </p>
                    </div>
                </div>

                <!-- Configuration -->
                <div
                    v-else
                    class="border border-primary/30 bg-primary/5 rounded-xl p-4 flex flex-col gap-4"
                    aria-label="Configure your stay"
                >
                    <!-- Selected room recap -->
                    <div>
                        <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">
                            Selected room
                        </p>
                        <p class="font-medium text-foreground">
                            {{ store.selectedUnit.name }}
                        </p>
                    </div>

                    <Separator />

                    <!-- Quantity counter -->
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-foreground">Rooms</p>
                            <p class="text-xs text-muted-foreground">
                                {{ maxQuantity }} available
                            </p>
                        </div>
                        <div
                            class="flex items-center gap-3"
                            role="group"
                            aria-label="Number of rooms"
                        >
                            <button
                                class="w-8 h-8 rounded-full border border-border bg-background flex items-center justify-center text-foreground hover:bg-accent disabled:opacity-40 disabled:cursor-not-allowed transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                :disabled="store.quantity <= 1"
                                :aria-label="`Decrease rooms, currently ${store.quantity}`"
                                @click="decrementQuantity"
                            >
                                <Minus class="w-3.5 h-3.5" aria-hidden="true" />
                            </button>
                            <span
                                class="w-6 text-center text-sm font-medium tabular-nums"
                                aria-live="polite"
                            >
                                {{ store.quantity }}
                            </span>
                            <button
                                class="w-8 h-8 rounded-full border border-border bg-background flex items-center justify-center text-foreground hover:bg-accent disabled:opacity-40 disabled:cursor-not-allowed transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                :disabled="store.quantity >= maxQuantity"
                                :aria-label="`Increase rooms, currently ${store.quantity}`"
                                @click="incrementQuantity"
                            >
                                <Plus class="w-3.5 h-3.5" aria-hidden="true" />
                            </button>
                        </div>
                    </div>

                    <!-- Adults counter -->
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-foreground">Adults</p>
                            <p class="text-xs text-muted-foreground">Age 13+</p>
                        </div>
                        <div
                            class="flex items-center gap-3"
                            role="group"
                            aria-label="Number of adults"
                        >
                            <button
                                class="w-8 h-8 rounded-full border border-border bg-background flex items-center justify-center text-foreground hover:bg-accent disabled:opacity-40 disabled:cursor-not-allowed transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                :disabled="store.guests.adults <= 1"
                                :aria-label="`Decrease adults, currently ${store.guests.adults}`"
                                @click="decrementAdults"
                            >
                                <Minus class="w-3.5 h-3.5" aria-hidden="true" />
                            </button>
                            <span
                                class="w-6 text-center text-sm font-medium tabular-nums"
                                aria-live="polite"
                            >
                                {{ store.guests.adults }}
                            </span>
                            <button
                                class="w-8 h-8 rounded-full border border-border bg-background flex items-center justify-center text-foreground hover:bg-accent disabled:opacity-40 disabled:cursor-not-allowed transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                :disabled="store.totalGuests >= maxGuests"
                                :aria-label="`Increase adults, currently ${store.guests.adults}`"
                                @click="incrementAdults"
                            >
                                <Plus class="w-3.5 h-3.5" aria-hidden="true" />
                            </button>
                        </div>
                    </div>

                    <!-- Children counter -->
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-foreground">Children</p>
                            <p class="text-xs text-muted-foreground">Ages 2–12</p>
                        </div>
                        <div
                            class="flex items-center gap-3"
                            role="group"
                            aria-label="Number of children"
                        >
                            <button
                                class="w-8 h-8 rounded-full border border-border bg-background flex items-center justify-center text-foreground hover:bg-accent disabled:opacity-40 disabled:cursor-not-allowed transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                :disabled="store.guests.children <= 0"
                                :aria-label="`Decrease children, currently ${store.guests.children}`"
                                @click="decrementChildren"
                            >
                                <Minus class="w-3.5 h-3.5" aria-hidden="true" />
                            </button>
                            <span
                                class="w-6 text-center text-sm font-medium tabular-nums"
                                aria-live="polite"
                            >
                                {{ store.guests.children }}
                            </span>
                            <button
                                class="w-8 h-8 rounded-full border border-border bg-background flex items-center justify-center text-foreground hover:bg-accent disabled:opacity-40 disabled:cursor-not-allowed transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                :disabled="store.totalGuests >= maxGuests"
                                :aria-label="`Increase children, currently ${store.guests.children}`"
                                @click="incrementChildren"
                            >
                                <Plus class="w-3.5 h-3.5" aria-hidden="true" />
                            </button>
                        </div>
                    </div>

                    <!-- Capacity warning -->
                    <p
                        v-if="store.totalGuests >= maxGuests"
                        role="status"
                        aria-live="polite"
                        class="text-xs text-muted-foreground"
                    >
                        Maximum capacity reached for {{ store.quantity }}
                        {{ store.quantity > 1 ? 'rooms' : 'room' }}.
                    </p>

                    <Separator />

                    <!-- Live price breakdown -->
                    <div
                        class="flex flex-col gap-1.5 text-sm"
                        role="region"
                        aria-label="Price breakdown"
                    >
                        <div class="flex justify-between text-muted-foreground">
                            <span>
                                {{ formatPrice(store.selectedUnit.price) }}
                                × {{ store.quantity }}
                                {{ store.quantity > 1 ? 'rooms' : 'room' }}
                                × {{ store.nights }}
                                {{ store.nights > 1 ? 'nights' : 'night' }}
                            </span>
                            <span>{{ formatPrice(store.basePrice) }}</span>
                        </div>
                        <div class="flex justify-between text-muted-foreground">
                            <span>VAT (15%)</span>
                            <span>{{ formatPrice(store.taxAmount) }}</span>
                        </div>
                        <Separator class="my-0.5" />
                        <div class="flex justify-between font-semibold text-foreground">
                            <span>Total</span>
                            <span aria-live="polite">{{ formatPrice(store.totalPrice) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="mt-8 flex justify-between">
            <Button
                variant="outline"
                aria-label="Back to date selection"
                @click="store.prevStep()"
            >
                Back
            </Button>
            <Button
                :disabled="!store.stepTwoValid"
                aria-label="Continue to booking summary"
                @click="store.nextStep()"
            >
                Continue
            </Button>
        </div>
    </div>
</template>