@include('layouts.header')

<body id="install" class="bg-light">
    <main class="m-auto bg-white border p-5">
        <div>
            <img class="mb-5" src="{{ asset('img/logo.gif') }}">
            <div class="progress mb-3">
                <div class="progress-bar" style="width: {{ $progress }}%" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="alert alert-info" role="alert">
                <h4 class="alert-heading">{{ _gettext('2. Site Configuration') }}</h4>
                <p>{{ _gettext('Please fill out the form below to configure your site.') }}</p>
            </div>
            <form action="{{ route('install.config') }}" method="post">
                @csrf
                <div class="mb-3">
                    <label>{{ _gettext('Optional Plugins') }}</label>
                    <div class="form-text">{{ _gettext('Which plugins would you like to use on your site?') }}</div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sections[]" id="sections-news" value="familynews"/>
                        <label class="form-check-label" for="sections-news">{{ _gettext('Family News') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sections[]" id="sections-recipes" value="recipes"/>
                        <label class="form-check-label" for="sections-recipes">{{ _gettext('Recipes') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sections[]" id="sections-tree" value="familytree"/>
                        <label class="form-check-label" for="sections-tree">{{ _gettext('Family Tree') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sections[]" id="sections-documents" value="documents"/>
                        <label class="form-check-label" for="sections-documents">{{ _gettext('Documents') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sections[]" id="sections-prayers" value="prayerconcerns"/>
                        <label class="form-check-label" for="sections-prayers">{{ _gettext('Prayer Concerns') }}</label>
                    </div>
                </div>
                <div class="text-end">
                    <input type="submit" class="btn btn-primary" value="{{ _gettext('Next') }}">
                </div>
            </form>
        </div>
    </main>
</body>
</html>
