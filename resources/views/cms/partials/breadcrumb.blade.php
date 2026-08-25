<nav aria-label="breadcrumb">
    <ol class="breadcrumb breadcrumb-no-gutter bg-transparent p-0 mb-3">
        <li class="breadcrumb-item">
            <a class="breadcrumb-link" href="{{ route('cms.dashboard') }}">
                <i class="tio-home-vs-1-outlined mr-1"></i> Trang chủ
            </a>
        </li>
        @foreach($breadcrumbs as $item)
            @if(!empty($item['url']))
                <li class="breadcrumb-item">
                    <a class="breadcrumb-link" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                </li>
            @else
                <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
            @endif
        @endforeach
    </ol>
</nav>
