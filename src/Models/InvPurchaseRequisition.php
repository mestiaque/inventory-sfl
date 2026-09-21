<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvPurchaseRequisition extends Model
{
    use HasAudit;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_purchase_requisitions';

    protected $fillable = [
        'requisition_no', 'requisition_date', 'department_id', 'requested_by',
        'status', 'approved_by', 'approved_at', 'approval_remarks', 'remarks', 'created_by',
    ];

    protected $casts = [
        'requisition_date' => 'date',
        'approved_at'      => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(InvDepartment::class, 'department_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvPurchaseRequisitionItem::class, 'purchase_requisition_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(InvPurchaseOrder::class, 'purchase_requisition_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isReferenced(): bool
    {
        return $this->purchaseOrders()->exists();
    }

    /**
     * Recomputed whenever a Purchase Order is created against this
     * requisition: approved -> partially_converted (some qty committed) ->
     * converted (every line fully committed).
     */
    public function refreshConversionStatus(): void
    {
        $this->loadMissing('items');

        $fullyConverted = $this->items->every(fn (InvPurchaseRequisitionItem $item) => $item->converted_qty >= $item->approved_qty);
        $anyConverted = $this->items->contains(fn (InvPurchaseRequisitionItem $item) => $item->converted_qty > 0);

        if ($fullyConverted && $anyConverted) {
            $this->status = 'converted';
        } elseif ($anyConverted) {
            $this->status = 'partially_converted';
        } elseif (in_array($this->status, ['converted', 'partially_converted'], true)) {
            $this->status = 'approved';
        }

        $this->save();
    }
}
