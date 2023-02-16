@extends('layouts.main')
@section('body-id', 'admin-members')

@section('content')
<div class="p-5">
    <h2>{{ __('Members') }}</h2>

    <table class="table table-hover">
        <thead>
            <tr>
                <th>{{ __('Id') }}</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Registered') }}</th>
                <th>{{ __('Last Seen') }}</th>
                <th>{{ __('Can Login?') }}</th>
                <th>{{ __('Access') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>
                    <span class="fw-bold text-purple">{{ getUserDisplayName($user->toArray()) }}</span><br>
                    <span class="text-muted small fst-italic">{{ $user->email }}</span>
                </td>
                <td>{{ $user->created_at->format('M j, Y') }}</td>
                <td>
                @if (is_null($user->activity))
                    {{ __('Never') }}
                @else
                    {{ $user->activity->diffForHumans() }}
                @endif
                </td>
                <td>
                @if ($user->activated)
                    <span data-id="{{ $user->id }}" class="alert alert-success py-1 px-2 m-0 small">{{ __('Yes') }}</span>
                @else
                    <span data-id="{{ $user->id }}" class="alert alert-danger py-1 px-2 m-0 small">{{ __('No') }}</span>
                @endif
                </td>
                <td>
                    <select data-id="{{ $user->id }}" class="form-select w-auto" name="access">
                        @foreach($levels as $name => $id)
                        <option value="{{ $id }}" {{ old('access', $user->access) == $id ? 'selected' : '' }}>{{ $id.': '.ucfirst(strtolower($name)) }}</option>
                        @endforeach
                    </select>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div id="spinner" class="spinner-grow d-none" role="status">
        <span class="visually-hidden">{{ __('Loading...') }}</span>
    </div>

</div>
<style>
#admin-members table span.alert
{
    cursor: pointer;
}
#admin-members table span.alert:hover
{
    border-color: var(--bs-gray-500);
    color: var(--bs-dark);
    background-color: var(--bs-light);
}
</style>
<script>
$(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        }
    });

    $('table td > span.alert-danger').click(function() {
        let userId = $(this).data('id');

        let url = '{{ route('admin.members.update', ':id') }}';
        url = url.replace(':id', userId);

        setActivated($(this), url, 1);
    });

    $('table td > span.alert-success').click(function() {
        let userId = $(this).data('id');

        let url = '{{ route('admin.members.update', ':id') }}';
        url = url.replace(':id', userId);

        setActivated($(this), url, 0);
    });

    $('select[name="access"]').on('change', function() {
        let $select = $(this);
        let spinner = $('#spinner').clone();
        let userId  = $select.data('id');

        let url = '{{ route('admin.members.update', ':id') }}';
        url = url.replace(':id', userId);

        spinner.id = 'this-spinner';

        $select.hide();
        $select.after(spinner);

        $.ajax({
            url         : url,
            type        : 'post',
            data        : { access : $select.val() },
            dataType    : 'json',
        }).done(function(data) {
            $('#this-spinner').remove();
            $select.show();
        }).fail(function() {
            alert('failed');
        });
    });
});

/**
 * setActivated 
 * 
 * @param string $url 
 * @param int $act 
 * @return null
 */
function setActivated ($btn, url, act)
{
    let spinner = $('#spinner').clone();

    spinner.id = 'this-spinner';

    $btn.hide();
    $btn.after(spinner);

    $.ajax({
        url         : url,
        type        : 'post',
        data        : { activated : act },
        dataType    : 'json',
    }).done(function(data) {
        let type = data.activated == 1 ? 'alert-success' : 'alert-danger';

        $('#this-spinner').remove();

        $btn.text(data.text)
            .removeClass('alert-danger alert-success')
            .addClass(type)
            .show();
    }).fail(function() {
        alert('failed');
    });
}
</script>
@endsection
