<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Senior write-up

**Caching and revalidation.** Booking reads in Next.js use `cache: 'no-store'` because availability changes frequently and correctness matters more than caching this small dataset. Successful saves and deletes call `revalidatePath('/bookings', 'layout')` before redirecting, refreshing affected routes through client navigation without a full page reload. The trade-off is more API requests; existing open pages do not update automatically.

**Simultaneous bookings.** My most confident decision is enforcing overlap in the database. SQLite triggers enforce the rule during inserts and updates. SQLite serializes writes, making the overlap check and its guarded write atomic against competing writers. Cancelled bookings are excluded, and adjacent bookings are allowed. Conflicts become Laravel `422` validation errors under `booking_slot`. The form shows one shared message below the time fields, linked to the service bay, start, and end inputs for accessibility. Individual field errors remain beside their inputs, and submitted values are preserved for correction. Next.js uses shared `HTTP_STATUS` constants for validation and not-found responses. A test sends eight concurrent requests for one slot and verifies one succeeds while seven are rejected. The triggers are SQLite-specific and would need revisiting when moving to another database.

**Timezone trade-off.** My least certain choice is UTC for date input and display. It keeps date round-trips predictable for the demo, but real dealers need local timezones and explicit handling of ambiguous or nonexistent times during daylight-saving transitions.

**Scaling to 200 dealers.** My first change would be tenant isolation: add `company_id`, derive the company from the authenticated user, and scope queries, route binding, authorization, and overlap checks to that company. Composite indexes would include company and service bay. I would add cross-tenant access tests, then assess database write capacity and introduce dealer-specific bays and timezones. Any future cache keys would include the tenant.

## AI assistance

I used only **OpenAI Codex**, with the **GPT-6.1-Sol** model and **High** reasoning effort, for implementation, review, fixes, and documentation. One mistake in the generated booking form was an unkeyed service-bay placeholder option: manual testing exposed React's missing-key warning. The placeholder was given a stable key; lint and TypeScript checks passed after the change. AI output still required manual review and runtime verification.

See [AI usage and review evidence](docs/ai-usage.md) and the [reconstructed implementation brief](docs/implementation-brief.md) for supporting details. These are retrospective summaries, not original chat transcripts.
