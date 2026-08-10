<?php

return [

    // App\Domain\Policies\Categories\* — category label() and PolicyField label/help
    // strings, driving the generic policy edit/show forms.
    'fields' => [

        'categories' => [
            'loans' => 'Loans',
            'contributions' => 'Contributions',
            'repayments' => 'Repayments',
            'membership' => 'Membership',
            'accounting' => 'Accounting',
            'treasury' => 'Treasury',
        ],

        'loans' => [
            'enabled' => 'Loans enabled',
            'minimum_amount' => 'Minimum amount',
            'maximum_amount' => 'Maximum amount',
            'interest_rate' => 'Interest rate',
            'interest_method' => 'Interest method',
            'interest_method_none' => 'No interest',
            'interest_method_flat' => 'Flat on principal',
            'interest_method_declining' => 'Declining balance',
            'minimum_term_months' => 'Minimum term',
            'maximum_term_months' => 'Maximum term',
            'maximum_active_loans' => 'Maximum active loans',
            'minimum_membership_days' => 'Minimum membership',
            'early_repayment_allowed' => 'Early repayment allowed',
        ],

        'contributions' => [
            'enabled' => 'Contributions enabled',
            'minimum_amount' => 'Minimum amount',
            'maximum_amount' => 'Maximum amount',
            'maximum_amount_help' => 'Leave blank for no upper limit.',
        ],

        'repayments' => [
            'enabled' => 'Repayments enabled',
            'minimum_amount' => 'Minimum amount',
        ],

        'membership' => [
            'member_approval_required' => 'Administrator approval required',
            'member_approval_required_help' => 'When disabled, a membership request becomes active immediately.',
        ],

        'accounting' => [
            'functional_currency' => 'Functional currency',
            'functional_currency_help' => 'The reporting currency journal entries balance in. Separate from the 18K gold valuation layer.',
        ],

        'treasury' => [
            'admin_verification_required' => 'Administrator verification required',
            'admin_verification_required_help' => 'When disabled, a transaction whose blockchain evidence the server verified is posted without a second human check.',
        ],

    ],

    'index' => [
        'title' => 'Policies',
        'heading' => 'Policies',
        'intro' => 'Financial rules are versioned. A published version is immutable; changing the rules means publishing a new version, and existing operations keep the version they were created under.',
        'new_draft' => 'New draft',
        'continue_draft' => 'Continue draft v:version',
        'framework_heading' => 'Financial framework',
        'framework_none' => 'None selected',
        'framework_change' => 'Change…',
        'version_header' => 'Version',
        'status_header' => 'Status',
        'effective_from_header' => 'Effective from',
        'effective_until_header' => 'Effective until',
        'published_by_header' => 'Published by',
        'active_badge' => 'Active',
        'edit_link' => 'Edit',
        'publish_link' => 'Publish',
        'compare_link' => 'Compare with active',
        'empty' => 'No policy versions yet.',
    ],

    'edit' => [
        'title' => 'Edit policy draft v:version',
        'breadcrumb' => 'Policies',
        'heading' => 'Draft v:version',
        'intro' => 'Saving a draft changes nothing. Publishing is a separate, explicit action.',
        'publish_link' => 'Publish…',
        'framework_drift_heading' => 'This draft drifts from your chosen financial framework:',
        'save' => 'Save draft',
        'view_draft' => 'View draft',
        'monetary_hint' => "Monetary limits are compared against the amount of each operation in the operation's own currency.",
    ],

    'show' => [
        'title' => 'Policy v:version',
        'breadcrumb' => 'Policies',
        'heading' => 'Policy v:version',
        'active_badge' => 'Active',
        'present' => 'present',
        'edit_link' => 'Edit draft',
        'publish_link' => 'Publish',
        'draft_notice' => 'This is a draft. It governs nothing and is not authoritative until published.',
        'framework_drift_heading' => 'This policy drifts from your chosen financial framework:',
        'provenance_heading' => 'Provenance',
        'created_by' => 'Created by',
        'published_by' => 'Published by',
        'governs_heading' => 'Governs',
        'loans' => 'Loans',
        'transactions' => 'Transactions',
        'governs_note' => 'These records keep this version permanently, whatever is published later.',
        'discard_heading' => 'Discard',
        'discard_note' => 'A draft can be deleted; a published version cannot.',
        'delete_draft' => 'Delete draft',
    ],

    'member' => [
        'title' => 'Fund rules',
        'heading' => 'Fund rules',
        'intro' => 'The rules currently governing new operations. Loans and contributions you already have keep the rules they were created under.',
        'no_active_policy' => 'This fund has no active policy. New financial operations are refused until an administrator publishes one.',
        'framework_drift_heading' => "This fund's rules drift from its chosen financial framework:",
        'active_since' => 'Policy v:version, active since',
    ],

    'publish' => [
        'title' => 'Publish policy v:version',
        'breadcrumb' => 'Policies',
        'heading' => 'You are publishing policy v:version',
        'changes_heading' => 'Changes',
        'no_changes' => 'This draft makes no changes to the active policy.',
        'setting_header' => 'Setting',
        'now_header' => 'Now',
        'after_header' => 'After publishing',
        'applies_note' => 'This policy will apply to operations created after publication. Existing loans and transactions remain governed by their original policy versions.',
        'framework_drift_heading' => 'This policy drifts from your chosen financial framework. You can still publish it — this is informational only.',
        'blocked_heading' => 'This draft cannot be published yet:',
        'confirm_line' => 'I understand that v:version becomes the active policy',
        'confirm_superseded' => 'and v:active_version is superseded',
        'submit' => 'Publish version :version',
        'what_happens_heading' => 'What publishing does',
        'step_validates' => 'Validates the complete policy.',
        'step_closes' => 'Closes the current active version.',
        'step_makes_active' => 'Makes v:version active from today.',
        'step_records' => 'Records who published it, and when.',
        'step_audit' => 'Writes an audit event.',
        'transaction_note' => 'All of it in one database transaction: the fund is never left with two active versions, or none.',
    ],

    'compare' => [
        'title' => 'Compare v:from with v:to',
        'breadcrumb' => 'Policies',
        'intro' => 'An administrative comparison. It changes nothing and touches no accounting.',
        'setting_header' => 'Setting',
        'identical' => 'These versions are identical.',
    ],

];
