'use server'

import { revalidatePath } from 'next/cache'
import { redirect } from 'next/navigation'
import { apiFetch } from '@/lib/bookings'
import { HTTP_STATUS } from '@/lib/http-status'
import {
  BOOKING_FIELDS,
  type Booking,
  type BookingFormValues,
  type FieldErrors,
  type FormState,
} from '@/lib/types'

const UNREACHABLE = 'Could not reach the booking service. Please try again.'

async function send(path: string, method: string, body?: unknown): Promise<Response | null> {
  try {
    return await apiFetch(path, {
      method,
      body: body === undefined ? undefined : JSON.stringify(body),
    })
  } catch {
    return null
  }
}

export async function saveBooking(
  id: number | null,
  _prev: FormState,
  formData: FormData,
): Promise<FormState> {
  const values = Object.fromEntries(
    BOOKING_FIELDS.map((field) => [field, String(formData.get(field) ?? '').trim()]),
  ) as BookingFormValues
  const res = await send(id === null ? '/bookings' : `/bookings/${id}`, id === null ? 'POST' : 'PATCH', {
    ...values,
    starts_at: values.starts_at ? `${values.starts_at}Z` : '',
    ends_at: values.ends_at ? `${values.ends_at}Z` : '',
    notes: values.notes || null,
  })

  if (!res) return { values, message: UNREACHABLE }
  if (res.status === HTTP_STATUS.UNPROCESSABLE_CONTENT) {
    const body: { errors: FieldErrors } = await res.json()
    return { values, errors: body.errors, message: 'Please fix the highlighted fields.' }
  }
  if (res.status === HTTP_STATUS.NOT_FOUND) return { values, message: 'This booking no longer exists.' }
  if (!res.ok) return { values, message: `The booking could not be saved (HTTP ${res.status}).` }

  const { data }: { data: Booking } = await res.json()
  revalidatePath('/bookings', 'layout')
  redirect(`/bookings/${data.id}`)
}

export async function deleteBooking(id: number): Promise<FormState> {
  const res = await send(`/bookings/${id}`, 'DELETE')
  if (!res) return { message: UNREACHABLE }
  if (!res.ok && res.status !== HTTP_STATUS.NOT_FOUND) {
    return { message: `Could not delete the booking (HTTP ${res.status}).` }
  }

  revalidatePath('/bookings', 'layout')
  redirect('/bookings')
}
