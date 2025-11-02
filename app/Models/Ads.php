<?php

namespace App\Models;

use App\Enums\AdsFor;
use App\Enums\AdsType;
use App\Enums\AdsStatus;
use App\Enums\PlanHistoryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ads extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function scopeActive($query)
    {
        return $query->where('status', AdsStatus::Active);
    }

    public function getTypeValueAttribute()
    {
        return match ($this->type) {
            AdsType::Link => __('Link/URL'),
            AdsType::Image => __('Banner/Image'),
            AdsType::Script => __('Script/Code'),
            AdsType::Youtube => __('YouTube'),
        };
    }

    public function getTypeBgAttribute()
    {
        return match ($this->type) {
            AdsType::Link => asset('frontend/default/images/shapes/ads-pattern-2.png'),
            AdsType::Image => asset('frontend/default/images/shapes/ads-pattern.png'),
            AdsType::Script => asset('frontend/default/images/shapes/ads-pattern-3.png'),
            AdsType::Youtube => asset('frontend/default/images/shapes/ads-pattern-4.png'),
            default => asset('frontend/default/images/shapes/ads-pattern.png')
        };
    }

    public function scopeSearch($query, $search)
    {
        if (filled($search)) {
            return $query->where(function ($query) use ($search) {
                $query->where('title', 'LIKE', '%' . $search . '%');
            });
        }

        return $query;
    }

    public function scopeType($query, $type)
    {
        if (filled($type) && $type != 'all') {
            return $query->where('type', $type);
        }

        return $query;
    }

    public function scopeStatus($query, $status)
    {
        if (filled($status) && $status != 'all') {
            return $query->where('status', $status);
        }

        return $query;
    }

    protected function scopeForUser($query)
    {
        // Get all active plans for the user
        $activePlans = PlanHistory::where('user_id', auth()->id())
            ->where('status', PlanHistoryStatus::ACTIVE)
            ->pluck('plan_id')
            ->toArray();

        if (empty($activePlans)) {
            // User is a free user - show only free user ads
            return $query->where(function ($q) {
                $q->where('for', AdsFor::FreeUsers)
                  ->orWhere('for', AdsFor::BothUsers);
            })->whereNull('plan_id');
        }

        // User has active subscription(s) - show ads for their plans
        return $query->where(function ($q) use ($activePlans) {
            // Show ads specifically for their subscribed plans
            $q->where(function ($subQ) use ($activePlans) {
                $subQ->where('for', AdsFor::SubscribedUsers)
                     ->whereIn('plan_id', $activePlans);
            })
            // Or show ads for both users (no specific plan required)
            ->orWhere(function ($subQ) {
                $subQ->where('for', AdsFor::BothUsers)
                     ->whereNull('plan_id');
            });
        });
    }

    public function getLastViewedAtAttribute()
    {
        return AdsHistory::where('user_id', auth()->id())->where('ads_id', $this->id)->where('created_at', '>=', now()->subHours(24))->first()->created_at ?? null;
    }

    public function isScheduledNow()
    {
        $currentDay = strtolower(now()->format('l'));
        $currentTime = now()->format('H:i');

        foreach ($this->schedules ?: [] as $schedule) {
            if (strtolower($schedule['day']) === $currentDay) {
                if ($schedule['start_time'] <= $currentTime && $schedule['end_time'] >= $currentTime) {
                    return true;
                }
            }
        }

        return $this->schedules === null ? true : false;
    }

    protected function scopeUser($query, $id = null)
    {
        return $query->where('user_id', $id ?? auth()->id());
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    // Check if user can view this ad based on their plan
    public function canBeViewedBy($userId)
    {
        $user = User::find($userId);
        
        if (!$user) {
            return false;
        }

        // Get user's active plans
        $activePlans = PlanHistory::where('user_id', $userId)
            ->where('status', PlanHistoryStatus::ACTIVE)
            ->pluck('plan_id')
            ->toArray();

        // If ad is for both users and has no specific plan requirement
        if ($this->for === AdsFor::BothUsers && $this->plan_id === null) {
            return true;
        }

        // If ad is for free users
        if ($this->for === AdsFor::FreeUsers && empty($activePlans)) {
            return true;
        }

        // If ad is for subscribed users with specific plan
        if ($this->for === AdsFor::SubscribedUsers && $this->plan_id && in_array($this->plan_id, $activePlans)) {
            return true;
        }

        return false;
    }

    protected $casts = [
        'type' => AdsType::class,
        'status' => AdsStatus::class,
        'for' => AdsFor::class,
        'schedules' => 'array',
    ];
}