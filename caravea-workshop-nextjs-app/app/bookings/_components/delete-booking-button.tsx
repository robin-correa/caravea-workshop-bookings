'use client'

import { useActionState } from 'react'
import { deleteBooking } from '../actions'

export function DeleteBookingButton({ id }: { id: number }) {
  const [state, formAction, pending] = useActionState(deleteBooking.bind(null, id), {})

  return (
    <form
      action={formAction}
      onSubmit={(e) => {
        if (!window.confirm(`Delete booking #${id}? This can't be undone.`)) e.preventDefault()
      }}
      className="inline-flex flex-col items-start"
    >
      <button
        type="submit"
        disabled={pending}
        className="text-sm text-red-700 underline disabled:no-underline disabled:opacity-60 dark:text-red-400"
      >
        {pending ? 'Deleting…' : 'Delete'}
      </button>
      {state.message && (
        <span role="alert" className="max-w-56 text-xs text-red-600 dark:text-red-400">
          {state.message}
        </span>
      )}
    </form>
  )
}
