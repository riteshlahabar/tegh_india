# Tegh India CRM — Apna Bazar / BUSY Multi-Company Integration

**Live:** https://tpi.turnkeyinfotech.live · **Local:** `laravel project/` (Laravel, PHP 8.2, cPanel)
**Investigated:** 2026-10-29 — all findings below verified against live API + live site. Read-only; nothing changed.

---

## The problem

Only Mumbai data appears in the CRM. Gujarat and Pune are missing.

## Root cause

**The CRM stores exactly one Apna Bazar credential, and the API has no company parameter.**
One login = one company's books. The configured login is Mumbai's, so only Mumbai is ever fetched.

- `AppSetting` holds a single `busy_username` / `busy_password` / `busy_base_url` triple.
- `BusyApiClient::username()` / `password()` read that one pair — no company argument anywhere.
- All 32 endpoints send only `Username` + `Password`. `get_outstanding` takes **zero** params.
- `grep` for `Comp0002|Comp0009|Comp0012|company_code` across the project = **0 hits**.

The company is implied by *which login you authenticate as*. There is no field to pass a company code in.

---

## Verified credentials (all three work)

Username = password = the mobile number. Same base URL for all three.

| Region | Company | Username | Password | Outstanding rows | Parties |
|---|---|---|---|---|---|
| Mumbai | Comp0002 | `9082479995` | `9082479995` | 5,050 | 707 |
| Gujarat | Comp0009 | `9136032692` | `9136032692` | 2,111 | 127 |
| Pune | Comp0012 | `7977861879` | `7977861879` | 2,780 | 146 |

**Base URL:** `http://api.apnabazarapp.in/WebAPI/V2` (plain HTTP, shared by all three)

**Currently missing from the CRM: 4,891 rows / 273 parties.**

### Request format — important

The API requires **form-encoded** or **multipart**. A JSON body returns a misleading
`{"statuscode":400,"statusresponse":"unauthorized user details"}` — it says "unauthorized"
when it actually means "I didn't see your fields." Query-string also fails.

```bash
# WORKS
curl -s -X POST http://api.apnabazarapp.in/WebAPI/V2/get_outstanding.php \
  -d "Username=9136032692&Password=9136032692"

# ALWAYS 400 (even with correct credentials)
curl -s -X POST ... -H "Content-Type: application/json" -d '{"Username":"...","Password":"..."}'
```

The app code is already correct here — both `BusyApiClient` and `ApnaBazarClient` use `asForm()`.
This note is for manual testing in Postman/curl.

**Postman:** the Cloud Agent cannot reach `http://`. Use the Desktop Agent or the desktop app.

---

## The "city" filter is fake

`OutstandingPageService.php:70-83` filters on a **hardcoded whitelist of customer city names**
in `parties.city` — the customer's own address, not the source company:

```php
if ($city === 'Mumbai')       $q->whereIn('city', ['Mumbai','Navi Mumbai','Thane','Palghar','Bhiwandi']);
elseif ($city === 'Gujarat')  $q->whereIn('city', ['Ahmedabad','Surat','Vadodara', ...]);
elseif ($city === 'Pune')     $q->where('city', 'Pune');
```

So the current 1,916 rows (Mumbai 796 / Gujarat 551 / Pune 128 / untagged 441) are **all
Mumbai-company data**, split by customer address. Proof: the top CRM "Pune" row is
`TEGH CABLES (INDIA) PVT LTD-PUNE.` — an **inter-company ledger inside the Mumbai books**.

The `get_outstanding` response carries no city/branch/company field at all:
`ERPCode, Inv_Date, Inv_No, Account_Name, Inv_Amount, Outstanding_Amt, DueDate`

The `branch` dropdown on the Outstanding page renders empty.

---

## ⚠️ ERP code collisions are real

BUSY restarts ERP codes per company. Measured across the three live datasets:

