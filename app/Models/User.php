<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\Wishlist;
use App\Models\ProductReview;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'provider',
        'provider_id',
        'email_verified_at',
        'phone',
        'avatar',
        'role',
        // Building store fields
        'customer_type',
        'company_name',
        'tax_number',
        'tax_number',
        'billing_address',
        'shipping_address',
        'is_verified',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        // Building store casts
        'is_verified' => 'boolean',
    ];
    
    // All other methods remain the same...
    
    /**
     * Get the addresses for the user.
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get the orders for the user.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    
    /**
     * Get the user's wishlist items.
     */
    public function wishlistItems()
    {
        return $this->belongsToMany(Product::class, 'wishlist_items')
                    ->withTimestamps();
    }
    
    /**
     * Check if a product is in the user's wishlist.
     *
     * @param int $productId
     * @return bool
     */
    public function hasInWishlist($productId)
    {
        return $this->wishlist()->where('product_id', $productId)->exists();
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Get the user's default shipping address.
     */
    public function defaultAddress()
    {
        return $this->addresses()->where('is_default', true)->first();
    }
    
    /**
     * Get the user's avatar URL.
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        
        // Default avatar based on name initials
        $name = $this->name ?? 'User';
        $initials = mb_strtoupper(mb_substr($name, 0, 1));
        $color = substr(md5($this->id ?? rand()), 0, 6);
        
        return "https://ui-avatars.com/api/?name={$initials}&background={$color}&color=ffffff&size=150";
    }
    
    /**
     * Check if user is a new customer (registered in the last 30 days).
     */
    public function getIsNewCustomerAttribute()
    {
        return $this->created_at->diffInDays(now()) <= 30;
    }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function productReviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * Mendapatkan alamat default pengguna.
     *
     * @return \App\Models\Address|null
     */
    public function getDefaultAddressAttribute()
    {
        return $this->addresses()->where('is_default', true)->first() 
               ?? $this->addresses()->first();
    }

    /**
     * Get the social accounts for the user.
     */
    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * User's contact messages
     */
    public function contactMessages()
    {
        return $this->hasMany(ContactMessage::class);
    }

    // ===== BUILDING STORE ENHANCEMENTS =====

    /**
     * Customer type constants
     */
    public const CUSTOMER_TYPE_RETAIL = 'retail';
    public const CUSTOMER_TYPE_WHOLESALE = 'wholesale';
    public const CUSTOMER_TYPE_CONTRACTOR = 'contractor';
    public const CUSTOMER_TYPE_DISTRIBUTOR = 'distributor';

    /**
     * Relationship with Delivery Notes as customer
     */
    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'customer_id');
    }

    /**
     * Check if user is retail customer
     */
    public function isRetailCustomer(): bool
    {
        return $this->customer_type === self::CUSTOMER_TYPE_RETAIL;
    }

    /**
     * Check if user is wholesale customer
     */
    public function isWholesaleCustomer(): bool
    {
        return $this->customer_type === self::CUSTOMER_TYPE_WHOLESALE;
    }

    /**
     * Check if user is contractor
     */
    public function isContractor(): bool
    {
        return $this->customer_type === self::CUSTOMER_TYPE_CONTRACTOR;
    }

    /**
     * Get customer type label
     */
    public function getCustomerTypeLabelAttribute(): string
    {
        return match($this->customer_type) {
            self::CUSTOMER_TYPE_RETAIL => 'Retail',
            self::CUSTOMER_TYPE_WHOLESALE => 'Grosir',
            self::CUSTOMER_TYPE_CONTRACTOR => 'Kontraktor',
            self::CUSTOMER_TYPE_DISTRIBUTOR => 'Distributor',
            default => 'Unknown',
        };
    }

    /**
     * Get company display name
     */
    public function getCompanyDisplayNameAttribute(): string
    {
        return $this->company_name ?: $this->name;
    }

    /**
     * Scope for verified customers
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope by customer type
     */
    public function scopeByCustomerType($query, string $customerType)
    {
        return $query->where('customer_type', $customerType);
    }

    /**
     * Scope for wholesale customers
     */
    public function scopeWholesale($query)
    {
        return $query->where('customer_type', self::CUSTOMER_TYPE_WHOLESALE);
    }

    /**
     * Scope for contractors
     */
    public function scopeContractors($query)
    {
        return $query->where('customer_type', self::CUSTOMER_TYPE_CONTRACTOR);
    }

    /**
     * Get all available customer types
     */
    public static function getAvailableCustomerTypes(): array
    {
        return [
            self::CUSTOMER_TYPE_RETAIL => 'Retail',
            self::CUSTOMER_TYPE_WHOLESALE => 'Grosir',
            self::CUSTOMER_TYPE_CONTRACTOR => 'Kontraktor',
            self::CUSTOMER_TYPE_DISTRIBUTOR => 'Distributor',
        ];
    }
}