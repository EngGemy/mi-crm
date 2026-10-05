<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Branded proposal template (the 13-page MI brochure)
    |--------------------------------------------------------------------------
    | Static pages are imported as-is; data pages are rebuilt with live data.
    */
    'template_path' => storage_path('app/pdf-templates/mi-proposal-template.pdf'),

    'page_count' => 13,

    // Pages rebuilt with dynamic data (everything else is imported verbatim).
    'dynamic_pages' => [2, 6, 9, 10],

    /*
    |--------------------------------------------------------------------------
    | Content region geometry (mm) for rebuilt data pages
    |--------------------------------------------------------------------------
    | A white panel is painted over this region of the imported page, then the
    | dynamic HTML is written inside it — preserving the branded header/footer.
    | A4 = 210 x 297 mm.
    */
    'content_region' => [
        'x' => 6,
        'y' => 38,
        'w' => 198,   // 210 - 6 - 6
        'h' => 235,   // down to ~273mm, above the dark footer bar
    ],

    // Page 2 card sits on the barn photo. Cover only that card, not the picture.
    'content_region_page2' => [
        'x' => 16,
        'y' => 164,
        'w' => 178,
        'h' => 82,
    ],

    // Page 10 has a slightly taller header strip in the brochure.
    'content_region_page10' => [
        'x' => 6,
        'y' => 36,
        'w' => 198,
        'h' => 237,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fixed battery / cage spec constants (match the brochure spec sheet)
    |--------------------------------------------------------------------------
    */
    'specs' => [
        'transfer_distance_cm' => 102,   // مسافة الانتقال بين الخطوط
        'battery_height_m' => 3.40,       // ارتفاع البطارية
        'cage_length_cm' => 100,          // طول القفص
        'cage_depth_cm' => 65,            // عمق القفص
        'cage_height_cm' => 45,           // ارتفاع القفص
        'cage_area_cm2' => 6500,          // مساحة القفص (100 × 65)
        'zinc_coating_g_m2' => 275,       // معدل الطلاء المجلفن
    ],

    // Feeding zone (cm per bird) overrides to match the official spec sheet;
    // falls back to round(cage_length / birds, 2) for weights not listed.
    'feeding_zone_cm' => [
        21 => 4.76,
        18 => 5.5,
        16 => 6.25,
        13 => 7.7,
        12 => 8.3,
    ],

    'primary_color' => '#C00000',

    /*
    |--------------------------------------------------------------------------
    | Page 10 — financial offer defaults
    |--------------------------------------------------------------------------
    | Copied into pricing_snapshot.terms the first time a quote is saved.
    | The PDF reads the snapshot; these values are only for legacy quotes.
    |
    | Unit «داجن» is the brochure's Unit column (a poultry unit), not «دواجن».
    */
    'validity_days' => 3,

    'warranty_years' => 12,

    'unit' => 'داجن',

    'offer_includes' => [
        'النقل.',
        'التركيب شاملاً إقامة العمالة.',
        'منظومة تعليف أتوماتيك مع سايلو.',
        'أعمال السباكة لمنظومة الشرب.',
        'منظومة خروج الفضلات وتحميلها آليًا.',
        'لوحة تحكم للبطاريات شاملة الكابلات وحامل الكابلات.',
        'مواتير الكهرباء الخاصة بتشغيل البطارية أتوماتيك (<span class="hl">جديدة تركي</span>).',
        'معدل الطلاء المجلفن <span class="hl">:zinc:</span> جم زنك/م².',
        'ضمان <span class="hl">:warranty:</span> عام للحديد وجلفنة الصاج.',
    ],
];
