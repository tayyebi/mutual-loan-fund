# mutual-loan-fund — reference for AI coding agents

> **This file is the canonical reference for AI coding agents working in this repository.**
> `CLAUDE.md`, `AGENTS.md`, `GEMINI.md`, and `.cursorrules` at the repo root are symlinks to this
> file — edit only this file, never them directly. **Any change that adds, renames, or removes a
> domain concept, convention, or architectural boundary described below MUST update this file in
> the same change.** Treat an unreviewed diff to `app/Domain/**`, `database/migrations/**`, or
> `routes/web.php` that doesn't touch this file as incomplete.

## What this is

A mutual loan fund / ROSCA-style platform: independent groups ("funds") whose members
contribute, borrow, and repay under a versioned set of financial rules. Laravel 12, PHP 8.2,
PostgreSQL. Server-rendered Blade only — no JS framework, no API surface, no AJAX. Every page is
a GET that renders Blade or a POST/PUT/DELETE that validates, authorizes, runs one database
transaction, and redirects.

## Tenant model

`Group` (`app/Models/Group.php`) is the tenant boundary — one independent fund. Canonical URL
prefix is `/g/{group}` (routed by slug); there is deliberately no `/groups/`. Everything under
that prefix sits behind the `group` middleware (`ResolveGroupContext`, verifies membership and
that every route-bound model actually belongs to that group — tenant isolation) and, for
admin-only actions, the additional `group.admin` middleware.

Membership is `GroupMembership`, with a **per-fund** admin role (`GroupMembership::ROLE_ADMIN`) —
distinct and unrelated to the **platform-wide** `User::system_role` /
`User::isSystemAdmin()` introduced for `/admin` operations. The platform-wide system administrator
manages user/fund lifecycle (suspend, reinstate, promote) and explicitly never reads a fund's own
financial data (see `app/Http/Controllers/Admin/FundController.php`'s docblock). Don't conflate
the two "administrator" concepts — most of this codebase's own comments say "administrator" to
mean the per-fund role.

Anyone can create their own fund (`POST /g`) and becomes its admin automatically — group creation
is not restricted to system administrators.

## Domain layout

`app/Domain/<Area>/<Area>Service.php` is the sole entry point business logic goes through
(`GroupService`, `PolicyService`, `MembershipService`, `TransactionService`, etc.). Controllers
stay thin: one service call per action, inside a DB transaction, done in the service, not the
controller. Models hold relations/casts/small read helpers — not business rules.

Where to look for X:
- Group/tenant lifecycle → `app/Domain/Groups/`
- Financial rules → `app/Domain/Policies/`
- Money/decimals → `app/Domain/Money/Decimal` (money is `NUMERIC(30,8)`, rates `NUMERIC(30,12)`
  in Postgres; PHP mirrors those scales with bcmath decimal strings — never float)
- Double-entry accounting → `app/Domain/Accounting/`
- Audit trail → `app/Domain/Audit/`
- Financial framework presets (advisory rule sets) → `app/Domain/Frameworks/`
- Routes → the single `routes/web.php`, grouped by the `/g/{group}` prefix

## Policy system

A group's financial rules are a **versioned, immutable-once-published JSON document**
(`GroupPolicy` model / `App\Domain\Policies\PolicyConfig`) — never mutable columns on `groups`.
See `config/fund.php`'s own docblock: "Anything that a group administrator is allowed to change
lives in a versioned group policy, never here." A draft is edited freely; publishing closes the
previous active version and makes the new one authoritative from that date forward. Every
financial object (loan, transaction) stores the policy version it was created under and never
follows a newer one.

`PolicyConfig::CATEGORIES` lists category classes (`LoanPolicy`, `ContributionPolicy`,
`RepaymentPolicy`, `MembershipPolicy`, `AccountingPolicy`, `TreasuryPolicy`). Each category
declares only a `fields(): array` of `PolicyField` objects (type, default, suffix, options,
monetary flag) — hydration, casting, the edit form, validation, and version-diffing are all
generic and driven from that field list. **Adding a policy value means adding one `PolicyField`
entry to one category's `fields()`, nothing else.**

`PolicyValidator` enforces business-rule correctness (bounds, contradictions) before a draft may
publish — keyed errors `"category.field" => message`. `App\Domain\Frameworks\
FrameworkComplianceChecker` is a separate, **non-blocking** advisory layer with the same
`"category.field"`-keyed return shape: it compares a policy against an optionally-chosen
`FinancialFramework` (a named, seeded preset like "Islamic Finance" or "Microfinance" — see
`database/seeders/FinancialFrameworkSeeder.php`) and produces warnings, never errors. A framework
never blocks a save or a publish.

## Audit trail

`app/Domain/Audit/AuditAction.php` is a flat catalogue of `public const` action-name strings
(`'group.created'`, `'policy.published'`, …). `AuditRecorder::record()` writes an `AuditLog` row
inside the *same* database transaction as the action it describes — if the action rolls back, so
does its trace. Policy changes get a second, fuller trail via `AuditRecorder::recordPolicyEvent()`
(carries the whole before/after config document, draft edits included).

## Conventions

- **No native PHP enums anywhere in this codebase** (verified: zero `enum` declarations under
  `app/`). The house style is a plain class with `public const` string values instead — e.g.
  `Group::STATUS_ACTIVE`, `GroupPolicy::STATUS_DRAFT`, `User::SYSTEM_ROLE_SYSTEM_ADMIN`,
  `AuditAction`. Follow this; don't introduce a native `enum`.
- **Migrations**: plain `Schema::create`/`Schema::table`, plus
  `DB::statement("ALTER TABLE ... ADD CONSTRAINT ... CHECK (col IN (...))")` for any enum-like
  string column — no native Postgres enum types. Small closed value sets (status-like columns)
  get a CHECK constraint; open-ended sets (e.g. IANA timezone identifiers) are validated at the
  application layer instead.
- **Seeders**: domain/demo data goes through domain services (`DemoFundSeeder` calls
  `GroupService::create()`, `PolicyService::createDraft()`, etc.) so seeded data obeys the same
  invariants UI-created data does. The one standing exception is pure reference/lookup data with
  no UI CRUD at all (e.g. `User::firstOrCreate` for demo accounts,
  `FinancialFramework::updateOrCreate` for the seeded framework catalogue in
  `FinancialFrameworkSeeder`, run unconditionally in every environment from `DatabaseSeeder`,
  unlike `DemoFundSeeder` which is skipped in production).
- **Naming collision**: `App\Models\GroupPolicy` (Eloquent model) and `App\Policies\GroupPolicy`
  (Laravel authorization policy) share a class basename across namespaces. Watch for this when
  naming new classes — prefer namespacing that avoids ambiguity (`App\Domain\<Area>\...`).
- **Testing**: `tests/TestCase.php` has shared helpers (e.g. spinning up a fund via
  `GroupService`); feature tests live under `tests/Feature/`.

## Locale / preferences

Language, default currency, timezone, and weekend days are per-user preferences
(`users.preferred_locale`, `preferred_currency`, `timezone`, `weekend_days`), set from
`/p/preferences` (`ProfileController::editPreferences`/`updatePreferences`), falling back to the
browser's `Accept-Language` header when no `preferred_locale` is set (`App\Http\Middleware\
SetLocale`, registered globally on the `web` middleware group in `bootstrap/app.php` — every page
gets a resolved locale, including guest/login screens).

