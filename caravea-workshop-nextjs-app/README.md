# Workshop bookings frontend

Next.js App Router and TypeScript frontend for the [Laravel bookings API](https://github.com/robin-correa/workshop-laravel-api).

## Run locally

Use Node.js 24 and pnpm 12. Start the Laravel API on port 8000, then run:

```bash
pnpm install
pnpm dev
```

Open [the bookings page](http://localhost:3000/bookings). The API URL defaults to `http://127.0.0.1:8000/api/v1`; copy `.env.example` to `.env.local` to override it. The demo uses four service bays and UTC for date input and display.

## Checks

```bash
pnpm lint
pnpm exec tsc --noEmit
pnpm build
```

## Senior write-up

**Caching and revalidation.** Booking reads in Next.js use `cache: 'no-store'` because availability changes frequently and correctness matters more than caching this small dataset. Successful saves and deletes call `revalidatePath('/bookings', 'layout')` before redirecting, refreshing affected routes through client navigation without a full page reload. The trade-off is more API requests; existing open pages do not update automatically.

**Simultaneous bookings.** My most confident decision is enforcing overlap in the database. SQLite triggers enforce the rule during inserts and updates. SQLite serializes writes, making the overlap check and its guarded write atomic against competing writers. Cancelled bookings are excluded, and adjacent bookings are allowed. Conflicts become Laravel `422` validation errors under `booking_slot`. The form shows one shared message below the time fields, linked to the service bay, start, and end inputs for accessibility. Individual field errors remain beside their inputs, and submitted values are preserved for correction. Next.js uses shared `HTTP_STATUS` constants for validation and not-found responses. A test sends eight concurrent requests for one slot and verifies one succeeds while seven are rejected. The triggers are SQLite-specific and would need revisiting when moving to another database.

**Timezone trade-off.** My least certain choice is UTC for date input and display. It keeps date round-trips predictable for the demo, but real dealers need local timezones and explicit handling of ambiguous or nonexistent times during daylight-saving transitions.

**Scaling to 200 dealers.** My first change would be tenant isolation: add `company_id`, derive the company from the authenticated user, and scope queries, route binding, authorization, and overlap checks to that company. Composite indexes would include company and service bay. I would add cross-tenant access tests, then assess database write capacity and introduce dealer-specific bays and timezones. Any future cache keys would include the tenant.

## AI tools

I used only **OpenAI Codex**, with the **GPT-6.1-Sol** model and **High** reasoning effort, for implementation, review, fixes, and documentation. One mistake in the generated booking form was an unkeyed service-bay placeholder option: manual testing exposed React's missing-key warning. The placeholder was given a stable key; lint and TypeScript checks passed after the change. AI output still required manual review and runtime verification.

See [AI usage and review evidence](docs/ai-usage.md) and the [reconstructed implementation brief](docs/implementation-brief.md) for supporting details. These are retrospective summaries, not original chat transcripts.
