=== ddd architecture rules ===

# Domain-Driven Design (DDD) Architecture

This project follows a DDD architecture with Actions, ViewModels, and DTOs. Business logic lives in `src/Domains/` (namespace `NouTools\`); Laravel infrastructure stays in `app/`.

## Request Flow

```
Request → DTO (validates) → Controller → Action → ViewModel → Response
```

## Core Rules

- **DTOs** (`src/Domains/{Domain}/DataTransferObjects/`): Extend `Spatie\LaravelData\Data`. Final. Validation via PHP attributes. Named `XxxDTO`.
- **Actions** (`src/Domains/{Domain}/Actions/`): Final readonly invokable classes. All business logic. Use `DB::transaction()` for multi-step writes. Use `saveOrFail()`. Named with a verb: `CreatePost`, `ListPosts`.
- **ViewModels** (`src/Domains/{Domain}/ViewModels/`): Extend `Spatie\LaravelData\Resource`. Final. Use `Lazy` for optional relationships. Named `XxxViewModel`.
- **Controllers** (`app/Http/Controllers/`): Thin orchestrators only — no business logic. Wire DTO → Action → ViewModel.
- **Models** (`app/Models/`): Expose `fillFromDTO()` for explicit attribute mapping. No mass assignment.

## Directory Layout

```
src/Domains/{Domain}/
├── Actions/
├── DataTransferObjects/
├── ViewModels/
└── QueryFilters/
```

## When to Create Each

| File        | Create when…                                |
| ----------- | ------------------------------------------- |
| DTO         | New endpoint receives user input            |
| Action      | Any business logic, even a single DB write  |
| ViewModel   | Endpoint returns structured data            |
| QueryFilter | List endpoint has multiple optional filters |

## Frontend Rendering

Public-facing pages are Inertia.js+Vue. The Blade+Alpine.js UI they replaced is gone, and Alpine is no longer a dependency at all; Filament's admin panel (`app/Filament/`, Blade+Livewire) is a separate, untouched system.

- Inertia pages live in `resources/js/Pages/{Domain}/`, mirroring the `src/Domains/{Domain}/` naming.
- Shared layout is `resources/js/Layouts/AppLayout.vue`; pages wrap themselves in it.
- ViewModels/DTOs pass straight into `Inertia::render()` as props — no reshaping, same objects that used to go into `view()`.
- **Exception: StudyRoom.** Inertia is used only for the page shell/navigation there. Live seat/session state is fetched and mutated via its existing REST JSON endpoints and Echo/Reverb broadcasts, not Inertia props — seat-claim state changes far more frequently than a page-prop model suits.
- **Markdown containers are hydrated, not compiled.** Article Markdown (`src/Domains/Articles/Markdown/`) is rendered to HTML server-side and mounted via `v-html`, so Vue never compiles it and the interactive `:::tabs`, `:::checklist`, and `:::countdown` containers cannot be Vue components. They emit framework-neutral `data-*` markup instead, and `resources/js/Composables/useMarkdownContainers.js` attaches the behaviour to the raw DOM. Anything adding a container that needs JavaScript goes through that composable — do not reach for a second framework.
  - There are **five** `v-html` roots, and each one must call the composable: `Articles/Show.vue` (body _and_ sidebar), `Articles/Index.vue`, `Components/StudyRoom/Wall.vue` (the announcement runs through the same converter via `RenderStudyRoomAnnouncement`), and `Newsletter/Show.vue` (intro, item summaries and columns all sit under one root; rendered via `RenderNewsletterMarkdown`).
  - The composable **watches its source props**. Inertia reuses the page component when navigating article → article, so `v-html` swaps content without a remount and new markup would otherwise never be hydrated.
  - Renderers keep a **working no-JS fallback**: the countdown's day count and the checklist's `<label>`/`disabled` handling are done server-side, and no tab panel ships `hidden` — CSS suppresses the tab strip until the composable sets `data-enhanced`, so content is never trapped behind a dead control.
  - The checklist's `nou:article-checklist:{path}:{index}:v1` localStorage key is a compatibility contract with readers who already have ticks saved; `tests/Browser/ArticleMarkdownContainersTest.php` asserts it literally.
- **Installed-PWA chrome is CSS-gated, not JS-gated, and phone-only.** The head script in `app.blade.php` sets `html[data-pwa]` before first paint (standalone display mode, incl. iOS's `navigator.standalone`), and the `bottom-nav:` Tailwind variant (`app.css`) matches only that flag **below `md`** (768px). There, `AppLayout.vue` hides the hamburger menu and the page footer (its disclaimer/contact/status links live on the About page instead; `main` takes over the bottom-bar clearance) and shows `Components/BottomNav.vue` (four tabs + a 更多 sheet; the active tab gets a top bar, a bolder label and `aria-current`). Tablet and desktop PWAs keep the web header nav. Fixed bottom UI (study-room `ActionBanner`, toasts) sits above the bar via `--pwa-nav-height` (0 outside that case); `--safe-bottom` is `0px` there because the bar already clears the home indicator. `viewport-fit=cover` is added for PWAs at any width, so browser tabs are unaffected.
- **Nav items can carry a `match(path)`** (`AppLayout.vue`) when a route prefix isn't specific enough: 我的課表 (`/schedules`) excludes learning-progress paths so 學習進度 (`/schedules/my/learning-progress`, which redirects to the remembered schedule's current-semester page) is the only one highlighted there.
- `resources/views/offline.blade.php` (PWA offline fallback) and the machine-readable exports (`sitemap.blade.php`, `redocly.blade.php`, `llms-txt.md.blade.php`, `*/markdown/*.md.blade.php`) stay plain Blade by design — no Inertia, no client-side framework.

## 浣熊的空大雙週報 (Newsletter)

Lives in `src/Domains/Newsletter/` plus `App\Models\NewsletterIssue`/`NewsletterItem`/`NewsletterColumn`; edited in Filament (`NewsletterIssueResource`).

- **Issue key = ISO week of the publish Monday** (`2026-W39`), but the every-other-Monday cadence is counted in 14-day steps from `config('newsletter.anchor_date')` in `ResolveNewsletterIssueSchedule`. Never derive it from week-number parity: 53-week ISO years flip it (2026-W53 → 2027-W02).
- **Issues are snapshots.** Items copy `source_name`/`url` from their announcement and the school-calendar events are stored in `highlights_events`, so later announcement edits or calendar config changes never rewrite a published issue.
- **Lifecycle:** `newsletter:draft` (Mondays 09:00) creates the issue whose editing week starts that day and runs the laravel/ai editor (`Ai/NewsletterItemCurator`, `Ai/NewsletterHighlightsWriter`); the editor marks it 待發布 and `newsletter:publish-due` (Mondays 08:00) publishes it. Drafts are never auto-published. All AI calls finish before any write, so a failed draft leaves the issue untouched.
- **Sections:** 空大新消息 and 各中心消息 split announcements by source group; 藝文活動 (`NewsletterSection::Arts`) is a topic, not a source group, so it draws from the same candidates as 空大新消息 (`candidatePool()`). It's curated first and its picks are withheld from 空大新消息, so nothing prints twice. The issue page renders them in the order 空大新消息 → 藝文活動 → 各中心消息.
- Announcements don't store bodies, so the curator gets each candidate's metadata **and URL** and decides per item whether to open it (HTML or PDF) with Anthropic's server-side web fetch, capped by `newsletter.ai.max_fetches_per_section` and limited to `newsletter.ai.fetch_domains`. Candidates are sent as plain-text blocks with the URL alone on its line: inside JSON the model sliced URLs badly and wasted fetches on URLs that don't exist.
- **Other Open Graph / Twitter tags** (`og:title`, `og:description`, `og:type`, `og:url`, `article:published_time`, `twitter:*`, `<meta name="description">`) are server-rendered from `resources/views/open-graph/{route name}.blade.php`, which `app.blade.php` includes into `<head>` when it exists (here `newsletter/show`). Inertia's `<Head>` is client-side only (no SSR), so crawlers never see tags set there. The description is the plain-text start of the issue's highlights intro.
- **Social card (`og:image`)** comes from `spatie/laravel-og-image`. `app.blade.php` includes `resources/views/og-image/{route name}.blade.php` (here `newsletter/show`) into the Inertia root view when it exists, and that view wraps the card in `<x-og-image>`; the package screenshots the page with `?ogimage` (preview it in a browser the same way). Pages without a card keep the static `og-image.png`. Two gotchas live in the published `resources/views/vendor/og-image/screenshot.blade.php`: its inline `<style>` needs `@cspNonce` (our CSP has no `'unsafe-inline'`, so the canvas size is otherwise dropped), and the package's unlayered `*` reset is removed because it would beat every Tailwind margin/padding utility. The card uses Noto Sans TC from Google Fonts, linked in that same published view; `PublicSitePolicy` allows `fonts.googleapis.com`/`fonts.gstatic.com` only on `?ogimage` requests, so the rest of the site's CSP is unchanged. Browsershot is the driver for now (`LARAVEL_SCREENSHOT_*` env vars; it can't cope with spaces in the node path); the plan is to switch to Cloudflare Browser Rendering via `OgImage::useCloudflare()`, which needs the card page to be publicly reachable.

## 自習室 (Study Room)

Lives in `src/Domains/StudyRoom/` (Actions, DTOs, ViewModels, PageData) plus `App\Models\StudyRoomSeat`/`StudyRoomProfile`/`StudyRoomSession`. Two design decisions are load-bearing — breaking either reintroduces race conditions or drift:

1. **Seats co-locate definition and occupancy.** A `study_room_seats` row is both "this seat exists" and "who's sitting in it right now" — there's no separate occupancy table. That means claiming a seat is one conditional `UPDATE ... WHERE student_schedule_id IS NULL`, not a read-then-write. Splitting occupancy into its own table would reopen the race two students taking the same seat simultaneously were supposed to be immune to.
2. **Open floor counts are derived, never stored.** Which floors are "open" is computed from current occupancy (`ResolveOpenFloorCount`) each time state is built, not persisted as a flag. Storing it would let it drift from the actual seat rows after a release, a sync, or a crash mid-write.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- prettier (PRETTIER) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== filament/filament rules ===

## Filament

- Filament is a Laravel UI framework built on Livewire, Alpine.js, and Tailwind CSS. UIs are defined in PHP via fluent, chainable components. Follow existing conventions in this app.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan --help`.
- Inspect required options before running, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
->options(CompanyType::class)
->required()
->live(),

TextInput::make('company_name')
->required()
->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `Set $set` inside `->afterStateUpdated()` on a `->live()` field to mutate another field reactively. Prefer `->live(onBlur: true)` on text inputs to avoid per-keystroke updates:

<code-snippet name="Reactive field update" lang="php">
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

TextInput::make('title')
->required()
->live(onBlur: true)
->afterStateUpdated(fn (Set $set, ?string $state) => $set(
        'slug',
        Str::slug($state ?? ''),
)),

TextInput::make('slug')
->required(),

</code-snippet>

Compose layout by nesting `Section` and `Grid`. Children need explicit `->columnSpan()` or `->columnSpanFull()`:

<code-snippet name="Section and Grid layout" lang="php">
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

Section::make('Details')
->schema([
Grid::make(2)->schema([
TextInput::make('first_name')
->columnSpan(1),
TextInput::make('last_name')
->columnSpan(1),
TextInput::make('bio')
->columnSpanFull(),
]),
]),

</code-snippet>

Use `Repeater` for inline `HasMany` management. `->relationship()` with no args binds to the relationship matching the field name:

<code-snippet name="Repeater for HasMany" lang="php">
use Filament\Forms\Components\Repeater;

Repeater::make('qualifications')
->relationship()
->schema([
TextInput::make('institution')
->required(),
TextInput::make('qualification')
->required(),
])
->columns(2),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Use `SelectFilter` for enum or relationship filters, and `Filter` with a `->query()` closure for custom logic:

<code-snippet name="Table filters" lang="php">
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

SelectFilter::make('status')
->options(UserStatus::class),

SelectFilter::make('author')
->relationship('author', 'name'),

Filter::make('verified')
->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),

</code-snippet>

Actions are buttons that encapsulate optional modal forms and behavior:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;

Action::make('updateEmail')
->schema([
TextInput::make('email')
->email()
->required(),
])
->action(fn (array $data, User $record) => $record->update($data)),

</code-snippet>

### Testing

Testing setup (requires `pestphp/pest-plugin-livewire` in `composer.json`):

- Always call `$this->actingAs(User::factory()->create())` before testing panel functionality.
- For edit pages, pass `['record' => $user->id]`, use `->call('save')` (not `->call('create')`), and do not assert `->assertRedirect()` (edit pages do not redirect after save).

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;

livewire(CreateUser::class)
->fillForm([
'name' => 'Test',
'email' => 'test@example.com',
])
->call('create')
->assertNotified()
->assertHasNoFormErrors()
->assertRedirect();

assertDatabaseHas(User::class, [
'name' => 'Test',
'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Edit resource test" lang="php">
livewire(EditUser::class, ['record' => $user->id])
    ->fillForm(['name' => 'Updated'])
    ->call('save')
    ->assertNotified()
    ->assertHasNoFormErrors();

assertDatabaseHas(User::class, [
'id' => $user->id,
'name' => 'Updated',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

Use `->callAction(DeleteAction::class)` for page actions, or `->callAction(TestAction::make('name')->table($record))` for table actions:

<code-snippet name="Calling actions" lang="php">
use Filament\Actions\Testing\TestAction;

livewire(ListUsers::class)
->callAction(TestAction::make('promote')->table($user), [
'role' => 'admin',
])
->assertNotified();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, `Repeater`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Table columns (`TextColumn`, `IconColumn`, etc.): `Filament\Tables\Columns\`
- Table filters (`SelectFilter`, `Filter`, etc.): `Filament\Tables\Filters\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, `Fieldset`, and `Repeater` do not span all columns by default.
- **Use `Select::make('author_id')->relationship('author', 'name')` for BelongsTo fields.** `BelongsToSelect` does not exist in v4.
- **`Repeater` uses `->schema()`, not `->fields()`.**
- **Never add `->dehydrated(false)` to fields that need to be saved.** It strips the value from form state before `->action()` or the save handler runs. Only use it for helper/UI-only fields.
- **Use correct property types when overriding `Page`, `Resource`, and `Widget` properties.** These properties have union types or changed modifiers that must be preserved:
  - `$navigationIcon`: `protected static string | BackedEnum | null` (not `?string`)
  - `$navigationGroup`: `protected static string | UnitEnum | null` (not `?string`)
  - `$view`: `protected string` (not `protected static string`) on `Page` and `Widget` classes

</laravel-boost-guidelines>
