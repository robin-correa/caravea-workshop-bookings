import 'server-only'

import { HTTP_STATUS } from './http-status'
import { BOOKING_STATUSES, type Booking, type BookingStatus, type Paginated } from './types'

const API_URL = (process.env.API_URL ?? 'http://127.0.0.1:8000/api/v1').replace(/\/$/, '')

export function apiFetch(path: string, init: RequestInit = {}): Promise<Response> {
  return fetch(`${API_URL}${path}`, {
    ...init,
    cache: 'no-store',
    headers: {
      Accept: 'application/json',
      ...(init.body ? { 'Content-Type': 'application/json' } : {}),
      ...init.headers,
    },
  })
}

export function parseStatus(value: unknown): BookingStatus | null {
  return typeof value === 'string' && (BOOKING_STATUSES as readonly string[]).includes(value)
    ? (value as BookingStatus)
    : null
}

export function parsePage(value: unknown): number {
  const page = typeof value === 'string' ? Number(value) : NaN
  return Number.isSafeInteger(page) && page > 0 ? page : 1
}

export async function getBookings(status: BookingStatus | null, page: number): Promise<Paginated<Booking>> {
  const query = new URLSearchParams({ page: String(page) })
  if (status) query.set('status', status)

  const res = await apiFetch(`/bookings?${query}`)
  if (!res.ok) throw new Error(`Failed to load bookings (HTTP ${res.status}).`)
  return res.json()
}

export async function getBooking(id: string): Promise<Booking | null> {
  if (!/^\d+$/.test(id)) return null

  const res = await apiFetch(`/bookings/${id}`)
  if (res.status === HTTP_STATUS.NOT_FOUND) return null
  if (!res.ok) throw new Error(`Failed to load booking (HTTP ${res.status}).`)
  const body: { data: Booking } = await res.json()
  return body.data
}
