# Mutual Loan Fund — Final V1 PRD

## 0. Agent instructions

Build a production-quality Laravel application from this specification.

Priorities:

1. Financial correctness.
2. Tenant isolation.
3. Double-entry accounting.
4. Auditability.
5. Server-side rendering.
6. Minimal dependencies.
7. Minimal UI and URL structure.

Do not invent features that are not required here.

When a requirement is ambiguous, preserve the accounting and security invariants rather than introducing complexity.

---

# 1. Product

A private web application for operating mutual loan funds between groups of people.

A group is an independent fund.

Members can:

* Join a group.
* Contribute money.
* Request loans.
* Repay loans.
* Register external wallets.
* View the group's financial activity.

Administrators can:

* Manage membership.
* Manage treasuries.
* Verify transactions.
* Approve loans.
* Manage accounting.
* Manage exchange rates.
* Reconcile external balances.
* Inspect the audit trail.

The application does not custody cryptocurrency.

---

# 2. Core concepts

```text id="2f1e4a"
Account
    global identity

Group
    tenant / independent fund

Membership
    account's relationship with a group

Treasury
    where assets are held

Wallet
    external wallet address

Transaction
    business financial event

Loan
    member obligation to the fund

Ledger Account
    accounting classification

Cost Center
    accounting attribution dimension

Journal Entry
    accounting representation of a transaction

Journal Line
    debit/credit entry

Exchange Rate
    global valuation/reference data

18K Gold Gram
    application's common valuation unit
```

---

# 3. Technology

Use:

```text id="r4kj1b"
PHP
Laravel
PostgreSQL
Blade
Tailwind CSS
```

Use Laravel's built-in:

* Authentication
* Sessions
* Policies
* Form Requests
* Validation
* Eloquent
* Database transactions
* Storage
* Scheduler

Do not use:

* React
* Vue
* Inertia
* Livewire
* Alpine.js
* SPA architecture
* WebSockets
* client-side state management
* frontend API architecture

The application must function with JavaScript disabled.

---

# 4. HTTP model

Use standard server-rendered HTTP.

```text id="e9u4sq"
GET
 ↓
Blade

POST
 ↓
Validate
 ↓
Authorize
 ↓
Database transaction
 ↓
Redirect
 ↓
GET
```

Use Post/Redirect/Get.

Do not use AJAX for core operations.

---

# 5. URL design

Keep URLs minimal.

Use:

```text id="k6z1de"
/login

/
/g/create
/g/{group}

/g/{group}/members
/g/{group}/members/requests

/g/{group}/wallets
/g/{group}/treasuries

/g/{group}/transactions
/g/{group}/transactions/{transaction}

/g/{group}/loans
/g/{group}/loans/create
/g/{group}/loans/{loan}

/g/{group}/ledger
/g/{group}/accounts
/g/{group}/cost-centers

/g/{group}/reports
/g/{group}/audit

/exchange-rates
```

Do not use `/groups/`.

The canonical group route prefix is:

```text
/g/{group}
```

---

# 6. Global accounts

An account represents a person.

```text id="w6h8l3"
users

id
name
email
password
email_verified_at
status
created_at
updated_at
```

Statuses:

```text id="t7p9g1"
active
suspended
```

Authentication is global.

An account may belong to multiple groups.

---

# 7. Groups

A group is the tenant boundary and represents one independent mutual loan fund.

```text id="c3w7p0"
groups

id
name
slug
description
status
created_by
created_at
updated_at
```

Statuses:

```text id="m4j8s2"
active
suspended
```

All financial data must belong to exactly one group.

---

# 8. Tenant isolation

This is a critical security requirement.

Every group-owned entity must contain `group_id`.

Examples:

```text id="n2x8q7"
group_memberships
wallets
treasuries
transactions
contributions
loans
loan_installments
chart_of_accounts
accounts
cost_centers
journal_entries
audit_logs
```

