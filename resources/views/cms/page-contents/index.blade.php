@extends('cms.layouts.app')
@section('title', 'Quản lý nội dung - CMS')
@section('breadcrumb', 'Quản lý nội dung')
@section('content')
<section class="content-pages" data-content-pages data-endpoint="{{ $config['api'] }}" data-edit-url="{{ url('/cms/page-contents') }}" data-sections-url="{{ url('/cms/page-contents') }}">
    <div class="content-pages-toolbar card">
        <div class="content-pages-title"><h1>Quản lý nội dung</h1><span class="content-pages-count" data-page-count>0 trang</span></div>
        <div class="content-pages-actions">
            <label class="content-pages-search">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" data-page-search placeholder="Tìm theo tên hoặc slug..." aria-label="Tìm trang">
            </label>
            <a class="btn primary" href="{{ route('cms.page-contents.create') }}">＋ Thêm trang</a>
        </div>
    </div>
    <div class="content-pages-panel card"><div class="content-pages-grid" data-page-grid><div class="content-pages-state loading-state">Đang tải dữ liệu...</div></div></div>
</section>
@endsection
@push('scripts')<script>CMS.pageContentCards();</script>@endpush
