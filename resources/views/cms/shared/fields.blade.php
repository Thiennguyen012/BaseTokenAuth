@foreach($config['fields'] as $field)
@php($type = $field['type'])
@if(($hidePageRelation ?? false) && $field['name'] === 'page_content_id')
    <input type="hidden" name="page_content_id" value="{{ $presetPageContentId ?? '' }}">
    @continue
@endif
@if(($hideSectionRelation ?? false) && $field['name'] === 'page_section_id')
    <input type="hidden" name="page_section_id" value="{{ $presetPageSectionId ?? '' }}">
    @continue
@endif

@if($field['break_before'] ?? false)
    <div class="w-100" aria-hidden="true"></div>
@endif
<div class="form-group {{ $field['column_class'] ?? (in_array($type, ['textarea','richtext','json','key_value','repeatable_values','files','single_file','lines','searchable_select_api','product_variant_groups','variant_options']) ? 'col-12' : 'col-md-6') }} mb-3" data-field-name="{{ $field['name'] }}">
    @if($type === 'checkbox')
        <div class="custom-control custom-checkbox pt-4">
            <input type="checkbox" class="custom-control-input" id="{{ $field['name'] }}" name="{{ $field['name'] }}" value="1" @checked($field['default'] ?? false)>
            <label class="custom-control-label font-weight-bold text-dark" for="{{ $field['name'] }}">{{ $field['label'] }}</label>
        </div>
    @else
        <label class="title-color font-weight-bold mb-2" for="{{ $field['name'] }}">{{ $field['label'] }}</label>
        
        @if(in_array($type, ['textarea','richtext','json','lines']))
            <textarea class="form-control" id="{{ $field['name'] }}" name="{{ $field['name'] }}" data-type="{{ $type }}" rows="5" placeholder="{{ $field['placeholder'] ?? '' }}" {{ ($field['required'] ?? false) ? 'required' : '' }}></textarea>
        @elseif($type === 'select_api')
            <select class="custom-select form-control" id="{{ $field['name'] }}" name="{{ $field['name'] }}" data-type="select_api" data-source="{{ $field['source'] }}" data-value="{{ $field['value'] }}" data-text="{{ $field['text'] }}" @if($field['lock_on_edit'] ?? false) data-lock-on-edit="1" @endif {{ ($field['required'] ?? false) ? 'required' : '' }}>
                <option value="">-- Chọn {{ mb_strtolower($field['label']) }} --</option>
            </select>
        @elseif($type === 'searchable_select_api')
            <div class="searchable-select" data-type="searchable_select_api" data-source="{{ $field['source'] }}" data-value="{{ $field['value'] }}" data-text="{{ $field['text'] }}" data-label="{{ $field['label'] }}" data-search-variant="{{ $field['search_variant'] ?? 'product' }}" data-selected-text="{{ $field['selected_text'] ?? '' }}">
                <input type="hidden" id="{{ $field['name'] }}" name="{{ $field['name'] }}">
                <div class="searchable-select-control">
                    <span class="searchable-select-icon">⌕</span>
                    <input class="form-control searchable-select-input" type="search" autocomplete="off" placeholder="{{ $field['placeholder'] ?? 'Nhập để tìm kiếm' }}" data-searchable-input @if($field['lock_on_edit'] ?? false) data-lock-on-edit="1" @endif {{ ($field['required'] ?? false) ? 'required' : '' }}>
                </div>
                <div class="searchable-select-results" data-searchable-results hidden></div>
            </div>
        @elseif($type === 'multi_select_api')
            <div class="category-picker p-3 border rounded bg-light" data-type="multi_select_api" data-name="{{ $field['name'] }}" data-source="{{ $field['source'] }}" data-value="{{ $field['value'] }}" data-text="{{ $field['text'] }}">
                <div class="category-search-control">
                    <i class="tio-search category-search-icon" aria-hidden="true"></i>
                    <input class="form-control category-search-input" type="search" autocomplete="off" placeholder="{{ $field['placeholder'] ?? 'Nhập để tìm kiếm' }}" data-category-search>
                </div>
                <div class="category-search-results" data-category-results hidden></div>
                <div class="category-selected-list mt-2" data-category-selected></div>
                <div data-category-inputs></div>
            </div>
        @elseif($type === 'product_group_select')
            <select class="custom-select form-control" id="{{ $field['name'] }}" name="{{ $field['name'] }}" data-type="product_group_select" {{ ($field['required'] ?? false) ? 'required' : '' }}>
                <option value="">-- Chọn sản phẩm trước --</option>
            </select>
        @elseif($type === 'product_variant_groups')
            <div class="relation-picker border rounded p-3" data-type="product_variant_groups" data-name="{{ $field['name'] }}" data-source="{{ $field['source'] }}">
                <div class="relation-loading text-muted py-2">Đang tải nhóm biến thể...</div>
            </div>
        @elseif($type === 'variant_options')
            <div class="relation-picker border rounded p-3" data-type="variant_options" data-name="{{ $field['name'] }}" data-source="{{ $field['source'] ?? '' }}">
                <div class="relation-loading text-muted py-2">Chọn sản phẩm để tải các giá trị biến thể.</div>
            </div>
        @elseif($type === 'key_value')
            <div class="key-value-editor p-3 border rounded bg-light" data-type="key_value" data-name="{{ $field['name'] }}" data-key-placeholder="{{ $field['key_placeholder'] ?? 'Tên mục' }}" data-value-placeholder="{{ $field['value_placeholder'] ?? 'Nhập giá trị' }}">
                <div class="key-value-toolbar d-flex justify-content-between align-items-center mb-3">
                    <small class="text-muted">Thêm từng nền tảng và đường dẫn tương ứng.</small>
                    <button type="button" class="btn btn-soft-primary btn-sm px-3 shadow-sm font-weight-bold" data-add-key-value>
                        <i class="ri-add-line mr-1"></i> Thêm mạng xã hội
                    </button>
                </div>
                <div class="key-value-rows d-flex flex-column gap-2" data-key-value-rows></div>
                <div class="key-value-empty text-muted text-center py-3 border rounded bg-white" data-key-value-empty>Chưa có mạng xã hội nào.</div>
            </div>
        @elseif($type === 'repeatable_values')
            <div class="repeatable-editor p-3 border rounded bg-light" data-type="repeatable_values" data-name="{{ $field['name'] }}" data-placeholder="{{ $field['placeholder'] ?? 'Nhập giá trị' }}">
                <div class="key-value-toolbar d-flex justify-content-between align-items-center mb-3">
                    <small class="text-muted">Thêm từng địa chỉ vào danh sách.</small>
                    <button type="button" class="btn btn-soft-primary btn-sm px-3 shadow-sm font-weight-bold" data-add-repeatable>
                        <i class="ri-add-line mr-1"></i> {{ $field['add_label'] ?? 'Thêm mục' }}
                    </button>
                </div>
                <div class="repeatable-rows d-flex flex-column gap-2" data-repeatable-rows></div>
                <div class="key-value-empty text-muted text-center py-3 border rounded bg-white" data-repeatable-empty>{{ $field['empty_label'] ?? 'Chưa có dữ liệu.' }}</div>
            </div>
        @elseif(in_array($type, ['files', 'single_file']))
            <x-cms.multiple-file-upload
                :name="$field['name']"
                :accept="$field['accept'] ?? 'image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar'"
                :required="$field['required'] ?? false"
                :multiple="$type === 'files'"
            />
        @else
            <input class="form-control" id="{{ $field['name'] }}" type="{{ $type }}" name="{{ $field['name'] }}" placeholder="{{ $field['placeholder'] ?? '' }}" @if(isset($field['min'])) min="{{ $field['min'] }}" @endif @if(isset($field['max'])) max="{{ $field['max'] }}" @endif @if(isset($field['step'])) step="{{ $field['step'] }}" @endif {{ ($field['required'] ?? false) ? 'required' : '' }}>
        @endif
        
        @if(!empty($field['help']))
            <small class="form-text text-muted mt-1">{{ $field['help'] }}</small>
        @endif
    @endif
</div>
@endforeach