```
MUM vs GUJ:  8 shared ERP codes —  8 are DIFFERENT parties
MUM vs PUN: 13 shared ERP codes — 12 are DIFFERENT parties
GUJ vs PUN:  0 overlap

ERP 153:  MUM = POLYCAB INDIA LTD RECEIVABLE A/C   ||  GUJ = YCube Engineered Solutions Pvt Ltd.
ERP 2229: MUM = Vashi Integrated Solutions Limited ||  GUJ = SOLEX GREEN ENERGY PVT LIMITED
ERP 4783: MUM = MAHADHAN AGRITECH LIMITED          ||  PUN = Veda Engineering Pvt Ltd
```

`apna_accounts.erp_code` is `unique()` with no company column. **Syncing Gujarat today would
silently rename Polycab to YCube and reattach its invoices.** The company column is mandatory
*before* any multi-company sync runs.

---

## Two parallel integrations exist — one is dead

| | Busy (live) | Apna (broken) |
|---|---|---|
| Client | `app/Services/Busy/BusyApiClient.php` | `app/Services/Apna/ApnaBazarClient.php` |
| Sync | `BusySyncService.php` (417 lines) | `ApnaSyncService.php` (391 lines) |
| Command | `busy:sync` | `apna:sync` |
| Tables | `parties`, `invoices` | `apna_*` |
| Credentials | `AppSetting` → `config/busy.php` | `config('services.apna.*')` |
| Status | **working** | **`config/services.php` has NO `apna` key** → empty credentials → throws |

`ApnaBazarClient::__construct()` **already accepts** `$baseUrl, $username, $password` as
parameters. It is the one piece already multi-company-ready.

**Decide which integration is the real one before building.** Keeping both doubles the work.

### Credential sources disagree

```
.env:46-47   BUSY_API_USERNAME=8591595795  /  PASSWORD=12345678   <- DEMO account
AppSetting   busy_username=9082479995                             <- Mumbai (wins)
```

`BusyApiClient::username()` is `AppSetting::getValue(...) ?: config(...)`, so the DB wins.
The demo account is live and returns fake data (`New Test Account`). **Pick one source.**

---

## Change list

### 1. Migration — do this first ⚠️
`database/migrations/` — new file. Blocks everything else.

- Add `company` (string, nullable, indexed) to: `apna_accounts`, `apna_items`, `apna_groups`,
  `apna_outstandings`, `apna_vouchers`, `apna_pending_orders`, `apna_sync_logs`, `parties`, `invoices`
- Rebuild unique keys:
  - `apna_accounts`: `unique('erp_code')` → `unique(['company','erp_code'])`
  - `apna_groups`: `unique(['kind','erp_code'])` → `unique(['company','kind','erp_code'])`
  - `apna_vouchers`: `unique(['kind','voucher_id'])` → `unique(['company','kind','voucher_id'])`
- Backfill: `UPDATE ... SET company='mumbai' WHERE company IS NULL` — **all existing rows are Mumbai**

Source schema: `2026_09_29_180000_create_apna_tables.php` lines 13, 59, 87.

### 2. Credentials → per company
- `config/busy.php` — replace flat `username`/`password` with a `companies` map (3 entries).
  Keep `base_url` shared.
- `.env` — 3 pairs (`BUSY_MUMBAI_USER/PASS`, `BUSY_GUJARAT_*`, `BUSY_PUNE_*`), or drop env
  entirely and use 3 DB rows. **One source, not both.**
- `AdminController.php:285-287, 307-316` — `updateSettings()` credential block becomes per-company.

### 3. `BusyApiClient.php`
- `username()` / `password()` (near EOF) — accept a company argument instead of reading the
  single AppSetting pair.
- `asForm()` is already correct — no change needed.

### 4. `BusySyncService.php` (417 lines) — the real work
- `sync()` :24 — wrap in a loop over the 3 companies
- `upsertParty()` :211-253 — stamp `company`; scope the `busy_party_id`/`code` lookup :230-231
- `upsertInvoice()` :274-357 — stamp `company`; scope lookups :287-288
- `closeMissingOutstanding()` :185-197 — **must be scoped per company**, or syncing Gujarat
  will mark every Mumbai invoice as paid
- `warmPartyCache()` :99-107 — cache key must include company
- `uniquePartyCode()` :386 / `uniqueInvoiceNo()` :397 — scope per company

