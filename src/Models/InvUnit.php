<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvUnit extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_units';

    protected $fillable = ['name', 'short_name', 'is_active', 'created_by'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isReferenced(): bool
    {
        return InvItem::where('unit_id', $this->id)->exists();
    }
}
