@extends('cms.layouts.app')
@section('title', 'Sửa '.$config['title'])
@section('breadcrumb', $config['title'].' / Chỉnh sửa')
@section('content') @include('cms.shared.form') @endsection
@push('scripts')<script>CMS.formPage();</script>@endpush
