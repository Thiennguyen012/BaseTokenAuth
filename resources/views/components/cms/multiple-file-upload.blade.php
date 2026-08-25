@props([
    'name',
    'accept' => 'image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar',
    'required' => false,
    'multiple' => true,
])

<div class="card p-3 border shadow-none mb-3 multi-upload bg-white" data-multi-upload data-field-name="{{ $name }}" @if(!$multiple) data-single-upload="1" @endif>
    <div class="upload-file-grid d-flex flex-wrap gap-3 mb-3" data-upload-preview></div>
    
    <div class="position-relative">
        <input class="multi-upload-input d-none" id="{{ $name }}" type="file" name="{{ $name }}[]"
               @if($multiple) multiple @endif accept="{{ $accept }}" @required($required) data-upload-input>
        <label class="d-flex align-items-center justify-content-between border rounded bg-white overflow-hidden m-0 cursor-pointer" for="{{ $name }}" data-upload-dropzone style="cursor: pointer; min-height: 42px;">
            <span class="px-3 text-muted small" data-upload-filename>Chọn File</span>
            <span class="btn btn-light border-left px-4 py-2 text-dark font-weight-medium bg-light" style="border-radius: 0; min-height: 42px; display: inline-flex; align-items: center;">Browse</span>
        </label>
    </div>
</div>

