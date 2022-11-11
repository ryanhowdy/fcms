@include('header')

<body id="install" class="bg-light">
    <main class="m-auto bg-white border p-5">
        <div>
            <img class="mb-5" src="{{ asset('img/logo.gif') }}">
            <div class="progress mb-3">
                <div class="progress-bar" style="width: {{ $progress }}%" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="alert alert-info" role="alert">
                <h4 class="alert-heading">{{ __('2. Site Configuration') }}</h4>
                <p>{{ __('Please fill out the form below to configure your site.') }}</p>
            </div>
            <form action="{{ route('install.config') }}" method="post">
                @csrf
            @if ($errors->any())
                <div class="alert alert-danger">
                    <h4 class="alert-heading">{{ __('An error has occurred') }}</h4>
                    <p>{{ __('Please fill out the required fields below.') }}</p>
                </div>
            @endif
                <div class="mb-3 required">
                    <label for="sitename">{{ __('Website Name') }}</label>
                    <input type="text" class="form-control" name="sitename" id="sitename" value="{{ old('sitename') }}" 
                        title="{{ __('What do you want your website to be called?') }}">
                    <div class="form-text">{{ __('Examples: "The Smith\'s" or "The Johnson Family Website"') }}</div>
                </div>
                <div class="mb-3 required">
                    <label for="contact">{{ __('Contact Email') }}</label>
                    <input type="text" class="form-control" name="contact" id="contact" value="{{ old('contact') }}">
                    <div class="form-text">{{ __('The email address you want all questions, comments and concerns about the site to go.') }}</div>
                </div>
                <div class="mb-3">
                    <label>{{ __('Optional Plugins') }}</label>
                    <div class="form-text">{{ __('Which plugins would you like to use on your site?') }}</div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sections[]" id="sections-news" value="familynews"/>
                        <label class="form-check-label" for="sections-news">{{ __('Family News') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sections[]" id="sections-recipes" value="recipes"/>
                        <label class="form-check-label" for="sections-recipes">{{ __('Recipes') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sections[]" id="sections-tree" value="familytree"/>
                        <label class="form-check-label" for="sections-tree">{{ __('Family Tree') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sections[]" id="sections-documents" value="documents"/>
                        <label class="form-check-label" for="sections-documents">{{ __('Documents') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sections[]" id="sections-prayers" value="prayerconcerns"/>
                        <label class="form-check-label" for="sections-prayers">{{ __('Prayer Concerns') }}</label>
                    </div>
                </div>
                <div class="text-end">
                    <input type="submit" class="btn btn-primary" value="{{ __('Next') }}">
                </div>
            </form>
        </div>
    </main>
</body>
</html>
