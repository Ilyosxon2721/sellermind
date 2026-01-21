<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Quyidagi til satrlari validatsiya xato xabarlarini o'z ichiga oladi.
    |
    */

    'accepted' => ':Attribute maydoni qabul qilinishi kerak.',
    'accepted_if' => ':Attribute maydoni :other :value bo\'lganda qabul qilinishi kerak.',
    'active_url' => ':Attribute maydoni to\'g\'ri URL bo\'lishi kerak.',
    'after' => ':Attribute maydoni :date dan keyingi sana bo\'lishi kerak.',
    'after_or_equal' => ':Attribute maydoni :date ga teng yoki undan keyingi sana bo\'lishi kerak.',
    'alpha' => ':Attribute maydoni faqat harflardan iborat bo\'lishi kerak.',
    'alpha_dash' => ':Attribute maydoni faqat harflar, raqamlar, tire va pastki chiziqlardan iborat bo\'lishi kerak.',
    'alpha_num' => ':Attribute maydoni faqat harflar va raqamlardan iborat bo\'lishi kerak.',
    'array' => ':Attribute maydoni massiv bo\'lishi kerak.',
    'ascii' => ':Attribute maydoni faqat bir baytli alfanumerik belgilardan iborat bo\'lishi kerak.',
    'before' => ':Attribute maydoni :date dan oldingi sana bo\'lishi kerak.',
    'before_or_equal' => ':Attribute maydoni :date ga teng yoki undan oldingi sana bo\'lishi kerak.',
    'between' => [
        'array' => ':Attribute maydoni :min dan :max gacha elementlardan iborat bo\'lishi kerak.',
        'file' => ':Attribute maydoni :min dan :max kilobaytgacha bo\'lishi kerak.',
        'numeric' => ':Attribute maydoni :min dan :max gacha bo\'lishi kerak.',
        'string' => ':Attribute maydoni :min dan :max belgigacha bo\'lishi kerak.',
    ],
    'boolean' => ':Attribute maydoni true yoki false bo\'lishi kerak.',
    'can' => ':Attribute maydoni ruxsatsiz qiymatni o\'z ichiga oladi.',
    'confirmed' => ':Attribute maydonining tasdiqlashi mos kelmaydi.',
    'contains' => ':Attribute maydonida talab qilingan qiymat yo\'q.',
    'current_password' => 'Parol noto\'g\'ri.',
    'date' => ':Attribute maydoni to\'g\'ri sana bo\'lishi kerak.',
    'date_equals' => ':Attribute maydoni :date ga teng sana bo\'lishi kerak.',
    'date_format' => ':Attribute maydoni :format formatiga mos kelishi kerak.',
    'decimal' => ':Attribute maydoni :decimal o\'nlik xonaga ega bo\'lishi kerak.',
    'declined' => ':Attribute maydoni rad etilishi kerak.',
    'declined_if' => ':Attribute maydoni :other :value bo\'lganda rad etilishi kerak.',
    'different' => ':Attribute va :other maydonlari farqli bo\'lishi kerak.',
    'digits' => ':Attribute maydoni :digits raqamdan iborat bo\'lishi kerak.',
    'digits_between' => ':Attribute maydoni :min dan :max gacha raqamdan iborat bo\'lishi kerak.',
    'dimensions' => ':Attribute maydoni noto\'g\'ri rasm o\'lchamlariga ega.',
    'distinct' => ':Attribute maydoni takrorlanuvchi qiymatga ega.',
    'doesnt_end_with' => ':Attribute maydoni quyidagilardan biri bilan tugamasligi kerak: :values.',
    'doesnt_start_with' => ':Attribute maydoni quyidagilardan biri bilan boshlanmasligi kerak: :values.',
    'email' => ':Attribute maydoni to\'g\'ri email manzil bo\'lishi kerak.',
    'ends_with' => ':Attribute maydoni quyidagilardan biri bilan tugashi kerak: :values.',
    'enum' => 'Tanlangan :attribute noto\'g\'ri.',
    'exists' => 'Tanlangan :attribute noto\'g\'ri.',
    'extensions' => ':Attribute maydoni quyidagi kengaytmalardan biriga ega bo\'lishi kerak: :values.',
    'file' => ':Attribute maydoni fayl bo\'lishi kerak.',
    'filled' => ':Attribute maydoni qiymatga ega bo\'lishi kerak.',
    'gt' => [
        'array' => ':Attribute maydoni :value dan ko\'p elementga ega bo\'lishi kerak.',
        'file' => ':Attribute maydoni :value kilobaytdan katta bo\'lishi kerak.',
        'numeric' => ':Attribute maydoni :value dan katta bo\'lishi kerak.',
        'string' => ':Attribute maydoni :value belgidan uzun bo\'lishi kerak.',
    ],
    'gte' => [
        'array' => ':Attribute maydoni :value yoki undan ko\'p elementga ega bo\'lishi kerak.',
        'file' => ':Attribute maydoni :value kilobayt yoki undan katta bo\'lishi kerak.',
        'numeric' => ':Attribute maydoni :value yoki undan katta bo\'lishi kerak.',
        'string' => ':Attribute maydoni :value belgi yoki undan uzun bo\'lishi kerak.',
    ],
    'hex_color' => ':Attribute maydoni to\'g\'ri o\'n oltilik rang bo\'lishi kerak.',
    'image' => ':Attribute maydoni rasm bo\'lishi kerak.',
    'in' => 'Tanlangan :attribute noto\'g\'ri.',
    'in_array' => ':Attribute maydoni :other da mavjud bo\'lishi kerak.',
    'integer' => ':Attribute maydoni butun son bo\'lishi kerak.',
    'ip' => ':Attribute maydoni to\'g\'ri IP manzil bo\'lishi kerak.',
    'ipv4' => ':Attribute maydoni to\'g\'ri IPv4 manzil bo\'lishi kerak.',
    'ipv6' => ':Attribute maydoni to\'g\'ri IPv6 manzil bo\'lishi kerak.',
    'json' => ':Attribute maydoni to\'g\'ri JSON qatori bo\'lishi kerak.',
    'list' => ':Attribute maydoni ro\'yxat bo\'lishi kerak.',
    'lowercase' => ':Attribute maydoni kichik harflarda bo\'lishi kerak.',
    'lt' => [
        'array' => ':Attribute maydoni :value dan kam elementga ega bo\'lishi kerak.',
        'file' => ':Attribute maydoni :value kilobaytdan kichik bo\'lishi kerak.',
        'numeric' => ':Attribute maydoni :value dan kichik bo\'lishi kerak.',
        'string' => ':Attribute maydoni :value belgidan qisqa bo\'lishi kerak.',
    ],
    'lte' => [
        'array' => ':Attribute maydoni :value dan ko\'p elementga ega bo\'lmasligi kerak.',
        'file' => ':Attribute maydoni :value kilobaytdan oshmasligi kerak.',
        'numeric' => ':Attribute maydoni :value dan oshmasligi kerak.',
        'string' => ':Attribute maydoni :value belgidan oshmasligi kerak.',
    ],
    'mac_address' => ':Attribute maydoni to\'g\'ri MAC manzil bo\'lishi kerak.',
    'max' => [
        'array' => ':Attribute maydoni :max dan ko\'p elementga ega bo\'lmasligi kerak.',
        'file' => ':Attribute maydoni :max kilobaytdan oshmasligi kerak.',
        'numeric' => ':Attribute maydoni :max dan oshmasligi kerak.',
        'string' => ':Attribute maydoni :max belgidan oshmasligi kerak.',
    ],
    'max_digits' => ':Attribute maydoni :max raqamdan oshmasligi kerak.',
    'mimes' => ':Attribute maydoni quyidagi turdagi fayl bo\'lishi kerak: :values.',
    'mimetypes' => ':Attribute maydoni quyidagi turdagi fayl bo\'lishi kerak: :values.',
    'min' => [
        'array' => ':Attribute maydoni kamida :min elementga ega bo\'lishi kerak.',
        'file' => ':Attribute maydoni kamida :min kilobayt bo\'lishi kerak.',
        'numeric' => ':Attribute maydoni kamida :min bo\'lishi kerak.',
        'string' => ':Attribute maydoni kamida :min belgi bo\'lishi kerak.',
    ],
    'min_digits' => ':Attribute maydoni kamida :min raqamga ega bo\'lishi kerak.',
    'missing' => ':Attribute maydoni mavjud bo\'lmasligi kerak.',
    'missing_if' => ':Attribute maydoni :other :value bo\'lganda mavjud bo\'lmasligi kerak.',
    'missing_unless' => ':Attribute maydoni :other :value bo\'lmasa mavjud bo\'lmasligi kerak.',
    'missing_with' => ':Attribute maydoni :values mavjud bo\'lganda mavjud bo\'lmasligi kerak.',
    'missing_with_all' => ':Attribute maydoni :values mavjud bo\'lganda mavjud bo\'lmasligi kerak.',
    'multiple_of' => ':Attribute maydoni :value ning ko\'paytmasi bo\'lishi kerak.',
    'not_in' => 'Tanlangan :attribute noto\'g\'ri.',
    'not_regex' => ':Attribute maydoni formati noto\'g\'ri.',
    'numeric' => ':Attribute maydoni son bo\'lishi kerak.',
    'password' => [
        'letters' => ':Attribute maydoni kamida bitta harf o\'z ichiga olishi kerak.',
        'mixed' => ':Attribute maydoni kamida bitta katta va bitta kichik harf o\'z ichiga olishi kerak.',
        'numbers' => ':Attribute maydoni kamida bitta raqam o\'z ichiga olishi kerak.',
        'symbols' => ':Attribute maydoni kamida bitta belgi o\'z ichiga olishi kerak.',
        'uncompromised' => 'Berilgan :attribute ma\'lumotlar oqishida aniqlangan. Iltimos, boshqa :attribute tanlang.',
    ],
    'present' => ':Attribute maydoni mavjud bo\'lishi kerak.',
    'present_if' => ':Attribute maydoni :other :value bo\'lganda mavjud bo\'lishi kerak.',
    'present_unless' => ':Attribute maydoni :other :value bo\'lmasa mavjud bo\'lishi kerak.',
    'present_with' => ':Attribute maydoni :values mavjud bo\'lganda mavjud bo\'lishi kerak.',
    'present_with_all' => ':Attribute maydoni :values mavjud bo\'lganda mavjud bo\'lishi kerak.',
    'prohibited' => ':Attribute maydoni taqiqlangan.',
    'prohibited_if' => ':Attribute maydoni :other :value bo\'lganda taqiqlangan.',
    'prohibited_unless' => ':Attribute maydoni :other :values ichida bo\'lmasa taqiqlangan.',
    'prohibits' => ':Attribute maydoni :other ning mavjudligini taqiqlaydi.',
    'regex' => ':Attribute maydoni formati noto\'g\'ri.',
    'required' => ':Attribute maydoni to\'ldirilishi shart.',
    'required_array_keys' => ':Attribute maydoni quyidagi kalitlarni o\'z ichiga olishi kerak: :values.',
    'required_if' => ':Attribute maydoni :other :value bo\'lganda to\'ldirilishi shart.',
    'required_if_accepted' => ':Attribute maydoni :other qabul qilinganda to\'ldirilishi shart.',
    'required_unless' => ':Attribute maydoni :other :values ichida bo\'lmasa to\'ldirilishi shart.',
    'required_with' => ':Attribute maydoni :values mavjud bo\'lganda to\'ldirilishi shart.',
    'required_with_all' => ':Attribute maydoni :values mavjud bo\'lganda to\'ldirilishi shart.',
    'required_without' => ':Attribute maydoni :values mavjud bo\'lmaganda to\'ldirilishi shart.',
    'required_without_all' => ':Attribute maydoni :values dan hech biri mavjud bo\'lmaganda to\'ldirilishi shart.',
    'same' => ':Attribute va :other maydonlari mos kelishi kerak.',
    'size' => [
        'array' => ':Attribute maydoni :size elementdan iborat bo\'lishi kerak.',
        'file' => ':Attribute maydoni :size kilobayt bo\'lishi kerak.',
        'numeric' => ':Attribute maydoni :size bo\'lishi kerak.',
        'string' => ':Attribute maydoni :size belgidan iborat bo\'lishi kerak.',
    ],
    'starts_with' => ':Attribute maydoni quyidagilardan biri bilan boshllanishi kerak: :values.',
    'string' => ':Attribute maydoni satr bo\'lishi kerak.',
    'timezone' => ':Attribute maydoni to\'g\'ri vaqt zonasi bo\'lishi kerak.',
    'unique' => 'Bunday :attribute allaqachon mavjud.',
    'uploaded' => ':Attribute ni yuklash muvaffaqiyatsiz bo\'ldi.',
    'uppercase' => ':Attribute maydoni katta harflarda bo\'lishi kerak.',
    'url' => ':Attribute maydoni to\'g\'ri URL bo\'lishi kerak.',
    'ulid' => ':Attribute maydoni to\'g\'ri ULID bo\'lishi kerak.',
    'uuid' => ':Attribute maydoni to\'g\'ri UUID bo\'lishi kerak.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [],

];
