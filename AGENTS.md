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
  - There are **six** `v-html` roots, and each one must call the composable: `Articles/Show.vue` (body _and_ sidebar), `Articles/Index.vue`, `Components/StudyRoom/Wall.vue` (the announcement runs through the same converter via `RenderStudyRoomAnnouncement`), `Newsletter/Show.vue` (intro, item summaries and columns all sit under one root; rendered via `RenderNewsletterMarkdown`), and `Changelog/Show.vue` (rendered via `RenderChangelogMarkdown`).
  - The composable **watches its source props**. Inertia reuses the page component when navigating article → article, so `v-html` swaps content without a remount and new markup would otherwise never be hydrated.
  - Renderers keep a **working no-JS fallback**: the countdown's day count and the checklist's `<label>`/`disabled` handling are done server-side, and no tab panel ships `hidden` — CSS suppresses the tab strip until the composable sets `data-enhanced`, so content is never trapped behind a dead control.
  - The checklist's `nou:article-checklist:{path}:{index}:v1` localStorage key is a compatibility contract with readers who already have ticks saved; `tests/Browser/ArticleMarkdownContainersTest.php` asserts it literally.
- **YouTube embeds are the one piece of raw HTML the Markdown pipeline keeps.** Markdown runs with `html_input => 'strip'`, so a pasted `<iframe>` used to vanish. `Embed/YoutubeEmbedProcessor` recognises a block that is exactly one `<iframe>` on `/embed/{11-char id}` of a YouTube host over https, keeps only the id, optional `start` and title, and `YoutubeEmbedRenderer` rebuilds the iframe on `www.youtube-nocookie.com` inside `.md-video` (16:9). It never copies the pasted attributes; anything else (other hosts, playlist embeds, extra markup) is still stripped. `PublicSitePolicy` adds that one origin to `frame-src` (`YoutubeEmbedProcessor::EMBED_ORIGIN`, shared so they cannot drift). The `.md` exports and the Atom feed carry the iframe as written, so feed readers may drop it. The Filament editor preview does not render it.
- **Installed-PWA chrome is CSS-gated, not JS-gated: a bottom bar on phones, the adaptable nav from `md` up.** The head script in `app.blade.php` sets `html[data-pwa]` before first paint (standalone display mode, incl. iOS's `navigator.standalone`), and the `bottom-nav:` Tailwind variant (`app.css`) matches only that flag **below `md`** (768px). There, `AppLayout.vue` hides the hamburger menu and the page footer (its disclaimer and links — contact, 無障礙說明, status and the rest — live on the About page instead, as bordered button links; `main` takes over the bottom-bar clearance) and shows `Components/BottomNav.vue` (four tabs + a 更多 sheet; the active tab gets a top bar, a bolder label and `aria-current`). From `md` up an installed PWA gets `Components/AdaptableNav.vue` instead (after Apple's `sidebarAdaptable`), behind the `wide-pwa:` variant: a sticky top tab bar (no brand; five tabs, a 更多 dropdown, theme popover and a toggle) or a 16rem sidebar, chosen with that toggle or the 導覽樣式 setting and kept in `nou:nav-style:v1` (`useNavStyle`, applied as `html[data-nav-style]`; the head script restores it before first paint). The header _and footer_ are hidden there (the About page carries the footer's links). The bar must render **before `<main>`** in `AppLayout.vue`, since it is sticky in normal flow; the sidebar is `fixed`. `--pwa-sidebar-width` (16rem in sidebar mode, else 0) pads the body, and fixed bottom UI (`CookieConsentBanner`, `ActionBanner`) uses `left-(--pwa-sidebar-width)`. Both nav modes are in the DOM and CSS shows one, so only the current mode's theme button carries `accesskey="3"`. Fixed bottom UI (study-room `ActionBanner`, toasts) sits above the bar via `--pwa-nav-height` (0 outside that case); `--safe-bottom` is `0px` there because the bar already clears the home indicator. `viewport-fit=cover` is added for PWAs at any width, so browser tabs are unaffected.
- **`/schedules/my` without a remembered schedule renders `Schedule/Find`** (not a redirect to create): "have you made one before?" → paste the share link or scan its QR code, POSTed to `schedules.my.store` (`FindScheduleFromLink` accepts the full URL, legacy `/schedule/…` URL or the bare token) which sets the same `student_schedule` cookie as the schedule page's remember modal. `Components/QrScanner.vue` uses `BarcodeDetector` when present and lazy-loads `jsqr` otherwise (Safari/Firefox), so the decoder costs nothing until a scan starts. `/schedules/my/learning-progress` still sends cookie-less visitors straight to create.
- **Form dates use `Components/DateField.vue`, not `<input type="date">`.** Native date inputs render dd/mm/yyyy or mm/dd/yyyy per browser and Safari shows today's date for an empty value. `DateField` shows a fixed `M/D` label (or 未設定), submits `YYYY-MM-DD` through a hidden input (so it works in native form POSTs like the learning-progress form) and teleports its calendar to `<body>` so `overflow-x-auto` tables can't clip it. Pass the server's date as `today` so it follows Taipei time. `variant="field"` is the standalone bordered version with the full date (the homepage's 今日視訊面授 filter); attributes such as `id` and `data-offline-disable` land on its trigger button.
- **Nav items can carry a `match(path)`** (`AppLayout.vue`) when a route prefix isn't specific enough: 我的課表 (`/schedules`) excludes learning-progress paths so 學習進度 (`/schedules/my/learning-progress`, which redirects to the remembered schedule's current-semester page) is the only one highlighted there.
- **Accesskeys are digits only, four global and five page-scoped; the help page is `/accessibility`** (`Accessibility/Show.vue`, linked from the footer, the About page and beside the skip link; not from the PWA 更多 sheet). Digits avoid the Alt+letter clashes with browser menus and screen-reader keys. **Global:** `0` 無障礙說明 (the skip-link copy carries it, since a key can sit on only one element), `1` skip link, `2` 我的課表 (an off-screen, `tabindex=-1`, `aria-hidden` link beside the skip links, because the header nav is `display:none` below `lg` and a hidden element can't take a key), `3` theme/text-size button. 首頁 and the other nav items deliberately have none; `9` is unused. **`4`-`8` are per page and mean different things:** on the schedule page `4` focuses the row/card of the next class (`nextClass`, the first row with an upcoming class; `tabindex="-1"`, plus a screen-reader-only 下一堂課 prefix), `5` is that row's 進入教室 link, `6` the sr-only 完整課表 heading and `7` the term picker; `8` is the search field on 本學期開課表, 優惠店家 and the schedule editor. On 自習室, `4` is 快速入座 (the real toolbar button) until you're seated, then the control panel; `5` starts/pauses/resumes the timer, `6` goes to your seat (or the first floor's), `7` speaks `timer.statusSentence()` and `8` opens focus mode. Except for the unseated `4`, they live on off-screen `aria-hidden` proxies in `Components/StudyRoom/AccessKeys.vue`, because their controls swap with `v-show` or sit in a panel that can be minimized. Don't reuse a digit for something else on the same page, and never put a key on a `v-show`/`hidden` element whose visible twin carries it too unless one copy is always `display:none` (the schedule's table row and phone card are). PWA phones hide the header, so only `0`, `1` and `2` (which live outside it) work there. `tests/Browser/AccessibilityTest.php` asserts the global set is exactly these five.
- **The schedule's 列印 button opens a server-rendered PDF, not `window.print()`.** `GET /schedules/{schedule}/print.pdf` (`schedules.print.pdf`, `throttle:6,1`) renders `resources/views/schedule/print.blade.php` (A4 landscape, plain Blade — no Inertia) through Browsershot; `/schedules/{schedule}/print` serves the same view as HTML for previewing in a browser (its CSP allows Google Fonts, like `?ogimage`). The left ~1/3 is a strip meant to be cut off and taken to the exam: courses, then one row per course that has an exam date with midterm and final dates under 週六/週日 (**both exams share one time slot, so the time is stored once per course**) and write-in fields for 班級代碼/期中教室/期末教室 (no 期中教室 in summer terms). Courses without an exam date are deliberately left out of that table. The right part has one calendar per month (class dates are filled circles, exam dates are underlined; the range extends to the final exam) with each date's courses listed underneath, plus a QR code back to the web schedule. Things that are easy to break:
  - **Two week starts, chosen from the 列印 button's menu.** `?week_start=monday|sunday` (`PrintWeekStart`, unknown values fall back to Monday) picks the calendars' first weekday; only the month grids change (the exam table's 週六/週日 columns don't). Sunday-first needs a sixth week row more often, and `ResolvePrintMonthColumns` already reads the real row count, so re-check the PDF on a Sunday-first term if you touch sizes. The variants cache separately because their HTML differs.
  - **In an installed PWA the menu shares the PDF instead of linking to it.** The PDF is same-origin, so a link would open it inside the app and iOS has no back button. `useSchedulePdfShare` (only when `html[data-pwa]` is set and `navigator.canShare` accepts a file) `fetch()`es the PDF, shows a loading state on the button (rendering can take seconds) and calls `navigator.share({ files })`. iOS rejects `share()` with `NotAllowedError` when too long has passed since the tap, so the button then becomes 分享 PDF and the next tap shares the already-fetched file. Browser tabs, and PWAs that cannot share files, keep the plain `target="_blank"` links. `public/sw.js` lets `/schedules/{token}/print.pdf` go straight to the network, since the stale-while-revalidate bucket would hand back the previous PDF after an edit.
  - **It must fit on one page.** The sheet clips (`overflow-hidden`) instead of spilling, so check the real PDF after touching sizes; the exam rows go `dense` above 9 courses.
  - **CSS is inlined via `Vite::content('resources/css/schedule-print.css')`** for the PDF (`Browsershot::html()` has no origin), so it needs a built asset (`npm run build`); the HTML preview uses `@vite`. The PDF variant carries no CSP nonce.
  - **`RenderSchedulePdf` caches the PDF by HTML with SVGs stripped**: the QR code's bytes differ between PHP processes for the same URL, so hashing it would never hit. It goes through the `Shared/Pdf/HtmlToPdf` interface (Browsershot implementation, sharing the `LARAVEL_SCREENSHOT_*` Chromium config); tests fake that interface instead of launching Chromium.
