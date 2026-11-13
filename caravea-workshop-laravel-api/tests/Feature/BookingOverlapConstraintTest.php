<?php

/*
 * SQLite triggers enforce the overlap rule atomically. These tests exercise
 * the triggers directly and under real concurrency.
 */

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;

it('rejects an overlapping insert that bypasses validation', function () {
    Booking::factory()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();

    $insertOverlappingBooking = fn () => Booking::factory()->slot(1, '2030-01-10 11:00:00', '2030-01-10 13:00:00')->create();

    expect($insertOverlappingBooking)->toThrow(QueryException::class, Booking::OVERLAP_CONSTRAINT);
    $this->assertDatabaseCount('bookings', 1);
});

it('accepts an overlapping insert when the new booking is cancelled', function () {
    Booking::factory()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();

    Booking::factory()->cancelled()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();

    $this->assertDatabaseCount('bookings', 2);
});

it('rejects an update that bypasses validation to move a booking into a taken slot', function () {
    Booking::factory()->slot(2, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();
    $booking = Booking::factory()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();

    $moveIntoTakenSlot = fn () => $booking->update(['service_bay' => 2]);

    expect($moveIntoTakenSlot)->toThrow(QueryException::class, Booking::OVERLAP_CONSTRAINT);
    expect($booking->fresh()->service_bay)->toBe(1);
});

it('rejects an update that bypasses validation to reinstate a cancelled booking whose slot was taken', function () {
    $cancelled = Booking::factory()->cancelled()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();
    Booking::factory()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();

    $reinstate = fn () => $cancelled->update(['status' => BookingStatus::Pending]);

    expect($reinstate)->toThrow(QueryException::class, Booking::OVERLAP_CONSTRAINT);
    expect($cancelled->fresh()->status)->toBe(BookingStatus::Cancelled);
});

it('returns 422 when another booking takes the slot after validation passed', function () {
    $this->travelTo('2030-01-01 08:00:00');
    Booking::creating(function () {
        // Simulates a competing request committing between this request's
        // validation and its insert: the race window validation cannot close.
        DB::table('bookings')->insert([
            'customer_name' => 'Competing Customer',
            'customer_email' => 'competitor@example.com',
            'rego' => 'RACE01',
            'service_bay' => 1,
            'starts_at' => '2030-01-10 10:00:00',
            'ends_at' => '2030-01-10 12:00:00',
            'status' => 'pending',
        ]);
    });

    $response = $this->postJson(route('v1.bookings.store'), bookingPayload([
        'service_bay' => 1,
        'starts_at' => '2030-01-10T10:00:00Z',
        'ends_at' => '2030-01-10T12:00:00Z',
    ]));

    $response->assertUnprocessable()
        ->assertOnlyJsonValidationErrors(['booking_slot' => Booking::OVERLAP_MESSAGE]);
    $this->assertDatabaseCount('bookings', 1);
    $this->assertDatabaseHas('bookings', ['customer_name' => 'Competing Customer']);
});

it('does not mistake an unrelated database failure for an overlap when a note contains the constraint name', function () {
    Booking::creating(function (Booking $booking): void {
        $booking->customer_name = null;
    });

    $response = $this->postJson(route('v1.bookings.store'), bookingPayload([
        'notes' => Booking::OVERLAP_CONSTRAINT,
    ]));

    $response->assertInternalServerError();
    $this->assertDatabaseEmpty('bookings');
});

it('accepts exactly one of several simultaneous requests for the same slot', function () {
    // Each request runs in its own PHP process against a shared SQLite file,
    // because an in-memory database cannot be shared between processes.
    $databasePath = tempnam(sys_get_temp_dir(), 'bookings-race-');
    config(['database.connections.race' => [...config('database.connections.sqlite'), 'database' => $databasePath]]);
    $this->artisan('migrate', ['--database' => 'race'])->assertSuccessful();

    $payload = bookingPayload();
    $releaseAt = microtime(true) + 1.5;
    $sendRequest = static function () use ($databasePath, $payload, $releaseAt): int {
        config(['database.connections.sqlite.database' => $databasePath]);
        DB::purge('sqlite');

        // Hold every process until the same instant so the requests collide.
        while (microtime(true) < $releaseAt) {
            usleep(500);
        }

        $request = Request::create('/api/v1/bookings', 'POST', $payload, server: ['HTTP_ACCEPT' => 'application/json']);

        return app(Kernel::class)->handle($request)->getStatusCode();
    };

    try {
        // Drop the closure's class scope: Pest's generated test class does not
        // exist in the child processes that unserialize it.
        $statusCodes = Concurrency::driver('process')->run(array_fill(0, 8, Closure::bind($sendRequest, null, null)));

        expect(array_count_values($statusCodes))->toEqual([201 => 1, 422 => 7]);
        expect(DB::connection('race')->table('bookings')->count())->toBe(1);
    } finally {
        DB::purge('race');
        unlink($databasePath);
    }
})->group('concurrency');
