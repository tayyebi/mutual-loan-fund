<?php

/*
| Chrome shared by every step-by-step wizard, on any surface — the words on
| the Back/Continue/Cancel controls and the step-progress row, kept in one
| place (rather than in member.php or fund.php) because <x-wizard-step> and
| <x-step-progress> (resources/views/components/*) are generic components
| used by /u and /g wizards alike, not member-only vocabulary.
*/

return [
    'back' => 'Back',
    'cancel' => 'Cancel',
    'continue' => 'Continue',
    'progress_label' => 'Step :current of :total',
];
