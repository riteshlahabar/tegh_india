# Tegh India CRM — Apna Bazar / BUSY Multi-Company Integration

**Live:** https://tpi.turnkeyinfotech.live · **Server:** `turnkeyi@s16425:~/tpi.turnkeyinfotech.live`
**Repo:** github.com/riteshlahabar/tegh_india · **Stack:** Laravel 13, PHP 8.4, MySQL, cPanel
**Last updated:** 2026-10-30

---

## Status

The original problem is solved and deployed. The CRM pulled one company's books; it
now pulls three. Live row counts on the Outstanding page:

| Region | Rows | Was |
|---|---|---|
| Mumbai | 1,916 | 1,916 (unchanged — nothing was overwritten) |
| Gujarat | 319 | 0 |
| Pune | 345 | 0 |
| **Total** | **2,580** | 1,916 |

Those are *sales invoices*, which is all the Outstanding page shows. The database
holds far more per company — the API also returns journal (`JV-`), payment (`PV-`)
and opening-balance rows. Raw API totals: Mumbai 5,050 · Gujarat 2,111 · Pune 2,780.

**Unpushed:** `3b94071` (Region → City cascade) as of this writing. Everything else is live.

---

## Root cause

**One Apna Bazar login == one company's books, and the API has no company parameter.**
The CRM stored a single credential — Mumbai's — so Gujarat and Pune were never fetched.

- All 32 endpoints accept only `Username` + `Password`. `get_outstanding` takes **zero** params.
- `grep` for `Comp0002|Comp0009|Comp0012|company_code` across the project: **0 hits**.
- The company is decided by *which login authenticates*. There is no field to pass a code in.

### Credentials

Username = password = the mobile number. One base URL for all three.

| Region | Company | Login | Invoice series |
|---|---|---|---|
| Mumbai | Comp0002 | `9082479995` | `TCIPL/` |
| Gujarat | Comp0009 | `9136032692` | `TCIG/` |
| Pune | Comp0012 | `7977861879` | `TCIPLP` |

**Base URL:** `http://api.apnabazarapp.in/WebAPI/V2` (plain HTTP)

Stored in `app_settings` via the admin Settings page — **not** `.env`.

### Request format

The API needs **form-encoded** or multipart. A JSON body returns a misleading
`{"statuscode":400,"statusresponse":"unauthorized user details"}` — it means "I didn't
see your fields", not "wrong password". Query-string also fails.

```bash
curl -s -X POST http://api.apnabazarapp.in/WebAPI/V2/get_outstanding.php \
  -d "Username=9136032692&Password=9136032692"
```

App code already uses `asForm()`. This note is for manual testing. **Postman:** the
Cloud Agent cannot reach `http://` — use the Desktop Agent.

---

## Two traps that made this more than a credentials change

### 1. ERP codes repeat across companies

BUSY restarts numbering per company. Measured on live data:

```
MUM vs GUJ:  8 shared ERP codes —  8 are DIFFERENT parties
MUM vs PUN: 13 shared ERP codes — 12 are DIFFERENT parties

ERP 153:  Mumbai "POLYCAB INDIA LTD RECEIVABLE A/C"
          Gujarat "YCube Engineered Solutions Pvt Ltd."
```

With `erp_code` unique on its own, syncing Gujarat would have renamed Polycab to
YCube and reattached its invoices. Same risk on `parties.code` and `invoices.invoice_no`.

### 2. Two separate places hid Gujarat even after syncing

- The region filter matched a **hardcoded list of customer city names** against
  `parties.city`. Every row was Mumbai's, sliced by where the customer sat. The top
  "Pune" entry was `TEGH CABLES (INDIA) PVT LTD-PUNE.` — an inter-company ledger
  *inside* the Mumbai books.
- `OutstandingPageService` **and** `DashboardPageService` both filtered admins to
  `invoice_no LIKE 'TCIPL%'`. Gujarat's 319 `TCIG/` invoices matched nothing. Pune's
  `TCIPLP` matched only because it happens to begin with `TCIPL`.

