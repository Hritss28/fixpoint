<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'format',
        'date_range_type',
        'start_date',
        'end_date',
        'customer_id',
        'supplier_id',
        'product_category',
        'include_charts',
        'group_by_category',
        'show_totals',
        'is_scheduled',
        'schedule_frequency',
        'schedule_time',
        'email_recipients',
        'last_generated_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'product_category' => 'array',
        'include_charts' => 'boolean',
        'group_by_category' => 'boolean',
        'show_totals' => 'boolean',
        'is_scheduled' => 'boolean',
        'schedule_time' => 'datetime',
        'email_recipients' => 'array',
        'last_generated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
