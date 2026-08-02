<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Received By" on a requisition means the floor employee who physically
     * takes the material — that's HR's `hr_employees` master, not a system
     * login account. Existing values referenced users.id and are meaningless
     * under the new meaning, so they're cleared.
     *
     * No DB-level foreign key here: hr_employees.id is a plain `int unsigned`
     * (not `bigint unsigned` like every other FK in this package), which MySQL
     * refuses to link via a real constraint (errno 150, mismatched column
     * types). Referential integrity is enforced at the application layer
     * instead (InvRequisitionRequest: exists:hr_employees,id).
     */
    public function up(): void
    {
        $this->dropForeignIfExists('received_by');

        DB::table('inv_requisitions')->update(['received_by' => null]);
    }

    public function down(): void
    {
        // Nothing to restore — the prior FK (to users) was never actually
        // present in the database (confirmed via information_schema before
        // writing this migration), so there is nothing to roll back to.
    }

    private function dropForeignIfExists(string $column): void
    {
        $constraint = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inv_requisitions'
             AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$column]
        );

        if ($constraint) {
            DB::statement("ALTER TABLE inv_requisitions DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
        }
    }
};
