<div class="person">
    <div class="dropdown no-caret float-end">
        <i class="bi-three-dots-vertical dropdown-toggle" data-bs-toggle="dropdown"></i>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" data-user="{{ $person['id'] }}" data-type="parent" href="#">{{ __('Add Parent') }}</a></li>
            <li><a class="dropdown-item" data-user="{{ $person['id'] }}" data-type="spouse" href="#">{{ __('Add Spouse') }}</a></li>
            <li><a class="dropdown-item" data-user="{{ $person['id'] }}" data-type="child" href="#">{{ __('Add Child') }}</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-muted" href="#">{{ __('Edit') }}</a></li>
            <li><a class="dropdown-item text-danger" href="#">{{ __('Delete') }}</a></li>
        </ul>
    </div>
    <img class="avatar rounded-5" src="{{ getUserAvatar($person) }}" title="{{ __('avatar') }}">
    <div class="d-block">
        {{ $person['fname'] }} {{ $person['mname'] }} {{ $person['lname'] }}
        @if (!empty($person['maiden']))
            {{ '('.$person['maiden'].')' }}
        @endif
    </div>
    <span class="text-muted">
        @if (!empty($person['dob_year']))
            {{ $person['dob_year'] }}
            -
            @if (!empty($person['dod_year']))
                {{ $person['dod_year'] }}
            @else
                {{ __('Living') }}
            @endif
        @else
            @if (!empty($person['dod_year']))
                {{ trans('Died in :year', ['year' => $person['dod_year']]) }}
            @else
                {{ __('Living') }}
            @endif

        @endif
    </span>
</div>
