<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Http\Requests\BookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /**
     * List bookings in chronological order, optionally filtered by status.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['sometimes', Rule::enum(BookingStatus::class)],
        ]);

        $bookings = Booking::query()
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('starts_at')
            ->orderBy('id')
            ->paginate()
            ->withQueryString();

        return BookingResource::collection($bookings);
    }

    /**
     * Store a newly created booking.
     */
    public function store(BookingRequest $request): BookingResource
    {
        $booking = Booking::create($request->bookingData());

        return BookingResource::make($booking);
    }

    /**
     * Display the specified booking.
     */
    public function show(Booking $booking): BookingResource
    {
        return BookingResource::make($booking);
    }

    /**
     * Update the specified booking.
     */
    public function update(BookingRequest $request, Booking $booking): BookingResource
    {
        $booking->update($request->bookingData());

        return BookingResource::make($booking);
    }

    /**
     * Remove the specified booking.
     */
    public function destroy(Booking $booking): Response
    {
        $booking->delete();

        return response()->noContent();
    }
}
