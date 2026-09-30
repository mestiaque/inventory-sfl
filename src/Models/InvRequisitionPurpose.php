<?php

namespace ME\SflInventory\Models;

use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * "Requisition For" master. Store Requisitions store the purpose's `code`
 * in inv_requisitions.requisition_for.
 */
class InvRequisitionPurpose extends Model
{
    use HasAudit;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_requisition_purposes';

    protected $fillable = ['name', 'code', 'sort_order', 'is_active', 'created_by'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function isReferenced(): bool
    {
        return InvRequisition::withTrashed()->where('requisition_for', $this->code)->exists();
    }
}
