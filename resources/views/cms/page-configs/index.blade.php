@extends('cms.layouts.app')
@section('title', $config['title'].' - CMS')
@section('breadcrumb', $config['title'])
@section('content') @include('cms.shared.form') @endsection
@push('scripts')<script>CMS.formPage();</script>@endpush
