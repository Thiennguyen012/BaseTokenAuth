<div class="heading">
    <div><h1>{{ $config['title'] }}</h1><p>{{ $config['description'] }}</p></div>
    <a class="btn primary" href="{{ route("cms.$module.create") }}">＋ Thêm mới</a>
</div>
<div class="card module-table" data-endpoint="{{ $config['api'] }}" data-edit-url="{{ url("/cms/$module") }}">
    <div class="toolbar">
        <div class="table-filter-group">
            <input class="input search" placeholder="Tìm kiếm..." data-search>
            @foreach($config['filters'] ?? [] as $filter)
                <div class="table-filter-wrap" data-filter-wrap>
                    <div class="table-filter-control @if($filter['multiple'] ?? false) checkbox-filter-control @endif">
                        @if($filter['multiple'] ?? false)
                            <button type="button" class="checkbox-filter-toggle" data-filter-toggle>
                                <span data-filter-summary>{{ $filter['label'] }}</span>
                                <span class="checkbox-filter-chevron" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="m7 10 5 5 5-5"/></svg>
                                </span>
                            </button>
                            <div class="checkbox-filter-menu" data-filter-menu hidden></div>
                            <div class="table-filter-chips" data-filter-chips hidden></div>
                        @endif
                        <select class="table-filter @if($filter['multiple'] ?? false) native-filter-source @endif"
                                data-filter-name="{{ $filter['name'] }}"
                                data-source="{{ $filter['source'] }}"
                                data-value="{{ $filter['value'] }}"
                                data-text="{{ $filter['text'] }}"
                                @if($filter['multiple'] ?? false)
                                    data-filter-multiple="1"
                                    data-filter-label="{{ $filter['label'] }}"
                                @endif>
                            <option value="">{{ $filter['label'] }}</option>
                        </select>
                    </div>
                </div>
            @endforeach
        </div>
        <button class="btn" data-reload>↻ Làm mới</button>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr>@foreach($config['columns'] as $label)<th>{{ $label }}</th>@endforeach<th>Thao tác</th></tr></thead>
            <tbody data-table-body><tr><td colspan="{{ count($config['columns']) + 1 }}" class="empty loading-state">Đang tải dữ liệu...</td></tr></tbody>
        </table>
    </div>
    <script type="application/json" data-columns>@json(array_keys($config['columns']))</script>
</div>
