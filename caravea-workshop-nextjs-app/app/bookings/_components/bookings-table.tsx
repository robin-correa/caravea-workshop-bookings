import Link from 'next/link'
import { getBookings } from '@/lib/bookings'
import { formatRange } from '@/lib/datetime'
import type { Booking, BookingStatus } from '@/lib/types'
import { bookingsHref } from './href'
import { StatusBadge } from './status-badge'

export async function BookingsTable({
  status,
  page,
}: {
  status: BookingStatus | null
  page: number
}) {
  const { data: bookings, meta } = await getBookings(status, page)

  if (bookings.length === 0) {
    return (
      <div className="rounded border border-dashed border-zinc-300 p-10 text-center text-sm dark:border-zinc-700">
        {page > 1 ? (
          <p>
            There are no bookings on page {page}.{' '}
            <Link href={bookingsHref(status)} className="underline">
              Go to the first page
            </Link>
          </p>
        ) : (
          <p>
            No {status ?? ''} bookings yet.{' '}
            <Link href="/bookings/new" className="underline">
              Create one
            </Link>
          </p>
        )}
      </div>
    )
  }

  return (
    <>
      <div className="overflow-x-auto rounded border border-zinc-200 dark:border-zinc-800">
        <table className="w-full text-left text-sm">
          <thead className="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900">
            <tr>
              <th className="px-3 py-2">When</th>
              <th className="px-3 py-2">Bay</th>
              <th className="px-3 py-2">Customer</th>
              <th className="px-3 py-2">Rego</th>
              <th className="px-3 py-2">Status</th>
              <th className="px-3 py-2">
                <span className="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody>
            {bookings.map((booking) => (
              <BookingRow key={booking.id} booking={booking} />
            ))}
          </tbody>
        </table>
      </div>

      <nav aria-label="Pagination" className="mt-4 flex items-center justify-between text-sm">
        <p className="text-zinc-500">
          Showing {meta.from}–{meta.to} of {meta.total}
        </p>
        <div className="flex items-center gap-4">
          {meta.current_page > 1 ? (
            <Link href={bookingsHref(status, meta.current_page - 1)} className="underline">
              ← Previous
            </Link>
          ) : (
            <span className="text-zinc-400">← Previous</span>
          )}
          <span>
            Page {meta.current_page} of {meta.last_page}
          </span>
          {meta.current_page < meta.last_page ? (
            <Link href={bookingsHref(status, meta.current_page + 1)} className="underline">
              Next →
            </Link>
          ) : (
            <span className="text-zinc-400">Next →</span>
          )}
        </div>
      </nav>
    </>
  )
}

function BookingRow({ booking }: { booking: Booking }) {
  return (
    <tr className="border-t border-zinc-200 align-top dark:border-zinc-800">
      <td className="px-3 py-2 whitespace-nowrap">{formatRange(booking.starts_at, booking.ends_at)}</td>
      <td className="px-3 py-2">{booking.service_bay}</td>
      <td className="px-3 py-2">
        <div>{booking.customer_name}</div>
        <div className="text-xs text-zinc-500">{booking.customer_email}</div>
      </td>
      <td className="px-3 py-2 font-mono">{booking.rego}</td>
      <td className="px-3 py-2">
        <StatusBadge status={booking.status} />
      </td>
      <td className="px-3 py-2">
        <div className="flex flex-wrap justify-end gap-3">
          <Link href={`/bookings/${booking.id}`} className="underline">
            View
          </Link>
          <Link href={`/bookings/${booking.id}/edit`} className="underline">
            Edit
          </Link>
        </div>
      </td>
    </tr>
  )
}
