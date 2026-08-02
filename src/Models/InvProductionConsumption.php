<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvProductionConsumption extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_production_consumptions';

    protected $fillable = [
        'consumption_no', 'department_id', 'issue_id', 'style', 'order_ref', 'consumption_date',
        'store_id', 'remarks', 'created_by',
    ];

    protected $casts = [
        'consumption_date' => 'date',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(InvDepartment::class, 'department_id');
    }

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
        return $this->hasMany(InvProductionConsumptionItem::class, 'consumption_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
