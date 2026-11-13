import Link from 'next/link'

export default function BookingNotFound() {
  return (
    <main className="mx-auto w-full max-w-6xl p-6">
      <h1 className="text-2xl font-semibold">Booking not found</h1>
      <p className="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
        This booking doesn&apos;t exist. It may have been deleted, or the link may be wrong.
      </p>
      <Link href="/bookings" className="mt-4 inline-block text-sm underline">
        ← Back to all bookings
      </Link>
    </main>
  )
}
