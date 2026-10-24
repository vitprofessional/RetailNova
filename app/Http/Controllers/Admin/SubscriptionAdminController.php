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
        Alert::success(__('common.notification_success'),__('common.notification_plan_created'));
        return back();
    }

    public function plansDelete($id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->delete();
        Alert::success(__('common.notification_deleted'),__('common.notification_plan_deleted'));
        return back();
    }

    public function index()
    {
        $subs = Subscription::with('plan')->orderBy('created_at','desc')->paginate(20);
        $plans = SubscriptionPlan::where('is_active',true)->get();
        $businesses = \App\Models\BusinessSetup::orderBy('businessName')->get(['id', 'businessName']);
        $businessMap = \App\Models\BusinessSetup::whereIn('id', $subs->getCollection()->pluck('business_id')->unique())
            ->pluck('businessName', 'id');
        return view('admin.subscriptions.index', compact('subs','plans','businesses','businessMap'));
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
        Alert::success(__('common.notification_success'),__('common.notification_subscription_started'));
        return back();
    }

    public function cancel($id)
    {
        $sub = Subscription::findOrFail($id);
        $sub->status = 'cancelled';
        $sub->save();
        Alert::success(__('common.notification_cancelled'),__('common.notification_subscription_cancelled'));
        return back();
    }
}
