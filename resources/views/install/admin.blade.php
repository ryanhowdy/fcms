@include('header')

<body id="install" class="bg-light">
    <main class="m-auto bg-white border p-5">
        <div>
            <img class="mb-5" src="{{ asset('img/logo.gif') }}">
            <div class="progress mb-3">
                <div class="progress-bar" style="width: {{ $progress }}%" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="alert alert-info">
                <h4 class="alert-heading">{{ __('3. Create Admin User') }}</h4>
                <p>{{ __('Everyone will be required to have an account and be logged in at all times to use this website.  This will help protect your site.') }}</p>
                <p>{{ __('You must have at least one administrative account.  Please fill out the information below for the person who will be the administrator of this site.') }}</p>
            </div>
            <form action="{{ route('install.admin') }}" method="post">
                @csrf
            @if ($errors->any())
<?php dump($errors); ?>
                <div class="alert alert-danger">
                    <h4 class="alert-heading">{{ __('An error has occurred') }}</h4>
                    <p>{{ __('Please fill out the required fields below.') }}</p>
                </div>
            @endif
                <div class="mb-3 required">
                    <label for="email">{{ __('Email') }}</label>
                    <input type="email" class="form-control" name="email" id="email" value="{{ old('email') }}">
                </div>
                <div class="mb-3 required">
                    <label for="password">{{ __('Password') }}</label>
                    <input type="password" class="form-control" name="password" id="password">
                </div>
                <div class="mb-3 required">
                    <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                    <input type="password" class="form-control" name="password_confirmation" id="password_confirmation">
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
                    <div class="d-flex flex-row">
                        <select class="form-select w-auto" id="bday" name="bday">
                            @foreach($days as $d => $val)
                            <option value="{{ $d }}" {{ old('bday') == $d ? 'selected' : '' }}>{{ $val }}</option>
                            @endforeach
                        </select>
                        <select class="form-select w-auto" id="bmonth" name="bmonth">
                            @foreach($months as $m => $val)
                            <option value="{{ $m }}" {{ old('bmonth') == $m ? 'selected' : '' }}>{{ $val }}</option>
                            @endforeach
                        </select>
                        <select class="form-select w-auto" id="byear" name="byear">
                            @foreach($years as $y => $val)
                            <option value="{{ $y }}" {{ old('byear') == $y ? 'selected' : '' }}>{{ $val }}</option>
                            @endforeach
                        </select>
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
