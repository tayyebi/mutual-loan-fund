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
