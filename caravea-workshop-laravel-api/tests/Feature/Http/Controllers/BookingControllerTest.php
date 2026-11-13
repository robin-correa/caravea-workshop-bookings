<?php

use App\Enums\BookingStatus;
use App\Models\Booking;

beforeEach(function () {
    $this->travelTo('2030-01-01 08:00:00');
});

describe('index', function () {
    it('lists bookings in chronological order', function () {
        $later = Booking::factory()->slot(1, '2030-01-03 09:00:00', '2030-01-03 10:00:00')->create();
        $earlier = Booking::factory()->slot(2, '2030-01-02 09:00:00', '2030-01-02 10:00:00')->create();

        $response = $this->getJson(route('v1.bookings.index'));

        $response->assertOk()
            ->assertJsonPath('data.*.id', [$earlier->id, $later->id])
            ->assertJsonPath('meta.total', 2);
    });

    it('filters bookings by status', function () {
        $confirmed = Booking::factory()->confirmed()->create();
        Booking::factory()->create();

        $response = $this->getJson(route('v1.bookings.index', ['status' => 'confirmed']));

        $response->assertOk()->assertJsonPath('data.*.id', [$confirmed->id]);
    });

    it('paginates filtered bookings without dropping the status from links', function () {
        Booking::factory()->count(16)->confirmed()->create();
        Booking::factory()->create();

        $response = $this->getJson(route('v1.bookings.index', ['status' => 'confirmed']));

        $response->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('links.next', route('v1.bookings.index', ['status' => 'confirmed', 'page' => 2]));

        $this->getJson($response->json('links.next'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'confirmed')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 16);
    });

    it('returns 422 for an unknown status filter', function () {
        $response = $this->getJson(route('v1.bookings.index', ['status' => 'finished']));

        $response->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['status' => 'The selected status is invalid.']);
    });
});

describe('show', function () {
    it('returns the booking', function () {
        $booking = Booking::factory()
            ->confirmed()
            ->slot(2, '2030-01-05 09:00:00', '2030-01-05 11:30:00')
            ->create([
                'customer_name' => 'Sam Driver',
                'customer_email' => 'sam@example.com',
                'rego' => 'XYZ789',
                'notes' => null,
            ]);

        $response = $this->getJson(route('v1.bookings.show', $booking));

        $response->assertOk()->assertExactJson([
            'data' => [
                'id' => $booking->id,
                'customer_name' => 'Sam Driver',
                'customer_email' => 'sam@example.com',
                'rego' => 'XYZ789',
                'service_bay' => 2,
                'starts_at' => '2030-01-05T09:00:00.000000Z',
                'ends_at' => '2030-01-05T11:30:00.000000Z',
                'status' => 'confirmed',
                'notes' => null,
                'created_at' => '2030-01-01T08:00:00.000000Z',
                'updated_at' => '2030-01-01T08:00:00.000000Z',
            ],
        ]);
    });

    it('returns 404 when the booking does not exist', function () {
        $response = $this->getJson(route('v1.bookings.show', 999));

        $response->assertNotFound();
    });
});

