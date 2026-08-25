@extends('cms.layouts.app')
@section('title', 'Tổng quan')

@push('css_or_js')
<style>
    .dashboard-container .stat-card {
        background: white;
        border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
        height: 100%;
        cursor: pointer;
    }
    .dashboard-container .stat-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }
    .dashboard-container .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .dashboard-container .icon-purple { background: #e3e0f7; color: #6f42c1; }
    .dashboard-container .icon-yellow { background: #fff3cd; color: #ffc107; }
    .dashboard-container .icon-cyan { background: #d1ecf1; color: #17a2b8; }
    .dashboard-container .icon-teal { background: #d4edda; color: #20c997; }
    .dashboard-container .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        color: #212529;
        margin: 0;
    }
    .dashboard-container .stat-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }
    .dashboard-container .section-card {
        background: white;
        border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
</style>
@endpush

@section('content')
<div class="content container-fluid dashboard-container">
    <!-- Page Header -->
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2 font-weight-bold">
                <i class="ri-home-5-line text-primary mr-1"></i> Tổng quan
            </h1>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                Xin chào <strong>{{ auth()->user()->name ?? 'Quản trị viên' }}</strong>, theo dõi nhanh dữ liệu cửa hàng của bạn.
            </p>
        </div>
        <div>
            <a class="btn btn-primary btn-sm px-3 shadow-sm" href="{{ route('cms.products.create') }}">
                <i class="ri-add-line mr-1"></i> Thêm sản phẩm mới
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="row g-3 mb-4">
        @foreach($stats as $index => $stat)
            @php
                $iconClasses = ['icon-purple', 'icon-yellow', 'icon-cyan', 'icon-teal'];
                $tioIcons = ['ri-box-3-line', 'ri-folders-line', 'ri-price-tag-3-line', 'ri-folder-3-line'];
                $iconBg = $iconClasses[$index % count($iconClasses)];
                $tio = $tioIcons[$index % count($tioIcons)];
            @endphp
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-label text-uppercase">{{ $stat['label'] }}</div>
                                <h3 class="stat-number">{{ number_format($stat['value']) }}</h3>
                            </div>
                            <div class="stat-icon {{ $iconBg }}">
                                <i class="{{ $tio }}"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Quick Shortcuts Card -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card section-card mb-4">
                <div class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="section-title font-weight-bold mb-0">
                        <i class="ri-flashlight-line text-primary mr-2"></i> Lối truy cập nhanh
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="{{ route('cms.products.index') }}" class="card card-hover-shadow border p-3 text-decoration-none text-dark d-flex align-items-center gap-3">
                                <div class="stat-icon icon-purple mr-3">
                                    <i class="ri-box-3-line"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 font-weight-bold">Quản lý Sản phẩm</h6>
                                    <small class="text-muted">Xem & chỉnh sửa sản phẩm</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="{{ route('cms.categories.index') }}" class="card card-hover-shadow border p-3 text-decoration-none text-dark d-flex align-items-center gap-3">
                                <div class="stat-icon icon-yellow mr-3">
                                    <i class="ri-folders-line"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 font-weight-bold">Danh mục Sản phẩm</h6>
                                    <small class="text-muted">Tổ chức phân loại</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="{{ route('cms.tags.index') }}" class="card card-hover-shadow border p-3 text-decoration-none text-dark d-flex align-items-center gap-3">
                                <div class="stat-icon icon-cyan mr-3">
                                    <i class="ri-price-tag-3-line"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 font-weight-bold">Nhãn sản phẩm</h6>
                                    <small class="text-muted">Quản lý thẻ nhãn</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="{{ route('cms.tag-groups.index') }}" class="card card-hover-shadow border p-3 text-decoration-none text-dark d-flex align-items-center gap-3">
                                <div class="stat-icon icon-teal mr-3">
                                    <i class="ri-folder-3-line"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 font-weight-bold">Nhóm nhãn</h6>
                                    <small class="text-muted">Phân loại nhóm nhãn</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
