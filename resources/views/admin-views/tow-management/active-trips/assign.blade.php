@extends('layouts.back-end.app')

@section('title', translate('assign_provider'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <a href="{{ route('admin.tow-management.requests.list') }}" class="btn btn-outline-primary">
                <i class="tio-arrow-backward"></i> {{ translate('back_to_requests') }}
            </a>
            <h2 class="h1 mb-0 d-flex gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/active-trips.png') }}" alt="">
                {{ translate('assign_provider') }}
            </h2>
        </div>

        <div class="row g-3">
            <!-- Request Details -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">{{ translate('request_details') }}</h4>
                        <span class="badge badge-{{ $request->priority == 'emergency' ? 'danger' : 'info' }}">
                            {{ translate($request->priority) }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img src="{{ $request->customer->image_full_url['path'] ?? dynamicAsset('public/assets/back-end/img/customer.png') }}"
                                 class="rounded-circle" width="60" height="60" alt="">
                            <div>
                                <h5 class="mb-1">{{ $request->customer->f_name }} {{ $request->customer->l_name }}</h5>
                                <p class="mb-0 text-muted">{{ $request->customer->phone }}</p>
                            </div>
                        </div>

                        <table class="table table-borderless">
                            <tr>
                                <td width="40%">{{ translate('service_type') }}:</td>
                                <td><strong>{{ translate(str_replace('_', ' ', $request->service_type)) }}</strong></td>
                            </tr>
                            <tr>
                                <td>{{ translate('pickup_location') }}:</td>
                                <td><small>{{ $request->pickup_location }}</small></td>
                            </tr>
                            <tr>
                                <td>{{ translate('destination') }}:</td>
                                <td><small>{{ $request->destination ?? translate('not_specified') }}</small></td>
                            </tr>
                            <tr>
                                <td>{{ translate('estimated_price') }}:</td>
                                <td><strong>${{ number_format($request->estimated_price, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td>{{ translate('created_at') }}:</td>
                                <td><small>{{ $request->created_at->format('M d, Y H:i') }}</small></td>
                            </tr>
                        </table>

                        @if($request->description)
                            <div class="mt-3 p-3 bg-light rounded">
                                <small class="text-muted">{{ translate('description') }}:</small>
                                <p class="mb-0">{{ $request->description }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Assign Form -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">{{ translate('select_provider') }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.tow-management.active-trips.assign') }}" method="POST">
                            @csrf
                            <input type="hidden" name="request_id" value="{{ $request->id }}">

                            <!-- Provider Selection -->
                            <div class="form-group mb-4">
                                <label class="title-color h5">{{ translate('available_providers') }} <span class="input-required-icon">*</span></label>
                                <select class="js-select2-custom form-control" name="provider_id" id="provider-select" required>
                                    <option value="" selected disabled>{{ translate('select_provider') }}</option>
                                    @foreach($providers as $provider)
                                        <option value="{{ $provider->id }}" 
                                                data-lat="{{ $provider->current_latitude }}"
                                                data-lng="{{ $provider->current_longitude }}"
                                                data-rating="{{ $provider->rating }}"
                                                data-distance="{{ $providerService->calculateDistance(
                                                    $request->pickup_latitude,
                                                    $request->pickup_longitude,
                                                    $provider->current_latitude,
                                                    $provider->current_longitude
                                                ) }}">
                                            {{ $provider->company_name }} - ⭐ {{ number_format($provider->rating, 1) }}
                                            ({{ $provider->availability_slots }} {{ translate('slots') }})
                                            @if($provider->current_latitude)
                                                - {{ $providerService->calculateDistance(
                                                    $request->pickup_latitude,
                                                    $request->pickup_longitude,
                                                    $provider->current_latitude,
                                                    $provider->current_longitude
                                                ) }} km
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Provider Preview Card -->
                            <div id="provider-preview" class="card bg-light mb-4 d-none">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">{{ translate('selected_provider') }}</h5>
                                        <span id="preview-distance" class="badge badge-info"></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <img id="preview-image" src="" class="rounded-circle" width="50" height="50" alt="">
                                        <div>
                                            <h6 id="preview-name" class="mb-1"></h6>
                                            <p id="preview-rating" class="mb-0"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Trip Details -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="title-color">{{ translate('estimated_arrival_minutes') }}</label>
                                        <input type="number" name="estimated_arrival_minutes" class="form-control" 
                                               id="estimated-arrival" min="1" max="120" 
                                               placeholder="{{ translate('enter_minutes') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="title-color">{{ translate('distance_estimate_km') }}</label>
                                        <input type="number" name="distance_estimate" class="form-control" 
                                               id="distance-estimate" step="0.1" min="0" 
                                               placeholder="{{ translate('enter_distance') }}">
                                    </div>
                                </div>
                            </div>

                            <!-- Map Preview -->
                            <div class="mb-4">
                                <div id="preview-map" style="height: 300px; width: 100%; border-radius: 8px;"></div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.tow-management.requests.list') }}" class="btn btn-secondary">
                                    {{ translate('cancel') }}
                                </a>
                                <button type="submit" class="btn btn--primary px-5">
                                    <i class="tio-checkmark-circle"></i> {{ translate('assign_provider') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden data -->
    <span id="pickup-lat" data-value="{{ $request->pickup_latitude }}"></span>
    <span id="pickup-lng" data-value="{{ $request->pickup_longitude }}"></span>
    <span id="mapbox-token" data-value="{{ env('MAPBOX_API_KEY') }}"></span>
@endsection

@push('script')
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.js"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/select2/js/select2.min.js') }}"></script>
    <script>
        let map, pickupMarker;
        let pickupLat = parseFloat($('#pickup-lat').data('value'));
        let pickupLng = parseFloat($('#pickup-lng').data('value'));

        function initMap() {
            mapboxgl.accessToken = $('#mapbox-token').data('value');
            
            map = new mapboxgl.Map({
                container: 'preview-map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [pickupLng || -74.0060, pickupLat || 40.7128],
                zoom: 10
            });

            // Add pickup marker
            if (pickupLat && pickupLng) {
                pickupMarker = new mapboxgl.Marker({ color: '#28a745' })
                    .setLngLat([pickupLng, pickupLat])
                    .setPopup(new mapboxgl.Popup().setHTML('<b>{{ translate("pickup") }}</b>'))
                    .addTo(map);
            }
        }

        function updateProviderPreview() {
            let selected = $('#provider-select').find(':selected');
            if (!selected.val()) return;

            let lat = selected.data('lat');
            let lng = selected.data('lng');
            let distance = selected.data('distance');
            let rating = selected.data('rating');

            $('#preview-name').text(selected.text().split(' - ')[0]);
            $('#preview-rating').text('⭐ ' + rating.toFixed(1));
            $('#preview-distance').text(distance + ' km');
            $('#estimated-arrival').val(Math.ceil(distance / 0.5)); // Rough estimate: 30 km/h average speed
            $('#distance-estimate').val(distance.toFixed(1));
            
            $('#provider-preview').removeClass('d-none');

            // Update map with provider location
            if (lat && lng) {
                if (window.providerMarker) {
                    window.providerMarker.remove();
                }
                
                window.providerMarker = new mapboxgl.Marker({ color: '#007bff' })
                    .setLngLat([lng, lat])
                    .setPopup(new mapboxgl.Popup().setHTML('<b>{{ translate("provider") }}</b>'))
                    .addTo(map);

                // Fit bounds to show both markers
                let bounds = new mapboxgl.LngLatBounds();
                bounds.extend([pickupLng, pickupLat]);
                bounds.extend([lng, lat]);
                map.fitBounds(bounds, { padding: 50 });
            }
        }

        $(document).ready(function() {
            initMap();
            
            $('#provider-select').on('change', function() {
                updateProviderPreview();
            });

            // Initialize select2
            $('.js-select2-custom').select2({
                width: '100%',
                placeholder: '{{ translate("search_providers") }}'
            });
        });
    </script>
@endpush