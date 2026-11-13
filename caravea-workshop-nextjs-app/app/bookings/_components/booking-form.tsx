'use client'

import Link from 'next/link'
import { useActionState } from 'react'
import {
  BOOKING_STATUSES,
  type BookingField,
  type BookingFormValues,
  type FieldErrors,
  type FormState,
} from '@/lib/types'

type Props = {
  action: (state: FormState, formData: FormData) => Promise<FormState>
  defaults: BookingFormValues
  submitLabel: string
  cancelHref: string
}

const input =
  'w-full rounded border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 aria-[invalid=true]:border-red-500'

export function BookingForm({
  action,
  defaults,
  submitLabel,
  cancelHref,
}: Props) {
  const [state, formAction, pending] = useActionState(action, { values: defaults })
  // After a failed submit, re-fill the form with what was sent. React resets
  // uncontrolled inputs to their defaultValue once the action settles.
  const values = state.values ?? defaults
  const errors = state.errors ?? {}

  const field = (name: BookingField) => {
    const hasSlotError = Boolean(errors.booking_slot?.length)
      && ['service_bay', 'starts_at', 'ends_at'].includes(name)
    const descriptions = [
      errors[name] ? `${name}-error` : null,
      hasSlotError ? 'booking-slot-error' : null,
    ].filter(Boolean).join(' ')

    return {
      id: name,
      name,
      defaultValue: values[name],
      'aria-invalid': errors[name] || hasSlotError ? true : undefined,
      'aria-describedby': descriptions || undefined,
    }
  }

  return (
    <form action={formAction} className="grid max-w-2xl gap-5" noValidate>
      {state.message && (
        <div role="alert" className="rounded border border-red-300 bg-red-50 p-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
          <p>{state.message}</p>
        </div>
      )}

      <div className="grid gap-5 sm:grid-cols-2">
        <Field label="Customer name" name="customer_name" errors={errors}>
          <input {...field('customer_name')} className={input} autoComplete="name" />
        </Field>
        <Field label="Customer email" name="customer_email" errors={errors}>
          <input {...field('customer_email')} type="email" className={input} autoComplete="email" />
        </Field>
        <Field label="Rego" name="rego" errors={errors}>
          <input {...field('rego')} className={input} />
        </Field>
        <Field label="Service bay" name="service_bay" errors={errors}>
          <select {...field('service_bay')} key={`bay-${values.service_bay}`} className={input}>
            <option key="placeholder" value="">Choose a bay…</option>
            {[1, 2, 3, 4].map((bay) => (
              <option key={bay} value={bay}>
                Bay {bay}
              </option>
            ))}
          </select>
        </Field>
        <Field label="Starts (UTC)" name="starts_at" errors={errors}>
          <input {...field('starts_at')} type="datetime-local" step="1" className={input} />
        </Field>
        <Field label="Ends (UTC)" name="ends_at" errors={errors}>
          <input {...field('ends_at')} type="datetime-local" step="1" className={input} />
        </Field>
        {errors.booking_slot && (
          <p id="booking-slot-error" role="alert" className="text-sm text-red-600 sm:col-span-2 dark:text-red-400">
            {errors.booking_slot.join(' ')}
          </p>
        )}
        <Field label="Status" name="status" errors={errors}>
          {/* key forces a remount so the select picks up a new defaultValue */}
          <select {...field('status')} key={`status-${values.status}`} className={input}>
            {BOOKING_STATUSES.map((s) => (
              <option key={s} value={s}>
                {s[0].toUpperCase() + s.slice(1)}
              </option>
            ))}
          </select>
        </Field>
      </div>

      <Field label="Notes (optional)" name="notes" errors={errors}>
        <textarea {...field('notes')} rows={4} className={input} />
      </Field>

      <div className="flex items-center gap-3">
        <button
          type="submit"
          disabled={pending}
          className="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-60 dark:bg-zinc-100 dark:text-zinc-900"
        >
          {pending ? 'Saving…' : submitLabel}
        </button>
        <Link href={cancelHref} className="text-sm underline">
          Cancel
        </Link>
      </div>
    </form>
  )
}

function Field({
  label,
  name,
  errors,
  children,
}: {
  label: string
  name: BookingField
  errors: FieldErrors
  children: React.ReactNode
}) {
  const messages = errors[name]
  return (
    <div className="grid gap-1">
      <label htmlFor={name} className="text-sm font-medium">
        {label}
      </label>
      {children}
      {messages && (
        <p id={`${name}-error`} className="text-sm text-red-600 dark:text-red-400">
          {messages.join(' ')}
        </p>
      )}
    </div>
  )
}
