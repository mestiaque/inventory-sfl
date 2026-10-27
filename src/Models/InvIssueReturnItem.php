<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAudit;

class InvIssueReturnItem extends Model
{
    use HasAudit;

    protected $table = 'inv_issue_return_items';

    protected $fillable = [
        'return_id', 'issue_item_id', 'item_id', 'color_id', 'size_id', 'returned_qty', 'unit_rate', 'amount',
    ];

    protected $casts = [
        'returned_qty' => 'decimal:4',
        'unit_rate'    => 'decimal:2',
        'amount'       => 'decimal:2',
    ];

    /**
     * Returned qty / amount per issue line (columns: issue_item_id, qty, amount). Left-join it on
     * inv_issue_items.id wherever issued quantities are summed, so "issued" means net of returns.
     */
    public static function totalsByIssueItem(): Builder
    {
        return DB::table('inv_issue_return_items as rti')
            ->join('inv_issue_returns as rt', 'rt.id', '=', 'rti.return_id')
            ->whereNull('rt.deleted_at')
            ->groupBy('rti.issue_item_id')
            ->selectRaw('rti.issue_item_id, SUM(rti.returned_qty) as qty, SUM(rti.amount) as amount');
    }

    public function issueReturn(): BelongsTo
    {
        return $this->belongsTo(InvIssueReturn::class, 'return_id');
    }

    public function issueItem(): BelongsTo
    {
        return $this->belongsTo(InvIssueItem::class, 'issue_item_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(InvColor::class, 'color_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(InvSize::class, 'size_id');
    }
}
