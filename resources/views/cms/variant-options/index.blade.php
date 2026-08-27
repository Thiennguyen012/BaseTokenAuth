@extends('cms.layouts.app')
@section('title', $config['title'])
@section('breadcrumb', $config['title'])
@section('content') @include('cms.shared.index') @endsection
@push('scripts')<script>CMS.indexPage();</script>@endpush
