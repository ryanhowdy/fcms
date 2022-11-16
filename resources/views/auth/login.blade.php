@include('header')

<body id="login" class="text-center bg-light">
    <main class="m-auto bg-white border p-5">
        <form action="{{ route('login') }}" method="post">
            @csrf
            <img class="mb-5" src="{{ asset('img/logo.gif') }}">

            <div class="form-floating">
                <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com">
                <label for="email">Email</label>
            </div>

            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="{{ __('Password') }}">
                <label for="password">Password</label>
            </div>

            <div class="checkbox mb-3">
                <label>
                    <input type="checkbox" class="me-1" name="remember-me" value="1">Remember Me
                </label>
            </div>

            <button class="w-100 btn btn-lg btn-primary" type="submit">Sign In</button>
        </form>
        <p class="mt-3">
            <a href="{{ route('auth.password.request') }}">{{ __('Forgot Password') }}</a>
            @if ($registrationOn)
            | <a href="{{ route('auth.register') }}">Register</a>
            @endif
        </p>
    </main>
</body>
</html>