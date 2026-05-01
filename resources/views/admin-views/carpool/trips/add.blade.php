@extends('layouts.back-end.app')

@section('title', translate('add_trip'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="content container-fluid">

    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 d-flex gap-2 align-items-center">
            <i class="tio-map"></i>
            {{ translate('add_new_trip') }}
        </h2>
        <div class="ml-auto">
            <a href="{{ route('admin.carpool.trips.list') }}" class="btn btn-outline-primary">
                <i class="tio-arrow-back-ios"></i> {{ translate('back_to_trips') }}
            </a>
        </div>
    </div>

    {{-- How it works callout --}}
    <div class="alert alert-soft-info d-flex gap-2 mb-3">
        <i class="tio-info-outlined mt-1"></i>
        <div>
            <strong>{{ translate('how_carpool_works') }}:</strong>
            {{ translate('driver_adds_trip_info') }}
            → {{ translate('passengers_book_seats_info') }}
        </div>
    </div>

    <form action="{{ route('admin.carpool.trips.store') }}" method="POST" id="trip-form">
        @csrf
        <div class="row">
            <div class="col-lg-8">

                {{-- Driver --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="tio-user mr-1"></i> {{ translate('select_driver') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <label class="title-color">{{ translate('driver') }} <span class="text-danger">*</span></label>
                            <select name="driver_id" id="driver_id"
                                    class="js-select2-custom form-control @error('driver_id') is-invalid @enderror"
                                    required onchange="fillDriverVehicle(this)">
                                <option value="" disabled selected>{{ translate('select_a_driver') }}</option>
                                @foreach($drivers as $d)
                                    <option value="{{ $d->id }}"
                                            data-vehicle="{{ $d->vehicle_type }} · {{ $d->vehicle_number }}"
                                            data-capacity="{{ $d->vehicle_capacity }}"
                                            {{ old('driver_id') == $d->id ? 'selected' : '' }}>
                                        {{ $d->name }} ({{ $d->phone }}) — {{ $d->vehicle_number }}
                                    </option>
                                @endforeach
                            </select>
                            @error('driver_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        {{-- Vehicle info preview --}}
                        <div id="vehicle-info" class="mt-2 d-none">
                            <span class="badge badge-soft-info" id="vehicle-badge"></span>
                            <span class="text-muted small ml-2">{{ translate('max_seats') }}: <strong id="max-seats"></strong></span>
                        </div>
                    </div>
                </div>

                {{-- Route --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="tio-location-arrow mr-1"></i>
                            {{ translate('trip_route') }}
                            <small class="text-muted font-weight-normal ml-2">{{ translate('origin_to_destination') }}</small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">

                            {{-- Origin --}}
                            <div class="col-12 mb-2">
                                <div class="d-flex align-items-center gap-1 mb-2">
                                    <span class="badge badge-soft-success px-2 py-1">&#9679; {{ translate('from') }}</span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('origin_city_area') }} <span class="text-danger">*</span></label>
                                    <input type="text" id="origin_name_ac" name="origin_name"
                                           class="form-control @error('origin_name') is-invalid @enderror"
                                           value="{{ old('origin_name') }}"
                                           placeholder="{{ translate('search_origin_address') }}"
                                           autocomplete="off" required>
                                    <small class="text-muted"><i class="tio-map-outlined"></i> {{ translate('type_to_search_address') }}</small>
                                    @error('origin_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <input type="hidden" name="origin_lat" id="origin_lat" value="{{ old('origin_lat') }}" required>
                                <input type="hidden" name="origin_lng" id="origin_lng" value="{{ old('origin_lng') }}" required>
                            </div>

                            {{-- Destination --}}
                            <div class="col-12 mt-2 mb-2">
                                <div class="d-flex align-items-center gap-1 mb-2">
                                    <span class="badge badge-soft-danger px-2 py-1">&#9679; {{ translate('to') }}</span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('destination_city_area') }} <span class="text-danger">*</span></label>
                                    <input type="text" id="destination_name_ac" name="destination_name"
                                           class="form-control @error('destination_name') is-invalid @enderror"
                                           value="{{ old('destination_name') }}"
                                           placeholder="{{ translate('search_destination_address') }}"
                                           autocomplete="off" required>
                                    <small class="text-muted"><i class="tio-map-outlined"></i> {{ translate('type_to_search_address') }}</small>
                                    @error('destination_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <input type="hidden" name="destination_lat" id="destination_lat" value="{{ old('destination_lat') }}" required>
                                <input type="hidden" name="destination_lng" id="destination_lng" value="{{ old('destination_lng') }}" required>
                            </div>
                        </div>

                        {{-- Estimated info --}}
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="title-color">{{ translate('estimated_distance_km') }}</label>
                                    <input type="number" step="0.1" min="0" name="estimated_distance_km"
                                           class="form-control"
                                           value="{{ old('estimated_distance_km') }}"
                                           placeholder="{{ translate('optional') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="title-color">{{ translate('estimated_duration') }}</label>
                                    <div class="input-group">
                                        <input type="number" id="dur_hours" min="0" max="99"
                                               class="form-control text-center" placeholder="0"
                                               oninput="syncDurationHidden()">
                                        <div class="input-group-append input-group-prepend">
                                            <span class="input-group-text">h</span>
                                        </div>
                                        <input type="number" id="dur_mins" min="0" max="59"
                                               class="form-control text-center" placeholder="0"
                                               oninput="syncDurationHidden()">
                                        <div class="input-group-append">
                                            <span class="input-group-text">min</span>
                                        </div>
                                    </div>
                                    <input type="hidden" name="estimated_duration_min" id="estimated_duration_min_hidden">
                                    <small class="text-muted">{{ translate('auto_filled_from_map') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Schedule & Seats --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="tio-calendar mr-1"></i> {{ translate('schedule_and_seats') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('departure_date_time') }} <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="departure_at"
                                           class="form-control @error('departure_at') is-invalid @enderror"
                                           value="{{ old('departure_at') }}" required>
                                    @error('departure_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('ride_type') }} <span class="text-danger">*</span></label>
                                    <select name="ride_type" class="js-select2-custom form-control" required>
                                        <option value="scheduled" {{ old('ride_type', 'scheduled') == 'scheduled' ? 'selected' : '' }}>
                                            {{ translate('scheduled') }}
                                        </option>
                                        <option value="instant" {{ old('ride_type') == 'instant' ? 'selected' : '' }}>
                                            {{ translate('instant') }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="title-color">
                                        {{ translate('available_seats') }} <span class="text-danger">*</span>
                                        <small class="text-muted" id="seat-hint"></small>
                                    </label>
                                    <input type="number" name="total_seats" id="total_seats"
                                           class="form-control @error('total_seats') is-invalid @enderror"
                                           value="{{ old('total_seats', 3) }}" min="1" max="50" required>
                                    @error('total_seats')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <small class="text-muted">{{ translate('seats_available_for_passengers') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pricing & Note --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="tio-money mr-1"></i> {{ translate('pricing') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('price_per_seat') }} <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ config('carpool.currency', 'INR') }}</span>
                                        </div>
                                        <input type="number" step="0.01" min="0" name="price_per_seat"
                                               id="price_per_seat"
                                               class="form-control @error('price_per_seat') is-invalid @enderror"
                                               value="{{ old('price_per_seat') }}"
                                               placeholder="0.00" required
                                               oninput="calcTotal()">
                                        @error('price_per_seat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <small class="text-muted">{{ translate('charged_per_seat_booked') }}</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('full_booking_value') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ config('carpool.currency', 'INR') }}</span>
                                        </div>
                                        <input type="text" id="total_value" class="form-control bg-light" readonly
                                               placeholder="—">
                                    </div>
                                    <small class="text-muted">{{ translate('if_all_seats_booked') }}</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-0">
                                    <label class="title-color">{{ translate('note_for_passengers') }}</label>
                                    <textarea name="note" class="form-control" rows="2"
                                              placeholder="{{ translate('any_instructions_or_info') }}">{{ old('note') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Right: Summary card --}}
            <div class="col-lg-4">
                <div class="card mb-3 border-primary">
                    <div class="card-header bg-soft-primary">
                        <h5 class="mb-0">{{ translate('trip_summary') }}</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted pl-0">{{ translate('driver') }}</td>
                                <td class="font-weight-bold" id="sum-driver">—</td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-0">{{ translate('from') }}</td>
                                <td id="sum-origin">—</td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-0">{{ translate('to') }}</td>
                                <td id="sum-dest">—</td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-0">{{ translate('seats') }}</td>
                                <td id="sum-seats">—</td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-0">{{ translate('price_seat') }}</td>
                                <td id="sum-price">—</td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-0"><i class="tio-map-outlined mr-1"></i>{{ translate('distance') }}</td>
                                <td id="sum-distance">—</td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-0"><i class="tio-time mr-1"></i>{{ translate('duration') }}</td>
                                <td id="sum-duration">—</td>
                            </tr>
                            <tr class="border-top">
                                <td class="pl-0 font-weight-bold">{{ translate('max_earnings') }}</td>
                                <td class="font-weight-bold text-success" id="sum-total">—</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn--primary btn-block">
                            <i class="tio-save mr-1"></i> {{ translate('publish_trip') }}
                        </button>
                        <a href="{{ route('admin.carpool.trips.list') }}" class="btn btn-outline-danger btn-block mt-2">
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
$(document).ready(function () {
    $('.js-select2-custom').select2();
    $('input, select').on('input change', updateSummary);
    updateSummary();
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
            calcDistanceAndDuration();
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') e.preventDefault();
        });
    }
    attachAutocomplete('origin_name_ac',      'origin_lat',      'origin_lng');
    attachAutocomplete('destination_name_ac', 'destination_lat', 'destination_lng');
}

function calcDistanceAndDuration() {
    var oLat = document.getElementById('origin_lat').value;
    var oLng = document.getElementById('origin_lng').value;
    var dLat = document.getElementById('destination_lat').value;
    var dLng = document.getElementById('destination_lng').value;
    if (!oLat || !oLng || !dLat || !dLng) return;

    setText('sum-distance', '...');
    setText('sum-duration',  '...');

    var service = new google.maps.DistanceMatrixService();
    service.getDistanceMatrix({
        origins:      [new google.maps.LatLng(parseFloat(oLat), parseFloat(oLng))],
        destinations: [new google.maps.LatLng(parseFloat(dLat), parseFloat(dLng))],
        travelMode:   google.maps.TravelMode.DRIVING,
        unitSystem:   google.maps.UnitSystem.METRIC,
    }, function (response, status) {
        if (status === 'OK') {
            var el = response.rows[0].elements[0];
            if (el.status === 'OK') {
                var distKm = (el.distance.value / 1000).toFixed(1);
                var durMin = Math.round(el.duration.value / 60);
                document.querySelector('[name="estimated_distance_km"]').value = distKm;
                document.querySelector('[name="estimated_duration_min"]').value = durMin;
                var h = Math.floor(durMin / 60);
                var m = durMin % 60;
                document.getElementById('dur_hours').value = h;
                document.getElementById('dur_mins').value  = m;
                document.getElementById('estimated_duration_min_hidden').value = durMin;
                setText('sum-distance', distKm + ' km');
                setText('sum-duration',  toHoursMin(durMin));
            }
        }
    });
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAP_API_KEY') }}&libraries=places&callback=initCarPoolMaps" async defer></script>
<script>
function fillDriverVehicle(sel) {
    var opt      = sel.options[sel.selectedIndex];
    var vehicle  = opt.dataset.vehicle  || '';
    var capacity = opt.dataset.capacity || '';
    document.getElementById('vehicle-badge').textContent = vehicle;
    document.getElementById('max-seats').textContent     = capacity;
    document.getElementById('seat-hint').textContent     = capacity ? '(max: ' + capacity + ')' : '';
    document.getElementById('vehicle-info').classList.remove('d-none');
    updateSummary();
}

function calcTotal() {
    var price = parseFloat(document.getElementById('price_per_seat').value) || 0;
    var seats  = parseInt(document.getElementById('total_seats').value)    || 0;
    var total  = price * seats;
    document.getElementById('total_value').value = total > 0 ? total.toFixed(2) : '';
}

function updateSummary() {
    calcTotal();
    var driverSel = document.getElementById('driver_id');
    var driverTxt = driverSel.value ? driverSel.options[driverSel.selectedIndex].text : '—';
    setText('sum-driver', driverTxt);
    setText('sum-origin', val('origin_name')      || '—');
    setText('sum-dest',   val('destination_name') || '—');
    var seats = val('total_seats');
    setText('sum-seats', seats ? seats + ' seats' : '—');
    var price = val('price_per_seat');
    var cur   = '{{ config("carpool.currency", "INR") }}';
    setText('sum-price', price ? cur + ' ' + parseFloat(price).toFixed(2) : '—');
    var tv = document.getElementById('total_value').value;
    setText('sum-total', tv ? cur + ' ' + tv : '—');
    var dist = val('estimated_distance_km');
    var dur  = document.getElementById('estimated_duration_min_hidden').value;
    if (dist) setText('sum-distance', dist + ' km');
    if (dur)  setText('sum-duration',  toHoursMin(parseInt(dur)));
}

function syncDurationHidden() {
    var h = parseInt(document.getElementById('dur_hours').value) || 0;
    var m = parseInt(document.getElementById('dur_mins').value)  || 0;
    var total = h * 60 + m;
    document.getElementById('estimated_duration_min_hidden').value = total > 0 ? total : '';
    setText('sum-duration', toHoursMin(total));
}

function toHoursMin(minutes) {
    if (!minutes || minutes <= 0) return '—';
    var h = Math.floor(minutes / 60);
    var m = minutes % 60;
    if (h > 0 && m > 0) return h + 'h ' + m + 'min';
    if (h > 0)          return h + 'h';
    return m + 'min';
}

function val(name) {
    var el = document.querySelector('[name="' + name + '"]');
    return el ? el.value.trim() : '';
}
function setText(id, txt) {
    var el = document.getElementById(id);
    if (el) el.textContent = txt;
}
</script>
@endpush
