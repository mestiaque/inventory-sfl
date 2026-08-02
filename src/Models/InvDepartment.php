<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvDepartment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_departments';

    protected $fillable = ['name', 'code', 'default_store_id', 'is_active', 'created_by'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function defaultStore(): BelongsTo
    {
        return $this->belongsTo(InvStore::class, 'default_store_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isReferenced(): bool
    {
        return InvRequisition::where('department_id', $this->id)->exists()
            || InvIssue::where('department_id', $this->id)->exists()
            || InvProductionConsumption::where('department_id', $this->id)->exists();
    }
}
