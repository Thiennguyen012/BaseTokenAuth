<nav class="breadcrumb" aria-label="Breadcrumb">
    @foreach($breadcrumbs as $item)
        @if(!$loop->first)<span class="breadcrumb-separator">/</span>@endif
        @if(!empty($item['url']))
            <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
        @else
            <span class="breadcrumb-current">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
