<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDraftBookingRequest;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly RoomService $roomService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Booking', [
            'property' => $this->roomService->getProperty(),
            'units' => $this->roomService->getRooms(),
        ]);
    }

    public function draft(StoreDraftBookingRequest $request): JsonResponse
    {
        // Validate Request & Business Rules (via StoreDraftBookingRequest)
        $validated = $request->validated();

        $unit = collect($this->roomService->getRooms())
            ->firstWhere('id', $validated['unit_id']);

        $booking = $this->bookingService->createOrUpdateDraft($validated, $unit);

        return response()->json([
            'booking_id' => $booking->id,
            'reference' => $booking->reference,
        ]);
    }

    public function claim(Request $request, Booking $booking): JsonResponse
    {
        if (! auth()->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->bookingService->claimDraft($booking);

        if ($result !== true) {
            $status = str_contains($result, 'expired') ? 403 : 422;
            // "already claimed by someone else" is a 403
            $status = str_contains($result, 'already claimed') ? 403 : $status;

            return response()->json(['message' => $result], $status);
        }

        return response()->json(['success' => true]);
    }

    public function confirm(Request $request, Booking $booking): JsonResponse
    {
        if (! auth()->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($booking->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $unit = collect($this->roomService->getRooms())
            ->firstWhere('id', $booking->unit_id);

        if (! $unit) {
            return response()->json(['message' => 'Room no longer available.'], 422);
        }

        $result = $this->bookingService->confirmDraft($booking, $unit);

        if ($result !== true) {
            return response()->json(['message' => $result], 422);
        }

        return response()->json(['success' => true]);
    }
}
