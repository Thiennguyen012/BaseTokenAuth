<style>
    .imagePreviewMulti {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 20px;
    }

    .image-container {
        position: relative;
        width: 150px;
        height: 150px;
    }

    .image-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
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
    }
</style>

<div class="card">
    <div class="d-flex flex-column justify-content-around">
        <div class="imagePreviewMulti">
            @if(isset($data_images) && $data_images)
                @foreach($data_images as $key => $value)
                    <div class="image-container">
                        <img src="{{ \App\CPU\Helpers::image_path($value) }}">
                        <button type="button" data-url="{{ $url_remove_image }}" data-key="{{ $key }}" class="remove-button remove_image"><i class="tio-delete"></i></button>
                    </div>
                @endforeach
            @endif

        </div>
        <div id="{{ $id_preview }}" class="imagePreviewMulti">

        </div>
        <div class="mt-4 position-relative">
            <input type="file" name="{{ $name }}" id="imageUpload"
                   class="cursor-pointer custom-file-input @error($name) is-invalid @enderror"
                   accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" multiple>
            <label class="custom-file-label cursor-pointer" for="imageUpload">{{\App\CPU\translate('choose')}} {{\App\CPU\translate('file')}}</label>
            @error($name)
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>


@push('script')
    <script>
        var storedFiles = [];

        $('#imageUpload').on('change', function(event) {
            var files = event.target.files;
            $('#{{$id_preview}}').empty();

            for (var i = 0; i < files.length; i++) {
                storedFiles.push(files[i]);
            }
            displayImages();
        });

        function displayImages() {
            $('#{{$id_preview}}').empty();
            for (var i = 0; i < storedFiles.length; i++) {
                (function(file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var imgContainer = $('<div class="image-container"></div>');
                        var img = $('<img>').attr('src', e.target.result);
                        var removeButton = $('<button class="remove-button">X</button>');

                        removeButton.on('click', function() {
                            var index = storedFiles.indexOf(file);
                            if (index > -1) {
                                storedFiles.splice(index, 1);
                            }
                            displayImages();
                            updateFileInput()
                        });

                        imgContainer.append(img).append(removeButton);
                        $('#{{$id_preview}}').append(imgContainer);
                    };
                    reader.readAsDataURL(file);
                })(storedFiles[i]);
            }
            updateFileInput()
        }

        function updateFileInput() {
            var dataTransfer = new DataTransfer();
            for (var i = 0; i < storedFiles.length; i++) {
                dataTransfer.items.add(storedFiles[i]);
            }
            var fileInput = $('#imageUpload')[0];
            fileInput.files = dataTransfer.files;
        }

        $(document).on('click', '.remove_image', function () {
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
    </script>
@endpush
