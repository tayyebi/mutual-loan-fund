# PRD Amendment — Versioned Group Policies

## 1. Policy model

Group financial rules must be configurable by the group administrator.

Policies are **versioned and immutable once published**.

Never modify a policy that has already been used by a financial operation.

Instead:

```text
Current Policy v3
      ↓
Admin edits
      ↓
Draft v4
      ↓
Admin publishes
      ↓
Policy v4 becomes active
```

Historical financial operations continue referencing their original policy version.

---

# 2. Policy entity

Add:

```text
group_policies
```

Fields:

```text
id
group_id
version
status
effective_from
effective_until nullable
created_by
published_by nullable
published_at nullable
created_at
updated_at
```

Statuses:

```text
draft
published
superseded
```

Constraints:

```text
UNIQUE(group_id, version)
```

Only one policy version may be active for a group at a given time.

---

# 3. Policy values

Do not create dozens of unrelated mutable columns on `groups`.

Store policy configuration in a structured JSON document:

```text
group_policies.config
```

Example:

```json
{
  "loans": {
    "enabled": true,
    "interest_rate": "0",
    "maximum_amount": "2500",
    "maximum_term_months": 12,
    "maximum_active_loans": 1,
    "minimum_membership_days": 30
  },
  "contributions": {
    "enabled": true,
    "minimum_amount": "0"
  },
  "repayments": {
    "enabled": true
  },
  "accounting": {
    "functional_currency": "IRT"
  }
}
```

Use decimal strings for monetary/rate values.

Never deserialize financial values into PHP floats.

---

# 4. Policy categories

V1 policies:

### Loans

```text
enabled
minimum_amount
maximum_amount
interest_rate
interest_method
maximum_term_months
minimum_term_months
maximum_active_loans
minimum_membership_days
early_repayment_allowed
```

### Contributions

```text
enabled
minimum_amount
maximum_amount
```

### Repayments

```text
enabled
minimum_amount
```

### Membership

```text
member_approval_required
```

### Accounting

```text
functional_currency
```

### Treasury

```text
admin_verification_required
```

The architecture must allow additional policy categories later.

---

# 5. Policy snapshots

Every financial object affected by a policy must preserve the policy version used.

Examples:

```text
loans.policy_version_id
transactions.policy_version_id nullable
```

For loans, the policy reference is mandatory.

Example:

```text
Loan #123
Policy: v4
Interest: 5%
Maximum term: 12 months
```

If the administrator later changes the interest rate to 3%, Loan #123 remains governed by v4.

---

# 6. Policy resolution

When creating a new financial operation:

```text
group
 ↓
active policy
 ↓
validate operation
 ↓
create operation
 ↓
store policy_version_id
```

Never resolve the policy dynamically when displaying or calculating historical operations.

Historical operations use their stored policy version.

---

# 7. Loan policy example

Current:

```text
Policy v3

interest_rate = 0%
maximum_amount = 2,000 USDT
maximum_term = 12 months
```

Ali takes:

```text
Loan #51
```

The loan stores:

```text
policy_version = v3
```

Admin later publishes:

```text
Policy v4

interest_rate = 5%
maximum_amount = 3,000 USDT
maximum_term = 18 months
```

Loan #51 remains governed by v3.

A new Loan #52 uses v4.

---

# 8. Policy versioning rules

Published policies are immutable.

The application must reject:

```text
UPDATE published policy
DELETE published policy
```

To change a published policy:

```text
published v3
      ↓
create draft v4
      ↓
edit draft
      ↓
validate
      ↓
publish v4
      ↓
v3 becomes superseded
```

---

# 9. Draft policies

Administrators can create and edit one draft policy at a time.

Draft policies:

* Do not affect existing operations.
* Do not affect new operations until published.
* Are not considered authoritative.
* Can be deleted before publication.

Publishing must be atomic.

---

# 10. Publishing

Publishing requires:

```text
POST /g/{group}/policies/{policy}/publish
```

The server must:

1. Authorize the administrator.
2. Validate the complete policy.
3. Ensure no conflicting active version exists.
4. Close the previous active version.
5. Set the new policy as active.
6. Set `effective_from`.
7. Record publisher and timestamp.
8. Write an audit event.

All steps must happen inside one database transaction.

---

# 11. Effective dates

Each published policy has:

```text
effective_from
effective_until
```

Example:

```text
v3
effective_from: 2026-08-01
effective_until: 2026-08-10

v4
effective_from: 2026-08-11
effective_until: NULL
```

The application must prevent overlapping policy periods.

V1 should normally publish policies effective immediately.

Future-dated policies may be supported by the schema but are not required in the UI.

---

# 12. Policy history

Administrators must be able to view:

```text
/g/{group}/policies
```

Example:

```text
Policies

v4   Active       2026-08-08
v3   Superseded   2026-08-01
v2   Superseded   2026-07-15
v1   Superseded   2026-07-01
```

Selecting a version displays its complete configuration.

---

# 13. Policy comparison

Provide:

```text
/g/{group}/policies/{version}/compare/{other}
```

The UI should show changes:

```text
Loan maximum
v3: 2,000 USDT
v4: 3,000 USDT

Interest rate
v3: 0%
v4: 5%

Maximum term
v3: 12 months
v4: 18 months
```

This is an administrative convenience, not an accounting operation.

---

# 14. Policy audit trail

Every policy change must create an audit event.

Record:

```text
group_id
actor_id
policy_version_id
action
old_config
new_config
ip_address
created_at
```

Actions:

```text
created
updated
published
deleted
superseded
```

Draft edits are auditable as well.

