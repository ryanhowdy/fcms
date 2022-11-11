
<div id="sidebar" class="col-auto col-xxl-2 d-none d-sm-block border-end min-vh-100">
    <div class="d-flex flex-column align-items-center align-items-sm-start px-3 py-4">
    @foreach($links as $g => $values)
        <ul class="nav nav-pills flex-column align-items-center align-items-sm-start mb-3 w-100">
        @foreach($values as $i => $link)
            @isset($link['route_name'])
            <li class="nav-item link w-100">
                <a class="nav-link align-middle w-100" href="{{ route($link['route_name']) }}">
                    <i class="bi-{{ $link['icon'] }}"></i><span class="ms-3 d-none d-xxl-inline">{{ $link['link'] }}</span>
                </a>
            </li>
            @else
            <li class="nav-item header d-none d-xxl-block">{{ $link['link'] }}</li>
            @endif
        @endforeach
        </ul>
    @endforeach
    </div>
</div><!-- /#sidebar -->
