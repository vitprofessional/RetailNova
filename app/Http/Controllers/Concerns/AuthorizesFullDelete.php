<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AdminUser;
use Illuminate\Http\Request;

/**
 * The "Delete All Data" option of the bulk/single delete dialogs (deleteType=fullDelete)
 * is permanent, so only admins and superadmins may send it.
 */
trait AuthorizesFullDelete
{
    protected function authorizeFullDelete(Request $request): void
    {
        if ((string) $request->input('deleteType', 'profileOnly') !== 'fullDelete') {
            return;
        }

        $actor = auth('admin')->user();
        abort_unless($actor instanceof AdminUser && $actor->canDeleteAllRelatedData(), 403,
            __('common.notification_only_admins_can_delete_all_related_data'));
    }
}
