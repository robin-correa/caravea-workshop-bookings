# Next.js frontend implementation brief

## Provenance

This is a **retrospective, reconstructed prompt brief**, assembled from the application task, the current implementation, and the review conversation. It is a reusable description of the intended work, not a verbatim export of the original generation prompt. See [AI usage and review evidence](ai-usage.md) for the tool declaration and observed checks.

## Reconstructed implementation instructions

```text
Build a focused Next.js App Router frontend in TypeScript for the Laravel
workshop bookings API. Follow the installed Next.js version's documentation
and existing project conventions. Keep styling simple and avoid adding
dependencies without a concrete need.

Use Server Components for booking reads. Keep the Laravel API address and
fetch helper on the Next.js server; the browser should not call Laravel
directly. Make /bookings support URL-driven status filtering and pagination.
Preserve the filter when changing pages; reset to page one when it changes.

Provide /bookings/[id], /bookings/new, and /bookings/[id]/edit. Distinguish
missing bookings from upstream failures. Use notFound() and a contextual
not-found screen for missing resources, plus route loading and error UI.

Use Server Actions for create, update, and delete. Share the create/edit
form, binding an existing ID for edits and null for creation. Use
useActionState for pending feedback and returned form state. Preserve
submitted values when Laravel rejects a request. Render its field-keyed
422 errors, including starts_at overlap errors, beside the correct inputs
with labels and accessibility attributes. Keep business validation in Laravel.

Read bookings with cache: 'no-store'. After successful mutations, revalidate
the bookings route subtree and redirect through Next.js navigation.
Document API-request cost and the lack of automatic updates in other tabs.

Keep the demo's input and display explicitly UTC. Preserve seconds when
populating edit fields. Do not introduce an untested dealer-timezone
conversion. Explain local workshop timezones as a future improvement.

Use stable keys for option lists, show pending and failure feedback, and
keep interactive client components small. Document setup, trade-offs,
actual AI assistance, and remaining verification work.
```

## Implementation map

| Requirement | Current source |
| --- | --- |
| URL-driven list and pagination | [List page](../app/bookings/page.tsx), [table](../app/bookings/_components/bookings-table.tsx), [URL helper](../app/bookings/_components/href.ts) |
| Server-only API reads and input parsing | [API helper](../lib/bookings.ts) |
| Mutations, field-error propagation, and revalidation | [Server Actions](../app/bookings/actions.ts) |
| Shared interactive form | [BookingForm](../app/bookings/_components/booking-form.tsx) |
| Form binding and initial values | [Create page](../app/bookings/new/page.tsx), [edit page](../app/bookings/[id]/edit/page.tsx) |
| Detail and missing-booking UI | [Detail page](../app/bookings/[id]/page.tsx), [not-found screen](../app/bookings/[id]/not-found.tsx) |
| Loading and upstream failures | [Loading](../app/bookings/loading.tsx), [error boundary](../app/bookings/error.tsx) |
| UTC formatting and edit values | [Date helpers](../lib/datetime.ts) |
| Delete interaction | [Delete control](../app/bookings/_components/delete-booking-button.tsx) |

The current implementation uses the automatic Suspense boundary supplied by `loading.tsx`. A separate table-level Suspense boundary, dealer-local timezone conversion, and tenant-aware authentication were discussed but have not been added.
