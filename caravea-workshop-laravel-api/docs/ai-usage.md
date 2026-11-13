# AI usage and review evidence — Laravel API

## Tool declaration

The project author confirms using only **OpenAI Codex**, with **GPT-6.1-Sol** and **High** reasoning effort, for implementation, review, fixes, and documentation.

These notes were written retrospectively. They distinguish the author's tool declaration, reconstructed instructions, and results observed during this review. They do not contain an original chat export or independently authenticate the model used for earlier generation.

The [implementation brief](implementation-brief.md) summarizes the requested behavior and maps it to source files. It is explicitly reconstructed rather than presented as the original prompt.

## Observed review requests and work

The following are summaries of requests in the review conversation, not verbatim prompts:

| Request | Codex's observed work |
| --- | --- |
| Explain how simultaneous bookings can be tested | Inspected the existing overlap test and ran its concurrency group. |
| Prepare reproducible setup instructions | Inspected configuration, seeders, and command help; verified migrations and seeding against a fresh temporary SQLite database. |
| Explain validation flow for the Loom walkthrough | Traced Form Request validation and database-conflict mapping into the Next.js Server Action and field errors. |
| Document AI usage | Recorded the author's tool declaration, source references, and review results. |

## A concrete tooling mistake and correction

Codex initially ran:

```bash
php artisan test --compact --group=concurrency --no-interaction
```

The test runner rejected `--no-interaction` as an unknown option. Codex corrected the invocation to the installed Pest runner:

```bash
vendor/bin/pest tests/Feature/BookingOverlapConstraintTest.php --group=concurrency
```

The corrected command passed. This was a command-selection mistake, not evidence of a booking-rule defect.

## Verification observed in this session

| Check | Observed result |
| --- | --- |
| Targeted concurrency test | 1 test passed, 3 assertions. |
| Fresh SQLite migrations and seeding | Passed using an isolated temporary file; the demo database was not used. |
| Entire backend test suite | Not run as part of this recorded review. |

The concurrency runner returned:

```json
{"tool":"pest","result":"passed","tests":1,"passed":1,"assertions":3,"duration_ms":2113}
```

The [test](../tests/Feature/BookingOverlapConstraintTest.php) starts eight PHP processes against one shared SQLite file and dispatches requests through Laravel's HTTP kernel. Its assertions require one `201` response, seven `422` responses, and exactly one stored booking. It does not send requests through a separately running HTTP server.

## Decisions retained after review

- Keep the overlap invariant inside the [database write](../database/migrations/2026_11_13_090000_create_bookings_table.php), with [specific exception mapping](../bootstrap/app.php) to field errors.
- Keep UTC persistence and the current explicitly UTC interface; dealer-local input and display remain a future improvement.
- Add authenticated company isolation before evolving the demo into a multi-tenant module. No tenant support is claimed in the current code.

These documents provide a source-linked account of AI-assisted work. Original prompt history would require an actual session export. The short application write-up remains in the [README](../README.md).
