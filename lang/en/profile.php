<?php

return [

    'home' => [
        'title' => 'My account',
        'heading' => 'My account',
        'intro' => 'Your account across every fund.',
        'profile_heading' => 'Profile',
        'name_label' => 'Name',
        'email_label' => 'Email',
        'funds_label' => 'Funds',
        'fund_word' => 'fund|funds',
        'settings_heading' => 'Settings',
        'transactions_link' => 'My transactions',
        'preferences_link' => 'Preferences',
        'password_link' => 'Change password',
    ],

    'password' => [
        'title' => 'Change password',
        'heading' => 'Change password',
        'intro' => 'Use at least 10 characters.',
        'back_link' => 'Back to my account',
        'current_label' => 'Current password',
        'new_label' => 'New password',
        'confirm_label' => 'Confirm new password',
        'submit' => 'Change password',
    ],

    'transactions' => [
        'title' => 'My transactions',
        'heading' => 'My transactions',
        'intro' => 'Activity in which you were a member, across all your funds.',
        'back_link' => 'Back to my account',
        'fund_header' => 'Fund',
        'date_header' => 'Date',
        'amount_header' => 'Amount',
        'type_header' => 'Type',
        'status_header' => 'Status',
        'empty' => 'You have no transactions yet.',
    ],

    'preferences' => [
        'title' => 'Preferences',
        'heading' => 'Preferences',
        'intro' => "Personal display settings. None of these change any fund's actual rules or records.",
        'back_link' => 'Back to my account',
        'language_label' => 'Language',
        'language_browser_default' => "Use my browser's language",
        'extras_summary' => 'Change default currency, timezone, or weekend days (optional)',
        'currency_label' => 'Default currency',
        'currency_none' => 'No preference',
        'currency_hint' => 'Pre-selects the currency when you create a new treasury or wallet. Nothing else.',
        'timezone_label' => 'Timezone',
        'timezone_server_default' => "Use the server's timezone",
        'timezone_hint' => 'Only changes how dates and times are displayed to you. It never changes due dates or when a period closes.',
        'weekend_days_label' => 'Weekend days',
        'weekend_days_hint' => 'Not used by any feature yet — stored for future scheduling features.',
        'days' => [
            'sunday' => 'Sunday',
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
        ],
        'submit' => 'Save preferences',
    ],

];