Deliberate scope boundaries — do not silently expand these:
- **Timezone is display-only.** It changes how timestamps are *rendered* (via the `<x-datetime>`
  Blade component), never what "today" means for business logic. Every `Carbon::today()`/`now()`
  call inside `app/Domain/**` runs on the single canonical `config('app.timezone')` regardless of
  any user's preference — this is intentional, so two admins of the same fund never disagree
  about which day a period closed or a loan came due.
- **Default currency is a pre-selection convenience only** (defaults the currency `<select>` on
  the new-treasury and new-wallet forms to the user's preference). It does not convert or display
  amounts in any other currency anywhere — a treasury/transaction's real currency is exactly what
  its own record says.
- **`weekend_days` is stored but not yet consumed by any feature.** No business-day/due-date
  logic reads it today. It exists so a future scheduling feature can build on it without a schema
  change — don't assume it does anything until you find a reader.
- **No Jalali/Persian calendar conversion.** Farsi (`fa`) dates stay on the Gregorian calendar;
  only the surrounding words (day/month names, UI strings) are translated via
  `Carbon::translatedFormat()` and the `lang/` files below. Calendar-system conversion is a
  separate, larger feature that has not been attempted.

Translations live under `lang/en/` and `lang/fa/`, one file per `resources/views/` top-level
subdirectory (`lang/{en,fa}/policies.php` backs every view under `resources/views/policies/`,
etc.), plus `nav.php` (the shared masthead/nav in `resources/views/layouts/*`), `validation.php`/
`auth.php`/`pagination.php`/`passwords.php` (Laravel's own framework strings), and `exceptions.php`
(every hardcoded message thrown by `app/Domain/**` services and `abort()`/`withErrors()` calls in
`app/Http/Middleware`/`app/Http/Controllers`). `App\Domain\Policies\Categories\*`'s `PolicyField`
labels/help text live under `policies.php`'s `fields` key. Every user-facing string, Blade or PHP
— including strings a controller builds itself, like `ReportController`'s report titles, not just
literal Blade text — must be wrapped in `__('file.key')`; there is no unwrapped literal English
left by design. If you add a new string, add its translation key to **both** locale files in the
same change, and keep their key structures identical (a key in one locale missing from the other
silently renders as the raw key).
