<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvRequisition extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_requisitions';

    protected $fillable = [
        'requisition_no', 'requisition_date', 'department_id', 'requisition_for', 'buyer_id', 'style', 'order_ref', 'store_id',
        'requested_by', 'received_by', 'status', 'approved_by', 'approved_at', 'approval_remarks', 'remarks', 'created_by',
    ];

    protected $casts = [
        'requisition_date' => 'date',
        'approved_at'      => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(InvDepartment::class, 'department_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(InvStore::class, 'store_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(InvBuyer::class, 'buyer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvRequisitionItem::class, 'requisition_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(InvIssue::class, 'requisition_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * "Received By" is the floor employee who physically takes the material
     * — HR's employee master (hr_employees), not a system login account.
     * Guarded with class_exists() so this package doesn't hard-depend on
     * hr-new being installed.
     */
    public function receiver(): BelongsTo
    {
        $employeeClass = class_exists(\ME\Hr\Models\HrEmployee::class) ? \ME\Hr\Models\HrEmployee::class : self::class;

        return $this->belongsTo($employeeClass, 'received_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isReferenced(): bool
    {
        return $this->issues()->exists();
    }

    /**
     * Recomputed whenever a challan against this requisition is prepared,
     * cancelled, or approved: approved -> partially_issued (some qty
     * committed) -> issued (every line fully committed). Bidirectional —
     * cancelling a challan decrements issued_qty back and this correctly
     * reverts the status to 'approved', not just advances it.
     */
    public function refreshIssueStatus(): void
    {
        $this->loadMissing('items');

        $fullyIssued = $this->items->every(fn (InvRequisitionItem $item) => $item->issued_qty >= $item->approved_qty);
        $anyIssued = $this->items->contains(fn (InvRequisitionItem $item) => $item->issued_qty > 0);

        if ($fullyIssued && $anyIssued) {
            $this->status = 'issued';
        } elseif ($anyIssued) {
            $this->status = 'partially_issued';
        } elseif (in_array($this->status, ['issued', 'partially_issued'], true)) {
            $this->status = 'approved';
        }

        $this->save();
    }
}