Never trust a browser-supplied `group_id`.

For every group request:

```text id="g9v3c2"
authenticated user
        ↓
group exists
        ↓
active membership
        ↓
correct role
        ↓
object belongs to group
```

A user must never be able to access another group's data by changing an ID.

---

# 9. Membership

Accounts request membership in groups.

Lifecycle:

```text id="z8d4q1"
requested
    ↓
approved
    ↓
active
```

Other states:

```text id="v5m2s8"
rejected
suspended
removed
```

A non-member cannot access group financial data.

---

# 10. Group roles

V1:

```text id="u4j6x2"
member
admin
```

Role belongs to the membership.

Example:

```text id="a8p1e6"
Mohammad
 ├── Fund A → admin
 └── Fund B → member
```

---

# 11. Member permissions

Members can:

* View dashboard.
* View activity.
* View treasuries.
* View members.
* Register wallets.
* Submit contributions.
* Request loans.
* Submit repayments.
* Submit transaction evidence.
* View exchange rates.
* View their own financial position.

Admins can additionally:

* Approve members.
* Suspend members.
* Create/manage treasuries.
* Verify transactions.
* Approve/reject loans.
* Manage chart of accounts.
* Manage cost centers.
* Enter exchange rates.
* Create accounting adjustments.
* Reconcile treasuries.
* View audit logs.

---

# 12. Wallets

The application is **non-custodial**.

Never store:

* Private keys.
* Seed phrases.
* Wallet passwords.

A wallet belongs to a group membership.

```text id="p3m8x7"
wallets

id
group_id
membership_id
currency
network
address
label
status
created_at
updated_at
```

Statuses:

```text id="j8v1c5"
active
inactive
```

Members may have multiple wallets.

---

# 13. Initial cryptocurrency

V1 prioritizes:

```text id="y4k2m8"
USDT
```

The network is a separate field.

Example:

```text id="n7x5r3"
currency = USDT
network = TRON
```

Do not hard-code USDT to a network.

The treasury configuration determines which network is used.

---

# 14. Treasuries

A treasury represents where the group's assets are held.

V1:

```text id="s8k4p2"
crypto
bank
```

Examples:

```text id="w2n9d5"
USDT Treasury
IRT Bank
```

Fields:

```text id="m7c3x8"
treasuries

id
group_id
name
type
currency
network nullable
external_identifier
status
created_at
updated_at
```

For crypto:

```text id="q5v9a1"
external_identifier = public wallet address
network = TRON
currency = USDT
```

No private key is stored.

---

# 15. Transactions

A transaction represents a business financial event.

```text id="e6t2r8"
transactions

id
group_id
treasury_id nullable
member_id nullable
type
direction
amount
currency
status
reference nullable
description nullable
verified_by nullable
verified_at nullable
created_by
created_at
updated_at
```

Types:

```text id="h4q9s1"
contribution
loan_disbursement
loan_repayment
treasury_transfer
treasury_exchange
adjustment
```

Statuses:

```text id="x3m7v5"
pending
verified
rejected
```

Only verified transactions become financially effective.

---

# 16. Double-entry accounting

The General Ledger is the authoritative financial source of truth.

Never make a mutable balance field the source of truth.

Every financial event must create a balanced journal entry.

Invariant:

```text id="b8c2k7"
SUM(debits) = SUM(credits)
```

For every posted journal entry.

---

# 17. Chart of Accounts

Each group has its own chart of accounts.

Categories:

```text id="j4v8m1"
ASSET
LIABILITY
EQUITY
INCOME
EXPENSE
```

Example:

```text id="q6s3n9"
1000 Assets

1100 USDT Treasury
1200 IRT Bank
1300 Other Cash
1400 Loans Receivable

2000 Liabilities

2100 Member Payables

3000 Equity

3100 Fund Capital
3200 Retained Results

4000 Income

4100 Loan Interest Income
4200 Other Income

5000 Expenses

5100 Network Fees
5200 Bank Fees
5300 Other Expenses
```

