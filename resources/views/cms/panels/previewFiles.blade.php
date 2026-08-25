{{-- filepath: d:\Laravel\QLCV\cms-pwf\resources\views\admin\panels\previewFiles.blade.php --}}
<style>
    .filePreviewReadOnly {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 0;
        padding: 15px;
    }

    .file-container-readonly {
        position: relative;
        width: 150px;
        height: 150px;
        border: 2px dashed #ddd;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background-color: #f9f9f9;
        border-radius: 8px;
    }

    .file-container-readonly.image-file {
        border: none;
        background-color: transparent;
    }

    .file-container-readonly img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
    }

    .file-icon-readonly {
        font-size: 40px;
        color: #666;
        margin-bottom: 5px;
    }

    .file-name-readonly {
        font-size: 12px;
        text-align: center;
        word-break: break-all;
        padding: 5px;
        color: #333;
    }

    .file-size-readonly {
        font-size: 10px;
        color: #888;
        text-align: center;
    }

    .download-button-readonly {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(40, 167, 69, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        width: 25px;
        height: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .download-button-readonly:hover {
        background: rgba(40, 167, 69, 1);
        transform: scale(1.1);
    }

    .view-button-readonly {
        position: absolute;
        top: 5px;
        left: 5px;
        background: rgba(0, 123, 255, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        width: 25px;
        height: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .view-button-readonly:hover {
        background: rgba(0, 123, 255, 1);
        transform: scale(1.1);
    }

    .no-files-message {
        padding: 30px 20px;
        text-align: center;
        color: #6c757d;
        font-style: italic;
        width: 100%;
    }

    .no-files-message i {
        display: block;
        margin-bottom: 10px;
        font-size: 48px;
        color: #dee2e6;
    }
</style>

<div class="card">
    <div class="d-flex flex-column justify-content-around">
        @if(isset($files) && is_array($files) && count($files) > 0)
            <div class="filePreviewReadOnly" id="{{ $preview_id ?? 'filesPreview' }}">
                @foreach($files as $index => $file)
                    @php
                        $filePath = is_array($file) ? ($file['file_path'] ?? $file) : $file;
                        $fileName = is_array($file) ? ($file['original_name'] ?? basename($filePath)) : basename($filePath);
                        $fileUrl = asset('storage/' . $filePath);
                        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
                        
                        // Calculate file size
                        $fullPath = storage_path('app/public/' . $filePath);
                        $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;
                        
                        // Format file size
                        if ($fileSize >= 1048576) {
                            $fileSizeFormatted = number_format($fileSize / 1048576, 2) . ' MB';
                        } elseif ($fileSize >= 1024) {
                            $fileSizeFormatted = number_format($fileSize / 1024, 2) . ' KB';
                        } else {
                            $fileSizeFormatted = $fileSize . ' Bytes';
                        }
                    @endphp
                    
                    <div class="file-container-readonly {{ $isImage ? 'image-file' : '' }}" data-file-path="{{ $filePath }}">
                        @if($isImage)
                            <img src="{{ $fileUrl }}" alt="{{ $fileName }}">
                        @else
                            <div class="file-icon-readonly">
                                @if($extension == 'pdf')
                                    <i class="fas fa-file-pdf" style="color: #dc3545;"></i>
                                @elseif(in_array($extension, ['doc', 'docx']))
                                    <i class="fas fa-file-word" style="color: #2b579a;"></i>
                                @elseif(in_array($extension, ['xls', 'xlsx']))
                                    <i class="fas fa-file-excel" style="color: #217346;"></i>
                                @elseif(in_array($extension, ['ppt', 'pptx']))
                                    <i class="fas fa-file-powerpoint" style="color: #d24726;"></i>
                                @elseif(in_array($extension, ['zip', 'rar', '7z']))
                                    <i class="fas fa-file-archive" style="color: #6c757d;"></i>
                                @else
                                    <i class="fas fa-file" style="color: #6c757d;"></i>
                                @endif
                            </div>
                            <div class="file-name-readonly">{{ $fileName }}</div>
                            <div class="file-size-readonly">{{ $fileSizeFormatted }}</div>
                        @endif
                        
                        <button type="button" class="view-button-readonly" onclick="window.open('{{ $fileUrl }}', '_blank')" title="{{\App\CPU\translate('view_file') ?? 'View'}}">
                            <i class="tio-visible"></i>
                        </button>
                        
                        <a href="{{ $fileUrl }}" download="{{ $fileName }}" class="download-button-readonly" title="{{\App\CPU\translate('download_file') ?? 'Download'}}">
                            <i class="tio-download"></i>
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="filePreviewReadOnly">
                <div class="no-files-message">
                    <i class="fas fa-folder-open"></i>
                    <p>{{\App\CPU\translate('no_files_attached') ?? 'No files attached'}}</p>
                </div>
            </div>
        @endif
    </div>
</div>

@push('script')
<script>
    console.log('Preview Files component loaded');
</script>
@endpush
