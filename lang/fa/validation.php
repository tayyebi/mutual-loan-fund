<?php

return [

    /*
    |--------------------------------------------------------------------------
    | خطوط زبان اعتبارسنجی (پیش‌فرض‌های چارچوب لاراول)
    |--------------------------------------------------------------------------
    |
    | خطوط زبان زیر پیام‌های خطای پیش‌فرض استفاده‌شده توسط کلاس اعتبارسنج
    | هستند. برخی از این قواعد نسخه‌های متعددی دارند، مانند قواعد اندازه.
    | در صورت نیاز می‌توانید هر یک از این پیام‌ها را ویرایش کنید.
    |
    */

    'accepted' => 'فیلد :attribute باید پذیرفته شود.',
    'accepted_if' => 'فیلد :attribute باید زمانی که :other برابر با :value است پذیرفته شود.',
    'active_url' => 'فیلد :attribute باید یک نشانی اینترنتی معتبر باشد.',
    'after' => 'فیلد :attribute باید تاریخی بعد از :date باشد.',
    'after_or_equal' => 'فیلد :attribute باید تاریخی بعد از یا برابر با :date باشد.',
    'alpha' => 'فیلد :attribute باید فقط شامل حروف باشد.',
    'alpha_dash' => 'فیلد :attribute باید فقط شامل حروف، اعداد، خط تیره و خط زیر باشد.',
    'alpha_num' => 'فیلد :attribute باید فقط شامل حروف و اعداد باشد.',
    'array' => 'فیلد :attribute باید آرایه باشد.',
    'ascii' => 'فیلد :attribute باید فقط شامل نویسه‌ها و نمادهای تک‌بایتی باشد.',
    'before' => 'فیلد :attribute باید تاریخی قبل از :date باشد.',
    'before_or_equal' => 'فیلد :attribute باید تاریخی قبل از یا برابر با :date باشد.',
    'between' => [
        'array' => 'فیلد :attribute باید بین :min تا :max مورد داشته باشد.',
        'file' => 'فیلد :attribute باید بین :min تا :max کیلوبایت باشد.',
        'numeric' => 'فیلد :attribute باید بین :min تا :max باشد.',
        'string' => 'فیلد :attribute باید بین :min تا :max نویسه باشد.',
    ],
    'boolean' => 'فیلد :attribute باید درست یا نادرست باشد.',
    'can' => 'فیلد :attribute حاوی مقداری غیرمجاز است.',
    'confirmed' => 'تکرار فیلد :attribute مطابقت ندارد.',
    'contains' => 'فیلد :attribute فاقد مقداری الزامی است.',
    'current_password' => 'گذرواژه نادرست است.',
    'date' => 'فیلد :attribute یک تاریخ معتبر نیست.',
    'date_equals' => 'فیلد :attribute باید تاریخی برابر با :date باشد.',
    'date_format' => 'فیلد :attribute با قالب :format مطابقت ندارد.',
    'decimal' => 'فیلد :attribute باید :decimal رقم اعشار داشته باشد.',
    'declined' => 'فیلد :attribute باید رد شود.',
    'declined_if' => 'فیلد :attribute باید زمانی که :other برابر با :value است رد شود.',
    'different' => 'فیلد :attribute و :other باید متفاوت باشند.',
    'digits' => 'فیلد :attribute باید :digits رقم باشد.',
    'digits_between' => 'فیلد :attribute باید بین :min تا :max رقم باشد.',
    'dimensions' => 'فیلد :attribute ابعاد تصویر نامعتبری دارد.',
    'distinct' => 'فیلد :attribute مقدار تکراری دارد.',
    'doesnt_end_with' => 'فیلد :attribute نباید با یکی از موارد زیر پایان یابد: :values.',
    'doesnt_start_with' => 'فیلد :attribute نباید با یکی از موارد زیر آغاز شود: :values.',
    'email' => 'فیلد :attribute باید یک نشانی ایمیل معتبر باشد.',
    'ends_with' => 'فیلد :attribute باید با یکی از موارد زیر پایان یابد: :values.',
    'enum' => ':attribute انتخاب‌شده نامعتبر است.',
    'exists' => ':attribute انتخاب‌شده نامعتبر است.',
    'extensions' => 'فیلد :attribute باید یکی از پسوندهای زیر را داشته باشد: :values.',
    'failed' => 'اطلاعات ورود با سوابق ما مطابقت ندارد.',
    'file' => 'فیلد :attribute باید یک فایل باشد.',
    'filled' => 'فیلد :attribute باید مقدار داشته باشد.',
    'gt' => [
        'array' => 'فیلد :attribute باید بیش از :value مورد داشته باشد.',
        'file' => 'فیلد :attribute باید بزرگ‌تر از :value کیلوبایت باشد.',
        'numeric' => 'فیلد :attribute باید بزرگ‌تر از :value باشد.',
        'string' => 'فیلد :attribute باید بیش از :value نویسه باشد.',
    ],
    'gte' => [
        'array' => 'فیلد :attribute باید :value مورد یا بیشتر داشته باشد.',
        'file' => 'فیلد :attribute باید بزرگ‌تر یا مساوی :value کیلوبایت باشد.',
        'numeric' => 'فیلد :attribute باید بزرگ‌تر یا مساوی :value باشد.',
        'string' => 'فیلد :attribute باید :value نویسه یا بیشتر باشد.',
    ],
    'hex_color' => 'فیلد :attribute باید یک رنگ هگزادسیمال معتبر باشد.',
    'image' => 'فیلد :attribute باید یک تصویر باشد.',
    'in' => ':attribute انتخاب‌شده نامعتبر است.',
    'in_array' => 'فیلد :attribute در :other وجود ندارد.',
    'integer' => 'فیلد :attribute باید عدد صحیح باشد.',
    'ip' => 'فیلد :attribute باید یک نشانی IP معتبر باشد.',
    'ipv4' => 'فیلد :attribute باید یک نشانی IPv4 معتبر باشد.',
    'ipv6' => 'فیلد :attribute باید یک نشانی IPv6 معتبر باشد.',
    'json' => 'فیلد :attribute باید یک رشته JSON معتبر باشد.',
    'lowercase' => 'فیلد :attribute باید با حروف کوچک باشد.',
    'lt' => [
        'array' => 'فیلد :attribute باید کمتر از :value مورد داشته باشد.',
        'file' => 'فیلد :attribute باید کمتر از :value کیلوبایت باشد.',
        'numeric' => 'فیلد :attribute باید کمتر از :value باشد.',
        'string' => 'فیلد :attribute باید کمتر از :value نویسه باشد.',
    ],
    'lte' => [
        'array' => 'فیلد :attribute نباید بیش از :value مورد داشته باشد.',
        'file' => 'فیلد :attribute باید کوچک‌تر یا مساوی :value کیلوبایت باشد.',
        'numeric' => 'فیلد :attribute باید کوچک‌تر یا مساوی :value باشد.',
        'string' => 'فیلد :attribute باید کوچک‌تر یا مساوی :value نویسه باشد.',
    ],
    'mac_address' => 'فیلد :attribute باید یک نشانی MAC معتبر باشد.',
    'max' => [
        'array' => 'فیلد :attribute نباید بیش از :max مورد داشته باشد.',
        'file' => 'فیلد :attribute نباید بزرگ‌تر از :max کیلوبایت باشد.',
        'numeric' => 'فیلد :attribute نباید بزرگ‌تر از :max باشد.',
        'string' => 'فیلد :attribute نباید بیش از :max نویسه باشد.',
    ],
    'max_digits' => 'فیلد :attribute نباید بیش از :max رقم داشته باشد.',
    'mimes' => 'فیلد :attribute باید فایلی از نوع: :values باشد.',
    'mimetypes' => 'فیلد :attribute باید فایلی از نوع: :values باشد.',
    'min' => [
        'array' => 'فیلد :attribute باید حداقل :min مورد داشته باشد.',
        'file' => 'فیلد :attribute باید حداقل :min کیلوبایت باشد.',
        'numeric' => 'فیلد :attribute باید حداقل :min باشد.',
        'string' => 'فیلد :attribute باید حداقل :min نویسه باشد.',
    ],
    'min_digits' => 'فیلد :attribute باید حداقل :min رقم داشته باشد.',
    'missing' => 'فیلد :attribute باید موجود نباشد.',
    'missing_if' => 'فیلد :attribute باید زمانی که :other برابر با :value است موجود نباشد.',
    'missing_unless' => 'فیلد :attribute باید موجود نباشد مگر آنکه :other برابر با :value باشد.',
    'missing_with' => 'فیلد :attribute باید زمانی که :values موجود است، موجود نباشد.',
    'missing_with_all' => 'فیلد :attribute باید زمانی که :values موجودند، موجود نباشد.',
    'multiple_of' => 'فیلد :attribute باید مضربی از :value باشد.',
    'not_in' => ':attribute انتخاب‌شده نامعتبر است.',
    'not_regex' => 'قالب فیلد :attribute نامعتبر است.',
    'numeric' => 'فیلد :attribute باید عدد باشد.',
    'password' => [
        'letters' => 'فیلد :attribute باید حداقل شامل یک حرف باشد.',
        'mixed' => 'فیلد :attribute باید حداقل شامل یک حرف بزرگ و یک حرف کوچک باشد.',
        'numbers' => 'فیلد :attribute باید حداقل شامل یک عدد باشد.',
        'symbols' => 'فیلد :attribute باید حداقل شامل یک نماد باشد.',
        'uncompromised' => ':attribute واردشده در یک نشت اطلاعاتی مشاهده شده است. لطفاً :attribute دیگری انتخاب کنید.',
    ],
    'present' => 'فیلد :attribute باید حاضر باشد.',
    'present_if' => 'فیلد :attribute باید زمانی که :other برابر با :value است حاضر باشد.',
    'present_unless' => 'فیلد :attribute باید حاضر باشد مگر آنکه :other برابر با :value باشد.',
    'present_with' => 'فیلد :attribute باید زمانی که :values حاضر است، حاضر باشد.',
    'present_with_all' => 'فیلد :attribute باید زمانی که :values حاضرند، حاضر باشد.',
    'prohibited' => 'فیلد :attribute ممنوع است.',
    'prohibited_if' => 'فیلد :attribute زمانی که :other برابر با :value است ممنوع است.',
    'prohibited_unless' => 'فیلد :attribute ممنوع است مگر آنکه :other در :values باشد.',
    'prohibits' => 'فیلد :attribute مانع از حضور :other می‌شود.',
    'regex' => 'قالب فیلد :attribute نامعتبر است.',
    'required' => 'فیلد :attribute الزامی است.',
    'required_array_keys' => 'فیلد :attribute باید شامل ورودی‌هایی برای: :values باشد.',
    'required_if' => 'فیلد :attribute زمانی که :other برابر با :value است الزامی است.',
    'required_if_accepted' => 'فیلد :attribute زمانی که :other پذیرفته شده است الزامی است.',
    'required_if_declined' => 'فیلد :attribute زمانی که :other رد شده است الزامی است.',
    'required_unless' => 'فیلد :attribute الزامی است مگر آنکه :other در :values باشد.',
    'required_with' => 'فیلد :attribute زمانی که :values حاضر است الزامی است.',
    'required_with_all' => 'فیلد :attribute زمانی که :values حاضرند الزامی است.',
    'required_without' => 'فیلد :attribute زمانی که :values حاضر نیست الزامی است.',
    'required_without_all' => 'فیلد :attribute زمانی که هیچ‌کدام از :values حاضر نیستند الزامی است.',
    'same' => 'فیلد :attribute و :other باید مطابقت داشته باشند.',
    'size' => [
        'array' => 'فیلد :attribute باید شامل :size مورد باشد.',
        'file' => 'فیلد :attribute باید :size کیلوبایت باشد.',
        'numeric' => 'فیلد :attribute باید :size باشد.',
        'string' => 'فیلد :attribute باید :size نویسه باشد.',
    ],
    'starts_with' => 'فیلد :attribute باید با یکی از موارد زیر آغاز شود: :values.',
    'string' => 'فیلد :attribute باید رشته باشد.',
    'timezone' => 'فیلد :attribute باید یک منطقه زمانی معتبر باشد.',
    'unique' => 'مقدار فیلد :attribute پیش‌تر ثبت شده است.',
    'uploaded' => 'بارگذاری فیلد :attribute ناموفق بود.',
    'uppercase' => 'فیلد :attribute باید با حروف بزرگ باشد.',
    'url' => 'فیلد :attribute باید یک نشانی اینترنتی معتبر باشد.',
    'ulid' => 'فیلد :attribute باید یک ULID معتبر باشد.',
    'uuid' => 'فیلد :attribute باید یک UUID معتبر باشد.',

    /*
    |--------------------------------------------------------------------------
    | خطوط زبان اعتبارسنجی سفارشی
    |--------------------------------------------------------------------------
    |
    | در اینجا می‌توانید پیام‌های اعتبارسنجی سفارشی را برای فیلدها با استفاده
    | از قرارداد "attribute.rule" برای نام‌گذاری خطوط مشخص کنید. این کار
    | تعیین یک خط زبان سفارشی برای یک قاعدهٔ فیلد خاص را ساده می‌کند.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'پیام-سفارشی',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | نام‌های سفارشی فیلدهای اعتبارسنجی
    |--------------------------------------------------------------------------
    |
    | خطوط زبان زیر برای جایگزینی نگه‌دارندهٔ نام فیلد با عبارتی خواناتر
    | استفاده می‌شوند، مانند "نشانی ایمیل" به‌جای "email". این کار پیام‌های
    | ما را کمی تمیزتر می‌کند.
    |
    */

    'attributes' => [],

];
