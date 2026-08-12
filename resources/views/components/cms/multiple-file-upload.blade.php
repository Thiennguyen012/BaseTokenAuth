@props([
    'name',
    'accept' => 'image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar',
    'required' => false,
    'multiple' => true,
])

<div class="multi-upload" data-multi-upload data-field-name="{{ $name }}" @if(!$multiple) data-single-upload="1" @endif>
    <input class="multi-upload-input" id="{{ $name }}" type="file" name="{{ $name }}[]"
           @if($multiple) multiple @endif accept="{{ $accept }}" @required($required) data-upload-input>
    <label class="multi-upload-dropzone" for="{{ $name }}" data-upload-dropzone>
        <span class="upload-main-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>
        </span>
        <span><strong>{{ $multiple ? 'Chọn hoặc kéo thả nhiều file' : 'Chọn hoặc kéo thả file' }}</strong><small>Hình ảnh sẽ hiển thị preview.</small></span>
        <span class="btn compact">Chọn file</span>
    </label>
    <div class="upload-file-grid" data-upload-preview></div>
</div>
