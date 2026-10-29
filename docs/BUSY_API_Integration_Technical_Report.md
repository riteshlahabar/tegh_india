# Technical Integration Report
## BUSY Cloud ↔ Tegh Cables Outstanding & Payment Follow-up CRM

| Document | Details |
|----------|---------|
| **Prepared for** | BUSY Software / BUSY Cloud API Team |
| **Prepared by** | Tegh Cables India Pvt. Ltd. – CRM Development Team |
| **Company** | Tegh Cables India Pvt. Ltd. |
| **BUSY Hosting** | BUSY Cloud |
| **CRM Stack** | Laravel (PHP), MySQL |
| **Document type** | API Integration Requirement Specification |
| **Version** | 1.0 |
| **Date** | 24 August 2026 |

---

## 1. Purpose

We are building a custom **Outstanding & Payment Follow-up CRM** for Tegh Cables India Pvt. Ltd.

- **BUSY** will remain the **accounting master** (parties, invoices, receipts, outstanding amounts).
- The **CRM** will remain the **follow-up master** (calls, remarks, payment promises, WhatsApp/email logs, telecaller assignment).

We request **official BUSY Cloud API / web-service access and documentation** so the CRM can sync accounting data on a scheduled basis (and optionally near real-time).

---

## 2. Integration Principles

| Rule | Description |
|------|-------------|
| **Source of truth – amounts** | Invoice amount, received amount, outstanding, due date, cancelled status → **BUSY only** |
| **Source of truth – follow-ups** | Calls, PTP, next follow-up, WhatsApp/email → **CRM only** |
| **No reverse write (Phase 1)** | CRM will **not** create/edit invoices or receipts in BUSY in Phase 1 |
| **Idempotent sync** | Every BUSY record must have a stable unique ID for upsert in CRM |
| **Incremental sync** | Prefer `modified_since` / change feed to avoid full dump every run |

---

## 3. Proposed Architecture

```
BUSY Cloud (Accounting Master)
        │
        │  REST / SOAP / Web Service API
        │  (Auth: API Key / OAuth / Token – as provided by BUSY)
        ▼
CRM Sync Service (Laravel scheduled job)
        │
        ├── Upsert Parties
        ├── Upsert Sales Invoices
        ├── Upsert Bill-wise Outstanding
        └── Upsert Receipts / Adjustments
        ▼
CRM Database (MySQL)
        │
        └── Follow-up module (telecallers)
            Calls · PTP · WhatsApp · Email · Assignment
```

**Sync frequency (preferred):** every 15–30 minutes via cron, or webhook push if BUSY supports it.

---

## 4. Data Required from BUSY

### 4.1 Party / Customer Master

| Field required | CRM field mapping | Mandatory |
|----------------|-------------------|-----------|
| BUSY Party unique ID | `parties.busy_party_id` | **Yes** |
| Party Name | `parties.name` | **Yes** |
| Party Code | `parties.code` | **Yes** |
| Contact Person | `parties.contact_person` | Preferred |
| Mobile Number | `parties.mobile` | Preferred |
| Email ID | `parties.email` | Preferred |
| Address (line / full) | *(extendable)* | Optional |
| City | `parties.city` | Preferred |
| State | `parties.state` | Preferred |
| GSTIN | *(to be added)* | Preferred |
| Salesperson Name | `parties.sales_person` | Preferred |
| Branch / Location | `parties.branch` | Preferred |
| Payment / Credit Terms | `parties.payment_terms` | Preferred |
| Credit Limit | `parties.credit_limit` | Optional |
| Active / Inactive flag | *(status)* | Preferred |
| Last modified timestamp | *(for delta sync)* | **Yes** |

---

### 4.2 Sales Invoice Data

| Field required | CRM field mapping | Mandatory |
|----------------|-------------------|-----------|
| BUSY Invoice unique ID | `invoices.busy_invoice_id` | **Yes** |
| Invoice Number | `invoices.invoice_no` | **Yes** |
| Invoice Date | `invoices.invoice_date` | **Yes** |
| Due Date | `invoices.due_date` | **Yes** |
| PO Number | `invoices.po_no` | Preferred |
| PO Date | *(to be added)* | Optional |
| Party ID (BUSY) | link → `party_id` | **Yes** |
| Invoice Amount (taxable / basic) | `invoices.invoice_amount` *(clarify)* | **Yes** |
| Tax / GST Amount | *(to be added)* | Preferred |
| Total Invoice Amount (incl. tax) | `invoices.invoice_amount` | **Yes** |
| Payment Terms | *(party or invoice level)* | Preferred |
| Salesperson | `invoices.sales_person` | Preferred |
| Branch | `invoices.branch` | Preferred |
| Invoice status (Open / Paid / Cancelled) | `invoices.status` | **Yes** |
| Cancelled flag / Cancel date | *(status)* | Preferred |
| Last modified timestamp | *(delta sync)* | **Yes** |

