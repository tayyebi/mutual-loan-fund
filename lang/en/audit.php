<?php

return [
    'index' => [
        'title' => 'Audit log',
        'subtitle' => 'Append-only. Each entry is written in the same database transaction as the action it records, so a committed action always has its trace.',
        'filter_label' => 'Action starts with',
        'filter_button' => 'Filter',
        'clear' => 'Clear',
        'table_when' => 'When',
        'table_actor' => 'Actor',
        'table_action' => 'Action',
        'table_object' => 'Object',
        'table_detail' => 'Detail',
        'system_actor' => 'system',
        'empty' => 'No audit entries match.',
        'policy_changes_title' => 'Policy changes',
        'policy_changes_subtitle' => 'Kept separately, with the full configuration before and after.',
        'policy_changes_empty' => 'No policy changes recorded.',
    ],
];
