|<div class="breadcrumb-wrapper">
    @if(@isset($breadcrumbs))
        <div class="d-flex align-items-center">
            @foreach ($breadcrumbs as $breadcrumb)
                <div class="breadcrumb-item">
                    @if(isset($breadcrumb['link']))
                        <a href="{{ $breadcrumb['link'] == 'javascript:void(0)' ? $breadcrumb['link']:url($breadcrumb['link']) }}">
                                <?= $breadcrumb['icon'] ?? '' ?>
                            @endif
                            {{$breadcrumb['name']}}
                            @if(isset($breadcrumb['link']))
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    @endisset
</div>
