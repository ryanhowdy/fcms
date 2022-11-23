@extends('layouts.photo')
@section('body-id', 'photos')

@section('photo')
<div id="photo-controls" class="carousel slide bg-black text-white" data-bs-ride="carousel" data-bs-pause="hover">
    <div class="carousel-inner">
    @foreach ($album[0]->photos as $k => $photo)
        <div class="carousel-item @if ($k == 0) active @endif">
            <div class="row">
                <div class="col-10 img-col">
                    <img class="d-block h-100" src="{{ route('photo', ['id' => $album[0]->created_user_id, 'file' => $photo->filename]) }}">
                </div>
                <div class="col-2 details-col bg-dark pt-5">
                    <a href="{{ route('photos.albums.show', $album[0]->id) }}" class="btn-close bg-white position-absolute top-0 end-0 me-3 mt-3" aria-label="Close"></a>
                    <div class="photo-nav border-bottom">
                        <ul class="nav" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a href="#" class="nav-link active" id="comments-tab-{{ $photo->id }}" data-bs-toggle="tab" data-bs-target="#comments-pane-{{ $photo->id }}">Comments</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#" class="nav-link" id="details-tab-{{ $photo->id }}" data-bs-toggle="tab" data-bs-target="#details-pane-{{ $photo->id }}">Details</a>
                            </li>
                        </ul>
                    </div>
                    <div class="tab-content">
                        <div class="tab-pane show active" id="comments-pane-{{ $photo->id }}" role="tabpanel" tabindex="0">

                        </div><!-- /#comments-pane -->
                        <div class="tab-pane py-3" id="details-pane-{{ $photo->id }}" role="tabpanel" tabindex="0">
                            <p>{{ $photo->caption }}</p>
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
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi-eye fs-3 me-3"></i>
                                <div class="">{{ $photo->views }}</div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi-star fs-3 me-3"></i>
                                <div class="">{{ $photo->rating }}</div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <b>{{ __('People') }}</b>
                            </div>
                        </div><!-- /#details-pane -->
                    </div>
                </div>
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
<style>
body#photos { overflow: hidden; }
.carousel { height: 100vh;}
.carousel-inner,.carousel-item, .row, .img-col, .details-col { height: 100%;}
.carousel-item img { height: 100%; object-fit: cover; object-position: center; margin: auto;}
.carousel-control-next { right: 16.66666667%; }

.carousel { font-size: 0.85rem; }
</style>
@endsection 
