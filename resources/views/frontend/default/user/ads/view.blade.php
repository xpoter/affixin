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

        .share-buttons-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 10px;
        }

        @media (min-width: 576px) {
            .share-buttons-grid {
                grid-template-columns: repeat(3, 1fr);
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

        .share-btn.whatsapp {
            background: #25D366;
            color: white;
        }

        .share-btn.telegram {
            background: #0088cc;
            color: white;
        }

        .share-btn.twitter {
            background: #1DA1F2;
            color: white;
        }

        .share-btn.facebook {
            background: #1877F2;
            color: white;
        }

        .share-btn.copy {
            background: #6c757d;
            color: white;
        }

        .share-btn.copy.copied {
            background: #28a745;
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
                                    <h4>📱 {{ __('Share on WhatsApp to Continue') }}</h4>
                                    <p>{{ __('Share this ad content to unlock the captcha') }}</p>
                                </div>

                                <div class="share-link-box">
                                    <p class="share-link-text" id="adContentToShare">
                                        @if($ads->type == App\Enums\AdsType::Link)
                                            {{ $ads->value }}
                                        @elseif($ads->type == App\Enums\AdsType::Youtube)
                                            {{ strip_tags($ads->value) }}
                                        @elseif($ads->type == App\Enums\AdsType::Image)
                                            {{ asset($ads->value) }}
                                        @elseif($ads->type == App\Enums\AdsType::Script)
                                            {{ $ads->title ?? 'Check out this ad!' }}
                                        @endif
                                    </p>
                                </div>

                                <div class="text-center">
                                    <button type="button" class="share-btn whatsapp" onclick="shareOnWhatsApp()" style="display: inline-flex; min-width: 200px;">
                                        📱 {{ __('Share on WhatsApp') }}
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
                    
                    <div class="input-btn-wrap mt-2" id="reportArea">
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
            let hasShared = false;

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

            // WhatsApp Share Handler
            window.shareOnWhatsApp = function() {
                // Create WhatsApp share message
                let message = '{{ __("Check out this ad:") }}\n\n' + adContent;
                
                // Encode for URL
                let whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
                
                // Open WhatsApp
                window.open(whatsappUrl, '_blank', 'width=600,height=500');
                
                // Mark as shared
                markShareCompleted();
            };

            function showNotification(message, type) {
                const notification = $('#shareAlert');
                notification.addClass('show ' + type).text(message);
                
                setTimeout(() => {
                    notification.removeClass('show');
                }, 3000);
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
                
                // Hide share section and show captcha after 1 second
                setTimeout(() => {
                    $('#shareSection').fadeOut(300, function() {
                        $('#captchaSection').removeClass('d-none').hide().fadeIn(300);
                        $('#submitArea').removeClass('d-none').hide().fadeIn(300);
                        $('#reportArea').removeClass('d-none');
                        $('form').attr('action', "{{ route('user.ads.submit',['id' => encrypt($ads->id) ]) }}");
                    });
                }, 1000);
            }

            // Progress Timer (Original Code)
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
                            // Show share section instead of captcha
                            $('#shareSection').removeClass('d-none');
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