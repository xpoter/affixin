<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $ad->title ?? 'Check out this ad!' }} - {{ config('app.name') }}</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="{{ $ad->description ?? 'Amazing offer you dont want to miss!' }}">
    <meta name="keywords" content="ads, offers, deals, {{ config('app.name') }}">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $ad->title ?? 'Check out this amazing ad!' }}">
    <meta property="og:description" content="{{ $ad->description ?? 'Click to see this amazing offer!' }}">
    <meta property="og:image" content="{{ $imageUrl }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $ad->title ?? 'Ad image' }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="{{ $ad->title ?? 'Check out this amazing ad!' }}">
    <meta name="twitter:description" content="{{ $ad->description ?? 'Click to see this amazing offer!' }}">
    <meta name="twitter:image" content="{{ $imageUrl }}">
    
    <!-- WhatsApp / Telegram -->
    <link rel="image_src" href="{{ $imageUrl }}">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset(setting('site_favicon','global')) }}" type="image/x-icon" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            line-height: 1.6;
        }
        
        .preview-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            width: 100%;
            overflow: hidden;
            animation: fadeInUp 0.6s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .preview-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        
        .preview-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="2"/></svg>');
            opacity: 0.3;
        }
        
        .preview-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }
        
        .preview-header p {
            opacity: 0.95;
            font-size: 16px;
            position: relative;
            z-index: 1;
        }
        
        .preview-image-container {
            position: relative;
            width: 100%;
            background: #f8f9fa;
        }
        
        .preview-image {
            width: 100%;
            height: auto;
            display: block;
            max-height: 600px;
            object-fit: contain;
        }
        
        .preview-content {
            padding: 40px 30px;
        }
        
        .preview-description {
            color: #555;
            font-size: 16px;
            margin-bottom: 30px;
            text-align: center;
            line-height: 1.8;
        }
        
        .cta-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px 50px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            position: relative;
            overflow: hidden;
        }
        
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
        }
        
        .cta-button:active {
            transform: translateY(-1px);
        }
        
        .cta-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .cta-button:hover::before {
            left: 100%;
        }
        
        .secondary-cta {
            text-align: center;
            margin-top: 20px;
        }
        
        .secondary-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .secondary-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .preview-footer {
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .footer-text {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .footer-logo {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
            text-decoration: none;
        }
        
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 40px;
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            flex-wrap: wrap;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            display: block;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        @media (max-width: 768px) {
            .preview-header h1 {
                font-size: 24px;
            }
            
            .preview-header p {
                font-size: 14px;
            }
            
            .preview-content {
                padding: 30px 20px;
            }
            
            .cta-button {
                padding: 16px 40px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <!-- Header -->
        <div class="preview-header">
            <h1>{{ $ad->title ?? '🎉 Amazing Offer!' }}</h1>
            <p>{{ $ad->description ?? 'Check out this exclusive deal just for you' }}</p>
        </div>
        
        <!-- Image -->
        <div class="preview-image-container">
            <img src="{{ $imageUrl }}" alt="{{ $ad->title ?? 'Ad image' }}" class="preview-image">
        </div>
        
        <!-- Stats Bar (Optional) -->
        @if(isset($ad->views) || isset($ad->remaining_views))
        <div class="stats-bar">
            @if(isset($ad->views))
            <div class="stat-item">
                <span class="stat-number">{{ number_format($ad->views ?? 0) }}</span>
                <span class="stat-label">Views</span>
            </div>
            @endif
            
            @if(isset($ad->remaining_views) && $ad->remaining_views > 0)
            <div class="stat-item">
                <span class="stat-number">{{ number_format($ad->remaining_views) }}</span>
                <span class="stat-label">Spots Left</span>
            </div>
            @endif
        </div>
        @endif
        
        <!-- Content & CTA -->
        <div class="preview-content">
            @if($ad->description)
            <div class="preview-description">
                <p>{{ $ad->description }}</p>
            </div>
            @endif
            
            <!-- Main CTA -->
            <div class="cta-section">
                <a href="{{ $ad->cta_button_url ?? route('home') }}" class="cta-button" target="_blank">
                    {{ $ad->cta_button_text ?? '✨ Visit ' . config('app.name') . ' Now' }}
                </a>
            </div>
            
            <!-- Secondary CTA -->
            <div class="secondary-cta">
                <a href="{{ route('home') }}" class="secondary-link">
                    Want to advertise your business? Click here →
                </a>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="preview-footer">
            <p class="footer-text">Powered by</p>
            <a href="{{ route('home') }}" class="footer-logo">
                {{ config('app.name') }}
            </a>
        </div>
    </div>
</body>
</html>