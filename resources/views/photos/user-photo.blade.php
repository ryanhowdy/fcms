@extends('layouts.photo')
@section('body-id', 'photo-carousel')

@section('photo')
<div id="photo-controls" class="carousel slide bg-black text-white" data-bs-ride="carousel" data-bs-pause="hover">

    <div class="carousel-inner">

    @foreach ($userPhotos as $k => $userPhoto)
        @if(is_null($userPhoto->photo))
            @continue
        @endif

        <div class="carousel-item @if ($activePhoto == $userPhoto->photo_id) active @endif" data-id="{{ $userPhoto->photo_id }}">
            <div class="row">

                {{-- Photo --}}
                <div class="col-10 img-col">
                    <img class="d-block h-100" src="{{ route('photo', ['id' => $userPhoto->photo->created_user_id, 'file' => $userPhoto->photo->filename]) }}">
                </div>

                {{-- Sidebar --}}
                <div class="col-2 details-col bg-dark pt-5">
                    <a href="{{ route('photos.users.show', $userPhoto->user_id) }}" class="btn-close bg-white position-absolute top-0 end-0 me-3 mt-3" aria-label="{{ _gettext('Close') }}"></a>
                    <div class="d-flex flex-row user-info border-bottom pb-3 mb-3">
                        <div>
                            <img class="avatar rounded-5 mx-3" src="{{ getUserAvatar($userPhoto->toArray()) }}" title="{{ _gettext('avatar') }}">
                        </div>
                        <div>
                            <p class="mb-1">{{ getUserDisplayName($userPhoto->toArray()) }}</p>
                            <span class="text-muted">{{ $userPhoto->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <p class="fs-6">{{ $userPhoto->photo->caption }}</p>
                    <p class="text-muted">{{ sprintf(_gettext('Photo %d of %d'), $k+1, $userPhoto->count()) }}</p>
                    <div class="photo-nav border-bottom">
                        <ul class="nav" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a href="#" class="nav-link active" id="comments-tab-{{ $userPhoto->photo_id }}" data-bs-toggle="tab" data-bs-target="#comments-pane-{{ $userPhoto->photo_id }}">{{ _gettext('Comments') }}</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#" class="nav-link" id="details-tab-{{ $userPhoto->photo_id }}" data-bs-toggle="tab" data-bs-target="#details-pane-{{ $userPhoto->photo_id }}">{{ _gettext('Details') }}</a>
                            </li>
                        </ul>
                    </div>

                    {{-- Comments/Photo Exif Details--}}
                    <div class="tab-content">
                        <div class="tab-pane show active" id="comments-pane-{{ $userPhoto->photo_id }}" role="tabpanel" tabindex="0">

                            <div class="comments">
                        @if(!is_null($userPhoto->comments))
                            @foreach($userPhoto->comments as $c)
                                <div class="comment py-4">
                                    <div class="d-flex flex-row">
                                        <div>
                                            <img class="avatar rounded-5 mx-3" src="{{ getUserAvatar($c->toArray()) }}" title="{{ _gettext('avatar') }}">
                                        </div>
                                        <div>
                                            <div class="mb-2">
                                                <b class="me-3">{{ getUserDisplayName($c->toArray()) }}</b><span class="text-primary">{{ $c->updated_at->diffForHumans() }}</span>
                                            </div>
                                            <div class="">
                                                {!! cleanUserComments($c->comments) !!}
                                            </div>
                                        </div>
                                    </div>
                                </div><!-- /.comment -->
                            @endforeach
                        @endif
                            </div>
                        </div><!-- /#comments-pane -->

                        <div class="tab-pane py-3" id="details-pane-{{ $userPhoto->photo_id }}" role="tabpanel" tabindex="0">
                        @isset($exif[ $userPhoto->photo_id ])
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi-calendar-date fs-3 me-3"></i>
                                <div class="">
                                    {{ \Carbon\Carbon::parse($exif[ $userPhoto->photo_id ]['DateTime'])->format('M j, Y') }}
                                    <div class="text-muted">{{ \Carbon\Carbon::parse($exif[ $userPhoto->photo_id ]['DateTime'])->format('l g:i a') }}</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi-camera fs-3 me-3"></i>
                                <div class="">
                                    {{ $exif[ $userPhoto->photo_id ]['Make'] }} {{ $exif[ $userPhoto->photo_id ]['Model'] }}
                                </div>
                            </div>
                        @endisset
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi-eye fs-3 me-3"></i>
                                <div class="">{{ $userPhoto->photo->views }}</div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi-star fs-3 me-3"></i>
                                <div class="">{{ $userPhoto->photo->rating }}</div>
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

    window.history.replaceState(null, '', '/photos/people/{{ $userPhoto->user_id }}/photos/' + photoId);
});
</script>
@endsection 
