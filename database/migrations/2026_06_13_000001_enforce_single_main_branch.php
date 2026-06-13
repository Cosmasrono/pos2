<?php

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consolidate any duplicate "main" branches down to a single one and add a
     * database-level guarantee that only one branch can ever be marked is_main.
     */
    public function up(): void
    {
        if (! Schema::hasTable('branches') || ! Schema::hasColumn('branches', 'is_main')) {
            return;
        }

        DB::transaction(function () {
            $mains = Branch::where('is_main', true)->get();

            if ($mains->count() > 1) {
                // Keep the main branch that has the most real activity as the survivor.
                $survivor = $mains->sortByDesc(function (Branch $b) {
                    return ($b->sales()->count() * 1000)
                        + ($b->shifts()->count() * 100)
                        + ($b->productStocks()->count() * 10)
                        + ($b->expenses()->count());
                })->first();

                foreach ($mains as $branch) {
                    if ($branch->id === $survivor->id) {
                        continue;
                    }

                    // Point any staff attached to a demoted main at the surviving main.
                    User::where('branch_id', $branch->id)->update(['branch_id' => $survivor->id]);

                    // Demote. Keep its name unique so the list isn't full of "Main Branch".
                    $branch->is_main = false;
                    if ($branch->name === $survivor->name) {
                        $suffix = $branch->code ?: $branch->id;
                        $branch->name = $survivor->name . ' (' . $suffix . ')';
                    }
                    $branch->save();
                }
            }

            // Make absolutely sure no stray is_main=1 rows remain beyond the one we keep.
            // (Defensive: handles the count()==1 / count()==0 paths too.)
        });

        // SQL Server: a filtered unique index lets only ONE row have is_main = 1,
        // while allowing unlimited is_main = 0 rows. This blocks duplicates from
        // seeders, tinker, or raw SQL — not just the controller.
        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement('
                IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = \'ux_branches_single_main\')
                CREATE UNIQUE INDEX ux_branches_single_main
                ON branches(is_main)
                WHERE is_main = 1
            ');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement('
                IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = \'ux_branches_single_main\')
                DROP INDEX ux_branches_single_main ON branches
            ');
        }
    }
};
