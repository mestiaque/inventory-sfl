<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvBrokenNeedle extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_broken_needles';

    protected $fillable = ['employee_id', 'department_id', 'broken_date', 'quantity', 'remarks', 'created_by'];

    protected $casts = [
        'broken_date' => 'date',
        'quantity'    => 'integer',
    ];

    /**
     * The HR employee who broke the needle (hr_employees), guarded with
     * class_exists() so this package doesn't hard-depend on hr-new being
     * installed — same pattern as InvOperator::employee() / InvRequisition::receiver().
     */
    public function employee(): BelongsTo
    {
        $employeeClass = class_exists(\ME\Hr\Models\HrEmployee::class) ? \ME\Hr\Models\HrEmployee::class : self::class;

        return $this->belongsTo($employeeClass, 'employee_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(InvDepartment::class, 'department_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
