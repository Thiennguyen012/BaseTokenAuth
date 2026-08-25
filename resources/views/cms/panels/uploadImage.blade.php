<style>
    #imagePreview {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 20px;
    }

    .image-container {
        position: relative;
    }

    .image-container img {
        max-width: 200px;
        max-height: 200px;
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
<div class="d-flex flex-column justify-content-around">
    <div class="m-auto">
        <img height="60" id="{{ $id_preview }}" onerror="this.src='{{asset('/assets/back-end/img/image-place-holder.png')}}'"
             src="{{ isset($old_path) && $old_path ? \App\CPU\Helpers::image_path($old_path) : '' }}" alt="thumbnail">
    </div>
    <div class="mt-4 position-relative">
        <input type="file" name="{{ $name }}" id="{{ $id_input }}"
               class="cursor-pointer custom-file-input @error($name) is-invalid @enderror"
               accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
        <label class="custom-file-label" for="customFileUploadWL">{{\App\CPU\translate('choose')}} {{\App\CPU\translate('file')}}</label>
        @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

@push('script')
    <script>
        $("#{{$id_input}}").change(function () {
            read_image(this, '{{ $id_preview }}');
        });

        function read_image(input, id) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $('#' + id).attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
@endpush