Accounting treatment of member contributions/equity must remain configurable according to the legal/accounting structure of the fund.

---

# 18. Ledger accounts

```text id="p8d2w6"
accounts

id
group_id
code
name
type
currency
parent_id nullable
is_system
is_active
created_at
updated_at
```

Do not create member-specific ledger accounts.

Use cost centers for member attribution.

---

# 19. Cost centers

Cost centers are first-class accounting dimensions.

```text id="k7r2m9"
cost_centers

id
group_id
code
name
description
parent_id nullable
member_id nullable
status
created_at
updated_at
```

Example:

```text id="x4n8p2"
CC-001 Mohammad
CC-002 Ali
CC-003 Sara
```

A cost center does not own money.

It identifies who/what financial activity belongs to.

---

# 20. Member cost centers

When an active member is created, automatically create a cost center for them.

Example:

```text id="c9v5j3"
Member:
Mohammad

Cost Center:
CC-001
```

The cost center remains historically stable even if the member's name changes.

---

# 21. Journal entries

```text id="m3q8x6"
journal_entries

id
group_id
entry_number
transaction_id nullable
entry_date
posting_date
description
status
created_by
posted_by nullable
posted_at nullable
created_at
```

Statuses:

```text id="t5n7b2"
draft
posted
reversed
```

Only posted entries affect official balances.

---

# 22. Journal lines

```text id="f8w3k1"
journal_lines

id
journal_entry_id
account_id
cost_center_id nullable
currency
debit
credit
exchange_rate_snapshot nullable
gold_value_snapshot nullable
description
```

Rules:

```text id="y6p4d8"
debit >= 0
credit >= 0

debit > 0 XOR credit > 0
```

Every posted journal entry must balance.

---

# 23. Cost-center requirement

Whether a journal line requires a cost center is defined by the ledger account/template.

Examples:

```text id="z9m2q5"
Loans Receivable
→ cost center required

Loan Interest Income
→ cost center required

USDT Treasury
→ cost center not required

Network Fees
→ optional
```

---

# 24. Contribution accounting

Mohammad contributes:

```text id="v4k8s1"
500 USDT
```

Journal:

```text id="h2p7x9"
Debit
1100 USDT Treasury
500 USDT
Cost Center: none

Credit
3100 Fund Capital
500 USDT
Cost Center: Mohammad
```

The exact equity/liability classification must follow the fund's accounting policy.

---

# 25. Loan disbursement accounting

Mohammad receives:

```text id="r8m3x5"
2,000 USDT
```

Journal:

```text id="q1v6n9"
Debit
1400 Loans Receivable
2,000 USDT
Cost Center: Mohammad

Credit
1100 USDT Treasury
2,000 USDT
Cost Center: none
```

The fund now owns a receivable from Mohammad.

---

# 26. Loan repayment accounting

Mohammad repays:

```text id="p5k9w2"
500 USDT principal
```

Journal:

```text id="x8c3m7"
Debit
1100 USDT Treasury
500 USDT
Cost Center: none

Credit
1400 Loans Receivable
500 USDT
Cost Center: Mohammad
```

---

# 27. Interest accounting

If Mohammad pays:

```text id="s7n4q2"
500 USDT principal
100 USDT interest
```

Journal:

```text id="w3k8p1"
Debit
1100 USDT Treasury
600 USDT

Credit
1400 Loans Receivable
500 USDT
Cost Center: Mohammad

Credit
4100 Loan Interest Income
100 USDT
Cost Center: Mohammad
```

Interest recognition timing must follow the fund's accounting policy.

---

# 28. Network fee

Example:

```text id="n5x2c8"
2 USDT fee
```

Journal:

```text id="r7m1v4"
Debit
5100 Network Fees
2 USDT

Credit
1100 USDT Treasury
2 USDT
```

