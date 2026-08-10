<?php

return [
    'index' => [
        'title' => 'Reports',
        'subtitle' => 'Every figure is derived from posted journal lines.',
        'titles' => [
            'trial-balance' => 'Trial Balance',
            'balance-sheet' => 'Balance Sheet',
            'income-statement' => 'Income Statement',
            'treasuries' => 'Treasury Report',
            'receivables' => 'Loan Receivables',
            'cost-centers' => 'Cost Center Statements',
            'gold' => 'Gold Valuation',
        ],
        'descriptions' => [
            'trial-balance' => 'Debits and credits per account, and the proof they agree.',
            'balance-sheet' => 'Assets against liabilities and equity.',
            'income-statement' => 'Income, expenses and the net result.',
            'treasuries' => 'What the fund holds, and where.',
            'receivables' => 'Outstanding principal by member.',
            'cost-centers' => 'Activity grouped by who it belongs to.',
            'gold' => 'The fund expressed in grams of 18K gold.',
        ],
    ],

    'head' => [
        'breadcrumb' => 'Reports',
        'as_of' => 'As at',
        'as_of_today' => 'As at today',
        'from' => 'from',
        'figures_in' => 'figures in :currency unless stated',
        'field_from' => 'From',
        'field_as_of' => 'As at',
        'apply' => 'Apply',
    ],

    'common' => [
        'balanced' => 'balanced',
        'out_by' => 'out by',
        'no_rate' => 'no rate',
        'grams_suffix' => 'g',
        'bank_fallback' => 'bank',
    ],

    'balance_sheet' => [
        'assets' => 'Assets',
        'liabilities' => 'Liabilities',
        'equity' => 'Equity',
        'result_for_period' => 'Result for the period',
        'liabilities_plus_equity' => 'Liabilities + equity (including result)',
        'balance_title' => 'Balance',
        'note' => 'Income and expenses for the period are folded into equity as the result; without that the sheet would not balance.',
    ],

    'cost_centers' => [
        'subtitle' => 'Activity grouped by who or what it belongs to. Open a cost center for its full statement.',
        'table_code' => 'Code',
        'table_name' => 'Name',
        'table_member' => 'Member',
        'table_status' => 'Status',
        'empty' => 'No cost centers.',
    ],

    'gold' => [
        'fund_assets' => 'Fund assets',
        'grams_of_gold' => 'grams of 18K gold (:unit)',
        'unvalued_warning' => ':count posted lines carry no gold snapshot: no rate existed for their currency at the time they were posted. They are excluded rather than valued at today\'s rate.',
        'table_treasury' => 'Treasury',
        'table_native_balance' => 'Native balance',
        'table_gold_today' => '≈ 18K gold today',
        'empty' => 'No treasuries.',
        'two_figures_title' => 'Two different figures',
        'note_headline' => 'The headline total is the sum of valuations frozen when each line was posted — what the fund\'s assets were worth in gold as they arrived.',
        'note_treasury_column' => 'The treasury column re-values today\'s balances at today\'s rate. The two differ when the gold price has moved, and both are correct answers to different questions.',
    ],

    'income_statement' => [
        'table_account' => 'Account',
        'table_amount' => 'Amount (:currency)',
        'empty' => 'No income or expenses in this period.',
        'income' => 'Income',
        'expenses' => 'Expenses',
        'net_result' => 'Net result',
        'interest_title' => 'Interest',
        'interest_note' => 'Interest income appears here only when a repayment carrying interest has been posted. The policy sets the contractual rate; the ledger records what was actually recognised.',
    ],

    'receivables' => [
        'table_cost_center' => 'Cost center',
        'table_member' => 'Member',
        'table_outstanding_principal' => 'Outstanding principal',
        'empty' => 'No outstanding loan principal.',
        'note' => 'Taken from the Loans Receivable balance per cost center — the ledger, not the loan records.',
    ],

    'treasuries' => [
        'table_treasury' => 'Treasury',
        'table_type' => 'Type',
        'table_held_at' => 'Held at',
        'table_native_balance' => 'Native balance',
        'table_gold' => '≈ 18K gold',
        'empty' => 'No treasuries.',
        'note' => 'Native currencies stay authoritative. The gold column is a valuation, computed at the selected date\'s rate.',
    ],

    'trial_balance' => [
        'table_code' => 'Code',
        'table_account' => 'Account',
        'table_debit' => 'Debit',
        'table_credit' => 'Credit',
        'table_balance' => 'Balance',
        'empty' => 'Nothing has been posted yet.',
        'totals' => 'Totals (:currency)',
    ],
];
