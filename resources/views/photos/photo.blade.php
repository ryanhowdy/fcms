@extends('layouts.photo')
@section('body-id', 'photo-carousel')

@section('photo')
<div id="photo-controls" class="carousel slide bg-black text-white" data-bs-ride="carousel" data-bs-pause="hover">

    <div class="carousel-inner">

    @foreach ($album[0]->photos as $k => $photo)

        <div class="carousel-item @if ($activePhoto == $photo->id) active @endif" data-id="{{ $photo->id }}">
            <div class="row">

                {{-- Photo --}}
                <div class="col-10 img-col">
                    <img class="d-block h-100" src="{{ route('photo', ['id' => $album[0]->created_user_id, 'file' => $photo->filename]) }}">
                </div>

                {{-- Sidebar --}}
                <div class="col-2 details-col bg-dark pt-5">
                    <a href="{{ route('photos.albums.show', $album[0]->id) }}" class="btn-close bg-white position-absolute top-0 end-0 me-3 mt-3" aria-label="{{ _gettext('Close') }}"></a>
                    <div class="d-flex flex-row user-info border-bottom pb-3 mb-3">
                        <div>
                            <img class="avatar rounded-5 mx-3" src="{{ getUserAvatar($album[0]->toArray()) }}" title="{{ _gettext('avatar') }}">
                        </div>
                        <div>
                            <p class="mb-1">{{ getUserDisplayName($album[0]->toArray()) }}</p>
                            <span class="text-muted">{{ $photo->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <p class="fs-6">{{ $photo->caption }}</p>
                    <p class="text-muted">{{ sprintf(_gettext('Photo %d of %d'), $k+1, count($album[0]->photos)) }}</p>
                    <div class="photo-nav border-bottom">
                        <ul class="nav" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a href="#" class="nav-link active" id="comments-tab-{{ $photo->id }}" data-bs-toggle="tab" data-bs-target="#comments-pane-{{ $photo->id }}">{{ _gettext('Comments') }}</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#" class="nav-link" id="details-tab-{{ $photo->id }}" data-bs-toggle="tab" data-bs-target="#details-pane-{{ $photo->id }}">{{ _gettext('Details') }}</a>
                            </li>
                        </ul>
                    </div>

                    {{-- Comments/Photo Exif Details--}}
                    <div class="tab-content">
                        <div class="tab-pane show active" id="comments-pane-{{ $photo->id }}" role="tabpanel" tabindex="0">

                        </div><!-- /#comments-pane -->
                        <div class="tab-pane py-3" id="details-pane-{{ $photo->id }}" role="tabpanel" tabindex="0">
                        @isset($exif[ $photo->id ])
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi-calendar-date fs-3 me-3"></i>
                                <div class="">
                                    {{ \Carbon\Carbon::parse($exif[ $photo->id ]['DateTime'])->format('M j, Y') }}
                                    <div class="text-muted">{{ \Carbon\Carbon::parse($exif[ $photo->id ]['DateTime'])->format('l g:i a') }}</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi-camera fs-3 me-3"></i>
                                <div class="">
                                    {{ $exif[ $photo->id ]['Make'] }} {{ $exif[ $photo->id ]['Model'] }}
                                </div>
                            </div>
                        @endisset
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi-eye fs-3 me-3"></i>
                                <div class="">{{ $photo->views }}</div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi-star fs-3 me-3"></i>
                                <div class="">{{ $photo->rating }}</div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <b>{{ _gettext('People') }}</b>
                            </div>
                        </div><!-- /#details-pane -->
                    </div>
                </div><!-- /.details-col -->

            </div>
        </div>
    @endforeach

    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#photo-controls" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#photo-controls" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>

</div>
<script>
$('#photo-controls').on('slide.bs.carousel', function(e) {
    let photoId = $('.carousel-item').eq(e.to).data('id');

    window.history.replaceState(null, '', '/photos/albums/{{ $album[0]->id }}/photos/' + photoId);
});
</script>
@endsection 