---

# 29. Treasury exchanges

A treasury exchange must be represented by balanced accounting entries.

Example:

```text id="b4q9w6"
1,000 USDT
        ↓
exchange
        ↓
920,000,000 IRT
```

The accounting must capture:

* Source asset decrease.
* Destination asset increase.
* Actual execution rate.
* Fees.
* Exchange gain/loss when applicable.

Never directly modify treasury balances.

---

# 30. Accounting service

Create one accounting service.

Suggested namespace:

```text id="f2m8q4"
App\Domain\Accounting\
```

Core services/classes:

```text id="y7n3k1"
AccountingService
PostingService
JournalEntry
JournalLine
Account
CostCenter
```

Responsibilities include:

```text id="c8v5m2"
createJournalEntry()
postJournalEntry()
reverseJournalEntry()
validateBalanced()
calculateAccountBalance()
calculateCostCenterBalance()
```

Business services must use the accounting layer.

Controllers must never construct journal entries directly.

---

# 31. Accounting templates

Financial events use predefined accounting templates.

Examples:

```text id="d4x9p7"
Contribution
LoanDisbursement
LoanRepayment
InterestPayment
TreasuryExchange
TreasuryTransfer
Fee
Adjustment
Reversal
```

Templates determine:

* Accounts.
* Debit/credit direction.
* Currency.
* Cost-center requirement.
* Gold valuation.
* Exchange-rate behavior.

---

# 32. Immutable ledger

Posted journal entries cannot be:

* Edited.
* Deleted.
* Reused.

Corrections are performed using reversal/adjustment entries.

Example:

```text id="m9q3w7"
Original
    ↓
Reversal
    ↓
Correct entry
```

The entire history remains visible.

---

# 33. Accounting periods

```text id="v2k8n4"
accounting_periods

id
group_id
year
month
status
closed_at
closed_by
```

Statuses:

```text id="c5m7x1"
open
closed
```

Closed periods cannot receive normal postings.

Corrections are posted in a new open period.

---

# 34. Functional currency

Each group has a configurable functional/reporting currency.

Example:

```text id="j8p2r5"
IRT
```

or:

```text id="s4m9v7"
USD
```

This is separate from the 18K gold valuation layer.

---

# 35. Global exchange rates

Exchange rates are global application data.

They do not contain `group_id`.

The reference asset is:

> **1 gram of 18K gold**

Supported valuation units:

```text id="x3q7m1"
XAU18G
USD
USDT
IRT
```

`XAU18G` is an application-defined unit meaning:

> one gram of 18K gold.

Do not use `XAU` as the fund's base unit.

---

# 36. Gold conversions

Standard:

```text id="q8v3n6"
18K = 75% pure gold
24K = 100% pure gold
```

Therefore:

```text id="r2m7k4"
1 g 18K = 0.75 g pure gold
```

Troy ounce:

```text id="w5x9p2"
1 troy oz = 31.1034768 g
```

Therefore:

```text id="n4c8v1"
1 troy oz 24K
= 31.1034768 g pure gold

1 troy oz 24K
= 41.4713024 g 18K equivalent
```

These are mathematical conversions, not manually entered exchange rates.

---

# 37. Daily exchange rates

Administrators manually enter global rates.

Preferred rate:

```text id="p6r3x8"
1 g 18K gold
=
X IRT
```

Also support:

```text id="v9m2k5"
1 g 18K gold
=
X USD

1 g 18K gold
=
X USDT
```

Optional standard market input:

```text id="x7n4q1"
1 troy oz 24K gold
=
X USD
```

The direct 18K gram rate is authoritative when entered.

---

# 38. Rate fallback

For a target date:

```text id="k2p8v6"
effective rate =
latest global rate
where effective_date <= target date
```

If today's rate is missing, use the latest previous rate.

The UI must identify fallback rates.

Never invent exchange rates.

