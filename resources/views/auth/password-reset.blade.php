@include('layouts.header')

<body id="login" class="text-center bg-light">
    <main class="m-auto bg-white border p-5">
        <form action="{{ route('password.store', $token) }}" method="post">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <a href="{{ route('index') }}">
                <img class="mb-5" src="{{ asset('img/logo.gif') }}">
            </a>

            @if (\Session::has('header') && \Session::has('message'))
                <div class="alert alert-danger text-start">
                    <h2>{{ \Session::get('header') }}</h2>
                    <p>{{ \Session::get('message') }}</p>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
                </div>
            @endif

            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com">
                <label for="email">{{ _gettext('Email') }}</label>
            </div>

            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="{{ _gettext('New Password') }}">
                <label for="password">{{ _gettext('New Password') }}</label>
            </div>

            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="{{ _gettext('Confirm New Password') }}">
                <label for="password_confirmation">{{ _gettext('Confirm New Password') }}</label>
            </div>

            <button class="w-100 btn btn-lg btn-primary" type="submit">{{ _gettext('Reset Password') }}</button>
        </form>
    </main>
</body>
</html>
