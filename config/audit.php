<?php

use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\ContractItem;
use App\Models\ContractMilestone;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadReminder;
use App\Models\Payment;
use App\Models\PoultryQuotation;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationSection;
use App\Models\QuotationTerm;
use App\Models\Setting;
use App\Models\User;

return [
    /*
    |--------------------------------------------------------------------------
    | Models to Audit
    |--------------------------------------------------------------------------
    |
    | List of Eloquent models that should be automatically audited.
    | The observer will be attached to each model at boot time.
    |
    */
    'models' => [
        Contract::class,
        ContractItem::class,
        ContractMilestone::class,
        ContractClause::class,
        Quotation::class,
        QuotationItem::class,
        PoultryQuotation::class,
        Customer::class,
        Payment::class,
        Product::class,
        Lead::class,
        LeadActivity::class,
        LeadReminder::class,
        QuotationSection::class,
        QuotationTerm::class,
        Setting::class,
        User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Excluded Fields
    |--------------------------------------------------------------------------
    |
    | Fields that should never be stored in audit logs for any model.
    |
    */
    'global_exclude' => [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ],

    /*
    |--------------------------------------------------------------------------
    | Per-Model Excluded Fields
    |--------------------------------------------------------------------------
    |
    | Model-specific fields to exclude (in addition to global_exclude).
    | Use the fully-qualified class name as the key.
    |
    */
    'per_model_exclude' => [
        Quotation::class => ['pricing_snapshot', 'attachments'],
        PoultryQuotation::class => ['pricing_snapshot', 'image_path'],
        QuotationItem::class => ['total_price'],
        ContractItem::class => ['total_price'],
        User::class => ['profile_photo_path'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum Value Length
    |--------------------------------------------------------------------------
    |
    | Long values will be truncated to this length to avoid bloating the log.
    |
    */
    'max_value_length' => 2000,

    /*
    |--------------------------------------------------------------------------
    | Queue Audit Writes
    |--------------------------------------------------------------------------
    |
    | When true, audit log entries are dispatched to the queue instead of
    | being written synchronously. Requires a running queue worker.
    |
    */
    'queue' => false,

    /*
    |--------------------------------------------------------------------------
    | Retention Days
    |--------------------------------------------------------------------------
    |
    | Number of days to keep audit logs before pruning.
    |
    */
    'retention_days' => 365,
];
