@extends('layouts.main')
@section('body-id', 'members')

@section('content')
<div class="p-5">
    <h2>{{ gettext('Members') }}</h2>

    <table class="table table-hover">
        <thead>
            <tr>
                <th>{{ gettext('Name') }}</th>
                <th>{{ gettext('Registered') }}</th>
                <th>{{ gettext('Last Seen') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($users as $user)
            <tr>
                <td>{{ getUserDisplayName($user->toArray()) }}</td>
                <td>{{ $user->created_at->format('M j, Y') }}</td>
                <td>
                @if (is_null($user->activity))
                    {{ gettext('Never') }}
                @else
                    {{ $user->activity->diffForHumans() }}
                @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
