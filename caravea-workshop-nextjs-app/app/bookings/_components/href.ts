import type { BookingStatus } from '@/lib/types'

/** Build a list URL. Changing the filter drops `page` so it resets to 1. */
export function bookingsHref(status: BookingStatus | null, page = 1) {
  const query = new URLSearchParams()
  if (status) query.set('status', status)
  if (page > 1) query.set('page', String(page))
  const qs = query.toString()
  return qs ? `/bookings?${qs}` : '/bookings'
}
