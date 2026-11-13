import Link from 'next/link'
import { saveBooking } from '../actions'
import { BookingForm } from '../_components/booking-form'

export const metadata = { title: 'New booking' }

const EMPTY = {
  customer_name: '',
  customer_email: '',
  rego: '',
  service_bay: '',
  starts_at: '',
  ends_at: '',
  status: 'pending',
  notes: '',
}

export default function NewBookingPage() {
  return (
    <main className="mx-auto w-full max-w-6xl p-6">
      <Link href="/bookings" className="text-sm underline">
        ← All bookings
      </Link>
      <h1 className="mt-2 mb-6 text-2xl font-semibold">New booking</h1>
      <BookingForm
        action={saveBooking.bind(null, null)}
        defaults={EMPTY}
        submitLabel="Create booking"
        cancelHref="/bookings"
      />
    </main>
  )
}
