<?php

namespace App\Http\Controllers\Concerns;

use Alert;
use App\Services\CatalogUsage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Bulk delete that keeps records still in use (see CatalogUsage) and names them. */
trait DeletesUnusedRecords
{
    /**
     * @param Collection<int, \Illuminate\Database\Eloquent\Model> $records
     * @param callable(int[]): mixed $delete receives the keys of the records that may go
     * @param (callable(\Illuminate\Database\Eloquent\Model): string)|null $label name shown for a kept record
     */
    private function bulkDeleteUnused(string $type, Collection $records, callable $delete, string $successKey, ?callable $label = null): void
    {
        $inUse = app(CatalogUsage::class)->inUseIds($type, $records);
        [$kept, $deletable] = $records->partition(fn ($record) => in_array((int) $record->getKey(), $inUse, true));

        if ($deletable->isNotEmpty()) {
            DB::transaction(fn () => $delete($deletable->map(fn ($record) => (int) $record->getKey())->all()));
        }

        if ($kept->isEmpty()) {
            Alert::success(__('common.notification_deleted'), __($successKey));
            return;
        }

        $label ??= fn ($record) => (string) $record->name;
        $message = __('common.bulk_delete_skipped_in_use', [
            'deleted' => $deletable->count(),
            'count' => $kept->count(),
            'names' => $kept->map($label)->implode(', '),
        ]);
        $deletable->isEmpty()
            ? Alert::error(__('common.notification_failed'), $message)
            : Alert::warning(__('common.notification_deleted'), $message);
    }
}