---

## What was built

Seven commits, oldest first:

| Commit | What |
|---|---|
| `f88762a` | Keep the documented schema and seed SQL files |
| `3ba68b0` | `company` column + per-company unique keys |
| `81aae9c` | Per-company credentials (`BusyCompanies` registry) |
| `265f44c` | Sync loop over all companies, scoped |
| `2a19d50` | Outstanding filters on the real column |
| `53c26e8` | Company selectors; last hardcoded assumptions removed |
| `3b94071` | Region → City cascade (**unpushed**) |

### Key pieces

**`app/Support/BusyCompanies.php`** — the registry. Credential resolution per company:

```
app_settings.busy_{slug}_username  →  config/busy.php (env)  →  legacy busy_username
                                                                  (Mumbai only)
```

That last step means Mumbai kept working untouched on deploy. `configured()` returns
only companies with a complete pair, so a half-filled setup fetches what it can.

**`BusyApiClient::forCompany($slug)`** returns a **clone**, not a mutation — the client
is container-shared, so a sync loop would otherwise leak the last company into whatever
resolved it next.

**`BusySyncService`** — `sync()` loops `BusyCompanies::configured()`. Scoping goes
through `parties()` and `invoices()`, the two private methods every read already
funnels into, so one `where('company', …)` covers lookups, uniqueness checks and the
cache warm-up together. That is what makes `closeMissingOutstanding()` safe: it pays
off invoices absent from the outstanding response, so unscoped, syncing Gujarat would
have marked **every Mumbai invoice Paid**. `partyCache` is cleared per company.

One company failing does not abandon the rest — each gets its own `busy_sync_logs`
row, errors are collected, and the run throws only if nothing succeeded.

**`Invoice::scopeSalesSeriesOnly()`** — per-company invoice prefixes, from
`config/busy.php`. Used by both the Outstanding and Dashboard services so a third
copy cannot drift.

**Filter keys:** `company` = region (the synced company), `city` = the customer's town.
A link still sending `?city=Gujarat` is read as a region, so bookmarks and the mobile
API keep working.

### Migrations

| File | Adds |
|---|---|
| `2026_10_29_120000_add_company_to_crm_and_apna_tables.php` | `company` to 7 `apna_*` tables + `parties` + `invoices`; rebuilds 5 unique keys; backfills `'mumbai'` |
| `2026_10_29_130000_add_company_to_busy_sync_logs.php` | `company` to `busy_sync_logs` |

Both applied on live.

---

## Operational notes

### Deploy

```bash
git push origin main                 # from Windows
# on cPanel:
cd ~/tpi.turnkeyinfotech.live
git pull origin main
php artisan migrate                  # only when a migration is pending
php artisan optimize:clear           # required after any view or config change
```

### PHP version

The shell defaults to PHP 8.3.36 but Laravel 13 needs 8.4.1+:

```bash
alias php="/opt/cpanel/ea-php84/root/usr/bin/php"
```

### Five pre-existing migrations were marked, not run

`2026_07_25_060000` through `2026_08_04_080000` were Pending but their tables and
columns already existed (created outside the migration system), and they carry no
`hasTable`/`hasColumn` guards — so `migrate` aborted on `Duplicate column name
'enquiry_product_name'`. They were inserted into the `migrations` table as applied.
Expect the same if any further legacy migration surfaces.

### Syncing

```bash
php artisan busy:sync                      # every configured company
php artisan busy:sync --company=gujarat    # one
```

Exits non-zero if any company failed, so a cron wrapper notices a stale credential.
The admin Sync modal has the same picker.

---

## Roles, logins and assignment

Audited in full on 2026-10-29 while planning the telecaller mobile app.

### Two roles, one login, one UI

`users.role` is a plain `VARCHAR` with `default('telecaller')` — **no enum, no DB
constraint** (`0001_01_01_000000_create_users_table.php:18`). Only two values are
recognised anywhere: `admin` and `telecaller`. `User` has `isAdmin()` and nothing else.