---

# 39. Historical rate snapshot

When a financial transaction is posted, preserve the rate used.

Example:

```text id="q5x9m3"
10,000,000 IRT

18K rate:
96,600,000 IRT/g

Gold value:
0.10351967 g
```

Future rate changes must not alter historical valuations.

---

# 40. Gold valuation

Gold is a valuation/reference layer.

It is not a replacement for native currency accounting.

Example:

```text id="v8n4p2"
USDT Treasury
10,000 USDT

≈ 95.2381 g 18K
```

The ledger still records:

```text id="r6m1x7"
10,000 USDT
```

not:

```text id="w3q8k5"
95.2381 gold
```

as the primary asset balance.

---

# 41. Wallet transaction flow

Complete crypto contribution flow:

```text id="m8p2v6"
Member wallet
      ↓
Blockchain
      ↓
Group treasury wallet
      ↓
Member submits TX hash
      ↓
Server verifies
      ↓
Admin confirms where required
      ↓
Transaction verified
      ↓
Journal entry posted
      ↓
Ledger balance changes
```

Submitting a TX hash does not itself create an accounting balance.

---

# 42. Blockchain verification

For crypto transactions verify:

* Network.
* Transaction existence.
* Confirmation/finality.
* Destination address.
* Token contract.
* Amount.
* Direction.
* Treasury.
* Transaction not already credited.

Use unique database constraints to prevent duplicate crediting.

---

# 43. Blockchain explorer

Verified blockchain transactions should expose:

```text id="y4n8q2"
View on blockchain explorer
```

Generate explorer URLs from:

```text id="p7m3v9"
network
transaction_hash
```

---

# 44. Bank transactions

Bank transactions are manually recorded.

Required evidence:

```text id="x2k7r5"
amount
currency
date
reference
receipt
```

Admin verifies the evidence.

No bank API is required for V1.

---

# 45. Receipts

Support:

```text id="n8v4q1"
JPEG
PNG
PDF
```

Store privately.

```text id="m6p2x9"
transaction_receipts

id
group_id
transaction_id
storage_path
original_filename
mime_type
size
sha256
uploaded_by
created_at
```

Receipt files must only be accessible through authorized Laravel routes.

---

# 46. Loans

```text id="q3m8v7"
loans

id
group_id
member_id
cost_center_id
currency
principal
interest_rate
term
status
created_at
approved_at
disbursed_at
```

Statuses:

```text id="r5x1n9"
requested
approved
rejected
disbursed
active
fully_repaid
overdue
defaulted
cancelled
```

---

# 47. Loan rules

Group settings control:

```text id="p8v2m4"
maximum loan amount
maximum term
interest rate
maximum fund exposure
minimum membership period
multiple active loans allowed
```

Initial defaults:

```text id="z6n3q8"
interest = 0%
maximum term = 12 months
one active loan = true
```

---

# 48. Loan eligibility

Before approval, calculate:

```text id="x4m9k2"
requested amount
+
existing outstanding principal
```

against configured limits.

Display the result:

```text id="c7p1v5"
Requested:
2,000 USDT

Maximum:
2,500 USDT

Outstanding:
0 USDT

Eligible:
YES
```

The system must enforce configured limits, not merely display them.

---

# 49. Loan installments

```text id="n2q8v6"
loan_installments

id
loan_id
due_date
amount
paid_amount
status
```

Statuses:

```text id="m5r3x9"
pending
partially_paid
paid
overdue
```

---

# 50. Cost-center loan reporting

Each loan belongs to the member's cost center.

Reports must be able to show:

```text id="q8v4n1"
Mohammad

Loans received
Principal outstanding
Principal repaid
Interest paid
Fees attributed
Net position
```

All figures must derive from the ledger.

---

# 51. Contributions

A contribution is associated with:

```text id="x3m7p9"
group_id
member_id
cost_center_id
transaction_id
```

