<div class="footer py-3 border-top bg-white mt-auto">
    <div class="container-fluid">
        <div class="row align-items-center justify-content-between">
            <div class="col-md-6 text-center text-md-left mb-2 mb-md-0">
                <p class="fs-13 text-muted mb-0">
                    &copy; {{ date('Y') }} <strong>{{ $cmsCompanyName }}</strong>. Tất cả quyền được bảo lưu.
                </p>
            </div>
            <div class="col-md-6 text-center text-md-right">
                <ul class="list-inline mb-0">
                    <li class="list-inline-item">
                        <a class="list-separator-link text-muted font-size-sm" href="{{ url('/') }}" target="_blank">Trang chủ</a>
                    </li>
                    <li class="list-inline-item">
                        <a class="list-separator-link text-muted font-size-sm" href="{{ url('/api/documentation') }}" target="_blank">Tài liệu API</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