Both roles enter through the **same** door and land on the **same** dashboard:

| Surface | Entry | Accepts |
|---|---|---|
| Web | `GET /login` → `AdminAuthController` | username **or** email, both roles → `admin.dashboard` |
| Mobile / API | `POST /api/login` → `ApiToken::issue` | same two roles, no role distinction in the token |

Gatekeepers are `EnsureCrmUser` (web, alias `crm`) and `AuthenticateApiToken` (api).
Both allow `['admin', 'telecaller']` identically. There is no telecaller-only
surface in use.

Seeded accounts (`DatabaseSeeder`) — **every password is `1234`**:

| Username | Role | Branch |
|---|---|---|
| `admin` | admin | All |
| `aakanksha` | telecaller | Mumbai HQ |
| `javed` | telecaller | Bhiwandi Plant |
| `amol` | telecaller | Pune Branch |

The web login's failure message reads *"Invalid username or password. Try admin /
1234."* — it prints the admin credentials to anyone who guesses wrong
(`AdminAuthController.php:48`).

### Creating a telecaller

One path only: admin → **Users / Team** → "Add telecaller" →
`POST /admin/telecallers` → `Admin\TelecallerController::store`.

- Fields: name, `username` (alpha_dash, unique), email (unique), password
  (`min:4`, rendered `type="text"` so it is visible on screen), branch
  (defaults to `'All'`).
- `role` is **hardcoded** to `'telecaller'`. No route or UI creates an `admin` —
  a second admin needs tinker, SQL or the seeder.
- `initials` is derived via `crm_initials()`.
- `update()` accepts **only** a new password. Name, email, username and branch
  are immutable after creation.
- `destroy()` is a plain delete. `calls.telecaller_id` is neither cascaded nor
  nulled, so past calls keep the dead id and `$call->telecaller?->name` renders
  blank in Call History.

All three are correctly guarded with `abort_unless(…->isAdmin(), 403)`.

### Assignment

One place: the **Assign Party** modal on the Users / Team page
(`partials/dashboard/modals.blade.php:178`) → `AdminController::assignParty`.

- `manual` picks a user; `round_robin` indexes by
  `Party::whereNotNull('assigned_to')->count() % telecallers->count()`.
- Writes `parties.assigned_to`, then cascades
  `Invoice::where('party_id', …)->update(['assigned_to' => …])`.
- Invoices created by a **later** sync inherit it —
  `BusySyncService::upsertInvoice()` passes `'assigned_to' => $party->assigned_to`
  on create only (line 473), so a manual reassignment is never clobbered by a
  re-sync. Assignment is a live relation, not a point-in-time stamp.

Limits, all of them UI-level:

- One party per submit. No bulk, no region or city filter.
- The `<select>` renders **every** party from `Party::orderBy('name')->get()`
  with no search box.
- No unassign — the user dropdown has no empty option.
- Round-robin is a modulo over a count, so it skews as soon as anything is
  assigned manually.

### What a telecaller can actually reach

Admin-only, properly gated:

| Action | Guard |
|---|---|
| Create / reset / delete telecallers | `TelecallerController` ×3 |
| BUSY API playground page + calls | `AdminPageController::busyApi`, `BusyApiController` |
| Save WhatsApp (Green API) settings | `AdminController::updateWhatsappSettings` |
| Save Settings | `AdminController::updateSettings` |

Not gated — and `AdminSharedDataService::navItems()` hides only **BUSY API** from
telecallers (line 103), so all of these sit in a telecaller's own sidebar:

| Reachable | Consequence |
|---|---|
| `GET /admin/users` | Full team performance for every telecaller |
| `POST /admin/assign`, `POST /api/assign` | A telecaller can reassign any party — and all its invoices — to any user, or fire round-robin |
| `GET /admin/settings` | `SettingsPageService:26` hands the view `smtpPassword` in **plaintext** — the Gmail App Password of open item 1 |
| `POST /admin/busy-sync` | Any telecaller can trigger a full three-company sync |
| `GET /admin/outstanding/export` | Full CSV, every company, including mobile and email |

