@include('layouts.header')

<body id="register" class="text-center bg-light">
    <main class="m-auto bg-white border p-5">
        <form action="{{ route('auth.register') }}" method="post">
            @csrf
            <img class="mb-5" src="{{ asset('img/logo.gif') }}">

            <div class="mb-3 text-start">
                <label for="name">{{ __('Name') }}</label>
                <input type="text" class="form-control" id="name" name="name">
            </div>
            <div class="mb-3 text-start">
                <label for="email">{{ __('Email') }}</label>
                <input type="email" class="form-control" id="email" name="email">
            </div>
            <div class="mb-3 text-start">
                <label for="password">{{ __('Password') }}</label>
                <input type="password" class="form-control" id="password" name="password">
            </div>
            <div class="mb-3 text-start">
                <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
            </div>

            <button class="w-100 btn btn-lg btn-primary" type="submit">{{ __('Register') }}</button>
        </form>
    </main>
</body>
</html>
