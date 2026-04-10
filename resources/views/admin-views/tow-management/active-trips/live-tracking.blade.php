@extends('layouts.back-end.app')

@section('title', translate('live_tracking') . ' #' . $activeTrip->id)

@push('css_or_js')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.css" rel="stylesheet">
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-draw/v1.3.0/mapbox-gl-draw.css" rel="stylesheet">
    <style>
        #live-map { height: calc(100vh - 150px); width: 100%; }
        .tracking-sidebar { position: absolute; top: 20px; right: 20px; width: 350px; z-index: 10; }
        .speedometer { width: 100%; height: 100px; background: linear-gradient(90deg, #28a745, #ffc107, #dc3545); border-radius: 50px; position: relative; margin: 20px 0; }
        .speed-needle { width: 4px; height: 80px; background: #333; position: absolute; bottom: 10px; left: 50%; transform-origin: bottom center; transition: transform 0.3s; }
    </style>
@endpush

@section('content')
    <div class="content p-0">
        <!-- Live Map -->
        <div id="live-map"></div>

        <!-- Tracking Info Sidebar -->
        <div class="tracking-sidebar">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ translate('live_tracking') }}</h4>
                    <a href="{{ route('admin.tow-management.active-trips.details', ['id' => $activeTrip->id]) }}" 
                       class="btn btn-sm btn-outline-primary">
                        <i class="tio-visible"></i> {{ translate('details') }}
                    </a>
                </div>
                <div class="card-body">
                    <!-- Status -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="h5 mb-0">{{ translate('status') }}:</span>
                        <span class="badge badge-{{ $activeTripService->getStatusBadge($activeTrip->current_status) }} p-2">
                            {{ translate($activeTrip->current_status) }}
                        </span>
                    </div>

                    <!-- Speedometer (if en route) -->
                    @if($activeTrip->current_status == 'en_route')
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>{{ translate('current_speed') }}</span>
                                <span id="current-speed" class="font-weight-bold">0 km/h</span>
                            </div>
                            <div class="speedometer">
                                <div class="speed-needle" id="speed-needle" style="transform: rotate(-45deg);"></div>
                            </div>
                        </div>
                    @endif

                    <!-- ETA -->
                    <div class="bg-light p-3 rounded mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ translate('estimated_arrival') }}</span>
                            <span class="h5 mb-0 text-primary" id="eta">
                                {{ $activeTrip->estimated_arrival_minutes ?? '--' }} {{ translate('min') }}
                            </span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" id="progress-bar" 
                                 style="width: {{ $activeTripService->getProgressPercentage($activeTrip->current_status) }}%"></div>
                        </div>
                    </div>

                    <!-- Provider Info -->
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="{{ $activeTrip->provider->user->image_full_url['path'] ?? dynamicAsset('public/assets/back-end/img/provider.png') }}"
                             class="rounded-circle" width="50" height="50" alt="">
                        <div>
                            <h5 class="mb-1">{{ $activeTrip->provider->company_name }}</h5>
                            <p class="mb-0 text-muted">{{ $activeTrip->provider->owner_name }}</p>
                        </div>
                    </div>

                    <!-- Location Info -->
                    <div class="small">
                        <div class="d-flex gap-2 mb-2">
                            <i class="tio-map"></i>
                            <span id="current-location">{{ translate('updating_location') }}...</span>
                        </div>
                        <div class="d-flex gap-2">
                            <i class="tio-flag"></i>
                            <span>{{ $activeTrip->request->pickup_location }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden data -->
    <span id="trip-id" data-value="{{ $activeTrip->id }}"></span>
    <span id="provider-lat" data-value="{{ $activeTrip->provider->current_latitude ?? 0 }}"></span>
    <span id="provider-lng" data-value="{{ $activeTrip->provider->current_longitude ?? 0 }}"></span>
    <span id="pickup-lat" data-value="{{ $activeTrip->request->pickup_latitude ?? 0 }}"></span>
    <span id="pickup-lng" data-value="{{ $activeTrip->request->pickup_longitude ?? 0 }}"></span>
    <span id="dest-lat" data-value="{{ $activeTrip->request->destination_latitude ?? 0 }}"></span>
    <span id="dest-lng" data-value="{{ $activeTrip->request->destination_longitude ?? 0 }}"></span>
    <span id="route-tracking-data" data-url="{{ route('admin.tow-management.active-trips.get-tracking-data') }}"></span>
    <span id="mapbox-token" data-value="{{ env('MAPBOX_API_KEY') }}"></span>
@endsection

@push('script')
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.js"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-draw/v1.3.0/mapbox-gl-draw.js"></script>
    <script>
        let map, providerMarker, pickupMarker, destMarker;
        let providerLat = parseFloat($('#provider-lat').data('value'));
        let providerLng = parseFloat($('#provider-lng').data('value'));
        let pickupLat = parseFloat($('#pickup-lat').data('value'));
        let pickupLng = parseFloat($('#pickup-lng').data('value'));
        let destLat = parseFloat($('#dest-lat').data('value'));
        let destLng = parseFloat($('#dest-lng').data('value'));

        function initMap() {
            mapboxgl.accessToken = $('#mapbox-token').data('value');
            
            map = new mapboxgl.Map({
                container: 'live-map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [providerLng || pickupLng || -74.0060, providerLat || pickupLat || 40.7128],
                zoom: 12
            });

            map.on('load', function() {
                // Add provider marker
                if (providerLat && providerLng) {
                    providerMarker = new mapboxgl.Marker({ color: '#007bff' })
                        .setLngLat([providerLng, providerLat])
                        .setPopup(new mapboxgl.Popup().setHTML('<b>{{ translate("provider") }}</b>'))
                        .addTo(map);
                }

                // Add pickup marker
                if (pickupLat && pickupLng) {
                    pickupMarker = new mapboxgl.Marker({ color: '#28a745' })
                        .setLngLat([pickupLng, pickupLat])
                        .setPopup(new mapboxgl.Popup().setHTML('<b>{{ translate("pickup") }}</b>'))
                        .addTo(map);
                }

                // Add destination marker
                if (destLat && destLng) {
                    destMarker = new mapboxgl.Marker({ color: '#dc3545' })
                        .setLngLat([destLng, destLat])
                        .setPopup(new mapboxgl.Popup().setHTML('<b>{{ translate("destination") }}</b>'))
                        .addTo(map);
                }

                // Fit bounds to show all markers
                let bounds = new mapboxgl.LngLatBounds();
                if (providerLat && providerLng) bounds.extend([providerLng, providerLat]);
                if (pickupLat && pickupLng) bounds.extend([pickupLng, pickupLat]);
                if (destLat && destLng) bounds.extend([destLng, destLat]);
                
                if (!bounds.isEmpty()) {
                    map.fitBounds(bounds, { padding: 50 });
                }
            });
        }

        function updateTracking() {
            $.ajax({
                url: $('#route-tracking-data').data('url'),
                type: 'GET',
                data: { trip_id: $('#trip-id').data('value') },
                success: function(response) {
                    if (response.success) {
                        let data = response.data;
                        
                        // Update provider marker position
                        if (data.provider_location && providerMarker) {
                            providerMarker.setLngLat([data.provider_location.lng, data.provider_location.lat]);
                            
                            // Update location text
                            $('#current-location').text(
                                data.provider_location.lat.toFixed(6) + ', ' + 
                                data.provider_location.lng.toFixed(6)
                            );
                            
                            // Update ETA
                            if (data.estimated_arrival) {
                                $('#eta').text(data.estimated_arrival + ' {{ translate("min") }}');
                            }
                            
                            // Update speed (simulated for demo)
                            let speed = Math.floor(Math.random() * 60);
                            $('#current-speed').text(speed + ' km/h');
                            
                            // Update speedometer needle (0-120 km/h range)
                            let rotation = -45 + (speed / 120 * 90);
                            $('#speed-needle').css('transform', 'rotate(' + rotation + 'deg)');
                        }
                    }
                }
            });
        }

        $(document).ready(function() {
            initMap();
            
            // Update every 5 seconds
            setInterval(updateTracking, 5000);
        });
    </script>
@endpush