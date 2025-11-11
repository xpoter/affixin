<?php

// ==================================================================
// CREATE FILE: app/Http/Controllers/AdPreviewController.php
// ==================================================================

namespace App\Http\Controllers\Frontend;

use App\Models\Ads;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdPreviewController extends Controller
{
    /**
     * Show ad preview page with call-to-action
     */
    public function show($id)
    {
        try {
            $adId = decrypt($id);
            $ad = Ads::findOrFail($adId);
            
            // Only show preview for image ads
            if ($ad->type->value !== 'image') {
                abort(404, 'Preview only available for image ads');
            }
            
            // Get image URL with proper path
            $imageUrl = asset($ad->value);
            
            return view('frontend.clickify.user.ads.ad-preview', compact('ad', 'imageUrl'));
            
        } catch (\Exception $e) {
            abort(404, 'Ad not found');
        }
    }
}