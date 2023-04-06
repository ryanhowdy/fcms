@extends('layouts.main')
@section('body-id', 'photos')
@section('main-bg', 'bg-light')

@section('content')
<div class="p-5">

    <form action="{{ route('discussions.create') }}" method="post">
        @csrf

        <div class="border rounded bg-white">
            <div class="tab-content">

                <div class="tab-pane fade show active" id="album-pane" role="tabpanel" tabindex="0">
                    <div class="d-flex flex-nowrap">
                        <div class="uploader-sidebar col-auto border-end">
                            <ul class="p-5 list-unstyled">
                                <li class="d-inline-block rounded-5 border border-2 text-muted p-2 mx-2 text-center active">1</li>
                                <li class="d-inline-block rounded-5 border border-2 text-muted p-2 mx-2 text-center">2</li>
                                <li class="d-inline-block rounded-5 border border-2 text-muted p-2 mx-2 text-center">3</li>
                            </ul>
                            <div class="instructions text-center text-muted">
                                <img style="width: 90px;" src="{{ asset('img/albums.jpg') }}"/>
                                <p class="p-5 pt-1">
                                    <b>{{ gettext('Album') }}</b><br/>
                                    {{ gettext('Upload your photos to a new or existing album') }}
                                </p>
                            </div>
                        </div><!-- /.uploader-sidebar -->
                        <div class="uploader-main col p-5">
                            <h5>{{ gettext('Album') }}</h5>
                            <div class="mb-3 required">
                                <label for="title" class="form-label">{{ gettext('Name') }}</label>
                                <input type="text" class="form-control" id="album-name" name="album-name" value="{{ old('album-name') }}">
                            </div>
                            <div class="mb-3">
                                <label for="title" class="form-label">{{ gettext('Description') }}</label>
                                <textarea class="form-control" id="album-description" name="album-description" value="{{ old('album-description') }}"></textarea>
                            </div>
                        @if ($albums->isNotEmpty())
                            <p class="text-center">-- or --</p>
                            <div class="mb-3 required">
                                <label for="title" class="form-label">{{ gettext('Existing Album') }}</label>
                                <select class="form-select" id="album-id" name="album-id">
                                    <option></option>
                                @foreach ($albums as $album)
                                    <option value="{{ $album->id }}">{{ $album->name }}</option>
                                @endforeach
                                </select>
                            </div>
                        @endif
                            <div class="uploader-footer d-flex justify-content-end mt-5">
                                <a href="#" class="next btn btn-primary px-4 text-white rounded-5">{{ gettext('Next') }}<i class="bi-chevron-compact-right ms-2"></i>
                                </a>
                            </div><!-- /.uploader-footer -->
                        </div><!-- /.uploader-main -->
                    </div>
                </div><!-- /#album-pane -->

                <div class="tab-pane fade" id="photos-pane" role="tabpanel" tabindex="0">
                    <div class="d-flex flex-nowrap">
                        <div class="uploader-sidebar col-auto border-end">
                            <ul class="p-5 list-unstyled">
                                <li class="d-inline-block rounded-5 border border-2 text-muted p-2 mx-2 text-center">1</li>
                                <li class="d-inline-block rounded-5 border border-2 text-muted p-2 mx-2 text-center active">2</li>
                                <li class="d-inline-block rounded-5 border border-2 text-muted p-2 mx-2 text-center">3</li>
                            </ul>
                            <div class="instructions text-center text-muted">
                                <img style="width: 90px;" src="{{ asset('img/photos.jpg') }}"/>
                                <p class="p-5 pt-1">
                                    <b>{{ gettext('Upload Photos') }}</b><br/>
                                    {{ gettext('Choose 1 or more photos to upload') }}
                                </p>
                            </div>
                        </div><!-- /.uploader-sidebar -->
                        <div class="uploader-main col p-5">
                            <h5>{{ gettext('Photos') }}</h5>
                            <div class="photo-area alert alert-info text-center">
                                <p class="mb-1">
                                    <a class="fs-5 text-decoration-none d-block" href="#">
                                        <i class="bi-cloud-arrow-up fs-1 d-block"></i>
                                        {{ gettext('Select photos to upload') }}
                                    </a>
                                </p>
                                <p class="fs-6"><small>{{ gettext('or drag and drop it here') }}</small></a>
                                <input type="file" class="d-none" id="photo-picker" multiple accept="image/*">
                            </div>
                            <ul id="photo-list" class="list-unstyled">
                                <li class="template p-2 my-2 border">
                                    <button type="button" class="btn-close float-end" aria-label="{{ gettext('Close') }}"></button>
                                    <div class="d-flex align-items-center">
                                        <div class="w-25">
                                        </div>
                                        <div class="ms-3 flex-grow-1">
                                            <p class="mb-1 name"><b></b></p>
                                            <div class="progress" style="height:5px">
                                                <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                            <div class="uploader-footer d-flex justify-content-between mt-5">
                                <a href="#" class="prev btn btn-outline-secondary px-4 rounded-5"><i class="bi-chevron-compact-left me-2"></i>{{ gettext('Previous') }}</a>
                                <a href="#" class="next btn btn-primary px-4 text-white rounded-5">{{ gettext('Next') }}<i class="bi-chevron-compact-right ms-2"></i></a>
                            </div><!-- /.uploader-footer -->
                        </div><!-- /.uploader-main -->
                    </div>
                </div><!-- /#photos-pane -->

                <div class="tab-pane fade" id="comments-pane" role="tabpanel" tabindex="0">
                    <div class="d-flex flex-nowrap">
                        <div class="uploader-sidebar col-auto border-end">
                            <ul class="p-5 list-unstyled">
                                <li class="d-inline-block rounded-5 border border-2 text-muted p-2 mx-2 text-center">1</li>
                                <li class="d-inline-block rounded-5 border border-2 text-muted p-2 mx-2 text-center">2</li>
                                <li class="d-inline-block rounded-5 border border-2 text-muted p-2 mx-2 text-center active">3</li>
                            </ul>
                            <div class="instructions text-center text-muted">
                                <img style="width: 90px;" src="{{ asset('img/photos.jpg') }}"/>
                                <p class="p-5 pt-1">
                                    <b>{{ gettext('Finishing Up') }}</b><br/>
                                    {{ gettext('Your photos have been uploaded successfully.  Feel free to tag people and add comments.') }}
                                </p>
                            </div>
                        </div><!-- /.uploader-sidebar -->
                        <div class="uploader-main col p-5">
                            <h5>{{ gettext('Success') }}</h5>
                            <div class="uploader-footer d-flex justify-content-between mt-5">
                                <a href="#" class="prev btn btn-outline-secondary px-4 rounded-5"><i class="bi-chevron-compact-left me-2"></i>{{ gettext('Previous') }}</a>
                                <a href="#" class="next btn btn-primary px-4 text-white rounded-5">{{ gettext('Next') }}<i class="bi-chevron-compact-right ms-2"></i></a>
                            </div><!-- /.uploader-footer -->
                        </div><!-- /.uploader-main -->
                    </div>
                </div><!-- /#photos-pane -->

            </div><!-- /.tab-content -->
        </div>

    </form>