BUSY credentials are **not** exposed there: the settings payload carries only
`username` and `hasPassword` per company.

### Visibility — `Invoice::scopeVisibleTo()`  *(Phase 0, 2026-10-30)*

Every list the CRM and the app render now funnels through **one** scope:

```php
Invoice::query()->visibleTo(auth()->user())
```

It applies two rules that were previously wrong or missing:

1. **`salesSeriesOnly()` applies to everyone.** It used to be wrapped in
   `if (auth()->user()?->isAdmin())` in three places, so a telecaller saw the raw
   ~5,050-row Mumbai feed — `JV-` journal and `PV-` payment vouchers included —
   against an admin's 1,916 sales invoices. The role with *less* authority saw
   *more* rows, most with no customer to call.
2. **A scoped telecaller sees only their own region and city.** `region` is a
   `BusyCompanies` slug matched against `invoices.company`; `city` is the
   customer's own town matched against `parties.city`.

Callers: `OutstandingPageService::baseQuery()` and `::citiesByCompany()`,
`DashboardPageService::openInvoices()`, `FollowupPageService::data()`. One scope
means the two rules cannot drift apart across services again.

`User::isScoped()` returns false for admins and for any telecaller with neither
field set — which is what **every account created before the migration looks
like**, so nothing silently went blank on deploy.

---

## Telecaller mobile app (`telecaller_app/`)

**Tegh Telecaller** — a Flutter app (Flutter 3.44.4, Dart SDK ^3.12.2) giving a
telecaller the Outstanding book on a phone with a tap-to-call flow. Android **and**
iOS. Package id `com.teghcables.telecaller`; display name `Tegh Telecaller`.

The backend is almost entirely built already: `routes/api.php` exposes 22
token-authenticated endpoints and `AuthenticateApiToken` accepts the `telecaller`
role. `CrmPayload::invoice()` already returns `mobile`, so tap-to-call needs no new
field.

### Decisions taken (2026-10-29)

| | |
|---|---|
| **"Pending"** | A dial queue, not pending sales orders — `outstanding > 0` and (never called **or** `next_followup_on <= today`). No new table, no new sync. |
| **Scoping** | By the telecaller's **region and city**, assigned when the account is created (see open item 7). This supersedes the earlier "everything like admin" note. |
| **Tabs** | **Home · Outstanding · Customers · History.** Queue is merged into Outstanding as sub-tabs (Pending / Overdue / Promise Given / All) rather than being its own tab. |
| **Dialling** | `tel:` hands off to the phone's own dialer. No `CALL_PHONE` permission, no Play Store friction. |
| **In scope** | WhatsApp and Email reminders, and fix-mobile, as actions on the invoice detail screen. |
| **Out of scope** | Reports, Team, Assign — admin functions, not a telecaller's job on a phone. |
| **Icon** | Text mark only for now; the launcher icon is still Flutter's default until a logo arrives. |

### Architecture

**Modular monolith, MVVM, Riverpod.** Riverpod over BLoC because `AsyncNotifier`
*is* a ViewModel — state plus loading and error built in — and its compile-checked
providers give dependency inversion without a service locator. BLoC would need an
event class per tap; for a list-and-detail CRM that is ceremony, not safety.

```
lib/
  app/          TeghApp, go_router shell, M3 theme + brand palette
  core/         config, money formatting   (no Flutter imports beyond intl)
  shared/       widgets used by more than one feature
  features/
    <feature>/
      domain/       models + the repository INTERFACE
      data/         repository implementations
      presentation/ view-model, screen, widgets
```

How SOLID lands here, concretely:

- **SRP** — the View renders, the ViewModel holds state and commands, the
  Repository does I/O. `HomeScreen` has no `http` import and no business rule.
- **DIP** — `HomeScreen` depends on `HomeRepository`, an `abstract interface
  class`. `homeRepositoryProvider` is the single injection point; swapping
  `SampleHomeRepository` for the HTTP one is a one-line override and no view or
  view-model changes.
