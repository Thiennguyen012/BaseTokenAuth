@foreach($config['fields'] as $field)
@php($type = $field['type'])
<div class="field {{ in_array($type, ['textarea','json','key_value','repeatable_values','files','single_file','lines','multi_select_api','product_variant_groups','variant_options']) ? 'full' : '' }}">
    @if($type === 'checkbox')
        <label class="check"><input type="checkbox" name="{{ $field['name'] }}" value="1"> {{ $field['label'] }}</label>
    @else
        <label for="{{ $field['name'] }}">{{ $field['label'] }}</label>
        @if(in_array($type, ['textarea','json','lines']))
            <textarea class="input" id="{{ $field['name'] }}" name="{{ $field['name'] }}" data-type="{{ $type }}" placeholder="{{ $field['placeholder'] ?? '' }}" {{ ($field['required'] ?? false) ? 'required' : '' }}></textarea>
        @elseif($type === 'select_api')
            <select class="input" id="{{ $field['name'] }}" name="{{ $field['name'] }}" data-type="select_api" data-source="{{ $field['source'] }}" data-value="{{ $field['value'] }}" data-text="{{ $field['text'] }}" {{ ($field['required'] ?? false) ? 'required' : '' }}>
                <option value="">-- Chọn {{ mb_strtolower($field['label']) }} --</option>
            </select>
        @elseif($type === 'multi_select_api')
            <div class="category-picker" data-type="multi_select_api" data-name="{{ $field['name'] }}" data-source="{{ $field['source'] }}" data-value="{{ $field['value'] }}" data-text="{{ $field['text'] }}">
                <select class="input" data-category-choice data-placeholder="{{ $field['placeholder'] ?? '-- Chọn danh mục để thêm --' }}"><option value="">{{ $field['placeholder'] ?? '-- Chọn danh mục để thêm --' }}</option></select>
                <div class="category-selected-list" data-category-selected></div>
                <div data-category-inputs></div>
            </div>
            <small>Chọn lần lượt từng danh mục để thêm vào sản phẩm.</small>
        @elseif($type === 'product_group_select')
            <select class="input" id="{{ $field['name'] }}" name="{{ $field['name'] }}" data-type="product_group_select" {{ ($field['required'] ?? false) ? 'required' : '' }}>
                <option value="">-- Chọn sản phẩm trước --</option>
            </select>
        @elseif($type === 'product_variant_groups')
            <div class="relation-picker" data-type="product_variant_groups" data-name="{{ $field['name'] }}" data-source="{{ $field['source'] }}"><div class="relation-loading">Đang tải nhóm biến thể...</div></div>
        @elseif($type === 'variant_options')
            <div class="relation-picker" data-type="variant_options" data-name="{{ $field['name'] }}" data-source="{{ $field['source'] }}"><div class="relation-loading">Đang tải giá trị biến thể...</div></div>
        @elseif($type === 'key_value')
            <div class="key-value-editor" data-type="key_value" data-name="{{ $field['name'] }}" data-key-placeholder="{{ $field['key_placeholder'] ?? 'Tên mục' }}" data-value-placeholder="{{ $field['value_placeholder'] ?? 'Nhập giá trị' }}">
                <div class="key-value-toolbar"><small>Thêm từng nền tảng và đường dẫn tương ứng.</small><button type="button" class="btn compact" data-add-key-value>＋ Thêm mạng xã hội</button></div>
                <div class="key-value-rows" data-key-value-rows></div>
                <div class="key-value-empty" data-key-value-empty>Chưa có mạng xã hội nào.</div>
            </div>
        @elseif($type === 'repeatable_values')
            <div class="repeatable-editor" data-type="repeatable_values" data-name="{{ $field['name'] }}" data-placeholder="{{ $field['placeholder'] ?? 'Nhập giá trị' }}">
                <div class="key-value-toolbar"><small>Thêm từng địa chỉ vào danh sách.</small><button type="button" class="btn compact" data-add-repeatable>＋ {{ $field['add_label'] ?? 'Thêm mục' }}</button></div>
                <div class="repeatable-rows" data-repeatable-rows></div>
                <div class="key-value-empty" data-repeatable-empty>{{ $field['empty_label'] ?? 'Chưa có dữ liệu.' }}</div>
            </div>
        @elseif(in_array($type, ['files', 'single_file']))
            <x-cms.multiple-file-upload
                :name="$field['name']"
                :accept="$field['accept'] ?? 'image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar'"
                :required="$field['required'] ?? false"
                :multiple="$type === 'files'"
            />
        @else
            <input class="input" id="{{ $field['name'] }}" type="{{ $type }}" name="{{ $field['name'] }}" placeholder="{{ $field['placeholder'] ?? '' }}" {{ ($field['required'] ?? false) ? 'required' : '' }}>
        @endif
    @endif
</div>
@endforeach
