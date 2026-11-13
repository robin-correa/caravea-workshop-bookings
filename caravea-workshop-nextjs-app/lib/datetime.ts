import 'server-only'

// Use UTC for both display and form input so a booking round-trips without DST conversion.
const dateFormat = new Intl.DateTimeFormat('en-AU', {
  timeZone: 'UTC',
  dateStyle: 'medium',
  timeStyle: 'short',
})

export function toDatetimeLocal(iso: string): string {
  return new Date(iso).toISOString().slice(0, 19)
}

export function formatDateTime(iso: string): string {
  return `${dateFormat.format(new Date(iso))} UTC`
}

export function formatRange(startsAt: string, endsAt: string): string {
  return `${formatDateTime(startsAt)} – ${formatDateTime(endsAt)}`
}
