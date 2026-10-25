<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $businessIds = DB::table('business_setups')->orderBy('id')->limit(2)->pluck('id');
        $unassignedCount = DB::table('admin_users')->whereNull('businessId')
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) <> 'superadmin'")
            ->count();

        if ($businessIds->count() !== 1) {
            if ($businessIds->count() > 1) {
                Log::warning('Admin business backfill skipped because the installation has multiple businesses.', [
                    'business_count' => $businessIds->count(),
                    'unassigned_admin_count' => $unassignedCount,
                ]);
            } elseif ($businessIds->isEmpty() && $unassignedCount > 0) {
                Log::warning('Admin business backfill skipped because no business exists.', [
                    'unassigned_admin_count' => $unassignedCount,
                ]);
            }

            return;
        }

        $businessId = (int) $businessIds->first();

        DB::transaction(function () use ($businessId): void {
            $users = DB::table('admin_users')->orderBy('id')->get(['id', 'role', 'businessId']);
            $eligible = $users->filter(function ($user) use ($businessId): bool {
                return strtolower(trim((string) $user->role)) !== 'superadmin'
                    && ($user->businessId === null || (string) $user->businessId === (string) $businessId);
            })->values();
            $firstUserId = $eligible->first()?->id;

            foreach ($eligible as $user) {
                $changes = [];
                if ($user->businessId === null) {
                    $changes['businessId'] = (string) $businessId;
                }

                if (trim((string) $user->role) === '') {
                    $changes['role'] = (int) $user->id === (int) $firstUserId ? 'admin' : 'sales';
                }

                if ($changes !== []) {
                    $changes['updated_at'] = now();
                    DB::table('admin_users')->where('id', $user->id)->update($changes);
                }
            }
        });
    }

    public function down(): void
    {
        // Deliberately irreversible: inferred ownership/role assignments must not be undone automatically.
    }
};
