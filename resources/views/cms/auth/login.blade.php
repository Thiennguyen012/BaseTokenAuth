<!doctype html>
<html lang="vi">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Đăng nhập CMS</title><link rel="stylesheet" href="{{ asset('cms-assets/cms.css') }}"></head>
<body class="login-page">
<form class="login-card" method="post" action="{{ route('cms.login.submit') }}">
    @csrf
    <div class="logo">N</div><h1>Đăng nhập CMS</h1><p>Quản lý cửa hàng và nội dung website.</p>
    <label for="email">Email</label><input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
    <label for="password">Mật khẩu</label><input class="input" id="password" type="password" name="password" required autocomplete="current-password">
    <button class="btn primary">Đăng nhập</button>
    @if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif
</form>
</body>
</html>