describe('store', function () {
    it('creates a pending booking', function () {
        $response = $this->postJson(route('v1.bookings.store'), bookingPayload());

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.starts_at', '2030-01-10T10:00:00.000000Z')
            ->assertJsonPath('data.ends_at', '2030-01-10T12:00:00.000000Z');
        $this->assertDatabaseHas('bookings', [
            'id' => $response->json('data.id'),
            'customer_name' => 'Jane Citizen',
            'customer_email' => 'jane@example.com',
            'rego' => 'ABC123',
            'service_bay' => 1,
            'starts_at' => '2030-01-10 10:00:00',
            'ends_at' => '2030-01-10 12:00:00',
            'status' => 'pending',
            'notes' => 'Check the brakes.',
        ]);
    });

    it('creates a booking that is already confirmed', function () {
        $response = $this->postJson(route('v1.bookings.store'), bookingPayload(['status' => 'confirmed']));

        $response->assertCreated()->assertJsonPath('data.status', 'confirmed');
        $this->assertDatabaseHas('bookings', ['status' => 'confirmed']);
    });

    it('preserves the supplied registration', function (string $rego) {
        $response = $this->postJson(route('v1.bookings.store'), bookingPayload(['rego' => $rego]));

        $response->assertCreated()->assertJsonPath('data.rego', $rego);
        $this->assertDatabaseHas('bookings', ['rego' => $rego]);
    })->with(['abc-12 3', 'AB$123', 'ABCDEFGHIJK']);

    it('allows recording a booking in the past', function () {
        $response = $this->postJson(route('v1.bookings.store'), bookingPayload([
            'starts_at' => '2029-12-30T10:00:00Z',
            'ends_at' => '2029-12-30T12:00:00Z',
        ]));

        $response->assertCreated()->assertJsonPath('data.starts_at', '2029-12-30T10:00:00.000000Z');
        $this->assertDatabaseHas('bookings', ['starts_at' => '2029-12-30 10:00:00']);
    });

    it('creates a cancelled booking even when its slot overlaps an active booking', function () {
        Booking::factory()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();

        $response = $this->postJson(route('v1.bookings.store'), bookingPayload(['status' => 'cancelled']));

        $response->assertCreated()->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('bookings', ['id' => $response->json('data.id'), 'status' => 'cancelled']);
        $this->assertDatabaseCount('bookings', 2);
    });

    it('converts times with a UTC offset to UTC', function () {
        $response = $this->postJson(route('v1.bookings.store'), bookingPayload([
            'starts_at' => '2030-01-10T09:00:00+10:00',
            'ends_at' => '2030-01-10T11:00:00+10:00',
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.starts_at', '2030-01-09T23:00:00.000000Z')
            ->assertJsonPath('data.ends_at', '2030-01-10T01:00:00.000000Z');
        $this->assertDatabaseHas('bookings', [
            'starts_at' => '2030-01-09 23:00:00',
            'ends_at' => '2030-01-10 01:00:00',
        ]);
    });

    it('returns 422 listing every required field when the payload is empty', function () {
        $response = $this->postJson(route('v1.bookings.store'), []);

        $response->assertUnprocessable()->assertOnlyJsonValidationErrors([
            'customer_name' => 'The customer name field is required.',
            'customer_email' => 'The customer email field is required.',
            'rego' => 'The rego field is required.',
            'service_bay' => 'The service bay field is required.',
            'starts_at' => 'The starts at field is required.',
            'ends_at' => 'The ends at field is required.',
        ]);
        $this->assertDatabaseEmpty('bookings');
    });

    it('returns 422 for an invalid field', function (string $field, mixed $value, string $message) {
        $response = $this->postJson(route('v1.bookings.store'), bookingPayload([$field => $value]));

        $response->assertUnprocessable()->assertOnlyJsonValidationErrors([$field => $message]);
        $this->assertDatabaseEmpty('bookings');
    })->with([
        'malformed email' => ['customer_email', 'not-an-email', 'The customer email field must be a valid email address.'],
        'rego is not text' => ['rego', ['ABC123'], 'The rego field must be a string.'],
        'rego too long' => ['rego', str_repeat('A', 256), 'The rego field must not be greater than 255 characters.'],
        'service bay below range' => ['service_bay', 0, 'The service bay field must be between 1 and 4.'],
        'service bay above range' => ['service_bay', 5, 'The service bay field must be between 1 and 4.'],
        'start that is not a date' => ['starts_at', 'not a date', 'The starts at field must be a valid date.'],
        'impossible start date' => ['starts_at', '2030-02-30T10:00:00Z', 'The starts at field must be a valid date.'],
        'impossible end date' => ['ends_at', '2030-02-30T12:00:00Z', 'The ends at field must be a valid date.'],
        'unknown status' => ['status', 'finished', 'The selected status is invalid.'],
        'empty status' => ['status', null, 'The status field is required.'],
    ]);

    it('returns 422 when the booking does not end after it starts', function (string $endsAt) {
        $response = $this->postJson(route('v1.bookings.store'), bookingPayload([
            'starts_at' => '2030-01-10T10:00:00Z',
            'ends_at' => $endsAt,
        ]));

        $response->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['ends_at' => 'The end time must be after the start time.']);
        $this->assertDatabaseEmpty('bookings');
    })->with([
        'ends when it starts' => '2030-01-10T10:00:00Z',
        'ends before it starts' => '2030-01-10T09:00:00Z',
        'ends in the same stored second' => '2030-01-10T10:00:00.500Z',
    ]);

    it('returns 422 when the slot overlaps a booking in the same bay', function (string $startsAt, string $endsAt) {
        Booking::factory()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();

        $response = $this->postJson(route('v1.bookings.store'), bookingPayload([
            'service_bay' => 1,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]));

        $response->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['booking_slot' => Booking::OVERLAP_MESSAGE]);
        $this->assertDatabaseCount('bookings', 1);
    })->with([
        'identical slot' => ['2030-01-10T10:00:00Z', '2030-01-10T12:00:00Z'],
        'overlaps the start' => ['2030-01-10T09:00:00Z', '2030-01-10T10:30:00Z'],
        'overlaps the end' => ['2030-01-10T11:30:00Z', '2030-01-10T13:00:00Z'],
        'inside the existing slot' => ['2030-01-10T10:30:00Z', '2030-01-10T11:00:00Z'],
        'surrounds the existing slot' => ['2030-01-10T09:00:00Z', '2030-01-10T13:00:00Z'],
    ]);

    it('returns 422 when the slot overlaps a confirmed booking', function () {
        Booking::factory()->confirmed()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();

        $response = $this->postJson(route('v1.bookings.store'), bookingPayload());

        $response->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['booking_slot' => Booking::OVERLAP_MESSAGE]);
        $this->assertDatabaseCount('bookings', 1);
    });

    it('allows a booking that starts or ends exactly when another one in the bay does', function (string $startsAt, string $endsAt) {
        Booking::factory()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();

        $response = $this->postJson(route('v1.bookings.store'), bookingPayload([
            'service_bay' => 1,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]));

        $response->assertCreated();
        $this->assertDatabaseCount('bookings', 2);
    })->with([
        'ends when the other starts' => ['2030-01-10T08:00:00Z', '2030-01-10T10:00:00Z'],
        'starts when the other ends' => ['2030-01-10T12:00:00Z', '2030-01-10T14:00:00Z'],
    ]);

    it('allows the same slot in a different bay', function () {
        Booking::factory()->slot(2, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();

        $response = $this->postJson(route('v1.bookings.store'), bookingPayload(['service_bay' => 1]));

        $response->assertCreated();
        $this->assertDatabaseCount('bookings', 2);
    });

    it('allows a slot that is only held by a cancelled booking', function () {
        Booking::factory()->cancelled()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();

        $response = $this->postJson(route('v1.bookings.store'), bookingPayload(['service_bay' => 1]));

        $response->assertCreated();
        $this->assertDatabaseCount('bookings', 2);
    });
});

describe('update', function () {
    it('replaces every field of the booking', function () {
        $booking = Booking::factory()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();

        $response = $this->patchJson(route('v1.bookings.update', $booking), bookingPayload([
            'customer_name' => 'Alex Updated',
            'service_bay' => 3,
            'starts_at' => '2030-01-11T13:00:00Z',
            'ends_at' => '2030-01-11T15:00:00Z',
            'status' => 'confirmed',
        ]));

        $response->assertOk()->assertJsonPath('data.customer_name', 'Alex Updated');
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'customer_name' => 'Alex Updated',
            'service_bay' => 3,
            'starts_at' => '2030-01-11 13:00:00',
            'ends_at' => '2030-01-11 15:00:00',
            'status' => 'confirmed',
        ]);
    });

    it('changes only the status when the payload contains only the status', function () {
        $booking = Booking::factory()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create([
            'customer_name' => 'Unchanged Name',
        ]);

        $response = $this->patchJson(route('v1.bookings.update', $booking), ['status' => 'confirmed']);

        $response->assertOk()->assertJsonPath('data.status', 'confirmed');
        expect($booking->fresh())
            ->status->toBe(BookingStatus::Confirmed)
            ->customer_name->toBe('Unchanged Name')
            ->starts_at->toDateTimeString()->toBe('2030-01-10 10:00:00');
    });

    it('cancels a booking', function () {
        $booking = Booking::factory()->confirmed()->create();

        $response = $this->patchJson(route('v1.bookings.update', $booking), ['status' => 'cancelled']);

        $response->assertOk()->assertJsonPath('data.status', 'cancelled');
        expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled);
    });

    it('does not treat the booking as overlapping itself when it is rescheduled', function () {
        $booking = Booking::factory()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();

        $response = $this->patchJson(route('v1.bookings.update', $booking), [
            'starts_at' => '2030-01-10T11:00:00Z',
            'ends_at' => '2030-01-10T13:00:00Z',
        ]);

        $response->assertOk();
        expect($booking->fresh()->starts_at->toDateTimeString())->toBe('2030-01-10 11:00:00');
    });

    it('returns 422 when the booking is moved into a slot that is taken', function () {
        Booking::factory()->slot(2, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();
        $booking = Booking::factory()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();

        $response = $this->patchJson(route('v1.bookings.update', $booking), ['service_bay' => 2]);

        $response->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['booking_slot' => Booking::OVERLAP_MESSAGE]);
        expect($booking->fresh()->service_bay)->toBe(1);
    });

    it('returns 422 when a cancelled booking is reinstated after its slot was taken', function () {
        $cancelled = Booking::factory()->cancelled()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();
        Booking::factory()->slot(1, '2030-01-10 11:00:00', '2030-01-10 13:00:00')->create();

        $response = $this->patchJson(route('v1.bookings.update', $cancelled), ['status' => 'pending']);

        $response->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['booking_slot' => Booking::OVERLAP_MESSAGE]);
        expect($cancelled->fresh()->status)->toBe(BookingStatus::Cancelled);
    });

    it('returns 422 when only the end time is changed to before the stored start time', function () {
        $booking = Booking::factory()->slot(1, '2030-01-10 10:00:00', '2030-01-10 12:00:00')->create();

        $response = $this->patchJson(route('v1.bookings.update', $booking), ['ends_at' => '2030-01-10T09:00:00Z']);

        $response->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['ends_at' => 'The end time must be after the start time.']);
        expect($booking->fresh()->ends_at->toDateTimeString())->toBe('2030-01-10 12:00:00');
    });

    it('returns 404 when the booking does not exist', function () {
        $response = $this->patchJson(route('v1.bookings.update', 999), ['status' => 'confirmed']);

        $response->assertNotFound();
    });
});

describe('destroy', function () {
    it('deletes the booking', function () {
        $booking = Booking::factory()->create();

        $response = $this->deleteJson(route('v1.bookings.destroy', $booking));

        $response->assertNoContent();
        $this->assertModelMissing($booking);
    });

    it('returns 404 when the booking does not exist', function () {
        $response = $this->deleteJson(route('v1.bookings.destroy', 999));

        $response->assertNotFound();
    });
});
