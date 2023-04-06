@include('layouts.header')

<body id="register" class="bg-light">
    <main class="m-auto bg-white border p-5">
        <div>
            <img class="mb-5" src="{{ asset('img/logo.gif') }}">

        @if(!env('FCMS_AUTO_ACTIVATE'))
            <div class="alert alert-info">
                <h4 class="alert-heading">{{ _gettext('Request Access') }}</h4>
                <p>{{ _gettext('In order to login and begin using the site, your administrator must activate your account.') }}</p>
                <p>{{ _gettext('Please fill out the form below to request access.') }}</p>
            </div>
        @endif

            <form action="{{ route('auth.register') }}" method="post">
                @csrf

            @if ($errors->any())
                <div class="alert alert-danger">
                    <h4 class="alert-heading">{{ _gettext('An error has occurred') }}</h4>
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
                </div>
            @endif

                <div class="mb-3 required">
                    <label for="email">{{ _gettext('Email') }}</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
                </div>
                <div class="mb-3 required">
                    <label for="password">{{ _gettext('Password') }}</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>
                <div class="mb-3 required">
                    <label for="password_confirmation">{{ _gettext('Confirm Password') }}</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                </div>
                <div class="mb-3 required">
                    <label for="name">{{ _gettext('Full Name') }}</label>
                    <input type="text" class="form-control" name="name" id="name" value="{{ old('name') }}">
                </div>
                <div class="mb-3">
                    <label for="displayname">{{ _gettext('Display Name') }}</label>
                    <input type="text" class="form-control" name="displayname" id="displayname" value="{{ old('displayname') }}">
                    <div class="form-text">{{ _gettext('What do you want to be called on the site?  Leave blank if it is the same as Full Name.') }}</div>
                </div>
                <div class="mb-3 required">
                    <label for="bday">{{ _gettext('Birthday') }}</label>
                    <input type="date" class="form-control" id="bday" name="bday" value="{{ old('bday') }}">
                </div>

                <div class="text-end">
        @if(env('FCMS_AUTO_ACTIVATE'))
                    <input type="submit" class="btn btn-primary" value="{{ _gettext('Register') }}">
        @else
                    <input type="submit" class="btn btn-primary" value="{{ _gettext('Send Registration Request') }}">
        @endif
                </div>
            </form>
        </div>
    </main>
</body>
</html>
