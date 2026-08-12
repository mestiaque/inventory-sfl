<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvMachine extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_machines';

    protected $fillable = [
        'name', 'code', 'machine_no', 'model', 'origin', 'type', 'color', 'description',
        'department_id', 'section', 'line', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(InvDepartment::class, 'department_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isReferenced(): bool
    {
        return InvBrokenNeedle::where('machine_id', $this->id)->exists();
    }
}