### 5. `OutstandingPageService.php:70-83`
Replace the hardcoded city-name whitelist with `where('company', $filters['city'])`.
Also fix the empty `branch` dropdown.

### 6. Remaining
- `BusyApiController.php` (49 lines) + `routes/web.php:44-45` — company selector on the API test page
- `app/Console/Commands/SyncBusyDataCommand.php` — add `--company=`
- `app/Models/Party.php`, `Invoice.php` — add `company` to `$fillable`
- Views `resources/views/admin/pages/` — company dropdown on BUSY page; 3 credential rows in settings
- `AdminController::syncBusy():252-266` — per-company counts in the result message
- `AdminController::exportOutstanding():321` — add `company` to the filter list
- `tests/Feature/BusyIntegrationTest.php` — asserts single credentials at :60-62; update

### 7. One-time data cleanup
Existing 1,916 rows are Mumbai-sourced but 551 are tagged Gujarat and 128 Pune by address
guessing. Tag everything `mumbai` in the migration, then sync the other two.

**Estimate: 1–2 days** including testing against all three live logins.

---

## Decisions

1. **Canonical integration: `busy:sync`** (decided 2026-10-29). `app/Services/Busy/` gets the
   multi-company work. `app/Services/Apna/` stays dead — do not build on it.
2. **Company slugs:** `mumbai`, `gujarat`, `pune` (not `Comp0002` etc. — those come from the
   client's note, never from the API, so nothing can validate them).

## Progress

- [x] **Step 1 — migration written**: `2026_10_29_120000_add_company_to_crm_and_apna_tables.php`
      — syntax checked, **NOT run**. Needs review before `php artisan migrate` on live.
- [x] **Steps 2 & 3 — per-company credentials + company-aware client**
  - `app/Support/BusyCompanies.php` (new) — the registry. Resolution order per company:
    `app_settings.busy_{slug}_username` → `config/busy.php` (env) → for Mumbai only,
    the legacy `busy_username`/`busy_password` keys. **Mumbai keeps working untouched
    on deploy**, since that is where its credentials sit today.
  - `config/busy.php` — `companies` map + `default_company`; legacy keys retained.
  - `BusyApiClient` — `forCompany($slug)` returns a *clone*, so binding cannot leak
    into the container-shared instance. `company()` exposes the current binding.
    Unconfigured companies throw naming the company and the env vars to set.
  - `AdminController::updateSettings()` — accepts `busy_credentials[{slug}][username|password]`;
    unknown slugs ignored; a blank password keeps the stored one.
  - `SettingsPageService` + `settings.blade.php` — one credential block per company,
    marked "not configured" when incomplete. Passwords are never echoed back.
  - `BusyIntegrationTest` — updated to the new config shape, plus 4 new cases
    (per-company send, no cross-company leak, unconfigured company named in the error).
  - Verified: 20/20 on a standalone harness of the resolution chain (no `vendor/` locally).
- [x] **Step 4 — sync loop + company scoping**
  - `sync()` now loops `BusyCompanies::configured()`, delegating one pass to
    `syncCompany()`. One company failing no longer abandons the rest — each gets its
    own `busy_sync_logs` row and the errors are collected; the run only throws when
    nothing succeeded. `busy_status` becomes Connected / Partly connected / Error.
  - **`parties()` and `invoices()` are the single chokepoint** — every read in the
    service goes through them, so adding `->where('company', $this->company)` there
    covers lookups, uniqueness checks and the cache warm-up at once. This is what
    makes `closeMissingOutstanding()` safe: unscoped, syncing Gujarat would have
    marked every Mumbai invoice Paid.
  - `partyCache` is cleared per company — party codes repeat across companies, so a
    cache warmed for one would resolve another's rows.
  - `upsertParty()` / `upsertInvoice()` stamp `company` on create.
  - New migration `2026_10_29_130000_add_company_to_busy_sync_logs.php` — the first
    migration covered `apna_sync_logs`, but `busy_sync_logs` is the table actually
    written.
  - `busy:sync --company=` added; prints a per-company table and exits non-zero if
    any company failed, so cron notices a stale credential.
  - `AdminController::syncBusy()` reports the per-company breakdown and flags
    partial success as an error rather than a clean run.
  - `Party`, `Invoice`, `BusySyncLog` — `company` added to `$fillable`.
  - 6 new tests, including same-ERP-code-two-companies and the cross-company
    close-out guard. Suite now 18 tests; **not run locally** (no `vendor/`).
- [x] **Step 5 — Outstanding filters on the real column**
  - `OutstandingPageService` region filter → `where('company', …)`; a city name that
    is not a company still falls through to the old party-address match.
  - **Second blocker found:** `baseQuery()` limited admins to `invoice_no LIKE
    'TCIPL%'`. Each company numbers its own bills — Mumbai `TCIPL/` (1,607),
    Gujarat `TCIG/` (319), Pune `TCIPLP` (345) — so every Gujarat invoice would
    have synced and then been invisible. Pune matched only by accident.
    Prefixes moved to `config/busy.php` per company.
  - Dropdown built from configured companies; label "City" → "Region". Request key
    stays `city`, so existing links and the mobile API keep working.
- [x] **Step 6 — company selectors and the last hardcoded assumptions**
  - `Invoice::scopeSalesSeriesOnly()` — the prefix logic extracted to the model.
    `DashboardPageService:100` had the **same** `TCIPL%` filter; both call the scope
    now so a third copy cannot drift.
  - `BusyApiController` takes `company`, validates it, and strips it before
    forwarding — Apna Bazar has no such field. Response echoes the company used.
  - BUSY API page gains a "Which company?" picker; unconfigured companies are listed
    but disabled.
  - Sync modal gains a company picker ("Every configured company" by default), shared
    across admin pages via `AdminSharedDataService`.
  - Audited for other hardcoded assumptions: `BusyPayload::city()` legitimately
    derives the *customer's* city and is unchanged; the `Bhiwandi` string in
    `SettingsPageService` is a default office address, not a filter.
  - Suite now 25 tests; **still not run** (no `vendor/` locally).
- [x] **Step 7 — Region → City cascade on Outstanding**
  - **Branch is dead data.** `invoices.branch` is NULL for all 2,580 rows and the
    dropdown rendered 0 options, because Apna Bazar has no branch field:
    `get_outstanding` returns 7 fields and `get_accounts` 45, none of them
    branch/station/depot/area. Sales Person is empty for the same reason.
  - Replaced the dead Branch dropdown with **City**, the only real geography the
    CRM holds — `BusyPayload::city()` reads the town from the party's address
    lines. Picking a region narrows the city list to that company's towns.
  - **Filter keys:** `company` = region (the synced company), `city` = the
    customer's town. An old link or mobile-API call sending `?city=Gujarat` is
    still read as a region, so bookmarks keep working.
  - A city left selected from a previous region is cleared rather than silently
    applying a filter the user can no longer see.
  - `company` added to the filter allowlists in `AdminPageController`,
    `CrmApiController` and `AdminController::exportOutstanding`.
  - Suite now 28 tests.

### Still dead, flagged not fixed

`branch` and `sales_person` are empty everywhere and the API supplies neither.
`reports.blade.php` still renders a Branch dropdown off `AdminSharedDataService`,
which will also be empty. Needs a client answer on where branch should come from.

### Credential source — decided

`AppSetting` is authoritative, `config/busy.php` is the fallback. Admins change
credentials from the settings page without a deploy. The stale demo account in
`.env:46-47` (`8591595795`/`12345678`) is now only a last-resort default for Mumbai
and is overridden by the stored `busy_username`; it should be removed from `.env`.

## Open questions

1. **Credential source** — `.env` or `AppSetting`? They currently disagree (demo vs Mumbai).
2. **Inter-company ledgers** — `TEGH CABLES (INDIA) PVT LTD-PUNE.` appears as a customer inside
   Mumbai's books. Exclude these from outstanding totals, or they'll double-count.

## Verification commands

```bash
# Per-region row counts
for u in 9082479995 9136032692 7977861879; do
  echo -n "$u: "
  curl -s -X POST http://api.apnabazarapp.in/WebAPI/V2/get_outstanding.php \
    -d "Username=$u&Password=$u" | grep -o '"ERPCode"' | wc -l
done
```
