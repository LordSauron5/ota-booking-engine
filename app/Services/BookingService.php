<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BookingService
{
    // ── Pricing ───────────────────────────────────────────────────────────

    /**
     * Calculate a price breakdown for a booking.
     *
     * @return array{nights: int, basePrice: float, taxAmount: float, total: float}
     */
    public function calculatePricing(array $unit, string $checkIn, string $checkOut, int $quantity): array
    {
        $nights = (int) Carbon::parse($checkIn)->diffInDays($checkOut);
        $basePrice = $unit['price'] * $quantity * $nights;
        $taxAmount = round($basePrice * 0.15, 2);
        $total = round($basePrice + $taxAmount, 2);

        return compact('nights', 'basePrice', 'taxAmount', 'total');
    }

    // ── Draft ─────────────────────────────────────────────────────────────

    /**
     * Create or refresh the single draft booking tied to this session.
     *
     * We use updateOrCreate keyed on (session_token + status = draft) so that
     * a user who goes "Back" and changes their dates doesn't orphan old rows.
     */
    public function createOrUpdateDraft(array $validated, array $unit): Booking
    {
        $pricing = $this->calculatePricing(
            $unit,
            $validated['check_in'],
            $validated['check_out'],
            $validated['quantity'],
        );

        return Booking::updateOrCreate(
            [
                'session_token' => session()->getId(),
                'status' => 'draft',
            ],
            [
                'reference' => 'LLL-'.date('Y').'-'.strtoupper(Str::random(5)),
                'user_id' => auth()->id(),
                'session_token' => session()->getId(),
                'session_token_expires_at' => now()->addHours(2),
                'unit_id' => $unit['id'],
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'nights' => $pricing['nights'],
                'quantity' => $validated['quantity'],
                'guests' => $validated['guests'],
                'price_per_unit' => $unit['price'],
                'base_price' => $pricing['basePrice'],
                'tax_amount' => $pricing['taxAmount'],
                'total_price' => $pricing['total'],
                'status' => 'draft',
            ]
        );
    }

    // ── Claim ─────────────────────────────────────────────────────────────

    /**
     * Attach an authenticated user to a guest-created draft.
     *
     * After Fortify login/register the session ID regenerates, so we can't
     * re-verify via session_token here. We rely on the booking still being
     * a draft with no owner and a valid expiry window.
     *
     * Returns true on success, or a string error message on failure.
     */
    public function claimDraft(Booking $booking): true|string
    {
        // Already owned — idempotent success for the current user
        if ($booking->user_id !== null) {
            if ($booking->user_id === auth()->id()) {
                return true;
            }

            return 'Booking already claimed.';
        }

        if ($booking->status !== 'draft') {
            return 'Booking is no longer a draft.';
        }

        if ($booking->session_token_expires_at->isPast()) {
            return 'Session expired. Please start again.';
        }

        $booking->update(['user_id' => auth()->id()]);

        return true;
    }
}
