export const BOOKING_STATUSES = ['pending', 'confirmed', 'cancelled'] as const

export type BookingStatus = (typeof BOOKING_STATUSES)[number]

export type Booking = {
  id: number
  customer_name: string
  customer_email: string
  rego: string
  service_bay: number
  starts_at: string
  ends_at: string
  status: BookingStatus
  notes: string | null
  created_at: string
  updated_at: string
}

export type Paginated<T> = {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
  }
}

/** Form fields exactly as the browser submits them. */
export const BOOKING_FIELDS = [
  'customer_name',
  'customer_email',
  'rego',
  'service_bay',
  'starts_at',
  'ends_at',
  'status',
  'notes',
] as const

export type BookingField = (typeof BOOKING_FIELDS)[number]

export type BookingFormValues = Record<BookingField, string>

/** Laravel's 422 body: field errors plus booking_slot for bay/time conflicts. */
export type FieldErrors = Partial<Record<string, string[]>>

export type FormState = {
  message?: string
  errors?: FieldErrors
  values?: BookingFormValues
}
