
    <h5 class="mt-3">{{ _gettext('Guest list') }}</h5>
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-tab-pane" type="button" role="tab" aria-controls="all-tab-pane" aria-selected="false">
                {{ _gettext('All') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="yes-tab" data-bs-toggle="tab" data-bs-target="#yes-tab-pane" type="button" role="tab" aria-controls="yes-tab-pane" aria-selected="true">
                {{ _gettext('Attending') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="maybe-tab" data-bs-toggle="tab" data-bs-target="#maybe-tab-pane" type="button" role="tab" aria-controls="maybe-tab-pane" aria-selected="false">
                {{ _gettext('Maybe') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="no-tab" data-bs-toggle="tab" data-bs-target="#no-tab-pane" type="button" role="tab" aria-controls="no-tab-pane" aria-selected="false">
                {{ _gettext('No') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="none-tab" data-bs-toggle="tab" data-bs-target="#none-tab-pane" type="button" role="tab" aria-controls="none-tab-pane" aria-selected="false">
                {{ _gettext('No Response') }}
            </button>
        </li>
    </ul>

    <div class="tab-content">

        <div class="tab-pane fade p-3" id="all-tab-pane" role="tabpanel" aria-labelledby="all-tab" tabindex="0">
        @foreach($invitations['all'] as $invite)
            <div class="d-flex align-items-start">
                <div>
                @if(empty($invite['email']))
                    {{ getUserDisplayName($invite) }}
                @else
                    {{ $invite['email'] }}
                @endif
                </div>
                <div>
                    <span @class([
                        'badge',
                        'text-bg-success' => $invite['status'] == 'attending',
                        'text-bg-primary' => $invite['status'] == 'maybe',
                        'text-bg-danger' => $invite['status'] == 'no',
                        'text-bg-secondary' => $invite['status'] == 'none',
                        ])>{{ $invite['status'] }}</span>
                </div>
            </div>
        @endforeach
        @if(empty($invitations['all']))
            <i>{{ _gettext('No responses yet') }}</i>
        @endif
        </div><!-- /#all-tab-pane -->

        <div class="tab-pane fade p-3 show active" id="yes-tab-pane" role="tabpanel" aria-labelledby="yes-tab" tabindex="0">
        @foreach($invitations['attending'] as $invite)
            <div class="d-flex align-items-start">
                <div>
                @if(empty($invite['email']))
                    {{ getUserDisplayName($invite) }}
                @else
                    {{ $invite['email'] }}
                @endif
                </div>
                <div>
                    <span @class([
                        'badge',
                        'text-bg-success' => $invite['status'] == 'attending',
                        'text-bg-primary' => $invite['status'] == 'maybe',
                        'text-bg-danger' => $invite['status'] == 'no',
                        'text-bg-secondary' => $invite['status'] == 'none',
                        ])>{{ $invite['status'] }}</span>
                </div>
            </div>
        @endforeach
        @if(empty($invitations['attending']))
            <i>{{ _gettext('No responses yet') }}</i>
        @endif
        </div><!-- /#attending-tab-pane -->

        <div class="tab-pane fade p-3" id="maybe-tab-pane" role="tabpanel" aria-labelledby="maybe-tab" tabindex="0">
        @foreach($invitations['maybe'] as $invite)
            <div class="d-flex align-items-start">
                <div>
                @if(empty($invite['email']))
                    {{ getUserDisplayName($invite) }}
                @else
                    {{ $invite['email'] }}
                @endif
                </div>
                <div>
                    <span @class([
                        'badge',
                        'text-bg-success' => $invite['status'] == 'attending',
                        'text-bg-primary' => $invite['status'] == 'maybe',
                        'text-bg-danger' => $invite['status'] == 'no',
                        'text-bg-secondary' => $invite['status'] == 'none',
                        ])>{{ $invite['status'] }}</span>
                </div>
            </div>
        @endforeach
        @if(empty($invitations['maybe']))
            <i>{{ _gettext('No responses yet') }}</i>
        @endif
        </div><!-- /#maybe-tab-pane -->

        <div class="tab-pane fade p-3" id="no-tab-pane" role="tabpanel" aria-labelledby="no-tab" tabindex="0">
        @foreach($invitations['no'] as $invite)
            <div class="d-flex align-items-start">
                <div>
                @if(empty($invite['email']))
                    {{ getUserDisplayName($invite) }}
                @else
                    {{ $invite['email'] }}
                @endif
                </div>
                <div>
                    <span @class([
                        'badge',
                        'text-bg-success' => $invite['status'] == 'attending',
                        'text-bg-primary' => $invite['status'] == 'maybe',
                        'text-bg-danger' => $invite['status'] == 'no',
                        'text-bg-secondary' => $invite['status'] == 'none',
                        ])>{{ $invite['status'] }}</span>
                </div>
            </div>
        @endforeach
        @if(empty($invitations['no']))
            <i>{{ _gettext('No responses yet') }}</i>
        @endif
        </div><!-- /#no-tab-pane -->

        <div class="tab-pane fade p-3" id="none-tab-pane" role="tabpanel" aria-labelledby="none-tab" tabindex="0">
        @foreach($invitations['none'] as $invite)
            <div class="d-flex align-items-start">
                <div>
                @if(empty($invite['email']))
                    {{ getUserDisplayName($invite) }}
                @else
                    {{ $invite['email'] }}
                @endif
                </div>
                <div>
                    <span @class([
                        'badge',
                        'text-bg-success' => $invite['status'] == 'attending',
                        'text-bg-primary' => $invite['status'] == 'maybe',
                        'text-bg-danger' => $invite['status'] == 'no',
                        'text-bg-secondary' => $invite['status'] == 'none',
                        ])>{{ $invite['status'] }}</span>
                </div>
            </div>
        @endforeach
        @if(empty($invitations['none']))
            <i>{{ _gettext('No responses yet') }}</i>
        @endif
        </div><!-- /#none-tab-pane -->

    </div>
