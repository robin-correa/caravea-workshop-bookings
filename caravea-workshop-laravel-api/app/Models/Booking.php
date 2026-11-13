<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $customer_name
 * @property string $customer_email
 * @property string $rego
 * @property int $service_bay
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property BookingStatus $status
 * @property string|null $notes
 */
#[Fillable([
    'customer_name',
    'customer_email',
    'rego',
    'service_bay',
    'starts_at',
    'ends_at',
    'status',
    'notes',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * Name raised by the database triggers that enforce the overlap rule.
     */
    public const string OVERLAP_CONSTRAINT = 'bookings_service_bay_overlap';

    public const string OVERLAP_MESSAGE = 'This bay is already booked during part of the selected time range. Choose another bay or adjust the start or end time.';

    /**
     * Mirrors the column default so a freshly created model reports its status.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_bay' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => BookingStatus::class,
        ];
    }

    /**
     * Whether the given exception was raised by the database overlap triggers.
     */
    public static function isOverlapViolation(QueryException $exception): bool
    {
        return ($exception->errorInfo[2] ?? null) === self::OVERLAP_CONSTRAINT;
    }
}