---

### 4.3 Outstanding / Receivable (Bill-wise) — **Critical**

This is the **core feed** for the CRM Outstanding list.

| Field required | CRM field mapping | Mandatory |
|----------------|-------------------|-----------|
| BUSY Party ID | link party | **Yes** |
| BUSY Invoice / Bill ID | link invoice | **Yes** |
| Invoice Number | reference | **Yes** |
| Invoice Date | reference | **Yes** |
| Due Date | `invoices.due_date` | **Yes** |
| Invoice Amount | `invoices.invoice_amount` | **Yes** |
| Received / Adjusted Amount | `invoices.received_amount` | **Yes** |
| Outstanding Amount | `invoices.outstanding` | **Yes** |
| Overdue Amount | *(derivable if due date given)* | Preferred |
| Days Overdue / Ageing bucket | *(CRM can compute from due date)* | Optional if due date present |
| Bill-wise status (Open / Partial / Closed) | `invoices.status` | **Yes** |
| As-on date of outstanding snapshot | sync metadata | Preferred |

**CRM business rules after sync:**

- Outstanding &gt; 0 → show in Outstanding / Follow-up queue  
- Outstanding = 0 → mark invoice **Paid / Closed** in CRM  
- Outstanding reduced by receipt → update amounts; **do not wipe** CRM call/PTP history  

---

### 4.4 Payment / Receipt Data

| Field required | CRM field mapping | Mandatory |
|----------------|-------------------|-----------|
| BUSY Receipt unique ID | *(receipts table – Phase 1.1)* | **Yes** |
| Receipt Number | | **Yes** |
| Receipt Date | | **Yes** |
| Party ID (BUSY) | | **Yes** |
| Amount Received | | **Yes** |
| Invoice / Bill reference(s) adjusted | | **Yes** |
| Amount adjusted per bill | | **Yes** |
| Payment mode (Cash / Cheque / NEFT / UPI / etc.) | | Preferred |
| Remaining outstanding after adjustment | | Preferred |
| Last modified timestamp | | **Yes** |

---

### 4.5 Change / Status Updates (Event or Delta)

We need a reliable way to detect:

| Event | CRM action |
|-------|------------|
| New invoice created in BUSY | Insert into CRM Outstanding |
| Invoice modified | Update amounts / dates / party |
| Payment / receipt posted | Reduce outstanding; refresh received |
| Invoice fully paid | Mark **Paid / Closed** |
| Invoice cancelled | Mark cancelled; remove from active outstanding |
| Party master updated | Upsert party contact / terms |

**Preferred APIs (any one or combination):**

1. Incremental APIs with `from_datetime` / `modified_since`  
2. Webhooks / callbacks on voucher save (ideal)  
3. Full dump endpoints for initial load + incremental thereafter  

---

## 5. Required Sync Behaviour (Examples)

| BUSY action | Expected CRM result |
|-------------|---------------------|
| New sales invoice saved | Appears in CRM Outstanding automatically |
| Receipt adjusted against invoice | `received_amount` ↑, `outstanding` ↓ |
| Outstanding becomes ₹0 | Invoice marked **Paid / Closed** |
| Invoice cancelled | Removed/hidden from active follow-up queue |
| Party mobile/email updated | CRM party contact updated |

---

## 6. Technical Questions for BUSY Team

Please share documentation / answers for:

1. **API availability on BUSY Cloud** for our company database (REST / SOAP / other)?  
2. **Authentication method** (API key, username/password, OAuth, IP whitelist)?  
3. **How to request API access** (form, email, partner portal, commercial SKU)?  
4. **Endpoints** for:
   - Party master list / get-by-id / modified-since  
   - Sales invoices  
   - Bill-wise outstanding / receivables  
   - Receipts with bill adjustments  
5. **Pagination**, rate limits, max records per call  
6. **Date/time format**, timezone, and multi-company / multi-branch handling  
7. **Sandbox / test company** for integration testing  
8. **Whether push webhooks** are supported on BUSY Cloud  
9. **Commercial terms** (API licence, Cloud add-on, support SLA)  
10. **Sample request/response JSON or XML** for outstanding + receipt adjustment  

---

## 7. Suggested API Contract (Ideal – for discussion)

### Example: Bill-wise Outstanding (conceptual)

```http
GET /api/v1/outstanding?modified_since=2026-08-24T00:00:00&page=1&page_size=200
Authorization: Bearer <token>
```

