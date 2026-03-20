<?php

namespace App\Http\Controllers;

use App\Services\RoomService;
use Inertia\Inertia;
use Inertia\Response;

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
}
