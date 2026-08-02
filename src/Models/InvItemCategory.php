<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvItemCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_item_categories';

    protected $fillable = ['name', 'code', 'parent_id', 'is_active', 'created_by'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeParents(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function isReferenced(): bool
    {
        return $this->children()->exists()
            || InvItem::where('category_id', $this->id)->orWhere('sub_category_id', $this->id)->exists();
    }
}
