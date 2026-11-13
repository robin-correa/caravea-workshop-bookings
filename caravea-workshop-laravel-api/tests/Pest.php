<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

/**
 * A valid booking request payload, with optional overrides.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function bookingPayload(array $overrides = []): array
{
    return [
        'customer_name' => 'Jane Citizen',
        'customer_email' => 'jane@example.com',
        'rego' => 'ABC123',
        'service_bay' => 1,
        'starts_at' => '2030-01-10T10:00:00Z',
        'ends_at' => '2030-01-10T12:00:00Z',
        'notes' => 'Check the brakes.',
        ...$overrides,
    ];
}
