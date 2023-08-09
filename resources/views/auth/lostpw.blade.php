@include('layouts.header')

<body id="register" class="bg-light">
    <main class="m-auto bg-white border p-5">
        <div>
            <img class="mb-5" src="{{ asset('img/logo.gif') }}">

            <div class="alert alert-info">
                <h4 class="alert-heading">{{ _gettext('Forgot your password?') }}</h4>
                <p>{{ _gettext('Fill out the email address you used to register with and we will email you a password reset link that will allow you to choose a new password.') }}</p>
            </div>

            <form action="{{ route('password.email') }}" method="post">
                @csrf

            @if ($errors->any())
                <div class="alert alert-danger">
                    <h4 class="alert-heading">{{ _gettext('An error has occurred') }}</h4>
                </div>
            @endif

                <div class="mb-3 required">
                    <label for="email">{{ _gettext('Email') }}</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
                </div>

                <div class="text-end">
                    <input type="submit" class="btn btn-primary" value="{{ _gettext('Email Password Reset Email') }}">
                </div>
            </form>
        </div>
    </main>
</body>
</html>
