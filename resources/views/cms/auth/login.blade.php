<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập trang quản trị</title>
    @if(!empty($cmsCompanyFaviconUrl))
        <link rel="shortcut icon" href="{{ $cmsCompanyFaviconUrl }}">
        <link rel="icon" href="{{ $cmsCompanyFaviconUrl }}">
    @endif
    <link rel="stylesheet" href="{{ asset('cms-assets/cms.css') }}">
    <style>
        .login-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .login-header .logo {
            margin: 0 auto 14px;
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: var(--brand, #0284c7);
            color: #fff;
            display: grid;
            place-items: center;
            font-size: 24px;
            font-weight: 700;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
        }
        .login-logo-img {
            max-height: 64px;
            max-width: 200px;
            object-fit: contain;
            margin: 0 auto 14px;
            display: block;
        }
        .login-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            text-align: center;
        }
        .input-password-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-password-wrap .input {
            padding-right: 42px;
        }
        .toggle-password {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: color 0.15s ease, background-color 0.15s ease;
        }
        .toggle-password:hover {
            color: #0f172a;
            background-color: #f1f5f9;
        }
        .toggle-password:focus {
            outline: none;
        }
    </style>
</head>
<body class="login-page">
<form class="login-card" method="post" action="{{ route('cms.login.submit') }}">
    @csrf
    <div class="login-header">
        @if(!empty($cmsCompanyLogoUrl))
            <img class="login-logo-img" src="{{ $cmsCompanyLogoUrl }}" alt="{{ $cmsCompanyName ?? 'Logo' }}">
        @else
            <div class="logo">{{ mb_strtoupper(mb_substr($cmsCompanyName ?? 'C', 0, 1)) }}</div>
        @endif
        <h1>Đăng nhập trang quản trị</h1>
    </div>

    <label for="email">Email</label>
    <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Nhập địa chỉ email" required autofocus autocomplete="username">

    <label for="password">Mật khẩu</label>
    <div class="input-password-wrap">
        <input class="input" id="password" type="password" name="password" placeholder="Nhập mật khẩu" required autocomplete="current-password">
        <button type="button" class="toggle-password" id="togglePasswordBtn" onclick="togglePasswordVisibility()" aria-label="Ẩn/Hiện mật khẩu" title="Ẩn/Hiện mật khẩu">
            <svg id="eyeIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
        </button>
    </div>

    <button class="btn primary" type="submit">Đăng nhập</button>

    @if($errors->any())
        <div class="form-error">{{ $errors->first() }}</div>
    @endif
</form>

<script>
    function togglePasswordVisibility() {
        const pwdInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        } else {
            pwdInput.type = 'password';
            eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
        }
    }
</script>
</body>
</html>
