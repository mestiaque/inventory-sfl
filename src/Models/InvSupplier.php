<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAudit;

class InvSupplier extends Model
{
    use HasAudit;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_suppliers';

    protected $fillable = [
        'name', 'code', 'address', 'contact_person', 'products_supplied', 'relation_since_year',
        'phone', 'email', 'tin_vat', 'price_rating', 'quality_rating', 'remarks', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(InvPurchaseOrder::class, 'supplier_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isReferenced(): bool
    {
        return $this->purchaseOrders()->exists();
    }
}
