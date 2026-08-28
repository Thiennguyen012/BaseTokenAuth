<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Trang quản trị') - {{ $cmsCompanyName }}</title>
    @if(!empty($cmsCompanyFaviconUrl))
        <link rel="shortcut icon" href="{{ $cmsCompanyFaviconUrl }}">
        <link rel="icon" href="{{ $cmsCompanyFaviconUrl }}">
    @endif
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
    
    <!-- CSS Plugins & Themes from cms-pwf -->
    <link rel="stylesheet" href="{{ asset('assets/back-end/css/vendor.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/back-end/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/back-end/vendor/icon-set/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/back-end/css/theme.minc619.css?v=1.0') }}">
    <link rel="stylesheet" href="{{ asset('assets/back-end/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/back-end/css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/back-end/css/toastr.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="{{ asset('cms-assets/cms-components.css') }}?v={{ @filemtime(public_path('cms-assets/cms-components.css')) ?: time() }}">
    <style>
        #toast-container {
            top: 14px !important;
            right: 24px !important;
            z-index: 999999 !important;
        }
        #toast-container > .toast {
            opacity: 1 !important;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12) !important;
            border-radius: 10px !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
            padding: 10px 14px 10px 42px !important;
            background-size: 20px !important;
            font-weight: 600 !important;
            font-size: 13.5px !important;
            line-height: 1.35 !important;
            width: auto !important;
            max-width: 320px !important;
        }
        #toast-container > .toast-success {
            background-color: #ecfdf5 !important;
            color: #065f46 !important;
            border: 1px solid #a7f3d0 !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23059669'%3E%3Cpath d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z'/%3E%3C/svg%3E") !important;
        }
        #toast-container > .toast-success .toast-close-button {
            color: #047857 !important;
            text-shadow: none !important;
            opacity: 0.7 !important;
        }
        #toast-container > .toast-success .toast-close-button:hover {
            opacity: 1 !important;
        }
        #toast-container > .toast-error {
            background-color: #fef2f2 !important;
            color: #991b1b !important;
            border: 1px solid #fecaca !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23dc2626'%3E%3Cpath d='M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z'/%3E%3C/svg%3E") !important;
        }
        #toast-container > .toast-error .toast-close-button {
            color: #b91c1c !important;
            text-shadow: none !important;
            opacity: 0.7 !important;
        }
        #toast-container > .toast-error .toast-close-button:hover {
            opacity: 1 !important;
        }
        #toast-container > .toast-warning {
            background-color: #fffbeb !important;
            color: #92400e !important;
            border: 1px solid #fde68a !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23d97706'%3E%3Cpath d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z'/%3E%3C/svg%3E") !important;
        }
        #toast-container > .toast-info {
            background-color: #f0f9ff !important;
            color: #075985 !important;
            border: 1px solid #bae6fd !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%230284c7'%3E%3Cpath d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z'/%3E%3C/svg%3E") !important;
        }
    </style>
    @stack('css')
    @stack('css_or_js')
</head>

<body class="footer-offset navbar-vertical-aside-show-xl"
      data-api="{{ url('/admin/api') }}" 
      data-storage="{{ url('/storage') }}" 
      data-login="{{ route('cms.login') }}" 
      data-refresh-url="{{ route('cms.refresh-token') }}" 
      data-access-token="{{ session('cms_access_token') }}" 
      data-access-token-expires-at="{{ session('cms_access_token_expires_at') }}">

    <!-- Header Topbar -->
    @include('cms.partials.header')

    <!-- Sidebar Navigation -->
    @include('cms.partials.sidebar')

    <!-- Main Content Area -->
    <main id="content" role="main" class="main pointer-event">
        <!-- Content -->
        @yield('content')

        <!-- Footer -->
        @include('cms.partials.footer')
    </main>

    <!-- Toast container -->
    <div class="toast" data-toast></div>

    <!-- JS Plugins & Scripts from cms-pwf -->
    <script src="{{ asset('assets/back-end/js/vendor.min.js') }}"></script>
    <script src="{{ asset('assets/back-end/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/back-end/js/theme.min.js') }}"></script>
    <script src="{{ asset('assets/back-end/js/sweet_alert.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('assets/back-end/js/toastr.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="{{ asset('assets/back-end/js/custom.js') }}"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <script src="{{ asset('cms-assets/cms.js') }}?v={{ @filemtime(public_path('cms-assets/cms.js')) ?: time() }}"></script>

    <script>
        if (typeof toastr !== 'undefined') {
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "timeOut": "4000",
                "extendedTimeOut": "1000",
                "preventDuplicates": true,
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };

            @if(session('success'))
                toastr.success({!! json_encode((string) session('success')) !!});
            @endif
            @if(session('status'))
                toastr.success({!! json_encode((string) session('status')) !!});
            @endif
            @if(session('error'))
                toastr.error({!! json_encode((string) session('error')) !!});
            @endif
            @if(session('warning'))
                toastr.warning({!! json_encode((string) session('warning')) !!});
            @endif
            @if(session('info'))
                toastr.info({!! json_encode((string) session('info')) !!});
            @endif
            @if(session('message'))
                toastr.info({!! json_encode((string) session('message')) !!});
            @endif
            @if(isset($errors) && $errors->any())
                @foreach($errors->all() as $error)
                    toastr.error({!! json_encode((string) $error) !!});
                @endforeach
            @endif
        }
    </script>

    <script>
        $(document).on('ready', function () {
            // NAVBAR VERTICAL NAVIGATION
            var sidebar = $('.js-navbar-vertical-aside').hsSideNav();

            // SELECT2 CUSTOM
            $('.js-select2-custom').each(function () {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });

            // UNFOLD
            $('.js-hs-unfold-invoker').each(function () {
                var unfold = new HSUnfold($(this)).init();
            });
        });
    </script>

    @stack('script')
    @stack('scripts')
</body>
</html>
