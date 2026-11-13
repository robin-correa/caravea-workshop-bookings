import Link from 'next/link'
import { notFound } from 'next/navigation'
import { getBooking } from '@/lib/bookings'
import { toDatetimeLocal } from '@/lib/datetime'
import { saveBooking } from '../../actions'
import { BookingForm } from '../../_components/booking-form'

export default async function EditBookingPage({ params }: PageProps<'/bookings/[id]/edit'>) {
  const { id } = await params
  const booking = await getBooking(id)
  if (!booking) notFound()

  return (
    <main className="mx-auto w-full max-w-6xl p-6">
      <title>{`Edit booking #${booking.id}`}</title>
      <Link href={`/bookings/${booking.id}`} className="text-sm underline">
        ← Booking #{booking.id}
      </Link>
      <h1 className="mt-2 mb-6 text-2xl font-semibold">Edit booking #{booking.id}</h1>
      <BookingForm
        action={saveBooking.bind(null, booking.id)}
        defaults={{
          customer_name: booking.customer_name,
          customer_email: booking.customer_email,
          rego: booking.rego,
          service_bay: String(booking.service_bay),
          starts_at: toDatetimeLocal(booking.starts_at),
          ends_at: toDatetimeLocal(booking.ends_at),
          status: booking.status,
          notes: booking.notes ?? '',
        }}
        submitLabel="Save changes"
        cancelHref={`/bookings/${booking.id}`}
      />
    </main>
  )
}
