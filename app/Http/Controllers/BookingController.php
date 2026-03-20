<?php

namespace App\Http\Controllers;

use App\Services\RoomService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Booking', [
            'property' => $this->roomService->getProperty(),
            'units' => $this->roomService->getRooms(),
        ]);
    }

    public function draft(Request $request): JsonResponse
    {
        dd("here");
        return response()->json(['success' => true, 'message' => 'Draft booking saved successfully.']);
    }
}
