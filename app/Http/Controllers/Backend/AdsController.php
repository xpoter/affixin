<?php

namespace App\Http\Controllers\Backend;

use App\Enums\AdsStatus;
use App\Enums\AdsType;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Enums\AdsFor;
use App\Facades\Txn\Txn;
use App\Http\Controllers\Controller;
use App\Models\Ads;
use App\Models\AdsReport;
use App\Models\SubscriptionPlan;
use App\Traits\ImageUpload;
use App\Traits\NotifyTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdsController extends Controller
{
    use ImageUpload, NotifyTrait;

    public function __construct()
    {
        $this->middleware('permission:pending-ads', ['only' => ['pending']]);
        $this->middleware('permission:inactive-ads', ['only' => ['inactive']]);
        $this->middleware('permission:active-ads', ['only' => ['active']]);
        $this->middleware('permission:rejected-ads', ['only' => ['rejected']]);
        $this->middleware('permission:all-ads', ['only' => ['index']]);
        $this->middleware('permission:ads-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:ads-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:ads-delete', ['only' => ['destroy']]);
        $this->middleware('permission:ads-reports-list', ['only' => ['reportList']]);
    }

    public function index()
    {
        $ads = Ads::with('plan')->when(filled(request('sort_field')), function ($query) {
            $query->orderBy(request('sort_field'), request('sort_dir'));
        })
            ->search(request('query'))
            ->type(request('type'))
            ->status(request('status'))
            ->latest()
            ->paginate()
            ->withQueryString();

        $title = __('All Ads');

        return view('backend.ads.index', compact('ads', 'title'));
    }

    public function pending()
    {
        $ads = Ads::with('plan')->when(filled(request('sort_field')), function ($query) {
            $query->orderBy(request('sort_field'), request('sort_dir'));
        })
            ->search(request('query'))
            ->type(request('type'))
            ->latest()
            ->status(AdsStatus::Pending)
            ->paginate();

        $title = __('Pending Ads');

        return view('backend.ads.index', compact('ads', 'title'));
    }

    public function inactive()
    {
        $ads = Ads::with('plan')->when(filled(request('sort_field')), function ($query) {
            $query->orderBy(request('sort_field'), request('sort_dir'));
        })
            ->search(request('query'))
            ->type(request('type'))
            ->latest()
            ->status(AdsStatus::Inactive)
            ->paginate();

        $title = __('Inactive Ads');

        return view('backend.ads.index', compact('ads', 'title'));
    }

    public function active()
    {
        $ads = Ads::with('plan')->when(filled(request('sort_field')), function ($query) {
            $query->orderBy(request('sort_field'), request('sort_dir'));
        })
            ->search(request('query'))
            ->type(request('type'))
            ->latest()
            ->status(AdsStatus::Active)
            ->paginate();

        $title = __('Active Ads');

        return view('backend.ads.index', compact('ads', 'title'));
    }

    public function rejected()
    {
        $ads = Ads::with('plan')->when(filled(request('sort_field')), function ($query) {
            $query->orderBy(request('sort_field'), request('sort_dir'));
        })
            ->search(request('query'))
            ->type(request('type'))
            ->latest()
            ->status(AdsStatus::Rejected)
            ->paginate();

        $title = __('Rejected Ads');

        return view('backend.ads.index', compact('ads', 'title'));
    }

    public function create()
    {
        $plans = SubscriptionPlan::get(['id', 'name']);

        return view('backend.ads.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'nullable|string|max:500',
            'amount' => 'required|numeric',
            'duration' => 'required|numeric',
            'max_views' => 'required|numeric',
            'type' => 'required|in:link,image,script,youtube',
            'status' => 'required',
            'for' => 'required|in:free_users,subscribed_users,both_users',
            'plan_id' => [
                Rule::requiredIf(function () use ($request) {
                    return $request->for === 'subscribed_users';
                }),
                'nullable',
                'exists:subscription_plans,id'
            ],
            'schedules' => 'nullable|array',
            'cta_button_text' => 'nullable|string|max:50',
            'cta_button_url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return redirect()->back()->withInput();
        }

        // Validate plan_id based on 'for' field
        if ($request->get('for') === 'subscribed_users' && !$request->get('plan_id')) {
            notify()->error(__('Plan is required for subscribed users ads'), 'Error');
            return redirect()->back()->withInput();
        }

        // If 'for' is free_users or both_users, set plan_id to null
        $planId = $request->get('for') === 'subscribed_users' ? $request->get('plan_id') : null;

        $bannerPath = null;
        if ($request->get('type') == 'image' && $request->hasFile('image')) {
            $bannerPath = self::imageUploadTrait($request->file('image'));
            
            // Add watermark to image
            $bannerPath = $this->addWatermarkToImage($bannerPath);
        }

        Ads::create([
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'user_id' => 0,
            'plan_id' => $planId,
            'amount' => $request->get('amount'),
            'for' => $request->get('for'),
            'duration' => $request->get('duration'),
            'max_views' => $request->get('max_views'),
            'remaining_views' => $request->get('max_views'),
            'type' => $request->get('type'),
            'value' => $request->get('type') == 'image' ? $bannerPath : $request->get($request->get('type')),
            'schedules' => $request->get('schedules'),
            'status' => $request->get('status'),
            'cta_button_text' => $request->get('cta_button_text'),
            'cta_button_url' => $request->get('cta_button_url'),
        ]);

        notify()->success(__('Ads added successfully!'));

        return to_route('admin.ads.index');
    }

    public function edit($id)
    {
        $ads = Ads::findOrFail($id);
        $plans = SubscriptionPlan::get(['id', 'name']);

        return view('backend.ads.edit', compact('ads', 'plans'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'nullable|string|max:500',
            'amount' => 'required|numeric',
            'duration' => 'required|numeric',
            'max_views' => 'required|numeric',
            'type' => 'required|in:link,image,script,youtube',
            'status' => 'required',
            'for' => 'required|in:free_users,subscribed_users,both_users',
            'plan_id' => [
                Rule::requiredIf(function () use ($request) {
                    return $request->for === 'subscribed_users';
                }),
                'nullable',
                'exists:subscription_plans,id'
            ],
            'schedules' => 'nullable|array',
            'cta_button_text' => 'nullable|string|max:50',
            'cta_button_url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first());

            return redirect()->back()->withInput();
        }

        // Validate plan_id based on 'for' field
        if ($request->get('for') === 'subscribed_users' && !$request->get('plan_id')) {
            notify()->error(__('Plan is required for subscribed users ads'), 'Error');
            return redirect()->back()->withInput();
        }

        // If 'for' is free_users or both_users, set plan_id to null
        $planId = $request->get('for') === 'subscribed_users' ? $request->get('plan_id') : null;

        $ads = Ads::findOrFail($id);

        $bannerPath = $ads->value;
        if ($request->get('type') == 'image' && $request->hasFile('image')) {
            $bannerPath = self::imageUploadTrait($request->file('image'));
            
            // Add watermark to image
            $bannerPath = $this->addWatermarkToImage($bannerPath);
        }

        $ads->update([
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'plan_id' => $planId,
            'for' => $request->get('for'),
            'amount' => $request->get('amount'),
            'duration' => $request->get('duration'),
            'max_views' => $request->get('max_views'),
            'remaining_views' => $request->get('max_views'),
            'type' => $request->get('type'),
            'value' => $request->get('type') == 'image' ? $bannerPath : $request->get($request->get('type')),
            'schedules' => $request->get('schedules'),
            'status' => $request->get('status'),
            'cta_button_text' => $request->get('cta_button_text'),
            'cta_button_url' => $request->get('cta_button_url'),
        ]);

        if ($ads->user_id != 0 && $ads->status == AdsStatus::Rejected) {

            $amountForAds = match ($request->type) {
                'link' => setting('link_ads_price', 'fee'),
                'script' => setting('script_ads_price', 'fee'),
                'image' => setting('image_ads_price', 'fee'),
                'youtube' => setting('youtube_ads_price', 'fee'),
            };

            $finalAmount = $ads->max_views * $amountForAds;
            $ads->user?->increment('balance', $finalAmount);

            (new Txn)->new(
                $finalAmount,
                0,
                $finalAmount,
                'System',
                'Refund for Ads Rejection',
                TxnType::Refund,
                TxnStatus::Success,
                null,
                null,
                $ads->user_id,
            );
        }

        if ($ads->wasChanged('status') && $ads->user_id !== 0) {
            $shortcodes = [
                '[[ads_title]]' => $ads->title,
                '[[status]]' => $ads->status->value,
                '[[site_title]]' => setting('site_title', 'global'),
                '[[site_url]]' => route('home'),
            ];

            $this->pushNotify('ads_post', $shortcodes, route('user.my.ads.index'), $ads->user_id);
            $this->mailNotify($ads?->user?->email, 'ads_post', $shortcodes);
        }

        notify()->success(__('Ads updated successfully!'));

        return to_route('admin.ads.index');
    }

    public function destroy($id)
    {
        $ads = Ads::findOrFail($id);

        if ($ads->type == AdsType::Image) {
            self::delete($ads->value);
        }
        $ads->delete();

        notify()->success(__('Ads deleted successfully!'));

        return to_route('admin.ads.index');
    }

    public function reportList()
    {
        $reports = AdsReport::with('ads', 'user')->latest()->paginate();

        return view('backend.ads.reports', compact('reports'));
    }

    /**
     * Add watermark to uploaded image
     */
    private function addWatermarkToImage($imagePath)
    {
        try {
            // Handle path - prepend 'assets/' if not already there
            if (!str_starts_with($imagePath, 'assets/')) {
                $fullPath = public_path('assets/' . $imagePath);
            } else {
                $fullPath = public_path($imagePath);
            }
            
            if (!file_exists($fullPath)) {
                \Log::error("Image not found for watermark: {$fullPath}");
                return $imagePath;
            }

            $imageInfo = getimagesize($fullPath);
            if (!$imageInfo) {
                return $imagePath;
            }

            $imageType = $imageInfo[2];
            $image = $this->loadImageResource($fullPath, $imageType);
            if (!$image) {
                return $imagePath;
            }

            $width = imagesx($image);
            $height = imagesy($image);

            // Add text watermark
            $watermarkText = config('app.name', 'Affixin');
            $image = $this->applyTextWatermark($image, $watermarkText, $width, $height);

            // Add logo watermark
            $logoPath = public_path('assets/global/images/5GtxdJSsj6RsyVJWeryp.png');
            if (file_exists($logoPath)) {
                $image = $this->applyLogoWatermark($image, $logoPath, $width, $height);
            }

            // Generate watermarked path
            $pathInfo = pathinfo($imagePath);
            $directory = $pathInfo['dirname'];
            if ($directory === '.' || empty($directory)) {
                $directory = 'global/images';
            }
            
            $watermarkedPath = $directory . '/' . $pathInfo['filename'] . '_wm.' . $pathInfo['extension'];
            
            if (!str_starts_with($watermarkedPath, 'assets/')) {
                $watermarkedFullPath = public_path('assets/' . $watermarkedPath);
            } else {
                $watermarkedFullPath = public_path($watermarkedPath);
            }

            $this->saveImageResource($image, $watermarkedFullPath, $imageType);
            imagedestroy($image);

            return $watermarkedPath;

        } catch (\Exception $e) {
            \Log::error('Watermark failed: ' . $e->getMessage());
            return $imagePath;
        }
    }

    private function loadImageResource($path, $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }

    private function saveImageResource($image, $path, $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($image, $path, 90);
                break;
            case IMAGETYPE_PNG:
                imagesavealpha($image, true);
                imagepng($image, $path, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($image, $path);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($image, $path, 90);
                break;
        }
    }

    private function applyTextWatermark($image, $text, $width, $height)
    {
        $fontSize = max(14, min($width, $height) * 0.035);
        $textWidth = strlen($text) * ($fontSize * 0.6);
        $textHeight = $fontSize;
        
        $padding = 15;
        $x = $width - $textWidth - $padding;
        $y = $height - $padding;
        
        $bgColor = imagecolorallocatealpha($image, 0, 0, 0, 50);
        imagefilledrectangle(
            $image,
            $x - 10,
            $y - $textHeight - 8,
            $x + $textWidth + 10,
            $y + 5,
            $bgColor
        );
        
        $textColor = imagecolorallocate($image, 255, 255, 255);
        $fontFile = public_path('fonts/arial.ttf');
        
        if (file_exists($fontFile)) {
            imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontFile, $text);
        } else {
            imagestring($image, 5, $x, $y - $textHeight, $text, $textColor);
        }
        
        return $image;
    }

    private function applyLogoWatermark($image, $logoPath, $width, $height)
    {
        $logoInfo = getimagesize($logoPath);
        $logo = $this->loadImageResource($logoPath, $logoInfo[2]);
        
        if (!$logo) {
            return $image;
        }

        $logoWidth = imagesx($logo);
        $logoHeight = imagesy($logo);
        
        $newLogoWidth = $width * 0.10;
        $newLogoHeight = ($logoHeight / $logoWidth) * $newLogoWidth;
        
        $resizedLogo = imagecreatetruecolor($newLogoWidth, $newLogoHeight);
        imagealphablending($resizedLogo, false);
        imagesavealpha($resizedLogo, true);
        $transparent = imagecolorallocatealpha($resizedLogo, 0, 0, 0, 127);
        imagefill($resizedLogo, 0, 0, $transparent);
        imagealphablending($resizedLogo, true);
        
        imagecopyresampled(
            $resizedLogo, $logo,
            0, 0, 0, 0,
            $newLogoWidth, $newLogoHeight,
            $logoWidth, $logoHeight
        );
        
        $padding = 15;
        imagecopy($image, $resizedLogo, $padding, $padding, 0, 0, $newLogoWidth, $newLogoHeight);
        
        imagedestroy($logo);
        imagedestroy($resizedLogo);
        
        return $image;
    }
}