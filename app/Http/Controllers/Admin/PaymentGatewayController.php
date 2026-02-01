<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use Alert;

class PaymentGatewayController extends Controller
{
    public function index()
    {
        $gateways = PaymentGateway::orderBy('name')->get();
        return view('admin.gateways.index', compact('gateways'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string|max:255',
            'mode' => 'required|in:sandbox,live',
            'api_key' => 'nullable|string|max:1024',
            'api_secret' => 'nullable|string|max:2048',
        ]);
        PaymentGateway::create([
            'name' => $request->name,
            'provider' => $request->provider,
            'mode' => $request->mode,
            'api_key' => $request->api_key,
            'api_secret' => $request->api_secret,
            'is_active' => (bool)$request->input('is_active',1),
        ]);
        Alert::success('Success','Gateway added');
        return back();
    }

    public function update(Request $request, $id)
    {
        $gateway = PaymentGateway::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string|max:255',
            'mode' => 'required|in:sandbox,live',
            'api_key' => 'nullable|string|max:1024',
            'api_secret' => 'nullable|string|max:2048',
            'is_active' => 'nullable|boolean',
        ]);
        $gateway->update([
            'name' => $request->name,
            'provider' => $request->provider,
            'mode' => $request->mode,
            'api_key' => $request->api_key,
            'api_secret' => $request->api_secret,
            'is_active' => (bool)$request->input('is_active',0),
        ]);
        Alert::success('Updated','Gateway updated');
        return back();
    }

    public function destroy($id)
    {
        $gateway = PaymentGateway::findOrFail($id);
        $gateway->delete();
        Alert::success('Deleted','Gateway deleted');
        return back();
    }
}
