@extends('cms.layouts.app')
@section('title', 'Quản lý trang nội dung')

@section('content')
<div class="content container-fluid" data-content-pages data-endpoint="{{ $config['api'] }}" data-edit-url="{{ url('/cms/page-contents') }}" data-sections-url="{{ url('/cms/page-contents') }}" data-per-page="{{ \App\CPU\Helpers::LIMIT_PER_PAGE }}">
    <!-- Page Header -->
    <div class="page-header pb-3 mb-3 border-bottom">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title text-capitalize d-flex align-items-center gap-2 font-weight-bold">
                    <i class="ri-file-text-line text-primary mr-2"></i>
                    Quản lý nội dung
                    <span class="badge badge-soft-info p-2 ml-2 font-weight-bold" data-page-count>0 trang</span>
                </h1>
                <p class="page-header-text text-muted mb-0">Quản lý danh sách các trang nội dung và các section tương ứng trên trang web.</p>
            </div>
            <div class="col-sm-auto d-flex gap-2">
                <a class="btn btn-primary btn-sm px-3 shadow-sm" href="{{ route('cms.page-contents.create') }}">
                    <i class="ri-add-line mr-1"></i> Thêm trang mới
                </a>
            </div>
        </div>
    </div>

    <!-- Filter & Grid Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header p-3 bg-white border-bottom flex-wrap gap-2 d-flex align-items-center justify-content-between">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="input-group input-group-merge border rounded bg-white" style="max-width: 380px;">
                    <div class="input-group-prepend">
                        <div class="input-group-text"><i class="ri-search-line"></i></div>
                    </div>
                    <input class="form-control form-control-sm border-0" type="search" placeholder="Tìm theo tên hoặc slug..." data-page-search autocomplete="off">
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="content-pages-grid" data-page-grid>
                <div class="content-pages-state loading-state py-5 text-center text-muted">
                    <span class="cms-loading-content">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        <span>Đang tải dữ liệu...</span>
                    </span>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white border-top d-flex flex-wrap align-items-center justify-content-between gap-2 px-4 py-3" data-pagination hidden>
            <span class="text-muted small" data-pagination-info></span>
            <nav aria-label="Phân trang nội dung"><ul class="pagination pagination-sm mb-0" data-pagination-list></ul></nav>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    if (typeof CMS !== 'undefined' && typeof CMS.pageContentCards === 'function') {
        CMS.pageContentCards();
    }
</script>
@endpush
