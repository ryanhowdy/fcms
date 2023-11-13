
    <div class="row">
        <div class="col-5">
            <h3 class="fw-normal mb-2">{{ $event['title'] }}</h3>
            <div class="text-muted pb-2">
                <i class="bi-calendar px-2"></i>
                {{ $event['dateFormatted'] }}
            </div>
            <div class="text-muted pb-2">
                <i class="bi-clock px-2"></i>
                {{ $event['timeStartFormatted'] }}
            </div>
        @if(!is_null($event['desc']))
            <div class="pb-3">
                {{ $event['desc'] }}
            </div>
        @endif
        </div><!-- /.col-5 -->

        <div class="col-7">
            <div class="row g-2">
                <div class="col-6 col-xl-3">
                    <div class="bg-success p-3 rounded h-100">
                        <h1 class="text-white">{{ $counts['attending'] }}</h1>
                        <div class="text-white">{{ _gettext('Attending') }}</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="bg-primary p-3 rounded h-100">
                        <h1 class="text-white">{{ $counts['maybe'] }}</h1>
                        <div class="text-white">{{ _gettext('Maybe') }}</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="bg-danger p-3 rounded h-100">
                        <h1 class="text-white">{{ $counts['no'] }}</h1>
                        <div class="text-white">{{ _gettext('No') }}</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="bg-secondary p-3 rounded h-100">
                        <h1 class="text-white">{{ $counts['none'] }}</h1>
                        <div class="text-white">{{ _gettext('No Response') }}</div>
                    </div>
                </div>
            </div>
        </div><!-- /.col-7 -->
    </div><!-- /.row -->

