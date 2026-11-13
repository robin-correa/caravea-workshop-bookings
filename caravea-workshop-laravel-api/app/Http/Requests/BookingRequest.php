<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        $presence = $this->route('booking') ? ['sometimes', 'required'] : ['required'];

        return [
            'customer_name' => [...$presence, 'string', 'max:255'],
            'customer_email' => [...$presence, 'string', 'email', 'max:255'],
            'rego' => [...$presence, 'string', 'max:255'],
            'service_bay' => [...$presence, 'integer', 'between:1,'.config('workshop.service_bays')],
            'starts_at' => [...$presence, 'date'],
            'ends_at' => [...$presence, 'date'],
            'status' => ['sometimes', 'required', Rule::enum(BookingStatus::class)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $booking = $this->route('booking');
                $startsAt = Carbon::parse($this->input('starts_at', $booking?->starts_at))->startOfSecond();
                $endsAt = Carbon::parse($this->input('ends_at', $booking?->ends_at))->startOfSecond();

                if ($endsAt->lte($startsAt)) {
                    $validator->errors()->add('ends_at', 'The end time must be after the start time.');
                }
            },
        ];
    }

    /**
     * Validate the original dates before normalising them for SQLite's text comparisons.
     *
     * @return array<string, mixed>
     */
    public function bookingData(): array
    {
        $data = $this->validated();

        foreach (['starts_at', 'ends_at'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = Carbon::parse($data[$field])->utc()->toDateTimeString();
            }
        }

        return $data;
    }
}