Contribution history is immutable after verification.

Corrections use accounting adjustments.

---

# 52. Aggregated group activity

Each group has one activity timeline.

Example:

```text id="r9k2v5"
08 Aug

12:41 Mohammad
+500 USDT
Contribution

12:12 Ali
-1,000 USDT
Loan disbursement

11:43 Sara
+200 USDT
Loan repayment
```

Filters:

* Date.
* Member.
* Treasury.
* Currency.
* Transaction type.
* Status.

Never mix data from different groups.

---

# 53. Dashboard

Group dashboard:

```text id="w4n8q2"
FRIENDS FUND

104.7619 g
18K GOLD

Treasuries
────────────────

10,000 USDT
≈ 95.2381 g

920,000,000 IRT
≈ 9.5238 g

Loans outstanding
2,400 USDT

Recent activity
...
```

Primary valuation is always 18K gold.

Native balances remain visible.

---

# 54. Cost-center dashboard

Members can see their own cost-center position.

Admin can see all cost centers.

Example:

```text id="p7x3m9"
Mohammad

Contributed:
5,000 USDT

Outstanding loans:
1,000 USDT

Interest paid:
100 USDT

18K gold equivalent:
...
```

Do not store these as mutable summary balances.

Calculate them from the ledger.

---

# 55. Reports

Required reports:

### Trial Balance

```text id="k4m8q2"
Account
Debit
Credit
Balance
```

### General Ledger

All journal lines for an account.

### Balance Sheet

```text id="v6n2p8"
Assets
Liabilities
Equity
```

### Income Statement

```text id="x9r3m5"
Income
Expenses
Net result
```

### Treasury Report

Native balance per treasury.

### Loan Receivables

Outstanding principal by member/cost center.

### Cost Center Statement

Financial activity grouped by cost center.

### Gold Valuation

Group and treasury values in grams of 18K gold.

---

# 56. Reconciliation

Treasury reconciliation compares:

```text id="q3v7m1"
External balance
        vs
Ledger balance
```

Example:

```text id="r8n2x5"
Blockchain:
10,000 USDT

Ledger:
10,000 USDT

RECONCILED
```

Differences must be visible.

Never silently modify ledger balances to match external balances.

---

# 57. Accounting adjustments

Admins can create corrections.

Never edit posted entries.

Flow:

```text id="m7p4x9"
Problem
 ↓
Adjustment/reversal
 ↓
Journal entry
 ↓
Post
 ↓
Audit
```

Every adjustment requires a reason.

---

# 58. Audit log

Immutable audit records for:

```text id="v2n8q4"
Member approved
Member suspended
Wallet added
Wallet changed
Treasury created
Transaction verified
Transaction rejected
Loan approved
Loan rejected
Loan disbursed
Exchange created
Exchange rate entered
Journal posted
Journal reversed
Adjustment created
Accounting period closed
Settings changed
```

Fields:

```text id="x5m1r7"
audit_logs

id
group_id nullable
actor_id
action
object_type
object_id
old_values
new_values
ip_address
created_at
```

---

# 59. Financial integrity

Use PostgreSQL transactions for all multi-record financial operations.

Examples:

```text id="q9v3k6"
Verify transaction
Post journal entry
Loan approval
Loan disbursement
Repayment
Treasury exchange
Adjustment
Reversal
```

Business transaction and accounting entry must commit atomically.

---

# 60. Money storage

Never use floating point.

Use PostgreSQL `NUMERIC`.

Example:

```text id="m4x8p2"
amount NUMERIC(30,8)
rate NUMERIC(30,12)
```

Use decimal arithmetic throughout PHP.

Do not convert money to float.

---

# 61. Database integrity

Use:

* Foreign keys.
* Unique constraints.
* Check constraints where practical.
* Database transactions.

Important uniqueness:

```text id="v7n2q5"
blockchain network + transaction hash
```

must not be credited twice.

