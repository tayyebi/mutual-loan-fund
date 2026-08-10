<?php

return [

    // App\Domain\Frameworks\FrameworkComplianceChecker — advisory, non-blocking.
    'framework_drift' => 'مقدار :field در سیاست مالی شما برابر :current است که با چارچوب مالی انتخابی‌تان (:framework) مغایرت دارد (:requirement).',

    // App\Domain\Loans\LoanService
    'loans_disabled' => 'وام‌دهی برای این صندوق فعال نیست.',
    'loan_minimum_amount' => 'حداقل مبلغ وام :amount :currency است.',
    'loan_maximum_amount' => 'حداکثر مبلغ وام :amount :currency است.',
    'loan_exceeds_total_exposure' => 'با این درخواست، مجموع بدهی به :total :currency می‌رسد که از سقف :maximum :currency بیشتر است.',
    'loan_term_out_of_range' => 'مدت بازپرداخت باید بین :min تا :max ماه باشد.',
    'loan_active_limit_one' => 'این صندوق در هر زمان تنها یک وام فعال را مجاز می‌داند.',
    'loan_active_limit_many' => 'این صندوق در هر زمان حداکثر :count وام فعال را مجاز می‌داند.',
    'loan_membership_days_required' => 'عضویت در صندوق پیش از دریافت وام باید حداقل :required روز باشد (در حال حاضر :current روز).',
    'loan_not_permitted' => 'این وام مجاز نیست.',
    'loan_cannot_approve' => 'وام :reference در وضعیت :status است و امکان تأیید آن وجود ندارد.',
    'loan_no_longer_permitted' => 'این وام دیگر مجاز نیست.',
    'loan_cannot_reject' => 'وام :reference در وضعیت :status است و امکان رد آن وجود ندارد.',
    'loan_cannot_cancel' => 'وام :reference دیگر قابل لغو نیست.',
    'loan_must_be_approved_to_disburse' => 'وام :reference باید پیش از پرداخت، تأیید شده باشد.',
    'loan_currency_mismatch' => 'وام :reference به ارز :currency است.',

    // App\Domain\Accounting\PostingService
    'journal_not_posted_cannot_reverse' => 'سند :entry ثبت نشده و امکان برگشت آن وجود ندارد.',
    'journal_already_reversed' => 'سند :entry پیش‌تر برگشت خورده است.',
    'journal_unbalanced' => 'سند حسابداری متوازن نیست: بدهکار :debits :currency، بستانکار :credits :currency (اختلاف :difference).',
    'journal_no_value' => 'سند :entry ارزشی برای ثبت ندارد.',
    'entry_needs_two_lines' => 'هر سند حسابداری به حداقل دو ردیف نیاز دارد.',
    'account_foreign_to_group' => 'حساب :code متعلق به این صندوق نیست.',
    'account_inactive' => 'حساب :account غیرفعال است و امکان ثبت روی آن وجود ندارد.',
    'cost_center_foreign_to_group' => 'مرکز هزینه متعلق به این صندوق نیست.',
    'account_requires_cost_center' => 'حساب :account نیازمند مرکز هزینه است.',
    'line_amount_must_be_positive' => 'مبلغ ردیف‌ها باید بزرگ‌تر از صفر باشد (حساب :account).',

    // App\Domain\Accounting\ChartOfAccounts
    'chart_account_missing' => 'صندوق «:group» حساب :code را ندارد. فهرست حساب‌ها ناقص است.',
    'no_treasury_codes_remaining' => 'کد حساب خزانه‌ای در بازهٔ ۱۱۰۱ تا ۱۱۹۹ باقی نمانده است.',

    // App\Domain\Accounting\AccountingService
    'transaction_already_posted' => 'تراکنش #:id پیش‌تر در دفتر کل ثبت شده است.',
    'no_accounting_template' => 'برای نوع تراکنش «:type» الگوی حسابداری تعریف نشده است.',
    'adjustment_requires_reason' => 'هر اصلاحیه نیازمند ذکر دلیل است.',
    'reversal_requires_reason' => 'هر برگشت سند نیازمند ذکر دلیل است.',

    // App\Domain\Accounting\Templates\* (BaseTemplate, LoanRepaymentTemplate, LoanDisbursementTemplate,
    // TreasuryTransferTemplate, TreasuryExchangeTemplate)
    'transaction_no_treasury' => 'تراکنش #:id هیچ خزانه‌ای ندارد؛ محلی برای قرارگیری وجه وجود ندارد.',
    'treasury_no_ledger_account' => 'خزانهٔ «:name» حساب دفتر کل ندارد.',
    'transaction_needs_member_cost_center' => 'تراکنش #:id برای اسناد نیازمند مرکز هزینهٔ عضو است.',
    'fee_currency_mismatch' => 'ارز کارمزد (:fee_currency) با ارز خزانه (:treasury_currency) هم‌خوانی ندارد.',
    'repayment_no_loan' => 'تراکنش #:id از نوع بازپرداخت است اما هیچ وامی به آن متصل نیست.',
    'disbursement_no_loan' => 'تراکنش #:id از نوع پرداخت وام است اما هیچ وامی به آن متصل نیست.',
    'transfer_no_destination_treasury' => 'انتقال #:id خزانهٔ مقصد ندارد.',
    'transfer_currency_mismatch' => 'انتقال بین خزانه‌های با ارز متفاوت، یک تبادل ارزی است؛ آن را به همین شکل ثبت کنید.',
    'exchange_no_destination_treasury' => 'تبادل #:id خزانهٔ مقصد ندارد.',
    'exchange_missing_destination_amount' => 'مبلغ مقصد در تبادل #:id مشخص نشده است.',

    // App\Domain\Accounting\PeriodService
    'period_has_draft_entries' => 'دورهٔ مالی :period هنوز :count سند پیش‌نویس ثبت‌نشده دارد. پیش از بستن دوره، آن‌ها را ثبت یا حذف کنید.',

    // App\Domain\Accounting\Exceptions\ClosedPeriodException
    'accounting_period_closed' => 'دورهٔ مالی :period بسته است و امکان ثبت سند در آن وجود ندارد. اصلاحیه را در یک دورهٔ باز ثبت کنید.',

    // App\Domain\Policies\Exceptions\NoActivePolicyException
    'no_active_policy' => 'صندوق «:group» سیاست مالی فعالی ندارد. پیش از ثبت عملیات مالی، یک سیاست مالی منتشر کنید.',

    // App\Domain\ExchangeRates\Exceptions\MissingRateException
    'missing_gold_rate' => 'برای واحد :unit در تاریخ :date یا پیش از آن، نرخ طلای ۱۸ عیار ثبت نشده است. پیش از ثبت این عملیات، نرخی وارد کنید.',

    // App\Domain\SystemAdmin\SystemAdminService
    'last_system_admin' => 'این تنها مدیر سیستم پلتفرم است. ابتدا کاربر دیگری را ارتقا دهید.',

    // App\Domain\Groups\MembershipService
    'membership_suspended' => 'عضویت شما در این صندوق معلق شده است.',
    'last_group_admin' => 'این تنها مدیر صندوق است. ابتدا عضو دیگری را به مدیریت ارتقا دهید.',

    // App\Domain\Policies\PolicyValidator
    'policy_field_required' => 'وارد کردن :field الزامی است.',
    'policy_field_must_be_number' => ':field باید یک عدد باشد.',
    'policy_field_cannot_be_negative' => ':field نمی‌تواند منفی باشد.',
    'interest_rate_out_of_range' => 'نرخ سود باید بین :min% تا :max% باشد.',
    'minimum_term_at_least_one' => 'حداقل مدت باید حداقل ۱ ماه باشد.',
    'maximum_term_at_least_one' => 'حداکثر مدت باید حداقل ۱ ماه باشد.',
    'maximum_term_exceeds_ceiling' => 'حداکثر مدت نمی‌تواند از :ceiling ماه بیشتر باشد.',
    'minimum_term_exceeds_maximum' => 'حداقل مدت نمی‌تواند از حداکثر مدت بیشتر باشد.',
    'maximum_loan_amount_required' => 'تعیین حداکثر مبلغ وام الزامی است.',
    'maximum_loan_amount_must_be_positive' => 'در صورت فعال بودن وام‌دهی، حداکثر مبلغ وام باید بزرگ‌تر از صفر باشد.',
    'minimum_loan_amount_exceeds_maximum' => 'حداقل مبلغ وام نمی‌تواند از حداکثر مبلغ بیشتر باشد.',
    'at_least_one_active_loan' => 'باید حداقل یک وام فعال مجاز باشد.',
    'minimum_membership_days_negative' => 'حداقل روزهای عضویت نمی‌تواند منفی باشد.',
    'unknown_interest_method' => 'روش محاسبه سود نامشخص است.',
    'interest_rate_needs_method' => 'نرخ سود بالاتر از صفر نیازمند تعیین روش محاسبه سود است.',
    'interest_method_needs_rate' => 'روش محاسبه سود نیازمند نرخ سودی بالاتر از صفر است.',
    'minimum_contribution_exceeds_maximum' => 'حداقل آورده نمی‌تواند از حداکثر آورده بیشتر باشد.',
    'maximum_contribution_must_be_positive' => 'حداکثر آورده باید بزرگ‌تر از صفر باشد، یا برای عدم محدودیت خالی بماند.',
    'functional_currency_unsupported' => 'ارز مبنا پشتیبانی نمی‌شود.',
    'gold_unit_cannot_be_functional_currency' => 'واحد طلای ۱۸ عیار یک لایهٔ ارزش‌گذاری است و نمی‌تواند ارز مبنا باشد.',

    // App\Domain\Policies\PolicyService
    'draft_already_exists' => 'صندوق «:group» پیش‌تر پیش‌نویس نسخهٔ :version را دارد. ابتدا آن را ویرایش یا حذف کنید.',
    'policy_published_cannot_edit' => 'سیاست مالی نسخهٔ :version منتشر شده و قابل ویرایش نیست.',
    'policy_published_cannot_delete' => 'سیاست مالی نسخهٔ :version منتشر شده و قابل حذف نیست.',

    // App\Domain\Policies\PolicyPublisher
    'policy_not_draft' => 'سیاست مالی نسخهٔ :version پیش‌نویس نیست و امکان انتشار دوبارهٔ آن وجود ندارد.',

    // App\Domain\Transactions\TransactionService
    'contributions_disabled' => 'این صندوق آورده نمی‌پذیرد.',
    'contribution_minimum_amount' => 'حداقل آورده :amount :currency است.',
    'contribution_maximum_amount' => 'حداکثر آورده :amount :currency است.',
    'repayments_disabled' => 'بازپرداخت برای این صندوق فعال نیست.',
    'loan_not_outstanding' => 'این وام بدهی باقی‌مانده‌ای ندارد.',
    'repayment_minimum_amount' => 'حداقل مبلغ بازپرداخت :amount :currency است.',
    'repayment_currency_mismatch' => 'این وام باید به ارز :currency بازپرداخت شود.',
    'early_repayment_not_permitted' => 'بازپرداخت زودتر از موعد طبق سیاست مالی این وام مجاز نیست.',
    'transaction_already_in_status' => 'این تراکنش پیش‌تر در وضعیت :status قرار گرفته است.',
    'chain_transaction_duplicate' => 'این تراکنش بلاکچینی پیش‌تر در این برنامه ثبت شده است.',
    'chain_no_tx_hash' => 'هیچ شناسهٔ تراکنشی ارسال نشده است.',

    // App\Domain\Wallets\WalletService
    'network_unsupported' => 'شبکهٔ «:network» پشتیبانی نمی‌شود.',
    'address_invalid' => 'این مقدار یک آدرس معتبر برای شبکهٔ :network به نظر نمی‌رسد.',

    // App\Domain\ExchangeRates\RateService
    'gold_unit_cannot_be_quoted' => 'واحد طلا مرجع است و نمی‌تواند نسبت به خودش نرخ‌گذاری شود.',
    'valuation_unit_unsupported' => 'واحد :unit یک واحد ارزش‌گذاری پشتیبانی‌شده نیست.',
    'rate_must_be_positive' => 'نرخی بزرگ‌تر از صفر لازم است.',

    // App\Domain\Treasuries\TreasuryService
    'crypto_treasury_needs_network' => 'خزانهٔ ارز دیجیتال نیازمند تعیین شبکه است.',
    'only_crypto_treasuries_have_network' => 'فقط خزانه‌های ارز دیجیتال شبکه دارند.',

    // App\Http\Middleware\ResolveGroupContext
    'account_not_active' => 'حساب کاربری شما فعال نیست.',
    'fund_suspended' => 'این صندوق معلق شده است.',

    // App\Http\Middleware\EnsureGroupAdmin
    'restricted_to_group_admins' => 'این عملیات مخصوص مدیران صندوق است.',

    // App\Http\Middleware\EnsureSystemAdmin
    'restricted_to_system_admins' => 'این عملیات مخصوص مدیران سیستم است.',

    // App\Http\Controllers\TreasuryController
    'enter_reported_balance' => 'موجودی اعلام‌شده توسط بانک یا کاوشگر بلاکچین را وارد کنید.',

    // App\Http\Controllers\GroupController
    'fund_not_accepting_members' => 'این صندوق عضوگیری نمی‌کند.',

    // App\Http\Controllers\LedgerController
    'account_not_in_fund' => 'یکی از حساب‌های انتخاب‌شده متعلق به این صندوق نیست.',

    // App\Http\Controllers\TransactionController
    'restricted_to_admins_treasury_movements' => 'ثبت جابه‌جایی‌های خزانه فقط برای مدیران امکان‌پذیر است.',

];
