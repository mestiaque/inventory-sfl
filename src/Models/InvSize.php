<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvSize extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_sizes';

    protected $fillable = ['name', 'sort_order', 'is_active', 'created_by'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public function isReferenced(): bool
    {
        return InvItem::where('size_id', $this->id)->exists();
    }
}
