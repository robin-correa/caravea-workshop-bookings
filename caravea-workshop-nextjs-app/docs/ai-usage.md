# AI usage and review evidence — Next.js frontend

## Tool declaration

The project author confirms using only **OpenAI Codex**, with **GPT-6.1-Sol** and **High** reasoning effort, for implementation, review, fixes, and documentation.

These notes were written retrospectively. They distinguish the author's tool declaration, reconstructed instructions, and results observed during this review. They do not contain an original chat export or independently authenticate the model used for earlier generation.

The [implementation brief](implementation-brief.md) summarizes the requested behavior and maps it to source files. It is explicitly reconstructed rather than presented as the original prompt.

## Observed review requests and work

The following are summaries of requests in the review conversation, not verbatim prompts:

| Request | Codex's observed work |
| --- | --- |
| Review frontend quality against the application task | Inspected the implementation, Laravel contract, installed Next.js docs, and README trade-offs; ran lint and TypeScript checks. |
| Explain server/client boundaries, Server Actions, and BFF structure | Traced the existing list, API helper, mutation actions, and client form. |
| Explain loading, missing bookings, and bound actions | Checked the installed framework docs and existing route files. |
| Investigate the edit form's missing-key warning | Added a stable key to the service-bay placeholder and reran lint and TypeScript checks. |
| Prepare submission and Loom documentation | Wrote the walkthrough and setup notes, then corrected the AI declaration to match the author's Codex-only usage. |

## A concrete generated-code mistake and correction

The author reported React's missing-key warning when opening a booking's edit page. The warning pointed to the service-bay placeholder in [BookingForm](../app/bookings/_components/booking-form.tsx). The mapped bay options already had keys; Codex added a stable key to the placeholder:

```tsx
// Before
<option value="">Choose a bay…</option>

// After
<option key="placeholder" value="">Choose a bay…</option>
```

Lint and TypeScript checks passed after the change. A standalone server-rendering check did not reproduce the browser warning, and a browser retest confirming it cleared was not observed in this session. The author was asked to reload the edit page and confirm.

## Verification observed in this session

| Check | Observed result |
| --- | --- |
| `pnpm lint` | Passed, including after the key change. |
| `pnpm exec tsc --noEmit` | Passed, including after the key change. |
| `pnpm build` | Blocked by the execution environment denying a local Turbopack worker-port binding; production build success was not verified. |
| Browser overlap validation | The author reported manually testing the overlap rule successfully. |
| Full browser create/edit/delete and recovery walkthrough | Not verified by Codex in this session. |

The checks above describe observed results, not a claim that every application flow has been tested. The [README](../README.md) lists the commands to rerun.

## Decisions retained after review

- Keep server-only [API access](../lib/bookings.ts) and small interactive client components.
- Return Laravel's validation errors and submitted values through [Server Actions](../app/bookings/actions.ts) into `useActionState`.
- Keep uncached reads and mutation revalidation, with their request-cost and cross-tab freshness trade-offs documented.
- Keep explicitly UTC input and display for the demo. Melbourne/dealer-local conversion was discussed as a future change, not implemented.
- Keep the existing route-level loading boundary. A separate table-level Suspense boundary was discussed, not implemented.

These documents provide a source-linked account of AI-assisted work. Original prompt history would require an actual session export. The short application write-up remains in the [README](../README.md).
