@extends('layouts.main')
@section('body-id', 'members')

@section('content')
<div class="p-5">
    <h2>{{ __('Members') }}</h2>

    <table class="table">
        <thead>
            <tr>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Registered') }}</th>
                <th>{{ __('Last Seen') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($users as $user)
            <tr>
                <td>{{ getUserDisplayName($user->toArray()) }}</td>
                <td>{{ $user->created_at->format('M j, Y') }}</td>
                <td>{{ $user->updated_at->diffForHumans() }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