```json
{
  "data": [
    {
      "busy_party_id": "P-1001",
      "party_code": "TC-2000",
      "party_name": "ABC Electricals Pvt Ltd",
      "busy_invoice_id": "INV-UID-2500",
      "invoice_no": "INV-2500",
      "invoice_date": "2026-07-01",
      "due_date": "2026-07-31",
      "po_no": "PO-9000",
      "invoice_amount": 250000.00,
      "tax_amount": 45000.00,
      "total_amount": 295000.00,
      "received_amount": 50000.00,
      "outstanding_amount": 245000.00,
      "status": "Open",
      "branch": "Mumbai HQ",
      "salesperson": "R. Bhosale",
      "modified_at": "2026-08-24T10:15:00+05:30"
    }
  ],
  "meta": { "page": 1, "total": 1280 }
}
```

### Example: Receipt with bill adjustment (conceptual)

```json
{
  "busy_receipt_id": "RCPT-UID-778",
  "receipt_no": "RCPT-778",
  "receipt_date": "2026-08-20",
  "busy_party_id": "P-1001",
  "amount": 50000.00,
  "payment_mode": "NEFT",
  "adjustments": [
    {
      "busy_invoice_id": "INV-UID-2500",
      "invoice_no": "INV-2500",
      "adjusted_amount": 50000.00
    }
  ],
  "modified_at": "2026-08-20T16:40:00+05:30"
}
```

*(Final field names/endpoints will follow BUSY’s official schema.)*

---

## 8. Security & Compliance

- HTTPS only  
- Credentials stored encrypted in CRM (server-side; not exposed to telecallers)  
- Least-privilege API user (read-only accounting data preferred for Phase 1)  
- Audit log of each sync run (started/finished, records upserted, errors)  
- No storage of unnecessary sensitive payroll/inventory data — **AR / party / invoice / receipt only**

---

## 9. Implementation Phases

| Phase | Scope |
|-------|-------|
| **Phase 0** | API access + docs + sample calls on Tegh’s BUSY Cloud company |
| **Phase 1** | Pull: Parties + Bill-wise Outstanding + Invoices (scheduled sync) |
| **Phase 1.1** | Pull: Receipts with bill adjustments; Paid/Cancelled status |
| **Phase 2** | Delta sync / webhooks; harden error handling & monitoring |
| **Phase 3 (optional)** | CRM → BUSY write-back (e.g. notes) — only if BUSY supports & approved |

---

## 10. What We Need from BUSY Immediately

1. Complete **API / integration documentation** for BUSY Cloud  
2. **Process to enable API access** for Tegh Cables’ cloud company  
3. Confirmation of endpoints covering **Party, Invoice, Outstanding, Receipt**  
4. Sample credentials or sandbox if available  
5. Contact person for technical support during integration  

---

## 11. Ready-to-Send Cover Email

```
Subject: API Integration Request – BUSY Cloud Outstanding Sync for Tegh Cables CRM

Hello BUSY Team,

We are developing a custom Outstanding & Payment Follow-up CRM for
Tegh Cables India Pvt. Ltd. Our accounting data is hosted on BUSY Cloud,
and we want to integrate BUSY with our CRM through API / web services.

BUSY will remain the accounting master. The CRM will only manage
follow-up activities (calls, remarks, payment promises, WhatsApp/email).
Invoice amounts and outstanding balances must sync from BUSY and must
not be manually editable in the CRM.

We require the following data from BUSY:

1. Party / Customer Master
   – Party Name, Code/ID, Contact Person, Mobile, Email, Address,
     City/State, GSTIN, Salesperson, Payment/Credit Terms

2. Sales Invoice Data
   – Invoice No., Invoice Date, Due Date, PO No. & PO Date,
     Party ID, Invoice Amount, Tax/GST, Total Amount, Payment Terms, Branch

3. Outstanding / Receivable (bill-wise) – critical
   – Invoice-wise outstanding, received amount, due amount,
     overdue/ageing, due date, bill-wise status

4. Payment / Receipt Data
   – Receipt No./Date, Party ID, Amount, Invoice adjustment references,
     Payment mode, remaining outstanding

5. Status / Change updates
   – New, modified, paid, cancelled invoices and receipt postings

Required sync behaviour:
• New invoice in BUSY → appears in CRM Outstanding
• Payment in BUSY → outstanding updates in CRM
• Outstanding = 0 → CRM marks invoice Paid/Closed

Please share:
• Complete API/integration documentation for BUSY Cloud
• Process to get API access for our company
• Available endpoints, auth method, rate limits, and sample payloads
• Whether incremental sync (modified_since) or webhooks are supported

A detailed Technical Integration Report (v1.0) is attached.

Thank you.
Tegh Cables India Pvt. Ltd. – CRM Development Team
```

---

## 12. CRM Internal Field Keys (for our developers)

Already provisioned in CRM for BUSY linkage:

- `parties.busy_party_id`
- `invoices.busy_invoice_id`
- Settings: `busy_status`, `busy_last_sync`

Sync job should **upsert by BUSY IDs**, never overwrite CRM-owned fields:

- `assigned_to`, `last_followup_at`, `next_followup_on`
- Calls, Payment Promises, WhatsApp, Email logs

---

**End of document**
