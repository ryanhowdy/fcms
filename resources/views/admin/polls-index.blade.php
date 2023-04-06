@extends('layouts.main')
@section('body-id', 'admin-members')

@section('content')
<div class="p-5">
    <h2>{{ _gettext('Polls') }}</h2>

    <table class="table table-hover">
        <thead>
            <tr>
                <th>{{ _gettext('Id') }}</th>
                <th>{{ _gettext('Question') }}</th>
                <th>{{ _gettext('Date') }}</th>
                <th>{{ _gettext('Votes') }}</th>
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