- **Emergency mode: the service worker never caches Inertia pages.** They need the hashed build assets and live `X-Inertia` requests, so a cached copy would boot Vue but break on the first link. Instead `GET /schedules/{schedule}/lite` (`ScheduleLiteController`, `schedule/lite.blade.php` on `layouts/lite.blade.php`) is plain Blade with inline CSS, no `@vite` and no external assets, listing each course with 進入教室/備用教室 and its class dates (the script works from the viewer's clock and zone: it dims past dates, names the next class and adds a `你的時間 · …` line when the viewer isn't on UTC+8, since times are Taipei time and this cached copy can't know who is looking; it shows the "can't reach the app" banner when the worker has set `data-emergency` on `<html>` — `/up` can't be the signal, it stays green when only the database is down). `public/sw.js` refreshes it in the background (`refreshLite`) whenever a navigation to `/schedules/{token}` succeeds, or an Inertia visit to it goes by, and stores the token as the latest. Any same-origin navigation that fails or gets an outage status (500, 502/503/504, 520-527) is answered by `emergencyResponse()`: the requested schedule's backup, else the latest one, else `/offline` (a 500 with no backup passes through instead, so a real error page isn't hidden behind a generic message). Keep the lite pages self-contained (a cached copy must survive later deploys), and bump `CACHE_VERSION` when changing the worker. `/offline` (same layout) is now only the no-backup fallback; it lists the backups it finds in the `nou-lite-*` caches.
- `resources/views/offline.blade.php`, the lite views (PWA offline fallbacks) and the machine-readable exports (`sitemap.blade.php`, `redocly.blade.php`, `llms-txt.md.blade.php`, `*/markdown/*.md.blade.php`) stay plain Blade by design — no Inertia, no client-side framework.

## 今日視訊面授 (Video Classes)

Shown on the homepage (`Components/Home/VideoCourses.vue`) and on its own page, `/video-classes` (`src/Domains/VideoClasses/`, `VideoClassController`, plus a `.md` twin). Both read the same `ListVideoCourses` action.

- **Ended / live state is decided in the browser** (`useSessionClock.js`, a 30s ticker over `Date.now()`), not the server, so the state follows the viewer's clock and needs no polling. A course is `ended` once 30 minutes past its **end** time (hidden unless 顯示已結束課程 is ticked, remembered in `nou:video-classes:show-ended:v1`), `live` between start and end (上課中), `soon` within 30 minutes before start (即將開始). Classes without a time never count as ended. UI copy says 課程, not 場次.
- **Times are Taipei time**; `useLocalTimeHint.js` (shared with `Schedule/Show.vue`) adds the 你的時間 line when the viewer's zone differs.
- Browser tests place classes around the real "now" (skipping near Taipei midnight) rather than mocking the clock.

## 浣熊的空大雙週報 (Newsletter)

Lives in `src/Domains/Newsletter/` plus `App\Models\NewsletterIssue`/`NewsletterItem`/`NewsletterColumn`; edited in Filament (`NewsletterIssueResource`).

- **Issue key = ISO week of the publish Monday** (`2026-W39`), but the every-other-Monday cadence is counted in 14-day steps from `config('newsletter.anchor_date')` in `ResolveNewsletterIssueSchedule`. Never derive it from week-number parity: 53-week ISO years flip it (2026-W53 → 2027-W02).
- **Issues are snapshots.** Items copy `source_name`/`url` from their announcement and the school-calendar events are stored in `highlights_events`, so later announcement edits or calendar config changes never rewrite a published issue.
- **Lifecycle:** `newsletter:draft` (Mondays 09:00) creates the issue whose editing week starts that day and runs the laravel/ai editor (`Ai/NewsletterItemCurator`, `Ai/NewsletterHighlightsWriter`); the editor marks it 待發布 and `newsletter:publish-due` (Mondays 08:00) publishes it. Drafts are never auto-published. All AI calls finish before any write, so a failed draft leaves the issue untouched.
- **Sections:** 空大新消息 and 各中心消息 split announcements by source group; 藝文活動 (`NewsletterSection::Arts`) is a topic, not a source group, so it draws from the same candidates as 空大新消息 (`candidatePool()`). It's curated first and its picks are withheld from 空大新消息, so nothing prints twice. The issue page renders them in the order 空大新消息 → 藝文活動 → 各中心消息.
- Announcements don't store bodies, so the curator gets each candidate's metadata **and URL** and decides per item whether to open it (HTML or PDF) with Anthropic's server-side web fetch, capped by `newsletter.ai.max_fetches_per_section` and limited to `newsletter.ai.fetch_domains`. Candidates are sent as plain-text blocks with the URL alone on its line: inside JSON the model sliced URLs badly and wasted fetches on URLs that don't exist.
- **Views, sharing and reactions have no login, so the session is the reader.** `RecordNewsletterIssueView` bumps `newsletter_issues.view_count` once per session (the viewed issue ids sit in the session) and never for unpublished previews; it runs inside `ShowNewsletterIssuePage`, so bots without a cookie each count once, and the Markdown twin doesn't count. Reactions (`NewsletterReactionType`: 👍 ❤️ 💡 🎉) are one `newsletter_reactions` row per (issue, session), enforced by a unique key and written by an upsert, so a session switches or withdraws (`reaction: null`) rather than stacking; `session_hash` is a sha256 of the session id, never the id. Counts and the session's own pick are page props; `PUT /newsletter/{issueKey}/reaction` (JSON, published issues only) returns fresh tallies and `useNewsletterReactions` applies the change optimistically. A new session (expiry, cleared cookies) can react and count again — that is the accepted trade-off. Feature tests must pin the session cookie with `withCredentials()` (see `NewsletterEngagementTest`). The 分享 button is `Components/ShareButton.vue` (native share sheet, else a copy-link dialog), shared with articles via `useArticleShare`; the issue has one under the title and one after the reactions, so their `testIdPrefix`es differ (`newsletter-share`, `newsletter-share-end`). Share links use the canonical `route('newsletter.show')` URL, not `location.href`.
- **The CC BY-NC-SA 4.0 footer covers only the newsletter's original content** (前言, columns, editor-written summaries), worded differently from the article's blanket 本文採用. Linked announcements and the Unsplash cover keep their original copyright, and the footer says so; keep that carve-out if you reword it.
- **Other Open Graph / Twitter tags** (`og:title`, `og:description`, `og:type`, `og:url`, `article:published_time`, `twitter:*`, `<meta name="description">`) are server-rendered from `resources/views/seo/{route name}.blade.php`, which `app.blade.php` includes into `<head>` when it exists (here `newsletter/show`). Inertia's `<Head>` is client-side only (no SSR), so crawlers never see tags set there. The description is the plain-text start of the issue's highlights intro.
- **Social card (`og:image`)** comes from `spatie/laravel-og-image`. `app.blade.php` includes `resources/views/og-image/{route name}.blade.php` (here `newsletter/show`) into the Inertia root view when it exists, and that view wraps the card in `<x-og-image>`; the package screenshots the page with `?ogimage` (preview it in a browser the same way). Pages without a card keep the static `og-image.png`. Two gotchas live in the published `resources/views/vendor/og-image/screenshot.blade.php`: its inline `<style>` needs `@cspNonce` (our CSP has no `'unsafe-inline'`, so the canvas size is otherwise dropped), and the package's unlayered `*` reset is removed because it would beat every Tailwind margin/padding utility. The card uses Noto Sans TC from Google Fonts, linked in that same published view; `PublicSitePolicy` allows `fonts.googleapis.com`/`fonts.gstatic.com` only on `?ogimage` requests, so the rest of the site's CSP is unchanged. Browsershot is the driver for now (`LARAVEL_SCREENSHOT_*` env vars; it can't cope with spaces in the node path); the plan is to switch to Cloudflare Browser Rendering via `OgImage::useCloudflare()`, which needs the card page to be publicly reachable.

## 自習室 (Study Room)

Lives in `src/Domains/StudyRoom/` (Actions, DTOs, ViewModels, PageData) plus `App\Models\StudyRoomSeat`/`StudyRoomProfile`/`StudyRoomSession`. Two design decisions are load-bearing — breaking either reintroduces race conditions or drift:

1. **Seats co-locate definition and occupancy.** A `study_room_seats` row is both "this seat exists" and "who's sitting in it right now" — there's no separate occupancy table. That means claiming a seat is one conditional `UPDATE ... WHERE student_schedule_id IS NULL`, not a read-then-write. Splitting occupancy into its own table would reopen the race two students taking the same seat simultaneously were supposed to be immune to.
2. **Open floor counts are derived, never stored.** Which floors are "open" is computed from current occupancy (`ResolveOpenFloorCount`) each time state is built, not persisted as a flag. Storing it would let it drift from the actual seat rows after a release, a sync, or a crash mid-write.

**Keyboard and screen-reader support** is part of the room's contract (`tests/Browser/StudyRoom/StudyRoomAccessibilityTest.php`):

- **Seats are a roving-tabindex grid** (`useSeatRovingFocus`): one Tab stop per floor, Left/Right in DOM order, Up/Down by on-screen position, Home/End. Every seat button (and every list-view row header) carries `data-seat-code`. **Occupied seats, and every seat once you hold one, are `aria-disabled`, never `disabled`**, since a disabled button drops focus and hides its occupant; `grid.activateSeat()` does the guarding. Your own seat is the exception: it stays actionable and leads to the control panel (`grid.onOwnSeatActivated` → `openControlPanel()` in `Show.vue`, expanding it if minimized); the list view's row has a 前往控制列 button for the same. Seat names come from `seatAriaLabel(seat, timer.spokenTimerLabel(seat))`, rounded to minutes so they don't change every second.
- **`RoomToolbar`** holds 快速入座 (`grid.firstFreeSeat()`), the 平面圖/清單 switch (`nou:study-room:view:v1`) and 語音提示. **`SeatList`** is the list view: one captioned table per floor over the same socket/grid/timer, so it works in the preview too.
- **Speech goes through one announcer** (`useStudyRoomAnnouncer` + `LiveAnnouncer.vue`, regions mounted for the page's lifetime). `useStudyRoomAnnouncements` reads your seat's timer columns (not the buttons), so changes from another tab read the same. Two opt-ins live in localStorage (`useStudyRoomVoiceSettings`): `nou:study-room:announce-interval:v1` (time left every 5/10/15 min) and `nou:study-room:announce-room:v1` (neighbours/floor sitting down or leaving, fed by `socket.onSeatChange` and batched for 3s).
- **Focus is moved deliberately.** `socket.onSeatEvent` (`taken`/`left`/`released`) drives it in `Show.vue`: into the control panel on taking a seat, back to the seat on leaving, to 快速入座 on an idle release (only if focus was on the panel). `ActionBanner`'s swapping buttons name their successor via `act(…)`, with `FALLBACK_FOCUS` for swaps that happen on their own. `Modal.vue` and `FocusMode.vue` use `useDialogFocus` (move in, trap Tab, restore focus).
- The demo socket must keep `floorForSeat`, `onSeatEvent` and `onSeatChange` (as no-ops where needed).

**Visitors without a schedule get a preview, not the room.** `Show.vue` swaps the socket for `Composables/useStudyRoomDemo.js`, a stub with the socket's shape whose seats are fictional fixtures built in the browser, so the real floor markup, `useSeatGrid` and `useStudyTimer` render unchanged. It never calls `GET /study-room/state`, Echo or the heartbeat, and `take()` only scrolls to, highlights and focuses the sign-up banner (`study-room-needs-schedule`, linking to `/schedules/create` and to `/schedules/my`, which recovers an existing schedule). The profile/stats modals, `ActionBanner`, `FocusMode` and the nameplate (`Wall.vue` `demo` prop) are hidden there. If the socket's surface grows, the stub must grow with it. `tests/Browser/StudyRoom/StudyRoomPreviewTest.php` covers it.

Pausing a Focus timer (`PauseStudyTimer` / `ResumeStudyTimer`, `seat.paused_at`) splits the record the same way `ChangeStudyActivity` does, and keeps the progress bar continuous:

- Pause records the elapsed segment but leaves `timer_started_at`/`timer_ends_at` alone; the client measures the frozen countdown and bar up to `pausedAt`. Resume shifts both forward by the paused duration and restarts `activity_started_at`, so the next session measures only what is left of the plan.
- `RecordStudySession` is a **no-op while `paused_at` is set** — that is what keeps stopping, leaving or idle-release from crediting the pause as study time. Anything that resets the timer columns must also clear `paused_at`.

**計時器結束通知** is a web push, because the page alone cannot deliver it: the chime in `useStudyTimer.js` is suppressed while the tab is hidden, and a phone suspends or discards a backgrounded tab outright. The page is unchanged — it still only chimes and updates the tab title — and the push is the sole notification.

- **`SendStudyTimerEndPushes` is a sub-minute scheduled sweep, not a job delayed until `timer_ends_at`.** It is registered in `routes/console.php` with `Schedule::call(...)->everyTenSeconds()`, which `ScheduleRunCommand::repeatEvents()` serves by keeping `schedule:run` alive for the rest of the minute — no extra process to provision. The sweep keeps the queue worker out of the path, the same reason `StudyRoomUpdated` is `ShouldBroadcastNow`, and because it reads current state, pausing, stopping or skipping a round needs no queued job to be cancelled. `Schedule::call` rather than `Schedule::command`: the latter shells out to `php artisan`, which at this frequency would be six extra Laravel boots a minute. `study-room:send-timer-end-pushes` still runs the same sweep by hand. Polling costs nothing because the room is capped at 120 seats by `study-room.layout`/`floors.max`.
- **`seat.timer_end_notified_at` is required, not an optimisation.** A finished countdown keeps `timer_ends_at` in the past while it runs into overtime, for up to `idle_release_seconds`, so without the marker every sweep would notify the same seat again. Like `paused_at`, anything that resets the timer columns must also clear it. The sweep stamps it with a conditional `UPDATE ... WHERE timer_end_notified_at IS NULL` before sending, so two overlapping `schedule:run` processes cannot both notify.
- **Two independent things gate a push**: `study_room_profiles.notify_on_timer_end` (opt-in, default off) and a row in `push_subscriptions`. A browser holds exactly one subscription, shared with the class-starting reminders, which have their own opt-in, `student_schedules.notify_on_class_start` (`EnableClassStartingReminders` / `DisableClassStartingReminders`). Neither feature may infer intent from the subscription alone, or subscribing for one would switch the other on. That is also why **neither toggle unsubscribes the browser** — turning one off only clears its flag, since unsubscribing would switch the other off too — and why `/study-room/push-subscriptions` has no `DELETE` (the schedule page's `DELETE` likewise only clears the flag). The endpoint exists at all because 自習室 identifies its viewer by the `student_schedule` cookie rather than the `{schedule}` route parameter the schedule page uses; the subscribe action and DTO behind it are the schedule page's. `usePushSubscription` takes the server's flag as `enabled`. The 設定 page (`Settings/Show.vue`, data from `ShowNotificationSettings`) exposes both switches for the remembered schedule: the class-reminder one reuses the schedule endpoints, the timer-end one registers the browser through `/study-room/push-subscriptions` and then saves the flag via `PUT /study-room/timer-end-notification` (`SetTimerEndNotification`), which never creates a profile — so it stays disabled until a nickname exists.
- **iOS delivers web push only to an installed PWA** (16.4+), so a Safari tab on iPhone cannot receive the timer-end notification.

## 背景音樂 (Music Library)

Lives in `src/Domains/StudyRoom/` (Actions/ViewModels, alongside the rest of the study room) plus `App\Models\MusicTrack`/`MusicPlaylist`/`MusicPlaylistItem`; managed in Filament (`MusicTrackResource`, `MusicPlaylistResource`, group 自習室). The cassette player on the study room Wall consumes `GET /study-room/music/playlists` (`ListMusicPlaylists` → `MusicPlaylistListViewModel`).

- **Every track has both an mp3 and an ogg file** (both required); the browser picks whichever it can play. Duration is stored in seconds and read from the uploaded file by `ReadAudioDuration` (getID3): the mp3 fills it, the ogg only fills a blank, and the field stays editable.
- **Files live on scoped, env-switchable disks** like the newsletter covers: `music_tracks` (`MUSIC_TRACKS_DISK`) and `music_playlist_covers` (`MUSIC_COVERS_DISK`), both `public` by default.
- **Filament never deletes stored files it replaces**, so the models do it: replacing an mp3/ogg/cover on update, or deleting the record, removes the old file (`booted()` hooks).
- **Playlist order is `music_playlist_items.position`**, edited via a `Repeater->relationship()->orderColumn('position')`. It's a real model rather than a bare pivot because that Repeater only works on `HasMany`; `MusicPlaylist::tracks()` is the ordered read side. A track can appear once per playlist.
- **Uploads are capped at 30MB** (`config/livewire.php` `temporary_file_upload.rules` and `MusicTrackForm::MAX_AUDIO_KB`, keep them in step); PHP's `upload_max_filesize`/`post_max_size` and any web-server body limit must allow it too.
- **CSP:** `AdminPanelPolicy` allows the S3/CDN origins of these disks via `ScopedDiskOrigins`. `PublicSitePolicy` adds `Directive::MEDIA` for the audio disk's origin and `IMG` for the cover disk's, only once they are on S3 (local disks are same-origin). `<audio>` needs no `connect-src`.
- **Player:** `Components/StudyRoom/CassettePlayer.vue`, driven by `Composables/useStudyRoomMusic.js` (created in `Show.vue`, passed to `Wall.vue`, exposed on `window.__studyRoomTest.music`). Playback is local to the listener, never synced through the socket, and never autoplays: the single `Audio` is created on the first press of play, and `dispose()` stops it when the page unmounts. The deck renders only once the endpoint returns a playlist.
  - **It is a slim strip on purpose** (a fuller deck with bay, progress bar and controls took ~200px of the Wall and was cut down): mini cassette, title, play and next. Tapping the title opens a popover above it (playlists, previous/next, eject, volume, credits; closes on outside click or Escape). Keep new controls in the popover rather than growing the strip. Next is hidden in the strip below `sm` because the row shares width with the clock; the popover always has one (`-popover-next`).
  - **Layout:** `Wall.vue` is a grid so one player instance sits under the window from `sm` up and to the left of the clock on phones. Keep it a single DOM node; do not add a second copy per breakpoint.
  - **Focus mode has a second deck on the desk** (`FocusMode.vue`, bottom centre, wrapped in a positioned `div` because the component's own `relative` would beat `absolute`). Both decks share the one `music` object from `Show.vue`, so a tape plays straight through entering and leaving focus mode; the Wall's deck sits hidden under the overlay meanwhile. The popover `id` comes from `useId()` for that reason, and browser tests scope focus-mode selectors under `[data-testid="study-room-focus-mode"]` since the `study-room-music-*` test ids exist twice. Escape closes an open popover and `stopPropagation()`s, so it doesn't also leave focus mode.
  - **A tape is "loaded" while playing or paused**; the cassette is only in the deck then, and eject or a fresh page shows an empty slot. The reel animation is `animate-reel` (`app.css`) with `motion-reduce:animate-none`.
  - **The title scrolls when it doesn't fit** via `Components/StudyRoom/MarqueeText.vue`: same `animate-marquee` slide as the seat status bubbles, but it measures the real overflow (the bubbles use a character count) at a constant 15px/s, and falls back to truncation under reduced motion.
  - **Attribution is part of the UI:** the tracks are CC-licensed, so the popover shows `author · license`, linked to `sourceUrl`/`licenseUrl`. Keep it when restyling.
  - Volume and the last playlist live in `localStorage` (`nou:study-room:music-volume:v1`, `nou:study-room:music-playlist:v1`).
  - The timer-end chime is a separate `Audio` and plays over the music; there is no ducking.
- **Service worker:** `public/sw.js` lets media requests (`destination` audio/video, or any `Range` request) go straight to the network. `<audio>` gets 206 responses that `cache.put()` rejects, so the same-origin stale-while-revalidate bucket would break playback and seeking. Bump `CACHE_VERSION` when changing this.

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
