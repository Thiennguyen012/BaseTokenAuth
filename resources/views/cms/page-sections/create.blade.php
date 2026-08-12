@extends('cms.layouts.app')
@section('title', 'Thêm '.$config['title'])
@section('breadcrumb', $config['title'].' / Thêm mới')
@section('content') @include('cms.shared.form') @endsection
@push('scripts')<script>CMS.formPage();</script>@endpush
