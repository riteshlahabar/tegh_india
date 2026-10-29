# Tegh India CRM — Apna Bazar / BUSY Multi-Company Integration

**Live:** https://tpi.turnkeyinfotech.live · **Server:** `turnkeyi@s16425:~/tpi.turnkeyinfotech.live`
**Repo:** github.com/riteshlahabar/tegh_india · **Stack:** Laravel 13, PHP 8.4, MySQL, cPanel
**Last updated:** 2026-10-29

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

### Dead code

`app/Services/Apna/` (`ApnaBazarClient`, `ApnaSyncService`, `apna:sync`, the `apna_*`
tables) is a parallel integration that can never run — `config/services.php` has no
`apna` key, so credentials resolve empty and it throws. ~400 lines. `busy:sync` is
canonical. Left in place; removing it is a separate decision.

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
