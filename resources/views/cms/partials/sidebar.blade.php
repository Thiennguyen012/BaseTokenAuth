<aside class="sidebar" data-sidebar>
    <a class="brand" href="{{ route('cms.dashboard') }}"><span>N</span> Nhựa CMS</a>
    <a class="nav-item {{ request()->routeIs('cms.dashboard') ? 'active' : '' }}" href="{{ route('cms.dashboard') }}">⌂ Tổng quan</a>
    <div class="nav-label">Quản lý sản phẩm</div>
    <a class="nav-item {{ request()->routeIs('cms.products.*') ? 'active' : '' }}" href="{{ route('cms.products.index') }}">▣ Sản phẩm</a>
    <a class="nav-item {{ request()->routeIs('cms.categories.*') ? 'active' : '' }}" href="{{ route('cms.categories.index') }}">◇ Danh mục sản phẩm</a>
    <div class="nav-label">Khách hàng</div>
    <a class="nav-item {{ request()->routeIs('cms.customer-contacts.*') ? 'active' : '' }}" href="{{ route('cms.customer-contacts.index') }}">✉ Khách hàng liên hệ</a>
    <div class="nav-label">Nội dung & bố cục</div>
    <a class="nav-item {{ request()->routeIs('cms.page-contents.*') ? 'active' : '' }}" href="{{ route('cms.page-contents.index') }}">▤ Trang nội dung</a>
    <a class="nav-item {{ request()->routeIs('cms.page-sections.*') ? 'active' : '' }}" href="{{ route('cms.page-sections.index') }}">☷ Bố cục / Section</a>
    <a class="nav-item {{ request()->routeIs('cms.section-items.*') ? 'active' : '' }}" href="{{ route('cms.section-items.index') }}">≡ Nội dung Section</a>
    <a class="nav-item {{ request()->routeIs('cms.page-configs.*') ? 'active' : '' }}" href="{{ route('cms.page-configs.index') }}">⚙ Cấu hình trang</a>
    <div class="nav-label">Hệ thống</div>
    <a class="nav-item" href="{{ url('/api/documentation') }}" target="_blank">⌗ Tài liệu API</a>
</aside>
