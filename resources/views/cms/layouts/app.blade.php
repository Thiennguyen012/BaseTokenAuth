<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CMS cửa hàng') - Nhựa CMS</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
    
    <!-- CSS Plugins & Themes from cms-pwf -->
    <link rel="stylesheet" href="{{ asset('/assets/back-end/css/vendor.min.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/back-end/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/back-end/vendor/icon-set/style.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/back-end/css/theme.minc619.css?v=1.0') }}">
    <link rel="stylesheet" href="{{ asset('/assets/back-end/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/back-end/css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/back-end/css/toastr.css') }}">
    <link rel="stylesheet" href="{{ asset('cms-assets/cms-components.css') }}?v={{ filemtime(public_path('cms-assets/cms-components.css')) }}">
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
    <script src="{{ asset('/assets/back-end/js/vendor.min.js') }}"></script>
    <script src="{{ asset('/assets/back-end/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('/assets/back-end/js/theme.min.js') }}"></script>
    <script src="{{ asset('/assets/back-end/js/sweet_alert.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('/assets/back-end/js/toastr.js') }}"></script>
    <script src="{{ asset('/assets/back-end/js/custom.js') }}"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <script src="{{ asset('cms-assets/cms.js') }}?v={{ filemtime(public_path('cms-assets/cms.js')) }}"></script>

    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "4000"
        };

        @if(session('success'))
            toastr.success("{{ session('success') }}");
        @endif
        @if(session('error'))
            toastr.error("{{ session('error') }}");
        @endif
        @if(session('warning'))
            toastr.warning("{{ session('warning') }}");
        @endif
        @if(session('info'))
            toastr.info("{{ session('info') }}");
        @endif
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
