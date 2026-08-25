<div id="sidebarMain">
    <aside class="bg-white js-navbar-vertical-aside navbar navbar-vertical-aside navbar-vertical navbar-vertical-fixed navbar-expand-xl navbar-bordered">
        <div class="navbar-vertical-container">
            <div class="navbar-vertical-footer-offset pb-0">
                <div class="navbar-brand-wrapper justify-content-between side-logo">
                    <!-- Logo -->
                    <a class="navbar-brand cms-sidebar-brand" href="{{ route('cms.dashboard') }}" aria-label="{{ $cmsCompanyName }}">
                        @if($cmsCompanyLogoUrl)
                            <img class="cms-sidebar-logo" src="{{ $cmsCompanyLogoUrl }}" alt="{{ $cmsCompanyName }}">
                        @else
                            <span class="avatar avatar-sm avatar-circle bg-primary text-white font-weight-bold d-inline-flex align-items-center justify-content-center mr-2" style="width:34px;height:34px;font-size:16px;">{{ mb_strtoupper(mb_substr($cmsCompanyName, 0, 1)) }}</span>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements font-weight-bold text-dark h4 mb-0">{{ $cmsCompanyName }}</span>
                        @endif
                    </a>

                    <!-- Navbar Vertical Toggle -->
                    <button type="button" class="js-navbar-vertical-aside-toggle-invoker navbar-vertical-aside-toggle btn btn-icon btn-xs btn-ghost-dark d-none">
                        <i class="tio-clear tio-lg"></i>
                    </button>
                    <button type="button" class="js-navbar-vertical-aside-toggle-invoker close">
                        <i class="tio-first-page navbar-vertical-aside-toggle-short-align" data-toggle="tooltip" data-placement="right" title="Thu gọn"></i>
                        <i class="tio-last-page navbar-vertical-aside-toggle-full-align" data-toggle="tooltip" data-placement="right" title="Mở rộng"></i>
                    </button>
                </div>

                <!-- Content -->
                <div class="navbar-vertical-content pt-3">
                    <ul class="navbar-nav navbar-nav-lg nav-tabs">
                        <!-- Dashboard -->
                        <li class="navbar-vertical-aside-has-menu {{ request()->routeIs('cms.dashboard') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('cms.dashboard') }}" title="Tổng quan">
                                <i class="ri-home-5-line nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements">
                                    Tổng quan
                                </span>
                            </a>
                        </li>

                        <!-- Quản lý Sản phẩm -->
                        <li class="nav-item">
                            <small class="nav-subtitle" title="Quản lý sản phẩm">Quản lý sản phẩm</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ request()->routeIs('cms.products.*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('cms.products.index') }}" title="Sản phẩm">
                                <i class="ri-box-3-line nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements">
                                    Sản phẩm
                                </span>
                            </a>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ request()->routeIs('cms.categories.*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('cms.categories.index') }}" title="Danh mục sản phẩm">
                                <i class="ri-folders-line nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements">
                                    Danh mục sản phẩm
                                </span>
                            </a>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ request()->routeIs('cms.tags.*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('cms.tags.index') }}" title="Nhãn sản phẩm">
                                <i class="ri-price-tag-3-line nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements">
                                    Nhãn sản phẩm
                                </span>
                            </a>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ request()->routeIs('cms.tag-groups.*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('cms.tag-groups.index') }}" title="Nhóm nhãn sản phẩm">
                                <i class="ri-folder-3-line nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements">
                                    Nhóm nhãn
                                </span>
                            </a>
                        </li>

                        <!-- Khách hàng -->
                        <li class="nav-item">
                            <small class="nav-subtitle" title="Khách hàng">Khách hàng</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ request()->routeIs('cms.customer-contacts.*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('cms.customer-contacts.index') }}" title="Khách hàng liên hệ">
                                <i class="ri-mail-send-line nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements">
                                    Khách hàng liên hệ
                                </span>
                            </a>
                        </li>

                        <!-- Nội dung & Bố cục -->
                        <li class="nav-item">
                            <small class="nav-subtitle" title="Nội dung & Bố cục">Nội dung & Bố cục</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ request()->routeIs('cms.page-contents.*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('cms.page-contents.index') }}" title="Trang nội dung">
                                <i class="ri-file-text-line nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements">
                                    Trang nội dung
                                </span>
                            </a>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ request()->routeIs('cms.page-configs.*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('cms.page-configs.index') }}" title="Cấu hình trang">
                                <i class="ri-settings-4-line nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements">
                                    Cấu hình trang
                                </span>
                            </a>
                        </li>

                        <!-- Hệ thống -->
                        <li class="nav-item">
                            <small class="nav-subtitle" title="Hệ thống">Hệ thống</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>

                        <li class="navbar-vertical-aside-has-menu">
                            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ url('/api/documentation') }}" target="_blank" title="Tài liệu API">
                                <i class="ri-code-s-slash-line nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements">
                                    Tài liệu API
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </aside>
</div>
