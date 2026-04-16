<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
  public function index()
{
    
    $plans = Plan::where('active', true)->get();
    $user = \App\Models\User::first();

    $currentPlan = $user?->plan ?? Plan::where('slug', 'free')->first();

    return view('plans.index', compact('plans', 'currentPlan'));
}

    public function subscribe(Plan $plan)
    {
        $user = auth()->user();

        if ($plan->isFree()) {
            $user->update([
                'plan_id' => $plan->id,
                'plan_expires_at' => null
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'Subscribed to Free plan');
        }

      
        $user->update([
            'plan_id' => $plan->id,
            'plan_expires_at' => now()->addMonth()
        ]);

        return redirect()->route('dashboard')
            ->with('success', "Subscribed to {$plan->name} plan");
    }

    public function cancel()
    {
        $freePlan = Plan::where('slug', 'free')->first();
        
        auth()->user()->update([
            'plan_id' => $freePlan->id,
            'plan_expires_at' => null
        ]);

        return redirect()->route('plans.index')
            ->with('success', 'Subscription cancelled');
    }
}