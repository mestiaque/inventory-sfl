<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAudit;

class InvStore extends Model
{
    use HasAudit;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_stores';

    /**
     * The factory's three store kinds (stored under their original type keys):
     *  - General Store: accessories bought through Purchase Requisition → Store Order → GRN
     *  - Buyer Store:   goods the buyer sends, received style-wise against a Merchandising order
     *  - Finish Store:  finished garments after production
     * Each receive screen only accepts its own kind (see storeTypeFor()).
     */
    public const TYPE_GENERAL = 'accessories';
    public const TYPE_BUYER = 'raw_material';
    public const TYPE_FINISH = 'finished_goods';

    public const TYPE_LABELS = [
        self::TYPE_GENERAL => 'General Store (Accessories)',
        self::TYPE_BUYER => 'Buyer Store',
        self::TYPE_FINISH => 'Finish Store (Finished Goods)',
    ];

    /** Store kind a receive of this source must go into. */
    public static function storeTypeFor(string $source): ?string
    {
        return [
            'purchase' => self::TYPE_GENERAL,
            'buyer_supplied' => self::TYPE_BUYER,
            'finished_goods' => self::TYPE_FINISH,
        ][$source] ?? null;
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucwords(str_replace('_', ' ', (string) $this->type));
    }

    protected $fillable = ['name', 'code', 'type', 'address', 'is_active', 'created_by'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isReferenced(): bool
    {
        return $this->stockTransactions()->exists();
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(InvStockTransaction::class, 'store_id');
    }
}