---

# 62. Security

Implement:

* CSRF.
* Secure password hashing.
* Session authentication.
* Authorization policies.
* Rate limiting.
* Secure cookies.
* Private receipt storage.
* File validation.
* Audit logs.
* Input validation.
* Decimal arithmetic.
* Tenant isolation.

Never store wallet private keys or seed phrases.

---

# 63. Architecture

Suggested structure:

```text id="p8m3x7"
app/
├── Domain/
│   ├── Accounting/
│   ├── Groups/
│   ├── Wallets/
│   ├── Treasuries/
│   ├── Transactions/
│   ├── Loans/
│   └── ExchangeRates/
│
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Middleware/
│
└── Policies/
```

Keep business logic outside controllers.

Controllers should primarily:

```text id="n4q7v2"
receive request
validate
authorize
call domain service
redirect
```

---

# 64. Testing requirements

Write automated tests for all financial invariants.

Minimum tests:

### Tenant isolation

```text id="x8m3p5"
User from Group A
cannot read/write Group B data.
```

### Double-entry

```text id="q4v9n1"
Every posted journal balances.
```

### Immutability

```text id="m7r2x6"
Posted journal cannot be edited/deleted.
```

### Contributions

```text id="p9k3v8"
Contribution
→ correct treasury
→ correct cost center
→ balanced journal
```

### Loans

```text id="n5x8q2"
Disbursement
→ receivable
→ treasury decrease
→ correct cost center
```

### Repayments

```text id="v3m7q1"
Repayment
→ treasury increase
→ receivable decrease
```

### Duplicate blockchain TX

```text id="r8p2k6"
Same TX cannot be credited twice.
```

### Historical exchange rates

```text id="x4n9m3"
Old transaction retains old valuation.
```

### Cost centers

```text id="q7v1p5"
Member activity is correctly attributed.
```

### Closed periods

```text id="m2k8r4"
Closed accounting periods reject normal postings.
```

---

# 65. V1 phases

## Phase 1 — Foundation

Implement:

* Laravel.
* PostgreSQL.
* Authentication.
* Accounts.
* Groups.
* Memberships.
* Roles.
* Tenant isolation.
* `/g/{group}` routing.
* Basic Blade UI.

Do not proceed until tenant isolation tests pass.

---

## Phase 2 — Accounting foundation

Implement:

* Chart of accounts.
* Cost centers.
* Journal entries.
* Journal lines.
* Posting.
* Reversal.
* Accounting periods.
* Trial balance.
* General ledger.
* Audit log.

Do not implement financial transactions before this layer works.

---

## Phase 3 — Wallets and treasuries

Implement:

* External wallets.
* USDT.
* Network configuration.
* Crypto treasuries.
* Bank treasuries.
* Treasury ledger accounts.

---

## Phase 4 — Transactions

Implement:

* Contributions.
* Receipts.
* Blockchain TX references.
* Blockchain verification.
* Transaction verification.
* Accounting posting.
* Activity timeline.
* Reconciliation.

---

## Phase 5 — Gold valuation

Implement:

* 18K gold gram reference.
* USD.
* USDT.
* IRT.
* Daily manual rates.
* Fallback rates.
* Historical snapshots.
* Troy-ounce conversion.
* 24K/18K conversion.
* Gold-valued reports.

---

## Phase 6 — Loans

Implement:

* Loan requests.
* Eligibility rules.
* Approval.
* Disbursement.
* Installments.
* Repayments.
* Interest.
* Overdue state.
* Cost-center reporting.

---

## Phase 7 — Reporting

Implement:

* Balance sheet.
* Income statement.
* Trial balance.
* General ledger.
* Treasury reports.
* Loan receivables.
* Cost-center reports.
* Gold valuation.
* Reconciliation reports.

---

# 66. MVP acceptance test

The application is considered functional when this complete scenario works:

