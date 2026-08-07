<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvOperator extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_operators';

    protected $fillable = ['name', 'code', 'designation', 'user_id', 'employee_id', 'store_id', 'is_active', 'created_by'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The HR employee record for this operator (hr_employees), separate
     * from their system login (user_id) — guarded with class_exists() so
     * this package doesn't hard-depend on hr-new being installed.
     */
    public function employee(): BelongsTo
    {
        $employeeClass = class_exists(\ME\Hr\Models\HrEmployee::class) ? \ME\Hr\Models\HrEmployee::class : self::class;

        return $this->belongsTo($employeeClass, 'employee_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(InvStore::class, 'store_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isStoreScoped(): bool
    {
        return in_array($this->designation, ['store_incharge', 'store_manager'], true);
    }
}
