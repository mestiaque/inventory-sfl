<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvIssue extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_issues';

    protected $fillable = [
        'issue_no', 'requisition_id', 'store_id', 'to_store_id', 'department_id', 'buyer_id', 'style', 'order_ref',
        'issue_date', 'status', 'issued_by', 'authorized_by', 'authorized_at', 'approved_by', 'approved_at',
        'department_received_by', 'department_received_at', 'department_receive_status',
        'department_receive_remarks', 'remarks', 'created_by',
    ];

    protected $casts = [
        'issue_date'              => 'date',
        'authorized_at'           => 'datetime',
        'approved_at'             => 'datetime',
        'department_received_at'  => 'datetime',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(InvRequisition::class, 'requisition_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(InvStore::class, 'store_id');
    }

    public function toStore(): BelongsTo
    {
        return $this->belongsTo(InvStore::class, 'to_store_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(InvDepartment::class, 'department_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(InvBuyer::class, 'buyer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvIssueItem::class, 'issue_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function departmentReceiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'department_received_by');
    }
}
