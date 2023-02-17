@extends('layouts.main')
@section('body-id', 'admin-members')

@section('content')
<div class="p-5">
    <h2>{{ __('Polls') }}</h2>

    <table class="table table-hover">
        <thead>
            <tr>
                <th>{{ __('Id') }}</th>
                <th>{{ __('Question') }}</th>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Votes') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($polls as $poll)
            <tr>
                <td>{{ $poll->id }}</td>
                <td>
                    <span class="fw-bold text-purple">{{ $poll->question }}</span>
                </td>
                <td>{{ $poll->created_at->format('M j, Y') }}</td>
                <td>{{ count($poll->votes) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
