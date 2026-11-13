# Laravel API implementation brief

## Provenance

This is a **retrospective, reconstructed prompt brief**, assembled from the application task, the current implementation, and the review conversation. It is a reusable description of the intended work, not a verbatim export of the original generation prompt. See [AI usage and review evidence](ai-usage.md) for the tool declaration and observed checks.

## Reconstructed implementation instructions

```text
Build a small Laravel API for a caravan dealer's workshop bookings.
Keep the scope appropriate for a three-hour application task shared with
a Next.js frontend. Inspect the installed framework and project conventions.

Use SQLite, migrations, Eloquent, Form Requests, API Resources, and Pest.
Expose versioned CRUD endpoints under /api/v1/bookings. Support the fields:
customer_name, customer_email, rego, service_bay, starts_at, ends_at,
status (pending, confirmed, cancelled), and optional notes.

List bookings in a stable chronological order, with pagination and an
optional status filter. Return field-keyed JSON validation errors.
Support partial updates, and require the effective end time to be after
the effective start time. Normalize stored timestamps to UTC.

Prevent overlapping active bookings in the same service bay, even when
requests compete. An application-level check followed by an insert is
insufficient. Enforce the rule inside the database write for SQLite.
Allow adjacent bookings, ignore cancelled bookings, and exclude the
booking being edited from its own overlap check. Convert overlap
violations into a 422 response keyed to starts_at, without disguising
unrelated database errors as validation failures.

Test CRUD, filtering, pagination, field validation, overlap boundaries,
updates, cancellation, and competing requests. Use a shared temporary
SQLite file for tests involving separate PHP processes.

Provide first-run setup and seed data. Keep authentication, multi-tenant
infrastructure, queues, and other production extensions outside this demo.
Document the SQLite dependency and the next steps for tenant isolation.
```

## Implementation map

| Requirement | Current source |
| --- | --- |
| Versioned routes and CRUD | [Routes](../routes/api.php), [controller](../app/Http/Controllers/BookingController.php) |
| Validation and UTC normalization | [BookingRequest](../app/Http/Requests/BookingRequest.php) |
| JSON representation | [BookingResource](../app/Http/Resources/BookingResource.php) |
| Atomic overlap enforcement | [Table and triggers](../database/migrations/2026_11_13_090000_create_bookings_table.php) |
| Specific conflict detection and error mapping | [Booking model](../app/Models/Booking.php), [exception mapping](../bootstrap/app.php) |
| Endpoint behavior | [Controller tests](../tests/Feature/Http/Controllers/BookingControllerTest.php) |
| Database constraints and competing requests | [Overlap tests](../tests/Feature/BookingOverlapConstraintTest.php) |
| Sample bookings | [BookingSeeder](../database/seeders/BookingSeeder.php) |

Tenant isolation and dealer-specific timezones are documented future changes, not implemented capabilities. The current overlap triggers deliberately support SQLite only.