- **OCP** — a new bottom-nav tab is one `ShellTab` enum entry plus one router
  branch; `AppShell` never changes. A new invoice status is one line in the
  `InvoiceStatusColor` extension; no widget changes.
- Features never import each other. Anything shared moves up to `shared/`.

### Design system — lifted from Demandium customerapp

The visual language comes from `C:\All Project\Demandium\Project\customerapp`, by
client decision on 2026-10-30. Demandium's own indigo `#4153B3` was replaced by
the client-chosen `#C3A0D9` the same day; orange `#FF9900` secondary on a white
page is unchanged.

**`#C3A0D9` is a light tint, and that drives the whole chrome.** White on it
measures ~2.2:1 contrast — below the 4.5:1 needed for readable text — while
near-black measures 9.4:1. So:

| Token | Value | Used for |
|---|---|---|
| `primary` | `#C3A0D9` | App bar and bottom-bar fill |
| `onPrimary` | `#2E2136` | **All** text and icons on those bars — never white |
| `primaryLight` | `#F1E8F7` | Tints behind icons and pills |
| `primaryDark` | `#7B5595` | Small coloured text, links, filled buttons, the "Promise Given" status |
| `primaryDeep` | `#4E3261` | With `primaryDark`, the hero-card gradient — the one place white text is correct |

Anything new that sits on `primary` must use `onPrimary`, not `Colors.white`.

Token files under `app/theme/`, mirroring Demandium's `util/`:

| File | Holds |
|---|---|
| `dimensions.dart` | `paddingSize*`, `radius*`, `fontSize*` — same names and values |
| `gaps.dart` | `Gaps.vSmall` / `Gaps.hDefault` spacing widgets |
| `styles.dart` | `robotoLight/Regular/Medium/SemiBold/Bold`, `searchBoxShadow`, `cardShadow` |
| `custom_theme_colors.dart` | `ThemeExtension`, read as `context.customThemeColors.subtitle` |
| `app_palette.dart` | The only file with hex values |

Layout patterns copied: `AddressAppBar` → `ScopeAppBar`, `CustomAppBar`,
`HomeSearchWidget` → `QueueSearchBar`, `CategoryView` → `QuickActions` tiles,
`TitleWidget`, the vertical card list, the filled indigo bottom bar and its
docked 70px circular action (their cart, our "call next").

**Three Demandium defects deliberately not reproduced:**

1. Their `pubspec.yaml` declares the Roboto TTFs under the family name `Ubuntu`
   while every style asks for `'Roboto'` — so their bundled font never loads.
   Ours declares `Roboto`.
2. Their `Dimensions` font sizes are static fields reading `Get.context!.width`,
   so the scale freezes at the first window width and never updates. Ours are
   real `const`, with `Dimensions.fontScaleFor(width)` for the desktop step.
3. They are on Material 2. We keep `useMaterial3: true` — every surface is
   explicitly coloured, so it looks the same with modern ripple behaviour.

**Logic stays ours.** Demandium is GetX MVC with a service locator and zero
repository interfaces (0 of 23). This app keeps Riverpod, `abstract interface
class` repositories and `go_router`. Only the presentation layer is theirs.

### Built so far

Home screen, bottom navigation and app bars, with `SampleHomeRepository` standing
in until Phase 3 wires `/api/dashboard`. `flutter analyze` clean, 23 tests passing.

- `app/theme/` — light + dark, Demandium tokens, Roboto in `assets/font/`
- `app/router/app_router.dart` — `StatefulShellRoute.indexedStack`, so each tab
  keeps its own scroll position and stack
- `features/shell/` — `ShellTab` enum + `AppShell` + `AppBottomNav`, content
  capped at 480 logical px so a tablet gets one readable column
- `features/home/` — the full MVVM slice: `HomeSummary`/`PriorityCall`,
  `HomeRepository`, `SampleHomeRepository`, `HomeViewModel`, `HomeScreen`, and
  five widgets (`ScopeAppBar`, `QueueSearchBar`, `OutstandingCard`,
  `QuickActions`, `PriorityCallList`)
