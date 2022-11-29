@extends('layouts.main')
@section('body-id', 'home')

@section('content')
<div class="d-flex flex-nowrap">
    <div class="col-auto col-3 mt-5">
        <ul class="addressbook list-unstyled">
    @foreach ($addresses as $letter => $a)
            <li class="header ps-4">{{ $letter }}</li>
        @foreach ($addresses[$letter] as $i => $address)
            <li class="p-4">
                <a data-id="{{ $address->id }}" href="#">{{ $address->lname }}, {{ $address->fname }}</a>
            </li>
        @endforeach
    @endforeach
        </ul>
    </div>
    <div class="col border-start min-vh-100">
        <div id="address-details" class="p-5">
            <ul class="list-unstyled d-none">
                <li id="address" class="d-none pb-5 d-flex align-items-center">
                    <div class="pe-5">
                        <div class="text-muted">{{ __('Address') }}</div>
                        <div id="street" class="fs-4"></div>
                        <span id="city"></span> <span id="state"></span> <span id="zip"></span>
                    </div>
                    <div class="flex-fill text-end">
                        <i class="fs-5 bg-info rounded-5 p-2 bi-house-door"></i>
                    </div>
                </li>
                <li id="cell" class="d-none pb-5 d-flex align-items-center">
                    <div class="pe-5">
                        <div class="text-muted">{{ __('Cell Phone') }}</div>
                        <span></span>
                    </div>
                    <div class="flex-fill text-end">
                        <i class="fs-5 bg-info rounded-5 p-2 bi-telephone"></i>
                    </div>
                </li>
                <li id="home" class="d-none pb-5 d-flex align-items-center">
                    <div class="pe-5">
                        <div class="text-muted">{{ __('Home Phone') }}</div>
                        <span></span>
                    </div>
                    <div class="flex-fill text-end">
                        <i class="fs-5 bg-info rounded-5 p-2 bi-telephone"></i>
                    </div>
                </li>
                <li id="work" class="d-none pb-5 d-flex align-items-center">
                    <div class="pe-5">
                        <div class="text-muted">{{ __('Work Phone') }}</div>
                        <span></span>
                    </div>
                    <div class="flex-fill text-end">
                        <i class="fs-5 bg-info rounded-5 p-2 bi-telephone"></i>
                    </div>
                </li>
            </ul>
        </div>
        <div id="address-map" class="p-5 border-top">
            <div id="map-error" class="alert alert-secondary d-none" role="alert">{{ __('A map view could not be created for this address.') }}</div>
            <iframe width="100%" height="500" src=""></iframe>
        </div>
    </div>
</div>
<style>
ul.addressbook li.header
{
    background: var(--bs-gray-200);
}
</style>
<script>
$(function() {
    $('ul.addressbook > li > a').click(function(e) {
        e.preventDefault();

        let id = $(this).data('id');
        let url = '{{ route('addressbook.show', ':id') }}';

        url = url.replace(':id', id);

        $.ajax({
            url         : url,
            dataType    : 'json',
        }).done(function(data) {
            $('#address-details > ul')
                .removeClass('d-none')
                .addClass('d-inline-block');

            // Show the address
            if (data.hasOwnProperty('hasAddress'))
            {
                $('li#address').removeClass('d-none');

                $('#street').text(data.address);
                $('#city').text(data.city);
                $('#state').text(data.state);
                $('#zip').text(data.zip);
            }

            // Show the phone numbers
            if (data.cell != null)
            {
                $('li#cell').removeClass('d-none');
                $('li#cell span').text(data.cell);
            }
            if (data.home != null)
            {
                $('li#home').removeClass('d-none');
                $('li#home span').text(data.home);
            }
            if (data.work != null)
            {
                $('li#work').removeClass('d-none');
                $('li#work span').text(data.work);
            }

            // Update the map
            if (data.hasOwnProperty('map'))
            {
                let iframeSource = 'https://maps.google.com/maps?q=';

                iframeSource += data.map;
                iframeSource += '&output=embed';

                $('#address-map > iframe').attr('src', iframeSource);
            }
            else
            {
                $('#map-error').removeClass('d-none');
            }
        }).fail(function() {
            alert('failed');
        });

    });
});
</script>
@endsection
