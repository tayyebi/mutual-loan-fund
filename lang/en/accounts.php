<?php

return [
    'index' => [
        'title' => 'Chart of accounts',
        'heading' => 'Chart of accounts',
        'intro' => 'A ledger account says what something is. Who it belongs to is a cost center; where it is held is a treasury.',
        'col_code' => 'Code',
        'col_account' => 'Account',
        'col_type' => 'Type',
        'col_cost_center' => 'Cost center',
        'col_balance' => 'Balance (:currency)',
        'inactive_badge' => 'inactive',
        'required_label' => 'required',
        'footer_note' => "Balances are signed by the account's normal side and derived from posted lines only.",
    ],

    'show' => [
        'breadcrumb' => 'Chart of accounts',
        'balance_prefix' => 'balance',
        'cost_center_required' => 'cost center required',
        'native_balances_heading' => 'Native balances',
        'filter_from_label' => 'From',
        'filter_to_label' => 'To',
        'filter_submit' => 'Filter',
        'ledger_heading' => 'General ledger',
        'col_date' => 'Date',
        'col_entry' => 'Entry',
        'col_description' => 'Description',
        'col_cost_center' => 'Cost center',
        'col_debit' => 'Debit',
        'col_credit' => 'Credit',
        'reversed_badge' => 'reversed',
        'empty' => 'No posted lines for this account.',
    ],
];