- Outstanding / Customers / History are one shared `ComingSoonScreen`

### Verifying a change — the user runs it, not Claude

**Do not launch Chrome to check the UI.** The user builds and runs on a real
device themselves and reports back. Claude verifies with:

```bash
cd telecaller_app
flutter analyze                 # must be clean
flutter test                    # 23 tests: 4 widget, 19 core
```

The widget tests catch real layout faults — a `RenderFlex overflowed` assertion
fails the test, which is how the 11px header overflow, the 66px progress-row
overflow and the zero-height-body bug were all found. That is the check that
matters; a browser screenshot is not.

`web/` exists only as a leftover preview harness and is not a target. Delete it
whenever; nothing imports it.

**After any `pubspec.yaml` font or asset change, a full rebuild is required** —
a hot restart will not pick up new fonts.

### Two Android/iOS facts already handled

`INTERNET` was declared **only** in `android/app/src/debug/AndroidManifest.xml`, so
a release APK would have had no network at all — it is now in `src/main/` along
with a `tel` entry in `<queries>` for Android 11+ package visibility.
`applicationId`, the Kotlin package path, the iOS bundle id and both display names
are off `com.example`.

---

## Build plan — eight phases

Agreed 2026-10-30. `/api/queue` is **not** needed: `/api/dashboard` already returns
every KPI the home screen uses (`total_outstanding`, `overdue`, `due_today_count`,
`ptp_count`, `followups_today`, `completed_followups`) plus today's followups as
full invoice payloads.

| # | Phase | Builds | Endpoints |
|---|---|---|---|
| **0** | Backend prerequisites | ✅ **Done 2026-10-30** — see below | — |
| **1** | Core infrastructure | ✅ **Done 2026-10-30** — see below | — |
| **2** | Splash + login + session | Splash, login, `SessionNotifier`, router redirect, logout, 401 handling | `POST /login`, `GET /me`, `POST /logout` |
| **3** | Home on real data | `HttpHomeRepository` replaces `SampleHomeRepository` — one provider override, no view changes | `GET /dashboard` |
| **4** | Outstanding | List with status/aging/city/company filters, pagination, search | `GET /outstanding` |
| **5** | Invoice detail + actions | Detail, tap-to-call, log a call, PTP, fix mobile, WhatsApp, email | `GET /invoices/{id}`, `POST /calls`, `POST /customers/{p}/mobile`, `POST /whatsapp`, `/whatsapp-preview`, `POST /email` |
| **6** | Customers | Party directory, search, per-party invoices and calls | `GET /customers`, `GET /customers/{party}` |
| **7** | History | Call log — today / week / all, by outcome | `GET /calls`, `GET /ptp` |
| **8** | Release hardening | Skeleton loaders, offline cache, empty/error states, icons, signing | — |

Dependencies: 1 → 2 → everything. Phases 4–7 are independent of each other once 3
lands, so their order is free.

### Phase 0 — what changed  *(2026-10-30, not yet deployed)*

| File | Change |
|---|---|
| `2026_10_30_100000_add_region_and_city_to_users.php` | **New.** `users.region` + `users.city`, both nullable, guarded with `hasColumn`. `branch` deliberately kept — `reports.blade.php` still renders it. |
| `app/Models/User.php` | `region`/`city` fillable; `isTelecaller()` and `isScoped()` added |
| `app/Models/Invoice.php` | **`scopeVisibleTo(?User)`** — the one visibility rule |
| `OutstandingPageService` | `baseQuery()` and `citiesByCompany()` use it; `citiesByCompany()` made **public** so the Users page reuses the same cascade source |
| `DashboardPageService` | `openInvoices()` uses it |
| `FollowupPageService` | `data()` uses it — this one was never filtered at all |
| `TelecallerController` | `store()` takes region + city; `update()` now takes them too and the password became **optional**, so a scope can be changed without resetting the login |
| `StaffPageService` | Injects `OutstandingPageService`, passes `companies` + `citiesByCompany` to the view |
| `users.blade.php` | Region + City selects on the add form and on every telecaller row, the same cascade JS as the Outstanding page, plus a "Scope:" line per telecaller |
| `CrmPayload::user()` | Returns `region`, `region_label`, `city`, `scoped` so the app can show the header chips without sending filters |
| `DatabaseSeeder` | Aakanksha and Javed → `mumbai`, Amol → `pune`. City left null on purpose: towns are derived from address lines and a wrong one would blank the account. |

