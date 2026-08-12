@extends('cms.layouts.app')
@section('title', 'Tổng quan - CMS')
@section('breadcrumb', 'Tổng quan')
@section('content')
<div class="heading"><div><h1>Tổng quan cửa hàng</h1><p>Theo dõi nhanh dữ liệu quản trị.</p></div></div>
<div class="stats">
    @foreach($stats as $stat)
        <div class="stat"><span>{{ $stat['label'] }}</span><strong>{{ $stat['value'] }}</strong></div>
    @endforeach
</div>
@endsection
