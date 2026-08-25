<div id="headerMain">
    <header id="header" class="navbar navbar-expand-lg navbar-fixed navbar-height navbar-flush navbar-container shadow-sm bg-white">
        <div class="navbar-nav-wrap">
            <div class="navbar-brand-wrapper">
                <a class="navbar-brand font-weight-bold text-primary" href="{{ route('cms.dashboard') }}">
                    <span class="avatar avatar-sm avatar-circle bg-primary text-white mr-2 d-inline-flex align-items-center justify-content-center" style="width:32px;height:32px;font-weight:bold;">N</span>
                    Nhựa CMS
                </a>
            </div>

            <div class="navbar-nav-wrap-content-left">
                <button type="button" class="js-navbar-vertical-aside-toggle-invoker close mr-3 d-xl-none">
                    <i class="tio-first-page navbar-vertical-aside-toggle-short-align"></i>
                    <i class="tio-last-page navbar-vertical-aside-toggle-full-align"></i>
                </button>
            </div>

            <div class="navbar-nav-wrap-content-right ml-auto">
                <ul class="navbar-nav align-items-center flex-row">
                    <li class="nav-item d-none d-sm-inline-block mr-3">
                        <a class="btn btn-xs rounded-pill px-3 cms-website-link" href="{{ rtrim(config('app.url'), '/') }}" target="_blank" rel="noopener noreferrer">
                            <i class="ri-global-line mr-1"></i> Tới Website
                        </a>
                    </li>

                    <li class="nav-item">
                        <div class="hs-unfold">
                            <a class="js-hs-unfold-invoker media align-items-center gap-3 navbar-dropdown-account-wrapper dropdown-toggle"
                               href="javascript:;"
                               data-hs-unfold-options='{
                                   "target": "#accountNavbarDropdown",
                                   "type": "css-animation"
                               }'>
                                <div class="avatar avatar-circle avatar-sm border mr-2 bg-soft-primary text-primary font-weight-bold d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                                    {{ strtoupper(substr(auth()->user()->name ?? auth()->user()->email ?? 'A', 0, 1)) }}
                                </div>
                                <div class="d-none d-md-block media-body text-left">
                                    <h5 class="profile-name mb-0 font-weight-bold text-dark">{{ auth()->user()->name ?? 'Quản trị viên' }}</h5>
                                    <span class="fz-12 text-muted">{{ auth()->user()->email ?? 'admin@example.com' }}</span>
                                </div>
                            </a>

                            <div id="accountNavbarDropdown" class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-right navbar-dropdown-menu navbar-dropdown-account" style="min-width: 220px;">
                                <div class="dropdown-item-text">
                                    <div class="media align-items-center text-break">
                                        <div class="avatar avatar-sm avatar-circle mr-2 bg-soft-primary text-primary font-weight-bold d-flex align-items-center justify-content-center">
                                            {{ strtoupper(substr(auth()->user()->name ?? auth()->user()->email ?? 'A', 0, 1)) }}
                                        </div>
                                        <div class="media-body">
                                            <span class="card-title h5 d-block mb-0">{{ auth()->user()->name ?? 'Administrator' }}</span>
                                            <span class="card-text text-muted font-size-sm">{{ auth()->user()->email ?? '' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <form method="post" action="{{ route('cms.logout') }}" id="logout-form">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger border-0 bg-transparent w-100 text-left">
                                        <i class="ri-logout-box-r-line mr-2"></i> Đăng xuất
                                    </button>
                                </form>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </header>
</div>
