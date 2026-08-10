<?php

return [

    'create' => [
        'title' => 'Create a fund',
        'breadcrumb' => 'Your funds',
        'heading' => 'Create a fund',
        'name_label' => 'Name',
        'description_label' => 'Description',
        'framework_label' => 'Financial framework',
        'optional' => 'optional',
        'framework_none' => 'None',
        'framework_hint' => "A named preset of advisory rules. It never blocks anything — if your fund's policy later drifts from it, you'll just see a warning. You can change or clear it any time from Policies.",
        'submit' => 'Create fund',
        'created_with_heading' => 'What is created with it',
        'created_with_chart' => 'A chart of accounts for this fund alone.',
        'created_with_membership' => 'Your administrator membership and cost center.',
        'created_with_policy' => 'Policy v1, published and active.',
        'transaction_note' => 'All of it in one database transaction, so the fund is never half-created.',
        'frameworks_heading' => 'Financial frameworks',
    ],

    'join' => [
        'title' => 'Join :name',
        'breadcrumb' => 'Your funds',
        'pending' => 'Your request is waiting for an administrator.',
        'rejected' => 'A previous request was declined. You can ask again.',
        'submit' => 'Request membership',
        'hint' => "Membership gives you access to this fund's financial activity. It gives you no access to any other fund.",
    ],

    'framework' => [
        'title' => 'Financial framework',
        'breadcrumb' => 'Policies',
        'heading' => 'Financial framework',
        'intro' => "A named preset of advisory rules. It never blocks anything: if your policy drifts from it, you'll see a warning on the policy screens.",
        'field_label' => 'Framework',
        'none' => 'None',
        'submit' => 'Save',
        'available_heading' => 'Available frameworks',
    ],

];
