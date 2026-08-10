<?php

return [

    // App\Domain\Policies\Categories\* — category label() and PolicyField label/help
    // strings, driving the generic policy edit/show forms.
    'fields' => [

        'categories' => [
            'loans' => 'وام‌ها',
            'contributions' => 'آورده‌ها',
            'repayments' => 'بازپرداخت‌ها',
            'membership' => 'عضویت',
            'accounting' => 'حسابداری',
            'treasury' => 'خزانه',
        ],

        'loans' => [
            'enabled' => 'وام‌دهی فعال باشد',
            'minimum_amount' => 'حداقل مبلغ',
            'maximum_amount' => 'حداکثر مبلغ',
            'interest_rate' => 'نرخ سود',
            'interest_method' => 'روش محاسبه سود',
            'interest_method_none' => 'بدون سود',
            'interest_method_flat' => 'ثابت بر مبنای اصل مبلغ',
            'interest_method_declining' => 'مانده کاهشی',
            'minimum_term_months' => 'حداقل مدت',
            'maximum_term_months' => 'حداکثر مدت',
            'maximum_active_loans' => 'حداکثر وام‌های فعال',
            'minimum_membership_days' => 'حداقل مدت عضویت',
            'early_repayment_allowed' => 'بازپرداخت زودتر از موعد مجاز باشد',
        ],

        'contributions' => [
            'enabled' => 'آورده‌گیری فعال باشد',
            'minimum_amount' => 'حداقل مبلغ',
            'maximum_amount' => 'حداکثر مبلغ',
            'maximum_amount_help' => 'برای عدم محدودیت، خالی بگذارید.',
        ],

        'repayments' => [
            'enabled' => 'بازپرداخت فعال باشد',
            'minimum_amount' => 'حداقل مبلغ',
        ],

        'membership' => [
            'member_approval_required' => 'تأیید مدیر صندوق الزامی باشد',
            'member_approval_required_help' => 'در صورت غیرفعال بودن، درخواست عضویت بلافاصله فعال می‌شود.',
        ],

        'accounting' => [
            'functional_currency' => 'ارز مبنا',
            'functional_currency_help' => 'ارز گزارش‌گیری که اسناد حسابداری بر مبنای آن متوازن می‌شوند. جدا از لایهٔ ارزش‌گذاری طلای ۱۸ عیار.',
        ],

        'treasury' => [
            'admin_verification_required' => 'تأیید مدیر صندوق الزامی باشد',
            'admin_verification_required_help' => 'در صورت غیرفعال بودن، تراکنشی که شواهد بلاکچینی آن توسط سرور تأیید شده، بدون بررسی انسانی دوم ثبت می‌شود.',
        ],

    ],

    'index' => [
        'title' => 'سیاست‌های مالی',
        'heading' => 'سیاست‌های مالی',
        'intro' => 'قواعد مالی نسخه‌بندی می‌شوند. نسخهٔ منتشرشده تغییرناپذیر است؛ تغییر قواعد به معنای انتشار نسخهٔ جدید است و عملیات موجود همان نسخه‌ای را که تحت آن ایجاد شده‌اند حفظ می‌کنند.',
        'new_draft' => 'پیش‌نویس جدید',
        'continue_draft' => 'ادامهٔ پیش‌نویس نسخهٔ :version',
        'framework_heading' => 'چارچوب مالی',
        'framework_none' => 'انتخاب نشده',
        'framework_change' => 'تغییر…',
        'version_header' => 'نسخه',
        'status_header' => 'وضعیت',
        'effective_from_header' => 'اجرا از',
        'effective_until_header' => 'اجرا تا',
        'published_by_header' => 'منتشرشده توسط',
        'active_badge' => 'فعال',
        'edit_link' => 'ویرایش',
        'publish_link' => 'انتشار',
        'compare_link' => 'مقایسه با نسخهٔ فعال',
        'empty' => 'هنوز هیچ نسخه‌ای از سیاست مالی وجود ندارد.',
    ],

    'edit' => [
        'title' => 'ویرایش پیش‌نویس سیاست مالی نسخهٔ :version',
        'breadcrumb' => 'سیاست‌های مالی',
        'heading' => 'پیش‌نویس نسخهٔ :version',
        'intro' => 'ذخیرهٔ پیش‌نویس چیزی را تغییر نمی‌دهد. انتشار یک اقدام جداگانه و صریح است.',
        'publish_link' => 'انتشار…',
        'framework_drift_heading' => 'این پیش‌نویس از چارچوب مالی انتخابی شما مغایرت دارد:',
        'save' => 'ذخیرهٔ پیش‌نویس',
        'view_draft' => 'مشاهدهٔ پیش‌نویس',
        'monetary_hint' => 'محدودیت‌های مالی با مبلغ هر عملیات در واحد پول خود آن عملیات مقایسه می‌شوند.',
    ],

    'show' => [
        'title' => 'سیاست مالی نسخهٔ :version',
        'breadcrumb' => 'سیاست‌های مالی',
        'heading' => 'سیاست مالی نسخهٔ :version',
        'active_badge' => 'فعال',
        'present' => 'اکنون',
        'edit_link' => 'ویرایش پیش‌نویس',
        'publish_link' => 'انتشار',
        'draft_notice' => 'این یک پیش‌نویس است. چیزی را اداره نمی‌کند و تا زمان انتشار معتبر نیست.',
        'framework_drift_heading' => 'این سیاست مالی از چارچوب مالی انتخابی شما مغایرت دارد:',
        'provenance_heading' => 'منشأ',
        'created_by' => 'ایجادشده توسط',
        'published_by' => 'منتشرشده توسط',
        'governs_heading' => 'اداره می‌کند',
        'loans' => 'وام‌ها',
        'transactions' => 'تراکنش‌ها',
        'governs_note' => 'این رکوردها همیشه همین نسخه را حفظ می‌کنند، هرچه بعداً منتشر شود.',
        'discard_heading' => 'حذف',
        'discard_note' => 'یک پیش‌نویس قابل حذف است؛ نسخهٔ منتشرشده قابل حذف نیست.',
        'delete_draft' => 'حذف پیش‌نویس',
    ],


    'publish' => [
        'title' => 'انتشار سیاست مالی نسخهٔ :version',
        'breadcrumb' => 'سیاست‌های مالی',
        'heading' => 'در حال انتشار سیاست مالی نسخهٔ :version هستید',
        'changes_heading' => 'تغییرات',
        'no_changes' => 'این پیش‌نویس هیچ تغییری نسبت به سیاست فعال ایجاد نمی‌کند.',
        'setting_header' => 'تنظیم',
        'now_header' => 'اکنون',
        'after_header' => 'پس از انتشار',
        'applies_note' => 'این سیاست مالی بر عملیات ایجادشده پس از انتشار اعمال می‌شود. وام‌ها و تراکنش‌های موجود همچنان تابع نسخه‌های اصلی سیاست خود باقی می‌مانند.',
        'framework_drift_heading' => 'این سیاست مالی از چارچوب مالی انتخابی شما مغایرت دارد. همچنان می‌توانید آن را منتشر کنید — این فقط جنبهٔ اطلاع‌رسانی دارد.',
        'blocked_heading' => 'این پیش‌نویس هنوز قابل انتشار نیست:',
        'confirm_line' => 'می‌دانم که نسخهٔ :version سیاست فعال می‌شود',
        'confirm_superseded' => 'و نسخهٔ :active_version جایگزین‌شده اعلام می‌شود',
        'submit' => 'انتشار نسخهٔ :version',
        'what_happens_heading' => 'انتشار چه می‌کند',
        'step_validates' => 'کل سیاست مالی را اعتبارسنجی می‌کند.',
        'step_closes' => 'نسخهٔ فعال کنونی را می‌بندد.',
        'step_makes_active' => 'نسخهٔ :version را از امروز فعال می‌کند.',
        'step_records' => 'ثبت می‌کند چه کسی و چه زمانی آن را منتشر کرده است.',
        'step_audit' => 'یک رویداد حسابرسی ثبت می‌کند.',
        'transaction_note' => 'همهٔ این‌ها در یک تراکنش پایگاه‌داده: صندوق هرگز با دو نسخهٔ فعال، یا بدون هیچ‌کدام، باقی نمی‌ماند.',
    ],

    'compare' => [
        'title' => 'مقایسهٔ نسخهٔ :from با نسخهٔ :to',
        'breadcrumb' => 'سیاست‌های مالی',
        'intro' => 'یک مقایسهٔ مدیریتی. چیزی را تغییر نمی‌دهد و به حسابداری دست نمی‌زند.',
        'setting_header' => 'تنظیم',
        'identical' => 'این نسخه‌ها یکسان هستند.',
    ],

];
