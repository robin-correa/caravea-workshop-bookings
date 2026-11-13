'use client'

import Link from 'next/link'
import { useEffect } from 'react'

// Catches anything thrown while rendering /bookings and its children, e.g. the
// API being down. retry() re-fetches the server components, so it recovers
// once the API is back.
export default function BookingsError({ error, retry }: { error: unknown; retry: () => void }) {
  useEffect(() => {
    console.error(error)
  }, [error])

  return (
    <main className="mx-auto w-full max-w-6xl p-6">
      <div role="alert" className="rounded border border-red-300 bg-red-50 p-6 dark:border-red-800 dark:bg-red-950">
        <h2 className="text-lg font-semibold">We couldn&apos;t load the bookings</h2>
        <p className="mt-1 text-sm">
          The booking service may be unavailable. Please try again in a moment.
        </p>
        <div className="mt-4 flex gap-4 text-sm">
          <button onClick={() => retry()} className="rounded bg-zinc-900 px-3 py-1.5 font-medium text-white dark:bg-zinc-100 dark:text-zinc-900">
            Try again
          </button>
          <Link href="/bookings" className="self-center underline">
            Back to all bookings
          </Link>
        </div>
      </div>
    </main>
  )
}
