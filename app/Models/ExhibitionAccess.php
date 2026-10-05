<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExhibitionAccess extends Model
{
    protected $table = 'exhibition_access';

    protected $fillable = [
        'user_id',
        'report_daily',
        'report_weekly',
        'view_team',
    ];

    protected $casts = [
        'report_daily' => 'boolean',
        'report_weekly' => 'boolean',
        'view_team' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