<style>
.uploader-sidebar
{
    max-width: 290px;
}
.uploader-sidebar li
{
    width: 44px;
}
.uploader-sidebar li.active
{
    color: var(--bs-primary) !important;
    border-color: var(--bs-primary) !important;
}
.photo-area > div
{
    border: 3px dashed white;
}
#photo-list
{
    max-height: 400px;
    overflow: auto;
}
#photo-list > li.template
{
    display: none;
}
#photo-list > li.template p > b
{
    color: var(--bs-gray-500);
}
</style>
<script>
$(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        }
    });

    // From Step 1 -> Step 2
    $('#album-pane .uploader-footer > a.next').click(function(e) {
        e.preventDefault();
        $('.tab-pane').removeClass('show active');
        $('#photos-pane').addClass('show active');
    });

    // From Step 2 <- Step 1
    $('#photos-pane .uploader-footer > a.prev').click(function(e) {
        e.preventDefault();
        $('.tab-pane').removeClass('show active');
        $('#album-pane').addClass('show active');
    });

    // some globals for the uploader
    let formData;
    let lastAlbumId;

    // From Step 2 -> Step 3
    $('#photos-pane .uploader-footer > a.next').click(function(e) {
        e.preventDefault();
        uploadPhotos();
    });

    async function uploadPhotos()
    {
        let photoPicker = $('#photo-picker')[0];

        let totalfiles = photoPicker.files.length;
        for (var index = 0; index < totalfiles; index++) {
            formData = new FormData();

            if (index == 0)
            {
                formData.append("album-name", $('#album-name').val());
                formData.append("album-description", $('#album-description').val());

                if ($('#album-id').length)
                {
                    if ($('#album-id').val() !== '')
                    {
                        formData.append("album-id", $('#album-id').val());
                    }
                }
            }
            else
            {
                formData.append('album-id', lastAlbumId);
            }

            // Set the next photo for uploading
            formData.append("photo", photoPicker.files[index]);

            let result = await uploadPhoto(index, formData);
        }
    }

    async function uploadPhoto(index, formData)
    {
        return await
            $.ajax({
                url         : '{{ route('photos.create') }}',
                type        : 'post',
                data        : formData,
                dataType    : 'json',
                enctype     : 'multipart/form-data',
                processData : false,
                contentType : false
        }).then(
            // success
            function(data) {
                $('li.index-'+index+' .progress-bar').width('100%');
                lastAlbumId = data.album.id;
            // failure
            }, function(data) {
                $('li.index-'+index+' .progress-bar').addClass('bg-danger');
            }
        );
    }

    // Open the file picker
    $('.photo-area a').click(function(e) {
        e.preventDefault();
        $('#photo-picker').trigger('click');
    });

    $('#photo-picker').on('change', function() {
        let input   = this;
        let $output = $('#photo-list');

        if (input.files)
        {
            let totalFiles = input.files.length;

            for (i = 0; i < totalFiles; i++)
            {
                let filename = input.files[i].name;

                let $li = $('#photo-list > li.template').clone();

                $li.removeClass('template');
                $li.addClass('index-'+i);

                let reader = new FileReader();

                reader.onload = function(event)
                {
                    let img = document.createElement('img');
                    img.src = event.target.result;
                    img.classList.add('img-fluid');

                    $li.find('.name > b').text(filename);
                    $li.find('div.w-25').append(img);
                    $output.append($li);
                }

                reader.readAsDataURL(input.files[i]);
            }
        }
    });
});
</script>

</div>
@endsection
