<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDraftBookingRequest;
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
}
