@extends('layouts.back-end.app')

@section('title', translate('add_booking'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="content container-fluid">

    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 d-flex gap-2 align-items-center">
            <i class="tio-receipt-outlined"></i>
            {{ translate('add_new_booking') }}
        </h2>
        <div class="ml-auto">
            <a href="{{ route('admin.carpool.bookings.list') }}" class="btn btn-outline-primary">
                <i class="tio-arrow-back-ios"></i> {{ translate('back_to_bookings') }}
            </a>
        </div>
    </div>

    <form action="{{ route('admin.carpool.bookings.store') }}" method="POST" id="booking-form">
        @csrf
        <div class="row">
            <div class="col-lg-8">

                {{-- Step 1: Select Trip --}}
                <div class="card mb-3">
                    <div class="card-header bg-soft-primary">
                        <h5 class="mb-0">
                            <span class="badge badge-primary mr-2">1</span>
                            {{ translate('select_trip') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <label class="title-color">{{ translate('available_trips') }} <span class="text-danger">*</span></label>
                            <select name="route_id" id="route_id"
                                    class="js-select2-custom form-control @error('route_id') is-invalid @enderror"
                                    required onchange="onTripChange(this)">
                                <option value="" disabled {{ !$selectedTrip ? 'selected' : '' }}>
                                    {{ translate('select_a_trip') }}
                                </option>
                                @foreach($trips as $trip)
                                    <option value="{{ $trip->id }}"
                                            data-origin="{{ $trip->origin_name }}"
                                            data-dest="{{ $trip->destination_name }}"
                                            data-departure="{{ $trip->departure_at ? $trip->departure_at->format('d M Y H:i') : '' }}"
                                            data-seats="{{ $trip->available_seats }}"
                                            data-price="{{ $trip->price_per_seat }}"
                                            data-currency="{{ $trip->currency }}"
                                            data-driver="{{ $trip->driver ? $trip->driver->name : '' }}"
                                            {{ (old('route_id', $selectedTrip?->id)) == $trip->id ? 'selected' : '' }}>
                                        {{ $trip->origin_name }} → {{ $trip->destination_name }}
                                        ({{ $trip->departure_at ? $trip->departure_at->format('d M H:i') : '' }})
                                        — {{ $trip->available_seats }} {{ translate('seats_left') }}
                                        @ {{ $trip->price_per_seat }} {{ $trip->currency }}/{{ translate('seat') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('route_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Trip Info Banner --}}
                        <div id="trip-info" class="{{ $selectedTrip ? '' : 'd-none' }} mt-3 p-3 rounded bg-soft-info">
                            <div class="row text-sm">
                                <div class="col-6 col-md-3">
                                    <div class="text-muted small">{{ translate('from') }}</div>
                                    <div class="font-weight-bold" id="ti-origin">
                                        {{ $selectedTrip?->origin_name ?? '' }}
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="text-muted small">{{ translate('to') }}</div>
                                    <div class="font-weight-bold" id="ti-dest">
                                        {{ $selectedTrip?->destination_name ?? '' }}
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="text-muted small">{{ translate('departure') }}</div>
                                    <div class="font-weight-bold" id="ti-departure">
                                        {{ $selectedTrip?->departure_at?->format('d M Y H:i') ?? '' }}
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="text-muted small">{{ translate('driver') }}</div>
                                    <div class="font-weight-bold" id="ti-driver">
                                        {{ $selectedTrip?->driver?->name ?? '' }}
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 d-flex gap-3 align-items-center">
                                <span class="badge badge-soft-success" id="ti-seats">
                                    {{ $selectedTrip?->available_seats ?? '' }} {{ translate('seats_available') }}
                                </span>
                                <span class="font-weight-bold text-primary" id="ti-price">
                                    {{ $selectedTrip ? $selectedTrip->currency . ' ' . $selectedTrip->price_per_seat : '' }}
                                    {{ $selectedTrip ? '/ ' . translate('seat') : '' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 2: Passenger --}}
                <div class="card mb-3">
                    <div class="card-header bg-soft-info">
                        <h5 class="mb-0">
                            <span class="badge badge-info mr-2">2</span>
                            {{ translate('select_passenger') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <label class="title-color">{{ translate('customer') }} <span class="text-danger">*</span></label>
                            <select name="passenger_id" id="passenger_id"
                                    class="js-select2-custom form-control @error('passenger_id') is-invalid @enderror"
                                    required>
                                <option value="" disabled selected>{{ translate('search_customer_by_name_phone') }}</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}"
                                            {{ old('passenger_id') == $c->id ? 'selected' : '' }}>
                                        {{ trim($c->f_name . ' ' . $c->l_name) }}
                                        @if($c->phone) ({{ $c->phone }})@endif
                                        @if($c->email) — {{ $c->email }}@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('passenger_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Step 3: Pickup & Drop --}}
                <div class="card mb-3">
                    <div class="card-header bg-soft-warning">
                        <h5 class="mb-0">
                            <span class="badge badge-warning text-dark mr-2">3</span>
                            {{ translate('pickup_and_drop_points') }}
                            <small class="font-weight-normal text-muted ml-2">
                                {{ translate('passenger_specific_points') }}
                            </small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-1">
                                <span class="badge badge-soft-success">&#9679; {{ translate('pickup') }}</span>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('pickup_area_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" id="pickup_name_ac" name="pickup_name"
                                           class="form-control @error('pickup_name') is-invalid @enderror"
                                           value="{{ old('pickup_name') }}"
                                           placeholder="{{ translate('search_pickup_address') }}"
                                           autocomplete="off" required>
                                    <small class="text-muted"><i class="tio-map-outlined"></i> {{ translate('type_to_search_address') }}</small>
                                    @error('pickup_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <input type="hidden" name="pickup_lat" id="pickup_lat" value="{{ old('pickup_lat') }}" required>
                                <input type="hidden" name="pickup_lng" id="pickup_lng" value="{{ old('pickup_lng') }}" required>
                            </div>

                            <div class="col-12 mt-1 mb-1">
                                <span class="badge badge-soft-danger">&#9679; {{ translate('drop') }}</span>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group mb-0">
                                    <label class="title-color">{{ translate('drop_area_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" id="drop_name_ac" name="drop_name"
                                           class="form-control @error('drop_name') is-invalid @enderror"
                                           value="{{ old('drop_name') }}"
                                           placeholder="{{ translate('search_drop_address') }}"
                                           autocomplete="off" required>
                                    <small class="text-muted"><i class="tio-map-outlined"></i> {{ translate('type_to_search_address') }}</small>
                                    @error('drop_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <input type="hidden" name="drop_lat" id="drop_lat" value="{{ old('drop_lat') }}" required>
                                <input type="hidden" name="drop_lng" id="drop_lng" value="{{ old('drop_lng') }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 4: Seats & Payment --}}
                <div class="card mb-3">
                    <div class="card-header bg-soft-success">
                        <h5 class="mb-0">
                            <span class="badge badge-success mr-2">4</span>
                            {{ translate('seats_and_payment') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('number_of_seats') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="seat_count" id="seat_count"
                                           class="form-control @error('seat_count') is-invalid @enderror"
                                           value="{{ old('seat_count', 1) }}"
                                           min="1" max="20" required
                                           oninput="calcFare()">
                                    @error('seat_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('total_fare') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="fare-currency">—</span>
                                        </div>
                                        <input type="text" id="fare-display"
                                               class="form-control bg-light font-weight-bold text-success" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('payment_status') }}</label>
                                    <select name="payment_status" class="js-select2-custom form-control">
                                        <option value="unpaid"  {{ old('payment_status', 'unpaid') == 'unpaid' ? 'selected' : '' }}>{{ translate('unpaid') }}</option>
                                        <option value="paid"    {{ old('payment_status') == 'paid'   ? 'selected' : '' }}>{{ translate('paid') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="title-color">{{ translate('payment_method') }}</label>
                                    <input type="text" name="payment_method" class="form-control"
                                           value="{{ old('payment_method') }}"
                                           placeholder="{{ translate('cash_wallet_card_etc') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Sidebar Summary --}}
            <div class="col-lg-4">
                <div class="card mb-3 border-success">
                    <div class="card-header bg-soft-success">
                        <h5 class="mb-0">{{ translate('booking_summary') }}</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted pl-0">{{ translate('trip') }}</td>
                                <td id="bs-trip" class="font-weight-bold">—</td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-0">{{ translate('departure') }}</td>
                                <td id="bs-departure">—</td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-0">{{ translate('pickup') }}</td>
                                <td id="bs-pickup">—</td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-0">{{ translate('drop') }}</td>
                                <td id="bs-drop">—</td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-0">{{ translate('seats') }}</td>
                                <td id="bs-seats">—</td>
                            </tr>
                            <tr class="border-top">
                                <td class="font-weight-bold pl-0">{{ translate('total_fare') }}</td>
                                <td class="font-weight-bold text-success" id="bs-fare">—</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn--primary btn-block">
                            <i class="tio-receipt-outlined mr-1"></i> {{ translate('confirm_booking') }}
                        </button>
                        <a href="{{ route('admin.carpool.bookings.list') }}" class="btn btn-outline-danger btn-block mt-2">
                            {{ translate('cancel') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('script')
<script src="{{ dynamicAsset(path: 'public/assets/select2/js/select2.min.js') }}"></script>
<script>
var tripData = {};

$(document).ready(function () {
    $('.js-select2-custom').select2();
    updateSummary();

    var sel = document.getElementById('route_id');
    if (sel.value) { onTripChange(sel); }
});

/* ── Google Places Autocomplete ── */
function initCarPoolMaps() {
    function attachAutocomplete(inputId, latId, lngId) {
        var input = document.getElementById(inputId);
        if (!input) return;
        var ac = new google.maps.places.Autocomplete(input, { types: ['geocode'] });
        ac.addListener('place_changed', function () {
            var place = ac.getPlace();
            if (!place.geometry) return;
            document.getElementById(latId).value = place.geometry.location.lat();
            document.getElementById(lngId).value = place.geometry.location.lng();
            input.value = place.formatted_address || place.name;
            updateSummary();
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') e.preventDefault();
        });
    }
    attachAutocomplete('pickup_name_ac', 'pickup_lat', 'pickup_lng');
    attachAutocomplete('drop_name_ac',   'drop_lat',   'drop_lng');
}

function onTripChange(sel) {
    var opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;

    tripData = {
        origin:    opt.dataset.origin    || '',
        dest:      opt.dataset.dest      || '',
        departure: opt.dataset.departure || '',
        seats:     parseInt(opt.dataset.seats) || 0,
        price:     parseFloat(opt.dataset.price) || 0,
        currency:  opt.dataset.currency  || '',
        driver:    opt.dataset.driver    || '',
    };

    // Show trip info banner
    setText('ti-origin',    tripData.origin);
    setText('ti-dest',      tripData.dest);
    setText('ti-departure', tripData.departure);
    setText('ti-driver',    tripData.driver);
    setText('ti-seats',     tripData.seats + ' {{ translate("seats_available") }}');
    setText('ti-price',     tripData.currency + ' ' + tripData.price.toFixed(2) + ' / {{ translate("seat") }}');
    document.getElementById('trip-info').classList.remove('d-none');
    document.getElementById('fare-currency').textContent = tripData.currency;

    // Clamp seat_count to available seats
    var seatInput = document.getElementById('seat_count');
    if (parseInt(seatInput.value) > tripData.seats) {
        seatInput.value = tripData.seats;
    }
    seatInput.max = tripData.seats;

    calcFare();
    updateSummary();
}

function calcFare() {
    var seats = parseInt(document.getElementById('seat_count').value) || 0;
    var fare  = seats * (tripData.price || 0);
    document.getElementById('fare-display').value = fare > 0 ? fare.toFixed(2) : '';
    updateSummary();
}

function updateSummary() {
    var origin = (tripData.origin || '') + (tripData.dest ? ' → ' + tripData.dest : '');
    setText('bs-trip',      origin || '—');
    setText('bs-departure', tripData.departure || '—');
    setText('bs-pickup',    val('pickup_name') || '—');
    setText('bs-drop',      val('drop_name')   || '—');
    var seats = val('seat_count');
    setText('bs-seats', seats ? seats + ' {{ translate("seats") }}' : '—');
    var fare = document.getElementById('fare-display')?.value;
    setText('bs-fare', fare ? (tripData.currency || '') + ' ' + fare : '—');
}

$('input[name="pickup_name"], input[name="drop_name"], input[name="seat_count"]').on('input', updateSummary);

function val(name) {
    var el = document.querySelector('[name="' + name + '"]');
    return el ? el.value.trim() : '';
}
function setText(id, txt) {
    var el = document.getElementById(id);
    if (el) el.textContent = txt;
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAP_API_KEY') }}&libraries=places&callback=initCarPoolMaps" async defer></script>
@endpush
