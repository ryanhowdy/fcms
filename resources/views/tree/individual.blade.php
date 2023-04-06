@extends('layouts.main')
@section('body-id', 'familytree')

@section('content')
<div class="p-5">

    <div class="header d-flex">
        <img class="rounded-5 me-3" src="{{ getIndividualPicture($individual) }}" title="{{ gettext('avatar') }}">
        <div>
            <h3 class="mb-0">
                {{ $individual['given_name'] }}
            @if (!empty($individual['maiden']))
                {{ '('.$individual['maiden'].')' }}
            @else
                {{ $individual['surname'] }}
            @endif
            </h3>
            <div class="pb-1">
            @if ($individual['living'])
                <span class="text-indigo">{{ gettext('Living') }}</span>
            @else
                {{ gettext('Deceased') }}
            @endif
            </div>
            <a href="{{ route('familytree.showTree', $individual['id']) }}" class="btn btn-sm btn-light rounded-5">
                <i class="bi-diagram-3-fill pe-1"></i>{{ gettext('View Tree') }}
            </a>
        </div>
    </div><!-- /.header -->

    <div class="border-bottom">
        <div class="label py-4 text-end float-start"><b>{{ gettext('Name') }}</b></div>
        <div class="content px-5 py-4">
            <div>
            @if (!empty($individual['name_prefix']))
                {{ $individual['name_prefix'] }}
            @endif
            {{ $individual['given_name'] }}
            @if (!empty($individual['maiden']))
                {{ '('.$individual['maiden'].')' }}
            @else
                {{ $individual['surname'] }}
            @endif
            @if (!empty($individual['name_suffix']))
                {{ $individual['name_suffix'] }}
            @endif
            </div>
        @if (!empty($individual['alias']))
            <div><b class="pe-1">{{ gettext('Alias') }}:</b><i>{{ $individual['alias'] }}</i></div>
        @endif
        @if (!empty($individual['nickname']))
            <div><b class="pe-1">{{ gettext('Nickname') }}:</b><i>{{ $individual['nickname'] }}</i></div>
        @endif
            <p>
                <a class="small" data-bs-toggle="collapse" href="#name-more" role="button" aria-expanded="false" aria-controls="name-more">
                    <i class="bi-chevron-right pe-1"></i>{{ gettext('Events, Media, More') }}
                </a>
            </p>
            <div class="collapse small" id="name-more">
                <p>
                    <a href="{{ route('familytree.edit', $individual['id']) }}"><i class="bi-pencil pe-1"></i>{{ gettext('Edit') }}</a>
                </p>
                <p>
                    <a href="{{ route('familytree.createEvent', $individual['id']) }}"><i class="bi-plus pe-1"></i>{{ gettext('Add Event') }}</a>
                </p>
            </div>
        </div>
    </div>

    <div class="border-bottom">
        <div class="label py-4 text-end float-start"><b>{{ gettext('Birth') }}</b></div>
        <div class="content px-5 py-4">
            <div>
            @if (!empty($individual['dob']))
                {{ $individual['dob'] }}
            @elseif (!empty($individual['dob_year']))
                {{ $individual['dob_year'] }}
            @else
                <i>{{ gettext('unknown') }}</i>
            @endif
            </div>
            <p>
                <a class="small" data-bs-toggle="collapse" href="#birth-more" role="button" aria-expanded="false" aria-controls="birth-more">
                    <i class="bi-chevron-right pe-1"></i>{{ gettext('Events, Media, More') }}
                </a>
            </p>
            <div class="collapse small" id="birth-more">
                <p>
                    <a href="{{ route('familytree.edit', $individual['id']) }}"><i class="bi-pencil pe-1"></i>{{ gettext('Edit') }}</a>
                </p>
                <p>
                    <a href="{{ route('familytree.createEvent', $individual['id']) }}"><i class="bi-plus pe-1"></i>{{ gettext('Add Event') }}</a>
                </p>
            </div>
        </div>
    </div>

    <div class="border-bottom">
        <div class="label py-4 text-end float-start"><b>{{ gettext('Death') }}</b></div>
        <div class="content px-5 py-4">
            <div>
            @if (!$individual['living'])
                @if (!empty($individual['dod']))
                    {{ $individual['dod'] }}
                @elseif (!empty($individual['dod_year']))
                    {{ $individual['dod_year'] }}
                @else
                    <i>{{ gettext('unknown') }}</i>
                @endif
            @else
                <i>{{ gettext('none') }}</i>
            @endif
            </div>
            <p>
                <a class="small" data-bs-toggle="collapse" href="#death-more" role="button" aria-expanded="false" aria-controls="death-more">
                    <i class="bi-chevron-right pe-1"></i>{{ gettext('Events, Media, More') }}
                </a>
            </p>
            <div class="collapse small" id="death-more">
                <p>
                    <a href="{{ route('familytree.edit', $individual['id']) }}"><i class="bi-pencil pe-1"></i>{{ gettext('Edit') }}</a>
                </p>
                <p>
                    <a href="{{ route('familytree.createEvent', $individual['id']) }}"><i class="bi-plus pe-1"></i>{{ gettext('Add Event') }}</a>
                </p>
            </div>
        </div>
    </div>

    <div class="border-bottom">
        <div class="label py-4 text-end float-start"><b>{{ gettext('Description') }}</b></div>
        <div class="content px-5 py-4">
            <div>
            @if (!empty($individual['description']))
                {{ $individual['description'] }}
            @else
                <i>{{ gettext('none') }}</i>
            @endif
            </div>
            <p>
                <a class="small" data-bs-toggle="collapse" href="#desc-more" role="button" aria-expanded="false" aria-controls="desc-more">
                    <i class="bi-chevron-right pe-1"></i>{{ ('Events, Media, More') }}
                </a>
            </p>
            <div class="collapse small" id="desc-more">
                <p>
                    <a href="{{ route('familytree.edit', $individual['id']) }}"><i class="bi-pencil pe-1"></i>{{ gettext('Edit') }}</a>
                </p>
                <p>
                    <a href="{{ route('familytree.createEvent', $individual['id']) }}"><i class="bi-plus pe-1"></i>{{ gettext('Add Event') }}</a>
                </p>
            </div>
        </div>
    </div>


</div>

<style>
#familytree div.label
{
    width: 200px;
}
#familytree div.content
{
    margin-left: 200px;
}
#familytree .border-bottom > .float-end
{
    display: none;
}
#familytree .border-bottom:hover > .float-end
{
    display: block;
}
.header > img
{
    max-height: 90px;
}
</style>
@endsection
