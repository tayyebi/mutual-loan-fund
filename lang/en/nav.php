<?php

return [

    'brand' => 'Mutual Loan Fund',
    'default_title' => 'Mutual Loan Fund',
    'exchange_rates' => 'Exchange rates',
    'sign_out' => 'Sign out',

    /*
    | The three access levels, as the switcher names them. These are the labels
    | on App\Domain\Access\Surfaces\*::label().
    */
    'surfaces' => [
        'aria_label' => 'Switch view',
        'member' => 'My view',
        'fund_admin' => 'Run the fund',
        'system_admin' => 'Platform',
    ],

    'sections' => [
        'aria_label' => 'Sections',
        'operations' => 'Day to day',
        'books' => 'Books',
        'governance' => 'Governance',
    ],

    'links' => [
        // Member surface — named after what the person wants.
        'overview' => 'Overview',
        'money' => 'My money',
        'borrowing' => 'Borrowing',
        'the_fund' => 'The fund',
        'wallets' => 'Wallets',

        // Fund administrator surface.
        'manage' => 'Manage',
        'activity' => 'Activity',
        'loans' => 'Loans',
        'members' => 'Members',
        'treasuries' => 'Treasuries',
        'ledger' => 'Ledger',
        'accounts' => 'Accounts',
        'cost_centers' => 'Cost centers',
        'periods' => 'Periods',
        'reports' => 'Reports',
        'policies' => 'Policies',
        'framework' => 'Framework',
        'audit' => 'Audit',

        // System surface.
        'users' => 'Users',
        'funds' => 'Funds',
    ],

    'hints' => [
        'overview' => 'Where you stand right now',
        'money' => 'What you have paid in, and what it is worth',
        'borrowing' => 'Borrow, repay, and what you owe',
        'activity' => 'Everything that has happened in this fund',
        'wallets' => 'The addresses you pay from',
        'the_fund' => 'How the fund is doing, and its rules',
    ],

];
