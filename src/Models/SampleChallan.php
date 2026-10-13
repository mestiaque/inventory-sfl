<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Delivery Challan — samples (buyer / PP) issued out of a store, against Merchandising PO lines. */
class SampleChallan extends Model
{
    use HasAudit;
    use SoftDeletes;

    protected $table = 'inv_sample_challans';

    protected $fillable = [
        'challan_no', 'challan_date', 'store_id', 'msfl_buyer_id', 'msfl_sample_type_id', 'sample_type_name',
        'to_name', 'to_address', 'to_contact', 'remarks', 'issued_by',
    ];

    protected $casts = ['challan_date' => 'date'];

    /** The "<TYPE> SAMPLE FROM SFL" column header — "P.P" when no sample type is picked. */
    public function ppColumnLabel(): string
    {
        return strtoupper($this->sample_type_name ?: 'P.P') . ' SAMPLE FROM SFL';
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(InvStore::class, 'store_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SampleChallanItem::class)->orderBy('sequence');
    }
}
