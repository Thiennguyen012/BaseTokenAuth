<div class="heading"><div><h1>{{ isset($recordId) ? 'Chỉnh sửa' : 'Thêm' }} {{ mb_strtolower($config['title']) }}</h1><p>{{ $config['description'] }}</p></div><a class="btn" href="{{ route("cms.$module.index") }}">← Quay lại</a></div>
<form class="card form-card module-form" data-endpoint="{{ $config['api'] }}" data-record-id="{{ $recordId ?? '' }}" data-index-url="{{ route("cms.$module.index") }}">
    <div class="form-grid">
        @include("cms.$module._form")
    </div>
    <div class="form-actions"><a class="btn" href="{{ route("cms.$module.index") }}">Hủy</a><button class="btn primary">Lưu thay đổi</button></div>
</form>
