<?php

namespace Database\Seeders\Concerns;

/**
 * Allows a demo seeder to target a specific business ID rather than the
 * hard-coded default of 1.  Usage:
 *
 *   $seeder = app(MobileShopSeeder::class);
 *   $seeder->businessId = $targetId;
 *   app()->call([$seeder, 'run']);
 */
trait BusinessTypeSeedable
{
    public int $businessId = 1;

    /**
     * Id (as string, the way products store it) of a per-business master row
     * such as a brand, category or unit, creating it for this business if needed.
     */
    protected function masterId(string $table, string $name): string
    {
        $query = \Illuminate\Support\Facades\DB::table($table)
            ->where('businessId', $this->businessId)->where('name', $name);
        $id = $query->value('id');
        if (!$id) {
            $id = \Illuminate\Support\Facades\DB::table($table)->insertGetId([
                'name' => $name, 'businessId' => $this->businessId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return (string) $id;
    }
}