**Verified by `php -l` only.** This machine has no `vendor/` and runs PHP 8.3.31,
while Laravel 13 needs 8.4.1+ — so `artisan migrate` and the test suite cannot run
here. Same constraint as open item 2.

**Deploy:**

```bash
git pull origin main
php artisan migrate          # 2026_10_30_100000 is pending
php artisan optimize:clear   # users.blade.php changed
```

**Regression check after deploying:** sign in as `aakanksha` (region `mumbai`) and
confirm the Outstanding page shows Mumbai sales invoices only, and that the count
dropped from ~5,050 to ~1,916. Then sign in as `admin` and confirm the total is
still 2,580 across all three companies.

### Phase 1 — core infrastructure  *(2026-10-30)*

Added `http` and `shared_preferences`. Nothing in the UI changed.

```
lib/core/
  config/app_config.dart        apiBaseUrl (--dart-define overridable), requestTimeout
  network/api_exception.dart    sealed ApiException, 8 cases
  network/api_client.dart       ApiClient interface + HttpApiClient
  storage/token_store.dart      TokenStore interface
  storage/prefs_token_store.dart
  providers.dart                sharedPreferences / tokenStore / apiClient
```

- **`ApiException` is sealed**, so a `switch` over it is exhaustive — adding a
  case makes the compiler point at every screen that must handle it. Views
  never see an `http` type or a status code.
- **`HttpApiClient` has three jobs**: attach the bearer token, map transport
  and status failures onto `ApiException`, decode the body. It holds no app
  state. `onUnauthorized` is a callback, so "a 401 signs the user out" stays
  session policy and is wired in Phase 2 — `core/` never imports a feature.
- **Empty and null query values are dropped** rather than sent as `?city=`,
  which Laravel's `!empty()` filter checks would treat as a real filter.
- **`main()` is async** and loads `SharedPreferences` before the first frame,
  overriding `sharedPreferencesProvider`. That is what lets the Phase 2 splash
  decide where to go without a second async hop. The provider throws if read
  without that override — a programming error, not a runtime state.
- `--dart-define=API_BASE_URL=http://10.0.2.2:8000/api` points a device at a
  local Laravel without editing source.

**19 core tests** cover token attachment, query pruning, path joining, every
status mapping (401 fires the callback exactly once, 422 carries Laravel's
field errors), HTML-served-as-200, empty bodies, socket failure and timeout —
all against `MockClient`, so they touch no network and no platform channel.

---

## Open items

1. **Rotate the Gmail App Password.** `qmmo ssvz larm rvna` for `manoj.turnkey@gmail.com`
   was committed in `database/turnkeyi_tpi.sql` and is still reachable in commit
   `2f5d774` on GitHub. Deleting the file did not remove it from history.
   → myaccount.google.com → Security → App passwords.
2. **Run the test suite.** 28 tests exist in `BusyIntegrationTest` and **none have ever
   been run** — `vendor/` is not installed on the dev machine. Everything is verified by
   `php -l`, a standalone harness for the credential chain, and live inspection.
   `php artisan test --filter=BusyIntegrationTest`
3. **Remove the demo account from `.env`** — `BUSY_API_USERNAME=8591595795` /
   `BUSY_API_PASSWORD=12345678`. Harmless now (overridden by `app_settings`) but misleading.
