# Workshop bookings

A workshop bookings module for a caravan dealer, built with a Laravel API, SQLite, and a Next.js App Router frontend in TypeScript. Staff can create, view, edit, delete, filter, and paginate bookings. Database triggers prevent overlapping active bookings in the same service bay, including competing writes.

## Structure

```text
caravea-workshop-bookings/
├── README.md
├── caravea-workshop-laravel-api/   # API, validation, database migrations, and Pest tests
└── caravea-workshop-nextjs-app/    # Server Components, Server Actions, and booking forms
```

The Next.js server calls Laravel; the browser does not call Laravel directly. The demo has four service bays and uses explicitly labelled UTC for input, storage, and display. Authentication and tenant isolation are outside the demo's scope.

Application repositories: [Laravel API](https://github.com/robin-correa/caravea-workshop-laravel-api) and [Next.js frontend](https://github.com/robin-correa/caravea-workshop-nextjs-app).

## Requirements

- PHP 8.5 with PDO SQLite and the extensions required by Composer.
- Composer 2.
- Node.js 24 and pnpm 12.18.0, as pinned in the frontend's `package.json`.

No external database, Redis, mail server, or queue worker is required for the booking flow.

## Run locally

Clone this repository to get both applications:

```bash
git clone https://github.com/robin-correa/caravea-workshop-bookings.git
cd caravea-workshop-bookings
```

Both application folders are tracked directly in this monorepo; no submodule setup is required.

Start from the directory containing both application folders. Use two terminals. The environment-file copy commands below are for first-time setup; retain your existing environment files if already configured.

### 1. Laravel API — terminal one

```bash
cd caravea-workshop-laravel-api
composer install
cp .env.example .env
php artisan key:generate --no-interaction
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed --no-interaction
php artisan serve --host=127.0.0.1 --port=8000 --no-interaction
```

The supplied environment example selects SQLite. With `DB_DATABASE` unset, Laravel uses `database/database.sqlite`. Keep this application on SQLite: the overlap triggers are implemented specifically for it.

The seed creates 26 sample bookings: 8 pending, 16 confirmed, and 2 cancelled. The 16 confirmed bookings make the second filtered page available. Run the seed on initial setup; it adds records rather than resetting the database.

These commands run the API directly. Installing or building the Laravel scaffold's JavaScript assets is unnecessary for the Next.js booking frontend.

### 2. Next.js frontend — terminal two

From the directory containing both application folders:

```bash
cd caravea-workshop-nextjs-app
cp .env.example .env.local
pnpm install --frozen-lockfile
pnpm dev
```

The frontend environment file contains the server-side API address:

```dotenv
API_URL=http://127.0.0.1:8000/api/v1
```

Open [the bookings list](http://localhost:3000/bookings). To check filtered pagination, open [/bookings?status=confirmed&page=2](http://localhost:3000/bookings?status=confirmed&page=2).

If you change the API host or port, update `API_URL` and restart Next.js.

## Checks

Run the backend suite from `caravea-workshop-laravel-api`:

```bash
vendor/bin/pest
```

Run just the simultaneous-booking test:

```bash
vendor/bin/pest tests/Feature/BookingOverlapConstraintTest.php --group=concurrency
```

It dispatches eight competing requests through Laravel's HTTP kernel in separate PHP processes against one temporary SQLite file. It asserts one `201` response, seven `422` responses, and exactly one stored booking. The temporary database is separate from the demo database.

Run frontend checks from `caravea-workshop-nextjs-app`:

```bash
pnpm lint
pnpm exec tsc --noEmit
pnpm build
```

After a successful build, `pnpm start` runs the production frontend. Keep the Laravel API running in the other terminal.

### Manual walkthrough

1. Filter by status and navigate between pages; the URL preserves both values. Selecting another status returns to page one.
2. Create an active booking, then attempt an overlapping booking in the same bay. The start field shows Laravel's validation error and the entered values are preserved.
3. Correct the slot and save. Follow the link back to the list to see the new booking without manually reloading the page.
4. Edit a booking without changing its times and confirm that they remain unchanged. Inputs and displayed times are UTC, including when your browser is in another timezone.
5. Request a nonexistent booking ID and its edit route to see the booking-specific not-found screen.
6. Stop the API and navigate to the list to check the error screen. Restart the API and select **Try again** to recover.

## Senior write-up

**Caching and revalidation.** Reads use `cache: 'no-store'` because availability changes frequently. Successful mutations call `revalidatePath('/bookings', 'layout')` before redirecting, refreshing subsequent navigation without a full page reload. This increases API requests; other users' open tabs do not update automatically.

**Simultaneous bookings.** SQLite insert and update triggers check overlap inside the write statement. Serialized writes make the check and write atomic against competitors. Cancelled bookings are excluded; adjacent bookings are allowed. Laravel maps conflicts to `422` errors on `starts_at`. The concurrency test verifies one success, seven rejections, and one stored booking from eight competing requests.

**Scaling to 200 dealers.** First derive `company_id` from authentication and scope queries, route binding, authorization, overlap checks, and indexes to that company. Add cross-tenant access tests. Then introduce dealer-specific bays and timezones and assess database write capacity. Future cache keys must include the tenant; changing databases requires an equivalent overlap guarantee.

## AI assistance

I used only **OpenAI Codex**, with the **GPT-6.1-Sol** model and **High** reasoning effort, for implementation, review, fixes, and documentation. One mistake in the generated booking form was an unkeyed service-bay placeholder option: manual testing exposed React's missing-key warning. The placeholder was given a stable key; lint and TypeScript checks passed after the change. AI output still required manual review and runtime verification.

Supporting documentation is available for each application:

- Laravel: [AI usage and review evidence](caravea-workshop-laravel-api/docs/ai-usage.md), [reconstructed implementation brief](caravea-workshop-laravel-api/docs/implementation-brief.md).
- Next.js: [AI usage and review evidence](caravea-workshop-nextjs-app/docs/ai-usage.md), [reconstructed implementation brief](caravea-workshop-nextjs-app/docs/implementation-brief.md).

These are retrospective summaries, not original chat transcripts.
