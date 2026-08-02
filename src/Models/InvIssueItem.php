<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvIssueItem extends Model
{
    use HasFactory;

    protected $table = 'inv_issue_items';

    protected $fillable = [
        'issue_id', 'requisition_item_id', 'item_id', 'issued_qty', 'department_received_qty',
        'unit_rate', 'amount', 'remarks',
    ];

    protected $casts = [
        'issued_qty'               => 'decimal:4',
        'department_received_qty'  => 'decimal:4',
        'unit_rate'                => 'decimal:2',
        'amount'                   => 'decimal:2',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(InvIssue::class, 'issue_id');
    }

    public function requisitionItem(): BelongsTo
    {
        return $this->belongsTo(InvRequisitionItem::class, 'requisition_item_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }
}
