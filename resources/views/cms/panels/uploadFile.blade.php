<style>
    .filePreviewMulti {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 20px;
    }

    .file-container {
        position: relative;
        width: 150px;
        height: 150px;
        border: 2px dashed #ddd;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background-color: #f9f9f9;
    }

    .file-container.image-file {
        border: none;
        background-color: transparent;
    }

    .file-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .file-icon {
        font-size: 40px;
        color: #666;
        margin-bottom: 5px;
    }

    .file-name {
        font-size: 12px;
        text-align: center;
        word-break: break-all;
        padding: 5px;
    }

    .remove-button {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(255, 0, 0, 0.8);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        width: 25px;
        height: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .file-size {
        font-size: 10px;
        color: #888;
        text-align: center;
    }

    .file-upload-info {
        margin-top: 10px;
        padding: 10px;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
    }

    .file-upload-info small {
        display: block;
        color: #6c757d;
        margin: 2px 0;
    }
</style>

<div class="card">
    <div class="d-flex flex-column justify-content-around">
        <div class="filePreviewMulti">
            @if(isset($data_files) && $data_files)
                @foreach($data_files as $key => $value)
                    @php
                        $fileExtension = pathinfo($value, PATHINFO_EXTENSION);
                        $isImage = in_array(strtolower($fileExtension), ['png', 'jpg', 'jpeg']);
                    @endphp
                    <div class="file-container {{ $isImage ? 'image-file' : '' }}">
                        @if($isImage)
                            <img src="{{ \App\CPU\Helpers::image_path($value) }}" alt="File">
                        @else
                            <div class="file-icon">
                                @if(strtolower($fileExtension) == 'pdf')
                                    <i class="fas fa-file-pdf" style="color: #dc3545;"></i>
                                @elseif(in_array(strtolower($fileExtension), ['doc', 'docx']))
                                    <i class="fas fa-file-word" style="color: #2b579a;"></i>
                                @else
                                    <i class="fas fa-file" style="color: #6c757d;"></i>
                                @endif
                            </div>
                            <div class="file-name">{{ basename($value) }}</div>
                        @endif
                        <button type="button" data-url="{{ $url_remove_file }}" data-key="{{ $key }}" class="remove-button remove_file"><i class="tio-delete"></i></button>
                    </div>
                @endforeach
            @endif
        </div>
        
        <div id="{{ $id_preview }}" class="filePreviewMulti">
        </div>
        
        <div class="mt-4 position-relative">
            <input type="file" name="{{ $name }}" id="{{ isset($id_input) ? $id_input : 'fileUpload' }}"
                   class="cursor-pointer custom-file-input @error($name) is-invalid @enderror"
                   accept=".png,.jpg,.jpeg,.pdf,.doc,.docx">
            <label class="custom-file-label cursor-pointer" for="{{ isset($id_input) ? $id_input : 'fileUpload' }}">{{\App\CPU\translate('choose')}} {{\App\CPU\translate('file')}}</label>
            @error($name)
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

@push('script')
    <script>
        var currentFile = null;
        const maxFileSize = 10 * 1024 * 1024; // 10MB in bytes
        const allowedTypes = ['image/png', 'image/jpg', 'image/jpeg', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        const allowedExtensions = ['png', 'jpg', 'jpeg', 'pdf', 'doc', 'docx'];

        $('#{{ isset($id_input) ? $id_input : 'fileUpload' }}').on('change', function(event) {
            var file = event.target.files[0];
            
            if (!file) {
                currentFile = null;
                displayFile();
                return;
            }
            
            var fileExtension = file.name.split('.').pop().toLowerCase();
            
            // Kiểm tra định dạng file
            if (!allowedExtensions.includes(fileExtension)) {
                toastr.error('File ' + file.name + ' không đúng định dạng cho phép (.png, .jpg, .jpeg, .pdf, .doc, .docx)');
                $(this).val('');
                currentFile = null;
                displayFile();
                return;
            }
            
            // Kiểm tra dung lượng file
            if (file.size > maxFileSize) {
                toastr.error('File ' + file.name + ' vượt quá dung lượng cho phép (10MB)');
                $(this).val('');
                currentFile = null;
                displayFile();
                return;
            }
            
            currentFile = file;
            displayFile();
        });

        function displayFile() {
            $('#{{$id_preview}}').empty();
            
            if (!currentFile) {
                return;
            }
            
            var fileExtension = currentFile.name.split('.').pop().toLowerCase();
            var isImage = ['png', 'jpg', 'jpeg'].includes(fileExtension);
            
            var fileContainer = $('<div class="file-container"></div>');
            if (isImage) {
                fileContainer.addClass('image-file');
            }
            
            var removeButton = $('<button type="button" class="remove-button">×</button>');
            
            if (isImage) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = $('<img>').attr('src', e.target.result);
                    fileContainer.append(img);
                };
                reader.readAsDataURL(currentFile);
            } else {
                var fileIcon = $('<div class="file-icon"></div>');
                if (fileExtension === 'pdf') {
                    fileIcon.html('<i class="fas fa-file-pdf" style="color: #dc3545;"></i>');
                } else if (fileExtension === 'doc' || fileExtension === 'docx') {
                    fileIcon.html('<i class="fas fa-file-word" style="color: #2b579a;"></i>');
                } else {
                    fileIcon.html('<i class="fas fa-file" style="color: #6c757d;"></i>');
                }
                
                var fileName = $('<div class="file-name"></div>').text(currentFile.name);
                var fileSize = $('<div class="file-size"></div>').text(formatFileSize(currentFile.size));
                
                fileContainer.append(fileIcon).append(fileName).append(fileSize);
            }
            
            removeButton.on('click', function() {
                currentFile = null;
                $('#{{ isset($id_input) ? $id_input : 'fileUpload' }}').val('');
                displayFile();
            });
            
            fileContainer.append(removeButton);
            $('#{{$id_preview}}').append(fileContainer);
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        $(document).on('click', '.remove_file', function () {
            var url = $(this).data("url");
            var key = $(this).data("key");
            Swal.fire({
                title: '{{\App\CPU\translate('are_you_sure_delete')}}?',
                text: "{{\App\CPU\translate('you_will_not_be_able_to_revert_this')}}!",
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{\App\CPU\translate('yes')}}',
                cancelButtonText: '{{\App\CPU\translate('cancel')}}',
                type: 'warning',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                        }
                    });
                    $.ajax({
                        url: url,
                        method: 'GET',
                        data: {key: key},
                        success: function (response) {
                            if(response.result){
                                location.reload();
                            }else{
                                toastr.options = {
                                    "positionClass": "toast-top-right"
                                }
                                toastr.error(response.message);
                            }
                        }
                    });
                }
            })
        });
        
        // Thêm toastr options nếu chưa có
        if (typeof toastr !== 'undefined') {
            toastr.options = {
                "positionClass": "toast-top-right",
                "timeOut": "5000"
            }
        }
    </script>
@endpush