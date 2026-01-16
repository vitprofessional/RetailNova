<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // Clean duplicates before adding unique index
        $duplicates = DB::table('expense_categories')
            ->select('name', DB::raw('COUNT(*) as count'))
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $idsToKeep = DB::table('expense_categories')
                ->where('name', $duplicate->name)
                ->orderBy('id')
                ->pluck('id')
                ->toArray();

            // Keep the first record, remove the rest
            if (count($idsToKeep) > 1) {
                $keepId = array_shift($idsToKeep);
                DB::table('expense_categories')
                    ->whereIn('id', $idsToKeep)
                    ->delete();
            }
        }

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
