@extends('layouts.main')
@section('body-id', 'documents')

@section('content')
<div class="d-flex flex-nowrap">

    {{-- Main Column --}}
    <div class="col border-end min-vh-100 p-5">
        <h3>{{ __('Folders') }}</h3>
        <div class="row mb-5">
            <div class="col">
                <a class="text-decoration-none text-dark float-start w-100 border p-2" href="">
                    <div class="alert alert-primary p-2 float-start fs-3 m-0 me-3">
                        <i class="bi-file-earmark-word"></i>
                    </div>
                    <div class="pt-3">{{ __('Documents') }}</div>
                    <span class="text-muted">{{ trans(':count Files', [ 'count' => $counts['document'] ]) }}</span>
                </a>
            </div>
            <div class="col">
                <a class="text-decoration-none text-dark float-start w-100 border p-2" href="">
                    <div class="alert alert-danger p-2 float-start fs-3 m-0 me-3">
                        <i class="bi-file-earmark-easel"></i>
                    </div>
                    <div class="pt-3">{{ __('Presentation') }}</div>
                    <span class="text-muted">{{ trans(':count Files', [ 'count' => $counts['presentation'] ]) }}</span>
                </a>
            </div>
            <div class="col">
                <a class="text-decoration-none text-dark float-start w-100 border p-2" href="">
                    <div class="alert alert-secondary p-2 float-start fs-3 m-0 me-3">
                        <i class="bi-file-earmark-zip"></i>
                    </div>
                    <div class="pt-3">{{ __('Archives') }}</div>
                    <span class="text-muted">{{ trans(':count Files', [ 'count' => $counts['archive'] ]) }}</span>
                </a>
            </div>
            <div class="col">
                <a class="text-decoration-none text-dark float-start w-100 border p-2" href="">
                    <div class="alert alert-success p-2 float-start fs-3 m-0 me-3">
                        <i class="bi-file-earmark-music"></i>
                    </div>
                    <div class="pt-3">{{ __('Multimedia') }}</div>
                    <span class="text-muted">{{ trans(':count Files', [ 'count' => $counts['multimedia'] ]) }}</span>
                </a>
            </div>
            <div class="col">
                <a class="text-decoration-none text-dark float-start w-100 border p-2" href="">
                    <div class="alert alert-warning p-2 float-start fs-3 m-0 me-3">
                        <i class="bi-file-earmark"></i>
                    </div>
                    <div class="pt-3">{{ __('Other') }}</div>
                    <span class="text-muted">{{ trans(':count Files', [ 'count' => $counts['other'] ]) }}</span>
                </a>
            </div>
        </div>

        <h3>{{ __('Recent') }}</h3>
    @if ($documents->isEmpty())
        <x-empty-state/>
    @else
        <table class="table table-hover">
            <tbody>
            @foreach ($documents as $doc)
                <tr class="align-baseline">
                    <td>
                        <a href="{{ route('documents.download', $doc->filename) }}">
                            <i class="bi-{{ @$mimeDataLkup[ $doc->mime ]['icon'] ?: 'file-earmark' }} fs-3"></i>
                            {{ $doc->name }}
                        </a>
                    </td>
                    <td>{{ $doc->created_at->format('M j, Y') }}</td>
                    <td>{{ $doc->mime }}</td>
                    <td>{{ getUserDisplayName($doc->toArray()) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
    </div>

    {{-- Sidebar --}}
    <div class="col-auto col-3 p-5">
        <div>
            <a href="{{ route('documents.create') }}" class="btn btn-success text-white">{{ __('Upload') }}</a>
        </div>
    </div>
</div>

<style>
#documents .col a.text-decoration-none:hover div
{
    text-decoration: underline!important;
}
</style>
@endsection
