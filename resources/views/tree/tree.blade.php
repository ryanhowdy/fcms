
<li class="rel mx-auto text-center">
    <div class="d-inline-flex">
        @include('tree.person', ['person' => $person])
        @isset($person['spouses'])
            @foreach ($person['spouses'] as $s)
                @include('tree.person', ['person' => $s])
            @endforeach
        @endisset
    </div>
    @isset($person['kids'])
        <ul class="list-unstyled">
        @foreach ($person['kids'] as $k)
            {{-- magic recursion --}}
            @include('tree.tree', ['person' => $k])
        @endforeach
        </ul>
    @endisset
</li>
