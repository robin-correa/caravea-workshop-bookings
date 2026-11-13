import Link from 'next/link'
import { parsePage, parseStatus } from '@/lib/bookings'
import { BOOKING_STATUSES } from '@/lib/types'
import { BookingsTable } from './_components/bookings-table'
import { bookingsHref } from './_components/href'

export const metadata = { title: 'Bookings' }

export default async function BookingsPage({ searchParams }: PageProps<'/bookings'>) {
  const params = await searchParams
  const status = parseStatus(params.status)
  const page = parsePage(params.page)

  return (
    <main className="mx-auto w-full max-w-6xl p-6">
      <div className="mb-6 flex items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">Workshop bookings</h1>
        <Link
          href="/bookings/new"
          className="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-zinc-100 dark:text-zinc-900"
        >
          New booking
        </Link>
      </div>
      <nav aria-label="Filter by status" className="mb-4 flex flex-wrap gap-2">
        {[null, ...BOOKING_STATUSES].map((s) => (
          <Link
            key={s ?? 'all'}
            href={bookingsHref(s)}
            aria-current={s === status ? 'page' : undefined}
            className={`rounded-full border px-3 py-1 text-sm capitalize ${
              s === status
                ? 'border-zinc-900 bg-zinc-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900'
                : 'border-zinc-300 dark:border-zinc-700'
            }`}
          >
            {s ?? 'All'}
          </Link>
        ))}
      </nav>
      <BookingsTable status={status} page={page} />
    </main>
  )
}
