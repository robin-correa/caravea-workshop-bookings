import Link from 'next/link'
import { notFound } from 'next/navigation'
import { getBooking } from '@/lib/bookings'
import { formatDateTime } from '@/lib/datetime'
import { DeleteBookingButton } from '../_components/delete-booking-button'
import { StatusBadge } from '../_components/status-badge'

export default async function BookingPage({ params }: PageProps<'/bookings/[id]'>) {
  const { id } = await params
  const booking = await getBooking(id)
  if (!booking) notFound()

  const rows: [string, React.ReactNode][] = [
    ['Customer', booking.customer_name],
    ['Email', <a key="email" href={`mailto:${booking.customer_email}`} className="underline">{booking.customer_email}</a>],
    ['Rego', <span key="rego" className="font-mono">{booking.rego}</span>],
    ['Service bay', `Bay ${booking.service_bay}`],
    ['Starts', formatDateTime(booking.starts_at)],
    ['Ends', formatDateTime(booking.ends_at)],
    ['Status', <StatusBadge key="status" status={booking.status} />],
    ['Notes', booking.notes ? <span key="notes" className="whitespace-pre-wrap">{booking.notes}</span> : <span key="notes" className="text-zinc-400">None</span>],
  ]

  return (
    <main className="mx-auto w-full max-w-6xl p-6">
      <title>{`Booking #${booking.id}`}</title>
      <Link href="/bookings" className="text-sm underline">
        ← All bookings
      </Link>
      <div className="mt-2 mb-6 flex flex-wrap items-center gap-4">
        <h1 className="text-2xl font-semibold">Booking #{booking.id}</h1>
        <Link
          href={`/bookings/${booking.id}/edit`}
          className="rounded bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white dark:bg-zinc-100 dark:text-zinc-900"
        >
          Edit
        </Link>
        <DeleteBookingButton id={booking.id} />
      </div>
      <dl className="grid max-w-2xl grid-cols-[10rem_1fr] gap-x-4 gap-y-3 text-sm">
        {rows.map(([label, value]) => (
          <div key={label} className="contents">
            <dt className="font-medium text-zinc-500">{label}</dt>
            <dd>{value}</dd>
          </div>
        ))}
      </dl>
    </main>
  )
}
