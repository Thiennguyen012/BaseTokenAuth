@extends('cms.layouts.app')
@section('title', $pageContent->title.' - CMS')
@section('breadcrumb', $pageContent->title)
@section('content')
<section class="section-manager" data-section-manager data-endpoint="{{ $config['api'] }}" data-page-id="{{ $pageContent->id }}" data-page-url="{{ url('/cms/page-contents/'.$pageContent->slug.'/sections') }}">
    <div class="section-manager-head card">
        <a class="section-manager-back" href="{{ route('cms.page-contents.index') }}">← Danh sách nội dung trang</a>
        <div class="section-manager-tags"><span>{{ $pageContent->slug }}</span><span>/{{ ltrim($pageContent->slug, '/') }}</span></div>
        <div class="section-manager-title-row"><h1>{{ $pageContent->title }}</h1><div><span class="section-total" data-section-count>0 section</span><button class="btn primary" type="button" data-add-section>＋ Thêm section</button></div></div>
        <label class="section-search-label">Tìm kiếm section</label>
        <label class="section-search"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input type="search" data-section-search placeholder="Tìm theo tiêu đề, mô tả hoặc thứ tự section..."></label>
    </div>
    <div class="section-list" data-section-list><div class="section-manager-state loading-state">Đang tải dữ liệu...</div></div>
</section>
@endsection
@push('scripts')<script>CMS.sectionManager();</script>@endpush
