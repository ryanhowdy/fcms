@include('header')

<body id="@yield('body-id')">

@include('layouts.navigation')

    <div class="container-fluid">
        <div class="row flex-nowrap">

            @include('layouts.sidebar')

            <div class="col px-0 @yield('main-bg')">
                <main>

                @yield('content')

                </main>
            </div>
        </div><!-- /.row.flex-nowrap -->
    </div><!-- /.container-fluid -->

</body>
</html>