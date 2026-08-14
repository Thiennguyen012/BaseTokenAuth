<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'CMS cửa hàng')</title>
    <link rel="stylesheet" href="{{ asset('cms-assets/cms.css') }}">
</head>
<body data-api="{{ url('/admin/api') }}" data-storage="{{ url('/storage') }}" data-login="{{ route('cms.login') }}" data-access-token="{{ session('cms_access_token') }}" data-access-token-expires-at="{{ session('cms_access_token_expires_at') }}">
<div class="shell">
    @include('cms.partials.sidebar')
    <main class="main">
        <header class="topbar"><div class="topbar-left"><button class="mobile-menu" data-menu>☰</button>@include('cms.partials.breadcrumb', ['breadcrumbs' => $breadcrumbs ?? []])</div><div class="user"><span>{{ auth()->user()->name ?? auth()->user()->email }}</span><form method="post" action="{{ route('cms.logout') }}">@csrf<button class="btn">Đăng xuất</button></form></div></header>
        <section class="content">@yield('content')</section>
    </main>
</div>
<div class="toast" data-toast></div>
<script src="{{ asset('cms-assets/cms.js') }}"></script>
@stack('scripts')
</body>
</html>
