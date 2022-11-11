@include('header')

<body id="install" class="bg-light">
    <main class="m-auto bg-white border p-5">
        <div>
            <img class="mb-5" src="{{ asset('img/logo.gif') }}">
            <div class="progress mb-3">
                <div class="progress-bar" style="width: {{ $progress }}%" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="alert alert-warning" role="alert">
                <h4 class="alert-heading">{{ __('This site hasn\'t been install yet') }}</h4>
                <p>{{ __('You must finish the installation before using the site.') }}</p>
            </div>
            <div class="alert alert-info" role="alert">
                <h4 class="alert-heading">1. Database setup</h4>
                <p>Please run the following command to configure your database</p>
                <code>php artisan migrate:fresh</code>
            </div>
            <div class="text-end">
                <a href="{{ route('install.config') }}" class="btn btn-primary">{{ __('Next') }}</a>
            </div>
        </div>
    </main>
</body>
</html>
