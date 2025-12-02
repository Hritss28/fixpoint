<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone',
        'address',
        'city',
        'province',
        'postal_code',
        'tax_number',
        'payment_terms',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'payment_terms' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship with Products
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get supplier full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->province,
            $this->postal_code
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get active suppliers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get suppliers with payment terms
     */
    public function scopeWithPaymentTerms($query, int $days = null)
    {
        if ($days) {
            return $query->where('payment_terms', $days);
        }
        
        return $query->where('payment_terms', '>', 0);
    }
}