4. **`branch` and `sales_person` are dead.** NULL for all 2,580 rows; Apna Bazar
   supplies neither (`get_outstanding` 7 fields, `get_accounts` 45, none of them
   branch/station/depot/area). `reports.blade.php` still renders a blank Branch
   dropdown. **Needs a client answer:** is branch a BUSY field not exposed in the API,
   or something to maintain manually in the CRM?
5. **Inter-company ledgers.** `TEGH CABLES (INDIA) PVT LTD-PUNE.` sits inside Mumbai's
   books as a customer and is counted in Mumbai's 1,916. It may double-count against
   Pune's own figures. Business call, not a code one.
6. **Invoice prefixes are derived, not confirmed.** `TCIG/` and `TCIPLP` came from
   reading the live API dumps. If either company ever issued bills under another
   series, those rows will not appear. Worth confirming with the client.
7. ~~**Assign a region and city when creating a telecaller.**~~ **Done — Phase 0,
   2026-10-30.** See "Phase 0" below. **Not yet migrated on live.**
   **Still open, and separate:** the five unguarded admin actions in the
   "What a telecaller can actually reach" table. `POST /admin/assign` lets any
   telecaller reassign any party, and `GET /admin/settings` renders the SMTP
   password in plaintext. Gating `/admin/settings` and `/admin/users` also means
   dropping them from `navItems()`, or telecallers get two links that 403.
8. **Default passwords are all `1234`.** Four seeded accounts, `min:4` validation on
   new ones, and `AdminAuthController:48` prints *"Try admin / 1234"* on every failed
   login. Raise the minimum and drop the hint before the mobile app puts this login
   on phones outside the office.
9. ~~**`isTelecaller()` does not exist.**~~ **Done — Phase 0.** Added to `User`
   alongside the new `isScoped()`.
10. ~~**`salesSeriesOnly()` is applied to admins only.**~~ **Done — Phase 0.**
    Folded into `Invoice::scopeVisibleTo()`. **This changes the existing web
    Outstanding and Dashboard pages for telecallers too, not just the app** —
    they will see ~1,916 sales invoices where they used to see ~5,050 rows. That
    is the intended fix, but it is a visible change to a live page.

### Dead code

`app/Services/Apna/` (`ApnaBazarClient`, `ApnaSyncService`, `apna:sync`, the `apna_*`
tables) is a parallel integration that can never run — `config/services.php` has no
`apna` key, so credentials resolve empty and it throws. ~400 lines. `busy:sync` is
canonical. Left in place; removing it is a separate decision.

`app/Http/Controllers/Telecaller/`, `app/Http/Controllers/TelecallerAuthController.php`,
`app/Services/Telecaller/`, `EnsureUserIsTelecaller`, `EnsureUserIsAdmin`,
`layouts/telecaller.blade.php`, `partials/telecaller/` and the six views under
`resources/views/telecaller/pages/` are a **second, unrouted telecaller web portal**.
`routes/web.php` has no telecaller group and `bootstrap/app.php` registers neither
middleware alias. It is also built on a different data model — `companies`, leads and
quotations, not `parties`/`invoices`/outstanding — and it calls `$user->isTelecaller()`,
which does not exist, so it would fatal the moment it were routed. The mobile app is
being built against `routes/api.php` instead; do not graft it onto this.

---

## Verification

```bash
# Raw API rows per company
for u in 9082479995 9136032692 7977861879; do
  echo -n "$u: "
  curl -s -X POST http://api.apnabazarapp.in/WebAPI/V2/get_outstanding.php \
    -d "Username=$u&Password=$u" | grep -o '"ERPCode"' | wc -l
done

# What actually landed
php artisan tinker --execute='
use Illuminate\Support\Facades\DB;
foreach (DB::table("invoices")->selectRaw("company, count(*) c")->groupBy("company")->get() as $r) {
  echo str_pad($r->company ?? "(null)", 12) . $r->c . PHP_EOL;
}'
```

Mumbai's count must stay at its pre-sync value. If it drops, the company scoping has
regressed — that is the single most important regression check for this work.
