<script setup lang="ts">
import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone,
    today,
} from '@internationalized/date';
import { Calendar as CalendarIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useBookingStore } from '@/stores/bookingStore';

const store = useBookingStore();

const df = new DateFormatter('en-ZA', { dateStyle: 'medium' });

const todayDate = today(getLocalTimeZone());

// ── Helpers to convert between string (store) and CalendarDate (shadcn) ──

function toCalendarDate(str: string | null): CalendarDate | undefined {
    if (!str) {
        return undefined;
    }

    const [y, m, d] = str.split('-').map(Number);

    return new CalendarDate(y, m, d);
}

function toString(date: CalendarDate): string {
    return date.toString(); // yields "YYYY-MM-DD"
}

// ── Bound values ──────────────────────────────────────────────────────────

const checkInValue = computed({
    get: () => toCalendarDate(store.checkIn),
    set: (val: CalendarDate | undefined) => {
        store.checkIn = val ? toString(val) : null;

        // If check-out is now before or equal to new check-in, clear it
        if (store.checkOut && val) {
            const out = toCalendarDate(store.checkOut);

            if (out && out.compare(val) <= 0) {
                store.checkOut = null;
            }
        }
    },
});

const checkOutValue = computed({
    get: () => toCalendarDate(store.checkOut),
    set: (val: CalendarDate | undefined) => {
        store.checkOut = val ? toString(val) : null;
    },
});

// ── Display labels ────────────────────────────────────────────────────────

const checkInLabel = computed(() =>
    checkInValue.value
        ? df.format(checkInValue.value.toDate(getLocalTimeZone()))
        : 'Select date',
);

const checkOutLabel = computed(() =>
    checkOutValue.value
        ? df.format(checkOutValue.value.toDate(getLocalTimeZone()))
        : 'Select date',
);

// ── Disabled date logic ───────────────────────────────────────────────────

const isCheckInDisabled = (date: CalendarDate) => date.compare(todayDate) < 0;

const isCheckOutDisabled = (date: CalendarDate) => {
    const minDate = checkInValue.value ?? todayDate;

    return date.compare(minDate) <= 0;
};

// ── Summary ───────────────────────────────────────────────────────────────

const nightsLabel = computed(() => {
    if (store.nights === 0) {
        return null;
    }

    return store.nights === 1 ? '1 night' : `${store.nights} nights`;
});
</script>

<template>
    <div>
        <h2 class="mb-1 text-lg font-medium">Select your dates</h2>
        <p class="mb-6 text-sm text-muted-foreground">
            Choose your check-in and check-out dates.
        </p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <!-- Check-in -->
            <div class="flex flex-col gap-1.5">
                <label
                    id="check-in-label"
                    class="text-sm font-medium text-foreground"
                >
                    Check-in
                </label>
                <Popover>
                    <PopoverTrigger as-child>
                        <Button
                            variant="outline"
                            aria-labelledby="check-in-label"
                            :aria-describedby="
                                checkInValue ? 'check-in-value' : undefined
                            "
                            class="w-full justify-start text-left font-normal"
                            :class="!checkInValue && 'text-muted-foreground'"
                        >
                            <CalendarIcon
                                class="mr-2 h-4 w-4 shrink-0"
                                aria-hidden="true"
                            />
                            <span id="check-in-value">{{ checkInLabel }}</span>
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-auto p-0" align="start">
                        <Calendar
                            v-model="checkInValue"
                            :is-date-disabled="isCheckInDisabled"
                            initial-focus
                        />
                    </PopoverContent>
                </Popover>
            </div>

            <!-- Check-out -->
            <div class="flex flex-col gap-1.5">
                <label
                    id="check-out-label"
                    class="text-sm font-medium text-foreground"
                >
                    Check-out
                </label>
                <Popover>
                    <PopoverTrigger as-child>
                        <Button
                            variant="outline"
                            aria-labelledby="check-out-label"
                            :aria-describedby="
                                checkOutValue ? 'check-out-value' : undefined
                            "
                            class="w-full justify-start text-left font-normal"
                            :class="!checkOutValue && 'text-muted-foreground'"
                            :disabled="!checkInValue"
                        >
                            <CalendarIcon
                                class="mr-2 h-4 w-4 shrink-0"
                                aria-hidden="true"
                            />
                            <span id="check-out-value">{{
                                checkOutLabel
                            }}</span>
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-auto p-0" align="start">
                        <Calendar
                            v-model="checkOutValue"
                            :is-date-disabled="isCheckOutDisabled"
                            initial-focus
                        />
                    </PopoverContent>
                </Popover>
                <p
                    v-if="!checkInValue"
                    class="text-xs text-muted-foreground"
                    aria-live="polite"
                >
                    Select a check-in date first.
                </p>
            </div>
        </div>

        <!-- Nights summary -->
        <div
            v-if="nightsLabel"
            role="status"
            aria-live="polite"
            class="mt-6 flex items-center gap-2 text-sm text-muted-foreground"
        >
            <span
                class="inline-block h-2 w-2 rounded-full bg-primary"
                aria-hidden="true"
            />
            {{ nightsLabel }} selected
            <span class="mx-1">·</span>
            {{ checkInLabel }} → {{ checkOutLabel }}
        </div>

        <!-- Validation hint -->
        <p
            v-if="store.checkIn && store.checkOut && store.nights <= 0"
            role="alert"
            class="mt-3 text-sm text-destructive"
        >
            Check-out must be after check-in.
        </p>

        <!-- Next button -->
        <div class="mt-8 flex justify-end">
            <Button
                :disabled="!store.stepOneValid"
                aria-label="Continue to room selection"
                @click="store.nextStep()"
            >
                Continue
            </Button>
        </div>
    </div>
</template>
