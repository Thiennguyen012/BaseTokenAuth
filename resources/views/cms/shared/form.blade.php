@php($resolvedIndexUrl = $indexUrl ?? route("cms.$module.index"))
<div class="content container-fluid">
    <div class="page-header pb-3 mb-3 border-bottom">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title text-capitalize font-weight-bold">
                    <i class="ri-edit-line mr-2 text-primary"></i>
                    {{ isset($recordId) ? 'Chỉnh sửa' : 'Thêm mới' }} {{ mb_strtolower($config['title']) }}
                </h1>
                <p class="page-header-text text-muted mb-0">{{ $config['description'] }}</p>
            </div>
            <div class="col-sm-auto">
                <a class="btn btn-secondary btn-sm px-3" href="{{ $resolvedIndexUrl }}">
                    <i class="ri-arrow-left-line mr-1"></i> Quay lại
                </a>
            </div>
        </div>
    </div>

    <form class="card form-card module-form border-0 shadow-sm mb-5" data-endpoint="{{ $config['api'] }}" data-record-id="{{ $recordId ?? '' }}" data-index-url="{{ $resolvedIndexUrl }}">
        <div class="card-body p-4">
            <div class="row">
                @include("cms.$module._form")
            </div>
        </div>
        <div class="card-footer bg-white px-4 py-3 d-flex justify-content-end align-items-center gap-2 border-top">
            <a class="btn btn-secondary btn-sm px-4 mr-2 action-cancel" href="{{ $resolvedIndexUrl }}">
                Hủy
            </a>
            <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm action-save">
                Lưu thay đổi
            </button>
        </div>
    </form>
</div>
