<?php

/*
| The member surface — /u/{group}.
|
| Every string here is written from the member's side of the relationship:
| "what you have paid in", not "capital contributions"; "what you owe", not
| "loans receivable". The accounting words are correct and they are used on the
| administrator's surface, where they belong.
*/

return [

    'overview' => [
        'heading' => 'You in :fund',
        'governed_by' => 'Running under policy v:version.',
        'read_the_rules' => 'Read the rules',
        'no_active_policy' => 'This fund has no active rules yet, so nothing can be paid in or borrowed.',
        'contribute' => 'Pay in',
        'request_loan' => 'Request a loan',

        'not_tracked' => 'Your place in the books is still being set up, so the figures below will read zero until an administrator finishes approving you.',

        'your_stake' => 'Your stake',
        'worth_gold' => 'about :grams :unit',
        'see_my_money' => 'See my money',

        'you_owe' => 'You owe',
        'open_loans' => '{0} No open loans|{1} One open loan|[2,*] :count open loans',
        'see_borrowing' => 'See my borrowing',

        'you_could_borrow' => 'You could borrow',
        'no_limit_set' => 'No limit set',

        'your_recent_activity' => 'Your recent activity',
        'all_activity' => 'All fund activity',
        'col_date' => 'Date',
        'col_what' => 'What',
        'col_amount' => 'Amount',
        'col_status' => 'Status',
        'nothing_yet' => 'You have not paid anything in yet.',

        'waiting_on' => 'Waiting on someone',
        'nothing_waiting' => 'Nothing of yours is waiting on a decision.',
        'pending_submissions' => '{1} One payment you submitted is waiting to be verified|[2,*] :count payments you submitted are waiting to be verified',
        'verification_note' => 'A payment counts towards your stake once an administrator has verified it against the fund\'s records.',
    ],

    'money' => [
        'title' => 'My money',
        'heading' => 'My money',
        'intro' => 'What you have paid into the fund, and what your share is worth today.',
        'contribute' => 'Pay in',

        'not_tracked' => 'Your place in the books is still being set up, so these figures will read zero for now.',

        'paid_in' => 'You have paid in',
        'lent_to_you' => 'The fund has lent you',
        'net_worth_today' => 'Your share today',
        'gold_note' => 'What you have paid in, less what you owe, valued at today\'s rate.',
        'no_rate' => 'Not valued',
        'no_rate_note' => 'No exchange rate has been recorded for today.',

        'your_payments' => 'Your payments in',
        'all_activity' => 'All fund activity',
        'col_date' => 'Date',
        'col_amount' => 'Amount',
        'col_paid_into' => 'Paid into',
        'col_status' => 'Status',
        'no_contributions' => 'You have not paid anything in yet.',
        'calculated_note' => 'These figures come from the fund\'s books, and only count payments an administrator has verified.',
    ],

    'contribute_wizard' => [
        'title' => 'Pay in',
        'steps' => ['Where', 'Amount', 'Review'],

        'treasury_heading' => 'Where are you paying this in to?',
        'treasury_intro' => 'The fund keeps money in more than one place. Pick the one you paid into.',
        'treasury_label' => 'Paid into',

        'amount_heading' => 'How much did you pay in?',
        'amount_intro' => 'Into :treasury.',
        'amount_label' => 'Amount (:currency)',

        'review_heading' => 'Review your payment',
        'review_summary' => 'into :treasury.',
        'review_note' => 'This counts towards your stake once an administrator verifies it against the fund\'s records.',
        'extras_summary' => 'Change the date, or add a note or receipt (optional)',
        'date_label' => 'Date paid',
        'note_label' => 'Note',
        'reference_label' => 'Reference',
        'receipt_label' => 'Receipt',
        'receipt_hint' => 'A photo or PDF of your proof of payment.',
        'tx_hash_label' => 'Transaction hash',
        'from_address_label' => 'Sent from address',
        'tx_hash_hint' => 'If you have it, this lets the fund confirm the transfer on the blockchain automatically.',
        'confirm_button' => 'Confirm payment',
    ],

    'borrowing' => [
        'title' => 'Borrowing',
        'heading' => 'Borrowing',
        'intro' => 'What you can borrow, what you owe, and how to pay it back.',
        'request' => 'Request a loan',
        'no_active_policy' => 'This fund has no active rules yet, so nobody can borrow from it.',

        'where_you_stand' => 'Where you stand',
        'can_borrow_now' => 'You could borrow',
        'no_limit_set' => 'No limit set',
        'already_owed' => 'You already owe',
        'open_loans' => 'Open loans',
        'of_maximum' => ':count of :max allowed',
        'membership_length' => 'Time in the fund',
        'days_of_required' => ':days days, of :required needed',
        'cannot_borrow_now' => 'You cannot borrow right now:',

        'what_you_owe' => 'What you owe',
        'col_reference' => 'Loan',
        'col_borrowed' => 'Borrowed',
        'col_still_owed' => 'Still owed',
        'col_next_payment' => 'Next payment',
        'col_status' => 'Status',
        'col_requested_on' => 'Requested',
        'nothing_owed' => 'You owe the fund nothing.',
        'repay_note' => 'Open a loan to record a repayment against it.',

        'past_loans' => 'Past loans',
    ],

    'loan_wizard' => [
        'title' => 'Request a loan',
        'steps' => ['Amount', 'Term', 'Review'],

        'not_lending_notice' => 'This fund is not lending right now, but you can still see what a request would look like.',

        'amount_heading' => 'How much would you like to borrow?',
        'amount_label' => 'Amount',
        'currency_label' => 'Currency',
        'amount_range_note' => 'Between :min and :max.',
        'unlimited' => 'no maximum',

        'term_heading' => 'How long do you need to pay it back?',
        'term_label' => 'Repayment term',
        'term_option' => '{1} 1 month|[2,*] :count months',
        'cannot_borrow_now' => 'You cannot borrow right now:',

        'review_heading' => 'Review your loan request',
        'review_summary' => '{1} Borrowed for one month.|[2,*] Borrowed for :count months.',
        'review_interest_note' => 'At :rate% interest, under this fund\'s current rules.',
        'extras_summary' => 'Add a note about what it\'s for (optional)',
        'purpose_label' => 'What is it for?',
        'confirm_button' => 'Request this loan',
    ],

    'repay_wizard' => [
        'title' => 'Repay a loan',
        'steps' => ['Where', 'Amount', 'Review'],

        'treasury_heading' => 'Where are you paying this back to?',
        'treasury_intro' => 'The fund keeps money in more than one place. Pick the one you paid into.',
        'treasury_label' => 'Paid into',

        'amount_heading' => 'How much are you repaying?',
        'amount_intro' => 'Towards loan :reference. You currently owe :outstanding.',
        'amount_label' => 'Amount (:currency)',

        'review_heading' => 'Review your repayment',
        'review_summary' => 'towards loan :reference, into :treasury.',
        'review_note' => 'This reduces what you owe once an administrator verifies it against the fund\'s records.',
        'extras_summary' => 'Change the date, or add a reference or blockchain hash (optional)',
        'date_label' => 'Date paid',
        'reference_label' => 'Reference',
        'tx_hash_label' => 'Transaction hash',
        'tx_hash_hint' => 'If you have it, this lets the fund confirm the transfer on the blockchain automatically.',
        'confirm_button' => 'Confirm repayment',
    ],

    'wallet_wizard' => [
        'title' => 'Register a wallet',
        'steps' => ['Network', 'Address'],

        'network_heading' => 'Which network and currency?',
        'network_label' => 'Network',
        'currency_label' => 'Currency',

        'address_heading' => "What's the address?",
        'address_intro' => 'A :currency address on the network you chose.',
        'address_label' => 'Address',
        'address_hint' => 'The public address only — never a private key or seed phrase.',
        'extras_summary' => 'Add a label to help you recognise it later (optional)',
        'label_label' => 'Label',
        'confirm_button' => 'Register this wallet',
    ],

    'fund' => [
        'title' => 'The fund',
        'heading' => ':fund',
        'intro' => 'How the fund is doing. Your money is in here, so these are your figures too.',
        'the_rules' => 'The rules',
        'the_members' => 'The members',

        'total_value' => 'The fund holds',
        'gold_grams' => 'grams of :unit',
        'unvalued_lines' => ':count entries could not be valued at today\'s rate.',

        'lent_out' => 'Lent out to members',
        'lent_out_note' => 'Owed back to the fund by everyone who has borrowed.',
        'none' => 'Nothing',

        'members' => 'Members',
        'see_everyone' => 'See everyone',

        'where_the_money_is' => 'Where the money is',
        'treasuries_note' => 'Every account and wallet the fund keeps money in.',
        'col_holding' => 'Held in',
        'col_balance' => 'Balance',
        'col_approx_gold' => 'Approx. gold',
        'no_rate' => 'no rate',
        'no_treasuries' => 'This fund holds nothing yet.',

        'governed_by' => 'Policy v:version has been in force since',
        'rules_note' => 'Loans and payments you already have keep the rules they were made under.',
        'read_the_rules' => 'Read the rules in full',
        'no_active_policy' => 'This fund has no active rules yet.',
    ],

    'rules' => [
        'title' => 'Fund rules',
        'breadcrumb' => 'The fund',
        'heading' => 'Fund rules',
        'intro' => 'The rules currently governing new operations. Loans and payments you already have keep the rules they were made under.',
        'no_active_policy' => 'This fund has no active policy. New financial operations are refused until an administrator publishes one.',
        'framework_drift_heading' => "This fund's rules drift from its chosen financial framework:",
        'active_since' => 'Policy v:version, active since',
    ],

];
