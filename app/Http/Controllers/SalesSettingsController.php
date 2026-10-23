<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesSettingsController extends Controller
{
    private function businesses()
    {
        $actor = auth('admin')->user();
        abort_unless($actor && $actor->canManageSalesSettings(), 403);
        $query = BusinessSetup::query();
        // Only an explicitly assigned platform role can cross business boundaries.
        if (!$actor->hasSuperAdminPrivileges()) { $query->whereKey((int) $actor->businessId); }
        return $query;
    }

    public function edit(Request $request)
    {
        $businesses = $this->businesses()->orderBy('id')->get();
        $businessId = $request->query('businessId', auth('admin')->user()->businessId);
        $business = $businessId ? $this->businesses()->findOrFail($businessId) : $businesses->first();
        return view('business.salesSettings', compact('business', 'businesses'));
    }

    public function update(Request $request)
    {
        $query = $this->businesses();
        $data = $request->validate(['businessId' => ['required', 'integer'],
            'sales_serial_selection_required' => ['required', 'boolean']]);
        DB::transaction(function () use ($query, $data) {
            $business = $query->lockForUpdate()->findOrFail($data['businessId']);
            $business->sales_serial_selection_required = (bool) $data['sales_serial_selection_required'];
            $business->save();
        });
        return redirect()->route('sales.settings.edit', ['businessId' => $data['businessId']])
            ->with('status', __('sales.sales_settings_saved_open_sales_forms_use_the_current_policy_when_saved'));
    }
}
