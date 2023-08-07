
    <form class="border rounded p-5 mt-3 mb-3 d-inline-block" action="{{ route('invitations.update', ['eid' => $event['id'], 'id' => $rsvp['id']]) }}" method="post">
        @csrf
    @if($rsvp['rsvp'] !== 'none')
        <h5>{{ _gettext("You have already RSVP'd") }}</h5>
    @else
        <h5>{{ _gettext('Are you Going?') }}</h5>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <h4 class="alert-heading">{{ _gettext('An error has occurred') }}</h4>
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
        </div>
    @endif
        <div class="mb-3">
            <input type="radio" class="btn-check" id="attending" name="rsvp" @checked(old('rsvp', $rsvp['rsvp'] == 'attending')) value="attending">
            <label class="btn btn-outline-success rounded-0" for="attending">
                <span class="bi-check"></span>
                {{ _gettext('Attending') }}
            </label>
            <input type="radio" class="btn-check" id="maybe" name="rsvp" @checked(old('rsvp', $rsvp['rsvp'] == 'maybe')) value="maybe">
            <label class="btn btn-outline-primary rounded-0" for="maybe">
                <span class="bi-question"></span>
                {{ _gettext('Maybe') }}
            </label>
            <input type="radio" class="btn-check" id="no" name="rsvp" @checked(old('rsvp', $rsvp['rsvp'] == 'no')) value="no">
            <label class="btn btn-outline-danger rounded-0" for="no">
                <span class="bi-x"></span>
                {{ _gettext('No') }}
            </label>
        </div>
        <div class="mb-3">
            <textarea class="form-control" id="comments" name="comments" placeholder="{{ _gettext('Comments') }}" rows="3"></textarea>
        </div>
        <div class="mb-3">
            <button type="submit" class="btn btn-primary mb-3">Save RSVP</button>
        </div>
    </form>