---

# 15. Policy validation

Before publishing, validate:

### Monetary values

```text
>= 0
```

### Interest rates

Must be within configured valid bounds.

### Terms

```text
minimum_term <= maximum_term
```

### Loan amounts

```text
minimum_amount <= maximum_amount
```

### Active loans

```text
maximum_active_loans >= 1
```

### Currency

Must be a supported currency.

### Accounting

Functional currency must be supported.

Invalid policies cannot be published.

---

# 16. Policy authorization

Only group administrators may:

```text
create policy draft
edit draft
publish policy
delete draft
view policy history
```

Normal members may only see policies that affect them.

The default V1 behavior should be to expose the currently active member-facing loan/contribution rules.

---

# 17. Policy-driven business logic

Business services must never hard-code group financial rules.

Bad:

```text
if ($loan->amount > 2500) {
    ...
}
```

Good:

```text
$policy = $group->activePolicy();

$policy->loans->maximum_amount
```

All policy-dependent validation must resolve through the policy service.

---

# 18. Policy service

Create:

```text
App\Domain\Policies\
```

Recommended classes:

```text
Policy
PolicyService
PolicyValidator
PolicyPublisher
LoanPolicy
ContributionPolicy
```

Core operations:

```text
activePolicy()
draftPolicy()
createDraft()
updateDraft()
publish()
validate()
history()
```

Controllers must not implement policy logic.

---

# 19. Policy snapshot vs accounting snapshot

Keep these concepts separate.

### Policy snapshot

Answers:

> Which rules governed this operation?

```text
policy_version_id
```

### Accounting snapshot

Answers:

> Which exchange rate/valuation was used?

Examples:

```text
exchange_rate_snapshot
gold_value_snapshot
```

Both must be preserved independently.

---

# 20. Policy changes must not rewrite history

Changing a policy must never:

* Recalculate an old loan's original eligibility.
* Change an old loan's interest rate.
* Change an old transaction's accounting.
* Change an old transaction's gold valuation.
* Modify an existing journal entry.
* Modify historical reports.

Historical records remain historically correct.

---

# 21. Policy and loan lifecycle

At loan creation:

```text
Active Policy v4
       ↓
Eligibility validation
       ↓
Loan created
       ↓
Loan.policy_version_id = v4
```

At approval:

```text
Loan.policy_version_id
        ↓
verify applicable rules
        ↓
approve
```

Do not silently switch the loan to a newer policy.

---

# 22. Policy and accounting

Policies may determine accounting behavior, but accounting records remain authoritative.

For example:

```text
Policy v4
interest_rate = 5%
```

creates the contractual/business rule.

The actual recognized interest creates:

```text
Journal Entry
```

The journal remains immutable even if policy v5 changes the interest rate.

---

# 23. Policy UI

Add:

```text
/g/{group}/policies
/g/{group}/policies/create
/g/{group}/policies/{version}
/g/{group}/policies/{version}/edit
/g/{group}/policies/{version}/compare/{other}
/g/{group}/policies/{version}/publish
```

Keep the UI server-rendered.

Use normal HTML forms.

No JavaScript is required.

---

# 24. Policy editing UX

The policy editor should be a simple server-rendered form:

```text
Loan Policy

Enabled              [x]

Minimum amount       [        ]
Maximum amount       [        ]

Interest rate        [        ] %
Interest method      [        ]

Minimum term         [        ] months
Maximum term         [        ] months

Maximum active loans [        ]

Minimum membership   [        ] days


[Save Draft]
```

Publishing is a separate explicit action:

```text
[Publish Version 4]
```

Do not combine save and publish.

---

# 25. Policy publication confirmation

Before publication show:

```text
You are publishing policy v4.

Changes:

Maximum loan
2,000 → 3,000 USDT

Interest
0% → 5%

Maximum term
12 → 18 months

This policy will apply to new operations after publication.

Existing loans remain governed by their original policy versions.
```

Require explicit confirmation.

---

# 26. Policy database model

Minimum tables:

```text
group_policies
```

Recommended:

```text
group_policy_versions
```

However, V1 may use a single `group_policies` table where every row is a version.

Recommended schema:

```text
group_policies

id
group_id
version
status
config
effective_from
effective_until
created_by
published_by
published_at
created_at
updated_at
```

Use:

```text
UNIQUE(group_id, version)
```

---

# 27. Final architecture addition

The group architecture becomes:

```text
GROUP
│
├── MEMBERS
│   └── COST CENTERS
│
├── POLICIES
│   ├── v1
│   ├── v2
│   ├── v3
│   └── v4 ACTIVE
│
├── TREASURIES
│
├── WALLETS
│
├── TRANSACTIONS
│
├── LOANS
│   └── policy_version
│
└── GENERAL LEDGER
    ├── ACCOUNTS
    ├── COST CENTERS
    ├── JOURNAL ENTRIES
    └── JOURNAL LINES
```

---

# 28. Updated core invariants

Add these to the existing final invariants:

```text
21. Group financial policies are versioned.

22. Published policies are immutable.

23. Policy changes create new versions.

24. Only one policy version is active at a time.

25. Every policy-dependent financial operation records
    the policy version under which it was created.

26. Historical operations never switch to newer policies.

27. Draft policies never affect financial operations.

28. Publishing a policy is an atomic database operation.

29. Policy changes are fully auditable.

30. Business logic must never hard-code configurable
    group financial policies.
```

The resulting principle is:

> **The ledger records what happened, the cost center records who/what it belongs to, the treasury records where the asset is, and the versioned policy records which rules governed the decision.**
