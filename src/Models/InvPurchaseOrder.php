<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvPurchaseOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_purchase_orders';

    protected $fillable = [
        'po_number', 'supplier_id', 'order_date', 'expected_date', 'status', 'total_amount',
        'remarks', 'approved_by', 'approved_at', 'created_by',
    ];

    protected $casts = [
        'order_date'    => 'date',
        'expected_date' => 'date',
        'approved_at'   => 'datetime',
        'total_amount'  => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(InvSupplier::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvPurchaseOrderItem::class, 'purchase_order_id');
    }

    public function grns(): HasMany
    {
        return $this->hasMany(InvGrn::class, 'purchase_order_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isReferenced(): bool
    {
        return $this->grns()->exists();
    }

    /**
     * A GRN (challan) can only be received against a PO that has been
     * approved — draft POs have no approval on record yet, so nothing
     * should be allowed to arrive against them.
     */
    public function scopeSelectableForGrn(Builder $query): Builder
    {
        return $query->whereIn('status', ['approved', 'received']);
    }

    /**
     * Recomputed after every GRN post: draft/approved -> received (once any
     * quantity has come in) -> closed (once every line is fully received).
     */
    public function refreshReceiptStatus(): void
    {
        $this->loadMissing('items');

        $fullyReceived = $this->items->every(fn (InvPurchaseOrderItem $item) => $item->received_qty >= $item->quantity);
        $anyReceived = $this->items->contains(fn (InvPurchaseOrderItem $item) => $item->received_qty > 0);

        if ($fullyReceived) {
            $this->status = 'closed';
        } elseif ($anyReceived) {
            $this->status = 'received';
        }

        $this->save();
    }
}
