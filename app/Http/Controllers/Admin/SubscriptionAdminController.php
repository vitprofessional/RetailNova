<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use Alert;

class SubscriptionAdminController extends Controller
{
    public function plansIndex()
    {
        $plans = SubscriptionPlan::orderBy('price','asc')->get();
        return view('admin.subscriptions.plans', compact('plans'));
    }

    public function plansStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
        ]);
        SubscriptionPlan::create([
            'name' => $request->name,
            'price' => $request->price,
            'duration_days' => $request->duration_days,
            'features' => $request->features,
            'is_active' => (bool)$request->input('is_active',1),
        ]);
        Alert::success('Success','Plan created');
        return back();
    }

    public function plansDelete($id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->delete();
        Alert::success('Deleted','Plan deleted');
        return back();
    }

    public function index()
    {
        $subs = Subscription::with('plan')->orderBy('created_at','desc')->paginate(20);
        $plans = SubscriptionPlan::where('is_active',true)->get();
        return view('admin.subscriptions.index', compact('subs','plans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'business_id' => 'required|integer',
            'plan_id' => 'required|exists:subscription_plans,id',
            'starts_at' => 'required|date',
        ]);
        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        $starts = new \DateTime($request->starts_at);
        $ends = (clone $starts)->modify('+' . $plan->duration_days . ' days');
        Subscription::create([
            'business_id' => $request->business_id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => $starts,
            'ends_at' => $ends,
        ]);
        Alert::success('Success','Subscription started');
        return back();
    }

    public function cancel($id)
    {
        $sub = Subscription::findOrFail($id);
        $sub->status = 'cancelled';
        $sub->save();
        Alert::success('Cancelled','Subscription cancelled');
        return back();
    }
}
