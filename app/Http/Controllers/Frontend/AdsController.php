<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\AdsStatus;
use App\Enums\PlanHistoryStatus;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Http\Controllers\Controller;
use App\Models\Ads;
use App\Models\AdsHistory;
use App\Models\AdsReport;
use App\Models\AdsReportCategory;
use App\Models\PlanHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdsController extends Controller
{
    public function index()
    {
        if (! setting('kyc_ads', 'kyc') && (auth()->user()->kyc == 0 || auth()->user()->kyc == 2)) {
            notify()->error(__('Please verify your KYC.'), 'Error');

            return to_route('user.dashboard');
        }

        $ads = Ads::active()->when(filled(request('type')), function ($query) {
            $query->where('type', request('type'));
        })->forUser()->where('remaining_views', '>', 0)->get()->filter(function ($ads) {
            return $ads->isScheduledNow();
        });

        return view('frontend::user.ads.index', compact('ads'));
    }

    public function adsView($id)
    {
        // Decrypt ads ID
        $adsId = decrypt($id);
        
        // Get ads
        $ads = Ads::findOrFail($adsId);
        
        // Check if user can view this ad based on their plan
        if (!$ads->canBeViewedBy(auth()->id())) {
            notify()->error(__('You do not have access to view this ad. Please subscribe to the required plan.'));
            return to_route('user.ads.index');
        }

        // Check daily ads limit and get plan_id
        $plan_id = $this->checkDailyAdsLimit($ads->plan_id);
        
        if ($plan_id === null) {
            return to_route('user.ads.index');
        }

        $firstInt = rand(1, 20);
        $secondInt = rand(1, 20);

        // Get categories
        $categories = AdsReportCategory::where('status', 1)->get();
        
        // Check if the user has viewed the ads in the last 24 hours
        $recentlyViewed = AdsHistory::user()->where('ads_id', $ads->id)->where('created_at', '>=', now()->subHours(24))->exists();

        // If the user has viewed the ads in the last 24 hours, show an error message
        if ($recentlyViewed) {
            notify()->error(__('Please wait 24 Hours before viewing ads'));

            return to_route('user.ads.index');
        }

        // Return the view with the ads, first integer, categories, and second integer
        return view('frontend::user.ads.view', compact('ads', 'firstInt', 'categories', 'secondInt'));
    }

    public function adsSubmit(Request $request, $id)
    {
        // Decrypt the ads ID
        $adsId = decrypt($id);
        
        // Get the ads
        $ads = Ads::where('status', AdsStatus::Active)->findOrFail($adsId);
        
        // Check if user can view this ad based on their plan
        if (!$ads->canBeViewedBy(auth()->id())) {
            notify()->error(__('You do not have access to view this ad. Please subscribe to the required plan.'));
            return to_route('user.ads.index');
        }

        // Check daily ads limit and get plan_id
        $plan_id = $this->checkDailyAdsLimit($ads->plan_id);
        
        if ($plan_id === null) {
            return to_route('user.ads.index');
        }

        // Check if the user has viewed the ads in the last 24 hours
        $recentlyViewed = AdsHistory::user()->where('ads_id', $adsId)->where('created_at', '>=', now()->subHours(24))->exists();

        // If the user has viewed the ads in the last 24 hours, show an error message
        if ($recentlyViewed) {
            notify()->error(__('Please wait 24 Hours before viewing ads'));

            return to_route('user.ads.index');
        }

        // Validate the request
        $firstInt = $request->integer('first_number');
        $secondInt = $request->integer('seconds_number');

        $finalResult = $firstInt + $secondInt;

        // Check if the calculation is correct
        if ($finalResult !== $request->integer('result')) {
            notify()->error(__('Invalid calculation'));

            return redirect()->back();
        }

        // Get the ads bonus type and delay hours
        $adsBonusType = setting('ads_bonus_system', 'ads');
        $delayHours = setting('ads_bonus_delay_hours', 'ads');

        // Store ads history
        AdsHistory::create([
            'user_id' => auth()->id(),
            'plan_id' => $plan_id,
            'ads_id' => $adsId,
            'amount' => $ads->amount,
            'claimable_at' => $adsBonusType ? null : Carbon::now()->addHours($delayHours),
            'is_claimed' => $adsBonusType ? true : false,
        ]);

        // Increment views
        $ads->increment('total_views');
        $ads->decrement('remaining_views');

        if ($adsBonusType) {
            // Credit to User
            $user = $request->user();
            $user->increment('balance', $ads->amount);

            // Add Transaction
            (new Txn)->new($ads->amount, 0, $ads->amount, 'System', 'Ads Viewed - '.$ads->title, TxnType::AdsViewed, TxnStatus::Success);
        }

        notify()->success(__('Ads viewed successfully'));

        return to_route('user.ads.index');
    }

    public function reportSubmit(Request $request)
    {
        $request->validate([
            'category_id' => 'required',
            'description' => 'required',
        ]);

        AdsReport::create([
            'user_id' => auth()->id(),
            'ads_id' => decrypt($request->id),
            'category_id' => $request->category_id,
            'description' => $request->description,
        ]);

        notify()->success(__('Ads reported successfully'));

        return to_route('user.ads.index');
    }

    protected function checkDailyAdsLimit($adsPlanId = null)
    {
        // Get the user ID
        $userId = auth()->id();

        // Get the free user daily limit
        $freeUserDailyLimit = (int) setting('ads_limit_free_user', 'ads');

        // Check if the user has active paid plans
        $activePlans = PlanHistory::where('user_id', $userId)
            ->where('status', PlanHistoryStatus::ACTIVE)
            ->get();

        // If ad has no specific plan (free or both users ads)
        if ($adsPlanId === null) {
            if ($activePlans->isEmpty()) {
                // Free user viewing free/both ads
                $freeUserAdViewCount = AdsHistory::where('user_id', $userId)
                    ->where('plan_id', 0)
                    ->whereDate('created_at', now())
                    ->count();

                if ($freeUserAdViewCount >= $freeUserDailyLimit) {
                    notify()->error(__('Daily ads limit exceeded'));
                    return null;
                }

                return 0;
            }

            // Subscribed user viewing free/both ads - use any active plan
            foreach ($activePlans as $plan) {
                $adViewCount = AdsHistory::where('user_id', $userId)
                    ->where('plan_id', $plan->id)
                    ->whereDate('created_at', now()->toDateString())
                    ->count();

                if ($adViewCount < $plan->daily_ads_limit) {
                    return $plan->id;
                }
            }

            notify()->error(__('Daily ads limit exceeded'));
            return null;
        }

        // Ad requires specific plan
        if ($activePlans->isEmpty()) {
            notify()->error(__('You need an active subscription to view this ad'));
            return null;
        }

        // Check if user has the required plan
        $requiredPlan = $activePlans->where('plan_id', $adsPlanId)->first();

        if (!$requiredPlan) {
            notify()->error(__('You need to subscribe to the required plan to view this ad'));
            return null;
        }

        // Check daily limit for the specific plan
        $adViewCount = AdsHistory::where('user_id', $userId)
            ->where('plan_id', $requiredPlan->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($adViewCount >= $requiredPlan->daily_ads_limit) {
            notify()->error(__('Daily ads limit exceeded for this plan'));
            return null;
        }

        return $requiredPlan->id;
    }
}