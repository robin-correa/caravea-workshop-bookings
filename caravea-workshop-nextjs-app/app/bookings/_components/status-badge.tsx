import type { BookingStatus } from '@/lib/types'

const styles: Record<BookingStatus, string> = {
  pending: 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200',
  confirmed: 'bg-green-100 text-green-900 dark:bg-green-900/40 dark:text-green-200',
  cancelled: 'bg-zinc-200 text-zinc-700 line-through dark:bg-zinc-800 dark:text-zinc-400',
}

export function StatusBadge({ status }: { status: BookingStatus }) {
  return (
    <span className={`inline-block rounded px-2 py-0.5 text-xs font-medium capitalize ${styles[status]}`}>
      {status}
    </span>
  )
}
