<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ __('Viewing Ads') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset(setting('site_favicon','global')) }}" type="image/x-icon" />
    <link rel="icon" href="{{ asset(setting('site_favicon','global')) }}" type="image/x-icon" />
    <!-- CSS here -->
    <link rel="stylesheet" href="{{ asset('frontend/default/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/default/css/iconsax.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/default/css/fontawesome-pro.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/default/css/styles.css') }}">
    
    <style>
        /* Share Section Styles */
        .share-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-top: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .share-header {
            text-align: center;
            margin-bottom: 15px;
        }

        .share-header h4 {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .share-header p {
            color: #666;
            font-size: 13px;
            margin: 0;
        }

        .share-link-box {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 2px dashed #dee2e6;
        }

        .share-link-text {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #495057;
            word-break: break-all;
            text-align: center;
            margin: 0;
        }

        .desktop-warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            text-align: center;
        }

        .desktop-warning h5 {
            color: #856404;
            font-size: 16px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .desktop-warning p {
            color: #856404;
            font-size: 13px;
            margin: 0;
        }

        .qr-code-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-top: 15px;
        }

        .share-buttons-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 10px;
        }

        @media (min-width: 576px) {
            .share-buttons-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .share-btn {
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .share-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .share-btn:active {
            transform: translateY(0);
        }

        .share-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .share-btn.whatsapp {
            background: #25D366;
            color: white;
        }

        .share-btn.facebook {
            background: #1877F2;
            color: white;
        }

        .share-btn.twitter {
            background: #1DA1F2;
            color: white;
        }

        .share-btn.instagram {
            background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%);
            color: white;
        }

        .share-alert {
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 13px;
            text-align: center;
            display: none;
        }

        .share-alert.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

        .share-alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .share-alert.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .image-preview {
            text-align: center;
            margin-bottom: 15px;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 250px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body>
    @include('frontend::include.__notify')
    
    <div class="mt-5 d-none" id="warningArea">
        <div class="text-center">
            <h1 style="font-size: 150px">
                <i class="icon-danger text-danger text-md"></i>
            </h1>
            <h4 class="text-danger my-2">
                {{ __('Unable to view ads, try again.') }}
            </h4>

            <div class="my-4">
                <button type="button" class="input-btn btn-danger me-3" onclick="window.close();">
                    <i class="icon-close-circle"></i> {{ __('Close Tab') }}
                </button>
                <a href="{{ route('user.ads.index') }}" class="input-btn btn-primary">
                    <i class="icon-arrow-left"></i> {{ __('Back') }}
                </a>
            </div>
        </div>
    </div>

    <div class="advertisement-area pt-20 pb-20">
        <div class="advertisement-card-wrapper d-flex flex-column gap-4">
            <div class="advertisement-card-item">
                <div class="container">
                    <form action="{{ route('user.ads.submit',encrypt($ads->id)) }}" method="post">
                        @csrf
                        <div class="advertisement-card-inner">
                            <div class="progress-advertisement-item fix">
                                <div class="progress-count-inner">
                                    <span class="progress-count">0%</span>
                                    <h4>100%</h4>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" data-width="100%" aria-valuenow="25"
                                        aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>

                            <!-- SHARE SECTION - Shows after 100% progress -->
                            <div class="share-section d-none" id="shareSection">
                                <div class="share-header">
                                    <h4>📱 {{ __('Share This Ad to Continue') }}</h4>
                                    <p>{{ __('Share on any platform to unlock the captcha') }}</p>
                                </div>

                                <!-- Desktop Warning for Image Ads -->
                                <div class="desktop-warning d-none" id="desktopWarning">
                                    <h5>📱 {{ __('Mobile Device Required') }}</h5>
                                    <p>{{ __('To share images, please open this page on your mobile device.') }}</p>
                                    <p style="margin-top: 8px; font-size: 12px;">
                                        {{ __('Current URL:') }} <strong>{{ url()->current() }}</strong>
                                    </p>
                                </div>

                                @if($ads->type == App\Enums\AdsType::Image)
                                <!-- Image Preview -->
                                <div class="image-preview">
                                    <img src="{{ asset($ads->value) }}" alt="Ad Image">
                                    <input type="hidden" id="adImageUrl" value="{{ asset($ads->value) }}">
                                    <input type="hidden" id="adType" value="image">
                                </div>
                                @else
                                <!-- Text/Link Content -->
                                <div class="share-link-box">
                                    <p class="share-link-text" id="adContentToShare">
                                        @if($ads->type == App\Enums\AdsType::Link)
                                            {{ $ads->value }}
                                        @elseif($ads->type == App\Enums\AdsType::Youtube)
                                            {{ strip_tags($ads->value) }}
                                        @elseif($ads->type == App\Enums\AdsType::Script)
                                            {{ $ads->title ?? $ads->value ?? 'Check out this ad!' }}
                                        @endif
                                    </p>
                                    <input type="hidden" id="adType" value="{{ $ads->type->value }}">
                                </div>
                                @endif

                                <div class="share-buttons-grid" id="shareButtonsGrid">
                                    <button type="button" class="share-btn whatsapp" onclick="shareOnPlatform('whatsapp')">
                                        📱 WhatsApp
                                    </button>
                                    <button type="button" class="share-btn facebook" onclick="shareOnPlatform('facebook')">
                                        👥 Facebook
                                    </button>
                                    <button type="button" class="share-btn twitter" onclick="shareOnPlatform('twitter')">
                                        <svg style="width: 14px; height: 14px; display: inline;" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                        </svg>
                                        X (Twitter)
                                    </button>
                                    <button type="button" class="share-btn instagram" onclick="shareOnPlatform('instagram')">
                                        📷 Instagram
                                    </button>
                                </div>

                                <div class="share-alert" id="shareAlert"></div>
                            </div>

                            <!-- CAPTCHA - Shows after sharing -->
                            <div class="advertisement-calc d-none" id="captchaSection">
                                <input class="count" type="number" name="first_number" value="{{ $firstInt }}" readonly>
                                <div class="icon"><span>+</span></div>
                                <input class="count" type="number" name="seconds_number" value="{{ $secondInt }}" readonly>
                                <div class="icon"><span>=</span></div>
                                <input class="count" type="number" min="0" name="result">
                            </div>
                            
                            <div class="input-btn-wrap d-none" id="submitArea">
                                <button class="input-btn btn-primary" type="submit">
                                    <span>
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="10" cy="10" r="10" fill="white" fill-opacity="0.1" />
                                            <path d="M14 7L8.5 12.5L6 10" stroke="white" stroke-width="1.8"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    {{ __('Confirm Earn') }}
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="input-btn-wrap mt-2 d-none" id="reportArea">
                        <button class="input-btn btn-danger" type="button" data-bs-toggle="modal"
                            data-bs-target="#reportModal">
                            <span><i class="icon-danger"></i></span>
                            {{ __('Report') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Modal -->
    <div class="modal fade" id="reportModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('user.ads.report.submit',encrypt($ads->id)) }}" method="post" id="reportForm">
                    @csrf
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="staticBackdropLabel">{{ __('Report Ads') }}</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="single-input">
                            <label class="input-label">{{ __('Report Category') }}</label>
                            <select name="category_id" class="form-select nice-select">
                                <option value="" selected disabled>{{ __('Select Report Category') }}</option>
                                @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="single-input">
                            <label class="input-label" for="">{{ __('Description') }}</label>
                            <div class="input-field">
                                <textarea class="box-input" name="description"></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="input-btn btn-primary m-3">{{ __('Submit Report') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="advertisement-field w-100 mt-2">
        @if($ads->type == App\Enums\AdsType::Link)
        <iframe src="{{ $ads->value }}"></iframe>
        @elseif($ads->type == App\Enums\AdsType::Image)
        <img src="{{ asset($ads->value) }}">
        @elseif($ads->type == App\Enums\AdsType::Youtube)
        {!! $ads->value !!}
        @elseif($ads->type == App\Enums\AdsType::Script)
        {!! $ads->value !!}
        @endif
    </div>

    <script src="{{ asset('frontend/default/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('frontend/default/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        (function ($) {
            'use strict';

            // Variables
            const adContent = $('#adContentToShare').text().trim();
            const adImageUrl = $('#adImageUrl').val() || '';
            const adType = $('#adType').val();
            let hasShared = false;
            
            // Detect if mobile device
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            
            console.log('Device type:', isMobile ? 'Mobile' : 'Desktop');
            console.log('Ad type:', adType);

            // Notify
            $('#notifyCloseBtn').on('click', function () {
                var parent = $('.notify-box');
                parent.fadeOut("slow", function () {
                    $(this).remove();
                });
            });

            setTimeout(() => {
                var parent = $('.notify-box');
                parent.fadeOut("slow", function () {
                    $(this).remove();
                });
            }, 5000);

            // Check device compatibility on share section show
            function checkDeviceCompatibility() {
                if (adType === 'image' && !isMobile) {
                    // Show desktop warning
                    $('#desktopWarning').removeClass('d-none');
                    // Disable share buttons
                    $('#shareButtonsGrid button').prop('disabled', true).css('opacity', '0.5');
                    showNotification('{{ __("Please use a mobile device to share images") }}', 'warning');
                }
            }

            // Share Handler for All Platforms
            window.shareOnPlatform = async function(platform) {
                if (adType === 'image') {
                    if (!isMobile) {
                        showNotification('{{ __("Please open this page on mobile to share images") }}', 'warning');
                        return;
                    }
                    await shareImageOnMobile(platform);
                } else {
                    shareTextContent(platform);
                }
            };

            // Share Image on Mobile using Native Share API
            async function shareImageOnMobile(platform) {
                const message = '{{ __("Check out this ad!") }}';
                
                try {
                    // Fetch image as blob
                    const response = await fetch(adImageUrl);
                    const blob = await response.blob();
                    const file = new File([blob], 'ad-image.jpg', { type: blob.type });
                    
                    // Check if Web Share API is available
                    if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
                        await navigator.share({
                            title: 'Check out this ad!',
                            text: message,
                            files: [file]
                        });
                        
                        // Share completed successfully
                        markShareCompleted();
                    } else {
                        // Fallback: download image
                        showNotification('{{ __("Web Share not supported. Downloading image...") }}', 'warning');
                        downloadImage();
                        setTimeout(() => {
                            markShareCompleted();
                        }, 2000);
                    }
                } catch (error) {
                    if (error.name === 'AbortError') {
                        console.log('User cancelled share');
                    } else {
                        console.error('Share failed:', error);
                        showNotification('{{ __("Share failed. Please try again.") }}', 'warning');
                    }
                }
            }

            // Download image for manual sharing
            function downloadImage() {
                fetch(adImageUrl)
                    .then(response => response.blob())
                    .then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'ad-image.jpg';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    })
                    .catch(err => {
                        console.error('Download failed:', err);
                    });
            }

            // Share Text/Link Content
            function shareTextContent(platform) {
                let message = '{{ __("Check out this ad:") }}\n\n' + adContent;
                let shareUrl = '';

                switch(platform) {
                    case 'whatsapp':
                        shareUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
                        window.open(shareUrl, '_blank', 'width=600,height=500');
                        break;
                    
                    case 'facebook':
                        shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(adContent)}`;
                        window.open(shareUrl, '_blank', 'width=600,height=500');
                        break;
                    
                    case 'twitter':
                        shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent('Check out this ad!')}&url=${encodeURIComponent(adContent)}`;
                        window.open(shareUrl, '_blank', 'width=600,height=500');
                        break;
                    
                    case 'instagram':
                        copyToClipboard(adContent);
                        showNotification('{{ __("Link copied! Open Instagram and paste.") }}', 'success');
                        break;
                }

                markShareCompleted();
            }

            // Copy to Clipboard
            function copyToClipboard(text) {
                const tempInput = document.createElement('textarea');
                tempInput.value = text;
                document.body.appendChild(tempInput);
                tempInput.select();
                
                try {
                    document.execCommand('copy');
                    document.body.removeChild(tempInput);
                } catch (err) {
                    document.body.removeChild(tempInput);
                    console.error('Copy failed:', err);
                }
            }

            function showNotification(message, type) {
                const notification = $('#shareAlert');
                notification.removeClass('success warning').addClass('show ' + type).text(message);
                
                setTimeout(() => {
                    notification.removeClass('show');
                }, 4000);
            }

            function markShareCompleted() {
                if (hasShared) return;
                
                hasShared = true;
                showNotification('{{ __("Thank you for sharing! Loading captcha...") }}', 'success');
                
                // Optional: Send to backend for tracking
                $.ajax({
                    url: '/verify-share',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: { shared: true },
                    success: function(response) {
                        console.log('Share tracked successfully');
                    },
                    error: function(xhr) {
                        console.log('Share tracking skipped');
                    }
                });
                
                // Hide share section and show captcha
                setTimeout(() => {
                    $('#shareSection').fadeOut(300, function() {
                        $('#captchaSection').removeClass('d-none').hide().fadeIn(300);
                        $('#submitArea').removeClass('d-none').hide().fadeIn(300);
                        $('#reportArea').removeClass('d-none');
                        $('form').attr('action', "{{ route('user.ads.submit',['id' => encrypt($ads->id) ]) }}");
                    });
                }, 1000);
            }

            // Progress Timer
            window.addEventListener("load", function () {
                const progressItems = document.querySelectorAll('.progress-advertisement-item');
                progressItems.forEach(item => {
                    let progress = 0;
                    const progressBar = item.querySelector('.progress-bar');
                    const progressCount = item.querySelector('.progress-count');
                    const durationInSeconds = "{{ $ads->duration }}";
                    const intervalTimeInMilliseconds = durationInSeconds * 10;

                    const intervalId = setInterval(calculateProgress, intervalTimeInMilliseconds);

                    function calculateProgress() {
                        if (progress >= 100) {
                            clearInterval(intervalId);
                            $('#shareSection').removeClass('d-none');
                            checkDeviceCompatibility();
                        } else {
                            progress++;
                            progressBar.style.width = progress + '%';
                            progressCount.textContent = progress + '%';
                        }
                    }
                });
            });

            // Visibility change warning
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState == 'hidden') {
                    $('.advertisement-area').remove();
                    $('.advertisement-field').remove();
                    $('#warningArea').removeClass('d-none');
                }
            });

            // Adjust iframe size
            let adsType = "{{ $ads->type->value }}";
            if (adsType == 'link' || adsType == 'youtube') {
                $(document).find('iframe').width($(window).width());
                $(document).find('iframe').height($(window).height());
            }

        })(jQuery);
    </script>
</body>

</html>