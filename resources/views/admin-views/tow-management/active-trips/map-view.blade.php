@extends('layouts.back-end.app')

@section('title', translate('map_view'))

@push('css_or_js')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.css" rel="stylesheet">
    <style>
        #map-view { height: calc(100vh - 150px); width: 100%; }
        .trip-list-sidebar { position: absolute; top: 20px; left: 20px; width: 350px; z-index: 10; background: white; border-radius: 8px; max-height: calc(100vh - 100px); overflow-y: auto; }
        .trip-list-item { padding: 10px; border-bottom: 1px solid #e0e0e0; cursor: pointer; transition: background 0.2s; }
        .trip-list-item:hover { background: #f5f5f5; }
        .trip-list-item.selected { background: #e3f2fd; border-left: 3px solid #007bff; }
        .marker-popup { padding: 10px; min-width: 200px; }
        .marker-popup h6 { margin-bottom: 5px; }
        .marker-popup .status { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; margin-bottom: 5px; }
    </style>
@endpush

@section('content')
    <div class="content p-0 position-relative">
        <!-- Map -->
        <div id="map-view"></div>

        <!-- Trip List Sidebar -->
        <div class="trip-list-sidebar card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">{{ translate('active_trips') }} ({{ $activeTrips->count() }})</h4>
                <button class="btn btn-sm btn-outline-primary" onclick="fitAllTrips()">
                    <i class="tio-fit-screen"></i> {{ translate('fit_all') }}
                </button>
            </div>
            <div class="card-body p-0">
                <div class="input-group p-3">
                    <input type="text" class="form-control" id="trip-search" 
                           placeholder="{{ translate('search_trips') }}..." 
                           onkeyup="filterTrips(this.value)">
                </div>
                <div id="trip-list-container">
                    @foreach($activeTrips as $trip)
                        <div class="trip-list-item" data-id="{{ $trip->id }}" 
                             data-lat="{{ $trip->provider->current_latitude }}"
                             data-lng="{{ $trip->provider->current_longitude }}"
                             onclick="selectTrip({{ $trip->id }})">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">#{{ $trip->id }} - {{ $trip->provider->company_name }}</h6>
                                <span class="badge badge-{{ $activeTripService->getStatusBadge($trip->current_status) }}">
                                    {{ translate($trip->current_status) }}
                                </span>
                            </div>
                            <div class="small text-muted">
                                <div><i class="tio-user"></i> {{ $trip->request->customer->f_name }} {{ $trip->request->customer->l_name }}</div>
                                <div><i class="tio-map"></i> {{ Str::limit($trip->request->pickup_location, 30) }}</div>
                                <div><i class="tio-clock"></i> {{ $trip->estimated_arrival_minutes }} min ETA</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden data -->
    <span id="mapbox-token" data-value="{{ env('MAPBOX_API_KEY') }}"></span>
    <span id="trips-data" data-value='@json($activeTrips->count())'></span>
@endsection

@push('script')
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.js"></script>
    <script>
        let map, markers = [], selectedMarker = null;
        let trips = JSON.parse($('#trips-data').data('value'));

        function initMap() {
            mapboxgl.accessToken = $('#mapbox-token').data('value');
            
            map = new mapboxgl.Map({
                container: 'map-view',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [-74.0060, 40.7128],
                zoom: 10
            });

            map.on('load', function() {
                addTripMarkers();
            });
        }

        function addTripMarkers() {
            trips.forEach(trip => {
                if (!trip.provider?.current_latitude) return;

                let color = getStatusColor(trip.current_status);
                let marker = new mapboxgl.Marker({ color: color })
                    .setLngLat([trip.provider.current_longitude, trip.provider.current_latitude])
                    .setPopup(new mapboxgl.Popup({
                        className: 'marker-popup'
                    }).setHTML(`
                        <div class="marker-popup">
                            <h6>#${trip.id} - ${trip.provider.company_name}</h6>
                            <span class="status" style="background: ${color}20; color: ${color}">
                                ${trip.current_status}
                            </span>
                            <p class="mb-1 mt-2"><strong>{{ translate("customer") }}:</strong> ${trip.request.customer.f_name} ${trip.request.customer.l_name}</p>
                            <p class="mb-1"><strong>{{ translate("pickup") }}:</strong> ${trip.request.pickup_location.substring(0, 30)}...</p>
                            <p class="mb-2"><strong>ETA:</strong> ${trip.estimated_arrival_minutes} min</p>
                            <a href="{{ url('admin/tow-management/active-trips/details') }}/${trip.id}" 
                               class="btn btn-sm btn-primary btn-block">
                                {{ translate("view_details") }}
                            </a>
                        </div>
                    `))
                    .addTo(map);

                marker.getElement().addEventListener('click', () => {
                    selectTrip(trip.id);
                });

                markers.push({
                    id: trip.id,
                    marker: marker
                });
            });
        }

        function getStatusColor(status) {
            const colors = {
                'assigned': '#3498db',
                'accepted': '#9b59b6',
                'en_route': '#f39c12',
                'arrived': '#27ae60',
                'in_progress': '#e67e22',
                'completed': '#2ecc71'
            };
            return colors[status] || '#95a5a6';
        }

        function selectTrip(tripId) {
            // Update list selection
            $('.trip-list-item').removeClass('selected');
            $(`.trip-list-item[data-id="${tripId}"]`).addClass('selected');

            // Find marker
            let markerObj = markers.find(m => m.id == tripId);
            if (markerObj) {
                // Fly to marker
                let coordinates = markerObj.marker.getLngLat();
                map.flyTo({
                    center: coordinates,
                    zoom: 14,
                    essential: true
                });

                // Open popup
                markerObj.marker.togglePopup();

                // Highlight marker
                if (selectedMarker) {
                    selectedMarker.getElement().style.transform = '';
                }
                markerObj.marker.getElement().style.transform = 'scale(1.2)';
                selectedMarker = markerObj.marker;
            }
        }

        function fitAllTrips() {
            let bounds = new mapboxgl.LngLatBounds();
            markers.forEach(m => {
                let coords = m.marker.getLngLat();
                bounds.extend([coords.lng, coords.lat]);
            });
            
            if (!bounds.isEmpty()) {
                map.fitBounds(bounds, { padding: 50 });
            }
        }

        function filterTrips(search) {
            search = search.toLowerCase();
            $('.trip-list-item').each(function() {
                let text = $(this).text().toLowerCase();
                if (text.includes(search)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        $(document).ready(function() {
            initMap();
        });
    </script>
@endpush