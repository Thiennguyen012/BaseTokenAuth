<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header pb-3 mb-3 border-bottom">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title text-capitalize d-flex align-items-center gap-2 font-weight-bold">
                    <i class="ri-folder-open-line text-primary mr-2"></i>
                    {{ $config['title'] }}
                </h1>
                <p class="page-header-text text-muted mb-0">{{ $config['description'] }}</p>
            </div>
            <div class="col-sm-auto d-flex gap-2">
                @if(!empty($backUrl))
                    <a class="btn btn-secondary btn-sm px-3 mr-2" href="{{ $backUrl }}">
                        <i class="ri-arrow-left-line mr-1"></i> {{ $backLabel ?? 'Quay lại' }}
                    </a>
                @endif
                <a class="btn btn-primary btn-sm px-3 shadow-sm" href="{{ $createUrl ?? route("cms.$module.create") }}">
                    <i class="ri-add-line mr-1"></i> Thêm mới
                </a>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card module-table border-0 shadow-sm mb-4" data-endpoint="{{ $config['api'] }}" data-edit-url="{{ url("/cms/$module") }}" data-fixed-params='@json($fixedParams ?? [])' data-per-page="{{ \App\CPU\Helpers::LIMIT_PER_PAGE }}">
        <div class="card-header p-3 bg-white border-bottom flex-wrap gap-2 d-flex align-items-center justify-content-between">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Search Box -->
                <div class="input-group input-group-merge border rounded bg-white" style="max-width: 300px;">
                    <div class="input-group-prepend">
                        <div class="input-group-text"><i class="ri-search-line"></i></div>
                    </div>
                    <input class="form-control form-control-sm border-0" type="search" placeholder="Tìm kiếm nhanh..." data-search>
                </div>

                <!-- Filters -->
                @foreach($config['filters'] ?? [] as $filter)
                    @if($filter['multiple'] ?? false)
                    <div class="checkbox-filter-control" data-filter-wrap style="width: 240px;">
                        <button class="checkbox-filter-toggle" type="button" data-filter-toggle>
                            <span data-filter-summary>{{ $filter['label'] }}</span>
                            <span class="checkbox-filter-chevron" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="m7 10 5 5 5-5"/></svg>
                            </span>
                        </button>
                        <select class="native-filter-source"
                                data-filter-name="{{ $filter['name'] }}"
                                data-filter-query-name="{{ $filter['query_name'] ?? '' }}"
                                data-source="{{ $filter['source'] ?? '' }}"
                                data-value="{{ $filter['value'] }}"
                                data-text="{{ $filter['text'] }}"
                                data-filter-multiple="1"
                                data-filter-label="{{ $filter['label'] }}"
                                @if(isset($filter['items'])) data-inline-items='@json($filter['items'])' @endif>
                            <option value="">{{ $filter['label'] }}</option>
                        </select>
                        <div class="checkbox-filter-menu" data-filter-menu hidden></div>
                    </div>
                    @else
                    <select class="custom-select custom-select-sm form-control form-control-sm border" style="max-width: 180px;"
                            data-filter-name="{{ $filter['name'] }}"
                            data-filter-query-name="{{ $filter['query_name'] ?? '' }}"
                            data-source="{{ $filter['source'] ?? '' }}"
                            data-value="{{ $filter['value'] }}"
                            data-text="{{ $filter['text'] }}"
                            @if(isset($filter['items'])) data-inline-items='@json($filter['items'])' @endif>
                        <option value="">{{ $filter['label'] }}</option>
                    </select>
                    @endif
                @endforeach

                <button class="btn btn-secondary btn-sm" data-reload title="Đặt lại bộ lọc">
                    <i class="ri-refresh-line mr-1"></i> Làm mới
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table mb-0">
                <thead class="thead-light">
                    <tr>
                        @foreach($config['columns'] as $label)
                            <th class="font-weight-bold">{{ $label }}</th>
                        @endforeach
                        <th class="text-right font-weight-bold px-4" style="width: 120px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody data-table-body>
                    <tr>
                        <td colspan="{{ count($config['columns']) + 1 }}" class="text-center py-5 text-muted empty loading-state">
                            <span class="cms-loading-content">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                <span>Đang tải dữ liệu...</span>
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <script type="application/json" data-columns>@json(array_keys($config['columns']))</script>
        <div class="card-footer bg-white border-top d-flex flex-wrap align-items-center justify-content-between gap-2 px-4 py-3" data-pagination hidden>
            <span class="text-muted small" data-pagination-info></span>
            <nav aria-label="Phân trang danh sách">
                <ul class="pagination pagination-sm mb-0" data-pagination-list></ul>
            </nav>
        </div>
    </div>
</div>