```text id="v9m4x2"
1. Mohammad registers.
        ↓
2. Mohammad creates a group.
        ↓
3. Ali requests membership.
        ↓
4. Mohammad approves Ali.
        ↓
5. Ali receives a member cost center.
        ↓
6. Ali registers an external USDT wallet.
        ↓
7. Admin creates a USDT treasury.
        ↓
8. Ali sends USDT to the treasury.
        ↓
9. Ali submits the blockchain TX.
        ↓
10. Server verifies the TX.
        ↓
11. Admin verifies the contribution.
        ↓
12. Contribution transaction is posted.
        ↓
13. Balanced journal entry is created.
        ↓
14. Ali's cost center receives the accounting attribution.
        ↓
15. Treasury ledger increases.
        ↓
16. Group value is shown in 18K gold grams.
        ↓
17. Ali requests a loan.
        ↓
18. Loan eligibility is calculated.
        ↓
19. Admin approves it.
        ↓
20. USDT is sent to Ali's wallet.
        ↓
21. Disbursement is recorded.
        ↓
22. Loan receivable is posted.
        ↓
23. Ali repays USDT.
        ↓
24. Repayment is verified.
        ↓
25. Receivable decreases.
        ↓
26. Treasury increases.
        ↓
27. Cost-center position updates.
        ↓
28. Trial balance remains balanced.
        ↓
29. Full activity appears in the group timeline.
        ↓
30. Full accounting history remains auditable.
```

---

# 67. Non-goals

Do not implement in V1:

* Custodial wallets.
* Private-key management.
* Seed-phrase management.
* Mobile application.
* SPA frontend.
* React.
* Vue.
* Inertia.
* Livewire.
* Automatic bank APIs.
* Automatic exchange-rate APIs.
* Cross-group loans.
* Cross-group transfers.
* Public fund discovery.
* Complex notifications.
* Automated loan collection.
* Complex multi-level organizations.

---

# 68. Final invariants

These rules must always hold:

```text id="m8q3v7"
1. Group is the tenant boundary.

2. No user can access another group's data.

3. Every financial event has accounting representation.

4. Every posted journal entry balances:
   total debits = total credits.

5. Posted journal entries are immutable.

6. Corrections use reversals/adjustments.

7. Ledger is the source of truth for balances.

8. Cost centers attribute financial activity;
   they do not replace ledger accounts.

9. Treasuries represent where assets are held.

10. Wallets represent external addresses.

11. The application never stores private keys.

12. Native currencies remain authoritative.

13. 18K gold grams provide the common valuation layer.

14. Exchange rates are global, not group-scoped.

15. Historical rates used by posted transactions are preserved.

16. Blockchain transactions cannot be credited twice.

17. External treasury balances must be reconcilable against
    internal ledger balances.

18. Financial operations are atomic database transactions.

19. Closed accounting periods cannot receive normal postings.

20. Every material administrative action is auditable.
```

# 69. Final domain model

```text id="x7p2m9"
                         APPLICATION
                              │
             ┌────────────────┼────────────────┐
             │                │                │
          ACCOUNTS         CURRENCIES     EXCHANGE RATES
             │                              GLOBAL
             │
        MEMBERSHIPS
             │
          ┌──┴──┐
          │     │
       GROUP A GROUP B
          │
          ├── MEMBERS
          │     └── COST CENTERS
          │
          ├── TREASURIES
          │
          ├── WALLETS
          │
          ├── TRANSACTIONS
          │
          ├── LOANS
          │
          └── GENERAL LEDGER
                 │
          ┌──────┼────────┐
          │      │        │
       ACCOUNTS COST     JOURNAL
                CENTERS   ENTRIES
                           │
                       JOURNAL LINES
```

The fundamental accounting relationship is:

> **Ledger Account = what it is. Cost Center = who/what it belongs to. Treasury = where it is. Group = which fund it belongs to. Gold = how its value is commonly expressed.**
