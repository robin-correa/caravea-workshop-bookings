<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Each booking gets its own future day so factory records never trip the
     * overlap rule unless a test places them in the same slot on purpose.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()
            ->addDays(fake()->unique()->numberBetween(1, 3650))
            ->setTime(fake()->numberBetween(8, 14), 0);

        return [
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'rego' => fake()->regexify('[A-Z]{3}[0-9]{3}'),
            'service_bay' => fake()->numberBetween(1, config('workshop.service_bays')),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(fake()->numberBetween(1, 3)),
            'status' => BookingStatus::Pending,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Place the booking in a specific service bay and time slot.
     */
    public function slot(int $serviceBay, string $startsAt, string $endsAt): static
    {
        return $this->state(fn (array $attributes) => [
            'service_bay' => $serviceBay,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }

    /**
     * Indicate that the booking has been confirmed by the workshop.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Confirmed,
        ]);
    }

    /**
     * Indicate that the booking has been cancelled and no longer holds its slot.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Cancelled,
        ]);
    }
}
