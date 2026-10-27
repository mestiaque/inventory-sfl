<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAudit;

class InvIssueReturn extends Model
{
    use HasAudit;
    use SoftDeletes;

    protected $table = 'inv_issue_returns';

    protected $fillable = [
        'return_no', 'issue_id', 'store_id', 'return_date', 'reason', 'returned_by', 'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(InvIssue::class, 'issue_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(InvStore::class, 'store_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvIssueReturnItem::class, 'return_id');
    }

    public function returner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
