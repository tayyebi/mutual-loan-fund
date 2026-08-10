<?php

return [

    // App\Domain\Frameworks\FrameworkComplianceChecker — advisory, non-blocking.
    'framework_drift' => "Your policy's :field is :current, which drifts from your chosen :framework framework (:requirement).",

    // App\Domain\Loans\LoanService
    'loans_disabled' => 'Loans are not enabled for this fund.',
    'loan_minimum_amount' => 'The minimum loan is :amount :currency.',
    'loan_maximum_amount' => 'The maximum loan is :amount :currency.',
    'loan_exceeds_total_exposure' => 'This would take total borrowing to :total :currency, above the :maximum :currency maximum.',
    'loan_term_out_of_range' => 'The term must be between :min and :max months.',
    'loan_active_limit_one' => 'This fund allows one active loan at a time.',
    'loan_active_limit_many' => 'This fund allows :count active loans at a time.',
    'loan_membership_days_required' => 'Members must belong to the fund for :required days before borrowing (currently :current).',
    'loan_not_permitted' => 'This loan is not permitted.',
    'loan_cannot_approve' => 'Loan :reference is :status and cannot be approved.',
    'loan_no_longer_permitted' => 'This loan is no longer permitted.',
    'loan_cannot_reject' => 'Loan :reference is :status and cannot be rejected.',
    'loan_cannot_cancel' => 'Loan :reference can no longer be cancelled.',
    'loan_must_be_approved_to_disburse' => 'Loan :reference must be approved before it can be disbursed.',
    'loan_currency_mismatch' => 'Loan :reference is denominated in :currency.',

    // App\Domain\Accounting\PostingService
    'journal_not_posted_cannot_reverse' => 'Journal entry :entry is not posted and cannot be reversed.',
    'journal_already_reversed' => 'Journal entry :entry has already been reversed.',
    'journal_unbalanced' => 'Journal entry does not balance: debits :debits :currency, credits :credits :currency (difference :difference).',
    'journal_no_value' => 'Journal entry :entry has no value to post.',
    'entry_needs_two_lines' => 'A journal entry needs at least two lines.',
    'account_foreign_to_group' => 'Account :code does not belong to this group.',
    'account_inactive' => 'Account :account is inactive and cannot be posted to.',
    'cost_center_foreign_to_group' => 'Cost center does not belong to this group.',
    'account_requires_cost_center' => 'Account :account requires a cost center.',
    'line_amount_must_be_positive' => 'Line amounts must be greater than zero (account :account).',

    // App\Domain\Accounting\ChartOfAccounts
    'chart_account_missing' => "Group ':group' has no account :code. The chart of accounts is incomplete.",
    'no_treasury_codes_remaining' => 'No treasury account codes remain in the 1101–1199 range.',

    // App\Domain\Accounting\AccountingService
    'transaction_already_posted' => 'Transaction #:id has already been posted to the ledger.',
    'no_accounting_template' => "No accounting template is defined for transaction type ':type'.",
    'adjustment_requires_reason' => 'Every adjustment requires a reason.',
    'reversal_requires_reason' => 'Every reversal requires a reason.',

    // App\Domain\Accounting\Templates\* (BaseTemplate, LoanRepaymentTemplate, LoanDisbursementTemplate,
    // TreasuryTransferTemplate, TreasuryExchangeTemplate)
    'transaction_no_treasury' => 'Transaction #:id has no treasury; there is nowhere for the money to be.',
    'treasury_no_ledger_account' => "Treasury ':name' has no ledger account.",
    'transaction_needs_member_cost_center' => 'Transaction #:id needs a member cost center for attribution.',
    'fee_currency_mismatch' => 'Fee currency :fee_currency does not match treasury currency :treasury_currency.',
    'repayment_no_loan' => 'Transaction #:id is a repayment with no loan attached.',
    'disbursement_no_loan' => 'Transaction #:id is a disbursement with no loan attached.',
    'transfer_no_destination_treasury' => 'Transfer #:id has no destination treasury.',
    'transfer_currency_mismatch' => 'A transfer between treasuries of different currencies is an exchange; record it as one.',
    'exchange_no_destination_treasury' => 'Exchange #:id has no destination treasury.',
    'exchange_missing_destination_amount' => 'Exchange #:id is missing its destination amount.',

    // App\Domain\Accounting\PeriodService
    'period_has_draft_entries' => 'Period :period still has :count unposted draft entries. Post or discard them before closing.',

    // App\Domain\Accounting\Exceptions\ClosedPeriodException
    'accounting_period_closed' => 'Accounting period :period is closed and cannot receive postings. Post the correction in an open period instead.',

    // App\Domain\Policies\Exceptions\NoActivePolicyException
    'no_active_policy' => "Group ':group' has no active policy version. Publish a policy before recording financial operations.",

    // App\Domain\ExchangeRates\Exceptions\MissingRateException
    'missing_gold_rate' => 'No 18K gold rate has been entered for :unit on or before :date. Enter a rate before recording this operation.',

    // App\Domain\SystemAdmin\SystemAdminService
    'last_system_admin' => "This is the platform's only administrator. Promote another user first.",

    // App\Domain\Groups\MembershipService
    'membership_suspended' => 'Your membership in this group is suspended.',
    'last_group_admin' => "This is the group's only administrator. Promote another member first.",

    // App\Domain\Policies\PolicyValidator
    'policy_field_required' => ':field is required.',
    'policy_field_must_be_number' => ':field must be a number.',
    'policy_field_cannot_be_negative' => ':field cannot be negative.',
    'interest_rate_out_of_range' => 'Interest rate must be between :min% and :max%.',
    'minimum_term_at_least_one' => 'Minimum term must be at least 1 month.',
    'maximum_term_at_least_one' => 'Maximum term must be at least 1 month.',
    'maximum_term_exceeds_ceiling' => 'Maximum term cannot exceed :ceiling months.',
    'minimum_term_exceeds_maximum' => 'Minimum term cannot exceed the maximum term.',
    'maximum_loan_amount_required' => 'A maximum loan amount is required.',
    'maximum_loan_amount_must_be_positive' => 'Maximum loan amount must be greater than zero while loans are enabled.',
    'minimum_loan_amount_exceeds_maximum' => 'Minimum loan amount cannot exceed the maximum amount.',
    'at_least_one_active_loan' => 'At least one active loan must be permitted.',
    'minimum_membership_days_negative' => 'Minimum membership days cannot be negative.',
    'unknown_interest_method' => 'Unknown interest method.',
    'interest_rate_needs_method' => 'An interest rate above zero requires an interest method.',
    'interest_method_needs_rate' => 'An interest method requires a rate above zero.',
    'minimum_contribution_exceeds_maximum' => 'Minimum contribution cannot exceed the maximum.',
    'maximum_contribution_must_be_positive' => 'Maximum contribution must be greater than zero, or blank for no limit.',
    'functional_currency_unsupported' => 'Functional currency is not supported.',
    'gold_unit_cannot_be_functional_currency' => 'The 18K gold unit is a valuation layer and cannot be a functional currency.',

    // App\Domain\Policies\PolicyService
    'draft_already_exists' => "Group ':group' already has draft v:version. Edit or delete it first.",
    'policy_published_cannot_edit' => 'Policy v:version is published and cannot be edited.',
    'policy_published_cannot_delete' => 'Policy v:version is published and cannot be deleted.',

    // App\Domain\Policies\PolicyPublisher
    'policy_not_draft' => 'Policy v:version is not a draft and cannot be published again.',

    // App\Domain\Transactions\TransactionService
    'contributions_disabled' => 'This fund is not accepting contributions.',
    'contribution_minimum_amount' => 'The minimum contribution is :amount :currency.',
    'contribution_maximum_amount' => 'The maximum contribution is :amount :currency.',
    'repayments_disabled' => 'Repayments are not enabled for this fund.',
    'loan_not_outstanding' => 'This loan is not outstanding.',
    'repayment_minimum_amount' => 'The minimum repayment is :amount :currency.',
    'repayment_currency_mismatch' => 'This loan is repaid in :currency.',
    'early_repayment_not_permitted' => "Early repayment is not permitted under this loan's policy.",
    'transaction_already_in_status' => 'This transaction is already :status.',
    'chain_transaction_duplicate' => 'That blockchain transaction has already been submitted to this application.',
    'chain_no_tx_hash' => 'No transaction hash was submitted.',

    // App\Domain\Wallets\WalletService
    'network_unsupported' => "Unsupported network ':network'.",
    'address_invalid' => 'That does not look like a valid :network address.',

    // App\Domain\ExchangeRates\RateService
    'gold_unit_cannot_be_quoted' => 'The gold unit is the reference and cannot be quoted against itself.',
    'valuation_unit_unsupported' => ':unit is not a supported valuation unit.',
    'rate_must_be_positive' => 'A rate above zero is required.',

    // App\Domain\Treasuries\TreasuryService
    'crypto_treasury_needs_network' => 'A crypto treasury needs a network.',
    'only_crypto_treasuries_have_network' => 'Only crypto treasuries have a network.',

    // App\Http\Middleware\ResolveGroupContext
    'account_not_active' => 'Your account is not active.',
    'fund_suspended' => 'This fund is suspended.',

    // App\Http\Middleware\EnsureGroupAdmin
    'restricted_to_group_admins' => 'This action is restricted to group administrators.',

    // App\Http\Middleware\EnsureSystemAdmin
    'restricted_to_system_admins' => 'This action is restricted to system administrators.',

    // App\Http\Controllers\TreasuryController
    'enter_reported_balance' => 'Enter the balance reported by the bank or explorer.',

    // App\Http\Controllers\GroupController
    'fund_not_accepting_members' => 'This fund is not accepting members.',

    // App\Http\Controllers\LedgerController
    'account_not_in_fund' => 'One of the chosen accounts does not belong to this fund.',

    // App\Http\Controllers\TransactionController
    'restricted_to_admins_treasury_movements' => 'Only administrators record treasury movements.',

];
