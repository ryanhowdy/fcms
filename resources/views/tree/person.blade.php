<div class="person {{ strtolower($person['sex']) }}" data-url="{{ route('familytree.show', $person['id']) }}">
    <div class="options">
        <div class="dropdown no-caret">
            <i class="bi-person-plus dropdown-toggle" data-bs-toggle="dropdown"></i>
            <ul class="dropdown-menu">
                <li>
                    <a class="dropdown-item" data-individual="{{ $person['id'] }}" data-type="parent" data-family="{{ $person['family_id'] }}" href="#"
                        data-header="{{ $person['strings']['parent']['header'] }}">
                        {{ _gettext('Add Parent') }}
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" data-individual="{{ $person['id'] }}" data-type="spouse" data-family="{{ $person['family_id'] }}" href="#"
                        data-header="{{ $person['strings']['spouse']['header'] }}" data-surname="{{ $person['surname'] }}">
                        {{ _gettext('Add Spouse') }}
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" data-individual="{{ $person['id'] }}" data-type="sibling" data-family="{{ $person['family_id'] }}" href="#"
                        data-header="{{ $person['strings']['sibling']['header'] }}">
                        {{ _gettext('Add Sibling') }}
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" data-individual="{{ $person['id'] }}" data-type="child" data-family="{{ $person['family_id'] }}" href="#"
                        data-header="{{ $person['strings']['child']['header'] }}">
                        {{ _gettext('Add Child') }}
                    </a>
                </li>
            </ul>
        </div><!-- /.dropdown.no-caret -->
        @if ($person['relationship'] == 'WIFE' && $person['sex'] == 'F')
        <a href="{{ route('familytree.showTree', $person['id']) }}">
            <i class="bi-diagram-3-fill"></i>
        </a>
        @endif
        <a href="{{ route('familytree.edit', $person['id']) }}">
            <i class="bi-pencil"></i>
        </a>
    </div><!-- /.options -->
    <img class="avatar rounded-5" src="{{ getIndividualPicture($person) }}" title="{{ _gettext('avatar') }}">
    <div class="d-block">
        {{ $person['given_name'] }}
        @if (!empty($person['maiden']))
            {{ '('.$person['maiden'].')' }}
        @else
            {{ $person['surname'] }}
        @endif
    </div>
    <span class="text-muted">
        @if (!empty($person['dob_year']))
            {{ $person['dob_year'] }}
            -
            @if (!empty($person['dod_year']))
                {{ $person['dod_year'] }}
            @else
                {{ _gettext('Living') }}
            @endif
        @else
            @if (!empty($person['dod_year']))
                {{ sprintf(_gettext('Died in %d'), $person['dod_year']) }}
            @else
                {{ _gettext('Living') }}
            @endif

        @endif
    </span>
</div>
