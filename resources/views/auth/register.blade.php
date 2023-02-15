@include('layouts.header')

<body id="register" class="bg-light">
    <main class="m-auto bg-white border p-5">
        <div>
            <img class="mb-5" src="{{ asset('img/logo.gif') }}">

        @if(!env('FCMS_AUTO_ACTIVATE'))
            <div class="alert alert-info">
                <h4 class="alert-heading">{{ __('Request Access') }}</h4>
                <p>{{ __('In order to login and begin using the site, your administrator must activate your account.') }}</p>
                <p>{{ __('Please fill out the form below to request access.') }}</p>
            </div>
        @endif

            <form action="{{ route('auth.register') }}" method="post">
                @csrf

            @if ($errors->any())
                <div class="alert alert-danger">
                    <h4 class="alert-heading">{{ __('An error has occurred') }}</h4>
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
                </div>
            @endif

                <div class="mb-3 required">
                    <label for="email">{{ __('Email') }}</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
                </div>
                <div class="mb-3 required">
                    <label for="password">{{ __('Password') }}</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>
                <div class="mb-3 required">
                    <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                </div>
                <div class="mb-3 row">
                    <div class="col required">
                        <label for="fname">{{ __('First Name') }}</label>
                        <input type="text" class="form-control" name="fname" id="fname" value="{{ old('fname') }}">
                    </div>
                    <div class="col">
                        <label for="lname">{{ __('Last Name') }}</label>
                        <input type="text" class="form-control" name="lname" id="lname" value="{{ old('lname') }}">
                    </div>
                </div>
                <div class="mb-3 required">
                    <label for="bday">{{ __('Birthday') }}</label>
                    <input type="date" class="form-control" id="bday" name="bday" value="{{ old('bday') }}">
                </div>

                <div class="text-end">
        @if(env('FCMS_AUTO_ACTIVATE'))
                    <input type="submit" class="btn btn-primary" value="{{ __('Register') }}">
        @else
                    <input type="submit" class="btn btn-primary" value="{{ __('Send Registration Request') }}">
        @endif
                </div>
            </form>
        </div>
    </main>
</body>
</html>
