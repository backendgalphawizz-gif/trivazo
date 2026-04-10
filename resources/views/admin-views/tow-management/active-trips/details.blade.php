@extends('layouts.back-end.app')

@section('title', translate('trip_details'))

@push('css_or_js')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.css" rel="stylesheet">
    <style>
        #tracking-map { height: 400px; width: 100%; border-radius: 8px; }
        .timeline-item { position: relative; padding-left: 30px; margin-bottom: 20px; }
        .timeline-item:before { content: ''; position: absolute; left: 10px; top: 24px; bottom: -20px; width: 2px; background: #e0e0e0; }
        .timeline-item:last-child:before { display: none; }
        .timeline-dot { position: absolute; left: 0; width: 20px; height: 20px; border-radius: 50%; background: #fff; border: 3px solid; }
        .timeline-dot.completed { border-color: #28a745; }
        .timeline-dot.current { border-color: #007bff; animation: pulse 2s infinite; }
        .timeline-dot.pending { border-color: #6c757d; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(0,123,255,0.7); } 70% { box-shadow: 0 0 0 10px rgba(0,123,255,0); } 100% { box-shadow: 0 0 0 0 rgba(0,123,255,0); } }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <a href="{{ route('admin.tow-management.active-trips.list') }}" class="btn btn-outline-primary">
                <i class="tio-arrow-backward"></i> {{ translate('back') }}
            </a>
            <h2 class="h1 mb-0 d-flex gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/active-trips.png') }}" alt="">
                {{ translate('trip_details') }} #{{ $activeTrip->id }}
            </h2>
            <div class="ml-auto">
                <span class="badge badge-{{ $activeTripService->getStatusBadge($activeTrip->current_status) }} p-3">
                    {{ translate($activeTrip->current_status) }}
                </span>
            </div>
        </div>

        <div class="row g-3">
            <!-- Left Column - Map & Tracking -->
            <div class="col-lg-8">
                <!-- Live Tracking Map -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">{{ translate('live_tracking') }}</h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshTracking()">
                                <i class="tio-refresh"></i> {{ translate('refresh') }}
                            </button>
                            <a href="{{ route('admin.tow-management.active-trips.live-tracking', ['id' => $activeTrip->id]) }}" 
                               class="btn btn-sm btn-primary">
                                <i class="tio-map"></i> {{ translate('fullscreen') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="tracking-map"></div>
                        
                        <!-- ETA Info -->
                        <div class="d-flex justify-content-between mt-3 p-3 bg-light rounded">
                            <div>
                                <small class="text-muted">{{ translate('estimated_arrival') }}</small>
                                <h4 class="mb-0 text-primary">{{ $activeTrip->estimated_arrival_minutes ?? '--' }} {{ translate('min') }}</h4>
                            </div>
                            <div>
                                <small class="text-muted">{{ translate('distance') }}</small>
                                <h4 class="mb-0">{{ $activeTrip->distance_estimate ?? '--' }} {{ translate('km') }}</h4>
                            </div>
                            <div>
                                <small class="text-muted">{{ translate('last_update') }}</small>
                                <h4 class="mb-0">{{ $activeTrip->trackingLocations->first()?->recorded_at?->diffForHumans() ?? translate('never') }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">{{ translate('trip_timeline') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <!-- Assigned -->
                            <div class="timeline-item">
                                <div class="timeline-dot {{ $activeTrip->acceptance_time ? 'completed' : ($activeTrip->current_status == 'assigned' ? 'current' : 'pending') }}"></div>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="mb-1">{{ translate('assigned') }}</h5>
                                        <p class="mb-0 text-muted">{{ translate('trip_assigned_to_provider') }}</p>
                                    </div>
                                    <div class="text-right">
                                        @if($activeTrip->acceptance_time)
                                            <strong>{{ $activeTrip->created_at->format('H:i') }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $activeTrip->created_at->format('M d, Y') }}</small>
                                        @else
                                            <span class="badge badge-warning">{{ translate('pending') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Accepted -->
                            <div class="timeline-item">
                                <div class="timeline-dot {{ $activeTrip->acceptance_time && $activeTrip->en_route_time ? 'completed' : ($activeTrip->current_status == 'accepted' ? 'current' : 'pending') }}"></div>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="mb-1">{{ translate('accepted') }}</h5>
                                        <p class="mb-0 text-muted">{{ translate('provider_accepted_the_trip') }}</p>
                                    </div>
                                    <div class="text-right">
                                        @if($activeTrip->acceptance_time)
                                            <strong>{{ $activeTrip->acceptance_time->format('H:i') }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $activeTrip->acceptance_time->diffForHumans() }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- En Route -->
                            <div class="timeline-item">
                                <div class="timeline-dot {{ $activeTrip->en_route_time && $activeTrip->arrival_time ? 'completed' : ($activeTrip->current_status == 'en_route' ? 'current' : 'pending') }}"></div>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="mb-1">{{ translate('en_route') }}</h5>
                                        <p class="mb-0 text-muted">{{ translate('provider_heading_to_location') }}</p>
                                    </div>
                                    <div class="text-right">
                                        @if($activeTrip->en_route_time)
                                            <strong>{{ $activeTrip->en_route_time->format('H:i') }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $activeTrip->en_route_time->diffForHumans() }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Arrived -->
                            <div class="timeline-item">
                                <div class="timeline-dot {{ $activeTrip->arrival_time && $activeTrip->start_time ? 'completed' : ($activeTrip->current_status == 'arrived' ? 'current' : 'pending') }}"></div>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="mb-1">{{ translate('arrived') }}</h5>
                                        <p class="mb-0 text-muted">{{ translate('provider_arrived_at_location') }}</p>
                                    </div>
                                    <div class="text-right">
                                        @if($activeTrip->arrival_time)
                                            <strong>{{ $activeTrip->arrival_time->format('H:i') }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $activeTrip->arrival_time->diffForHumans() }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- In Progress -->
                            <div class="timeline-item">
                                <div class="timeline-dot {{ $activeTrip->start_time && $activeTrip->completion_time ? 'completed' : ($activeTrip->current_status == 'in_progress' ? 'current' : 'pending') }}"></div>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="mb-1">{{ translate('in_progress') }}</h5>
                                        <p class="mb-0 text-muted">{{ translate('service_in_progress') }}</p>
                                    </div>
                                    <div class="text-right">
                                        @if($activeTrip->start_time)
                                            <strong>{{ $activeTrip->start_time->format('H:i') }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $activeTrip->start_time->diffForHumans() }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Completed -->
                            <div class="timeline-item">
                                <div class="timeline-dot {{ $activeTrip->completion_time ? 'completed' : 'pending' }}"></div>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="mb-1">{{ translate('completed') }}</h5>
                                        <p class="mb-0 text-muted">{{ translate('trip_completed') }}</p>
                                    </div>
                                    <div class="text-right">
                                        @if($activeTrip->completion_time)
                                            <strong>{{ $activeTrip->completion_time->format('H:i') }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $activeTrip->completion_time->format('M d, Y') }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Details -->
            <div class="col-lg-4">
                <!-- Customer Info -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h4 class="mb-0">{{ translate('customer_information') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="{{ $activeTrip->request->customer->image_full_url['path'] ?? dynamicAsset('public/assets/back-end/img/customer.png') }}"
                                 class="rounded-circle" width="100" height="100" alt="">
                            <h5 class="mt-2">{{ $activeTrip->request->customer->f_name }} {{ $activeTrip->request->customer->l_name }}</h5>
                            <span class="badge badge-soft-primary">{{ $activeTrip->request->customer->membership_level ?? translate('regular') }}</span>
                        </div>
                        
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="tio-call text-primary"></i>
                                <span>{{ $activeTrip->request->customer->phone }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="tio-email text-primary"></i>
                                <span>{{ $activeTrip->request->customer->email }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="tio-home text-primary"></i>
                                <span>{{ $activeTrip->request->customer->address ?? translate('not_provided') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Provider Info -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">{{ translate('provider_information') }}</h4>
                        <button class="btn btn-sm btn-outline-primary" onclick="openReassignModal({{ $activeTrip->id }})">
                            <i class="tio-change"></i> {{ translate('reassign') }}
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img src="{{ $activeTrip->provider->user->image_full_url['path'] ?? dynamicAsset('public/assets/back-end/img/provider.png') }}"
                                 class="rounded-circle" width="60" height="60" alt="">
                            <div>
                                <h5 class="mb-1">{{ $activeTrip->provider->company_name }}</h5>
                                <p class="mb-0 text-muted">{{ $activeTrip->provider->owner_name }}</p>
                            </div>
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="bg-light p-2 rounded text-center">
                                    <small class="text-muted">{{ translate('rating') }}</small>
                                    <p class="mb-0 font-weight-bold">⭐ {{ number_format($activeTrip->provider->rating, 1) }}</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light p-2 rounded text-center">
                                    <small class="text-muted">{{ translate('trips') }}</small>
                                    <p class="mb-0 font-weight-bold">{{ $activeTrip->provider->total_completed_trips }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="tio-call text-primary"></i>
                                <span>{{ $activeTrip->provider->owner_phone }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="tio-truck text-primary"></i>
                                <span class="badge badge-{{ $activeTrip->provider->status_badge }}">
                                    {{ translate($activeTrip->provider->status) }}
                                </span>
                                <small>({{ $activeTrip->provider->availability_slots }} {{ translate('slots_available') }})</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trip Details -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">{{ translate('trip_details') }}</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td width="40%">{{ translate('service_type') }}:</td>
                                <td><strong>{{ translate(str_replace('_', ' ', $activeTrip->request->service_type)) }}</strong></td>
                            </tr>
                            <tr>
                                <td>{{ translate('priority') }}:</td>
                                <td>
                                    @php
                                        $priorityColors = ['low' => 'success', 'normal' => 'info', 'high' => 'warning', 'emergency' => 'danger'];
                                    @endphp
                                    <span class="badge badge-{{ $priorityColors[$activeTrip->request->priority] ?? 'secondary' }}">
                                        {{ translate($activeTrip->request->priority) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>{{ translate('pickup') }}:</td>
                                <td><small>{{ $activeTrip->request->pickup_location }}</small></td>
                            </tr>
                            <tr>
                                <td>{{ translate('destination') }}:</td>
                                <td><small>{{ $activeTrip->request->destination ?? translate('not_specified') }}</small></td>
                            </tr>
                            <tr>
                                <td>{{ translate('price') }}:</td>
                                <td>
                                    @if($activeTrip->request->final_price)
                                        <h5 class="text-success mb-0">${{ number_format($activeTrip->request->final_price, 2) }}</h5>
                                    @else
                                        <h5 class="text-primary mb-0">${{ number_format($activeTrip->request->estimated_price, 2) }} <small>({{ translate('estimated') }})</small></h5>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        @if($activeTrip->request->description)
                            <div class="mt-3 p-3 bg-light rounded">
                                <small class="text-muted">{{ translate('description') }}:</small>
                                <p class="mb-0">{{ $activeTrip->request->description }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reassign Modal -->
    <div class="modal fade" id="reassignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('reassign_provider') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="reassign-form" method="POST" action="{{ route('admin.tow-management.active-trips.reassign') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="trip_id" value="{{ $activeTrip->id }}">
                        
                        <div class="form-group">
                            <label class="title-color">{{ translate('select_new_provider') }} <span class="input-required-icon">*</span></label>
                            <select class="js-select2-custom form-control" name="new_provider_id" required>
                                <option value="" selected disabled>{{ translate('select_provider') }}</option>
                                @foreach($nearbyProviders ?? [] as $provider)
                                    <option value="{{ $provider->id }}">
                                        {{ $provider->company_name }} - ⭐ {{ number_format($provider->rating, 1) }} ({{ round($provider->distance) }} km)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="title-color">{{ translate('reason_for_reassignment') }} <span class="input-required-icon">*</span></label>
                            <textarea name="reassign_reason" class="form-control" rows="3" required 
                                      placeholder="{{ translate('enter_reason') }}"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('reassign') }}</button>
                    </div>
                </form>
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
    <script>
        let map, marker, routeLayer;
        let providerLat = parseFloat($('#provider-lat').data('value'));
        let providerLng = parseFloat($('#provider-lng').data('value'));
        let pickupLat = parseFloat($('#pickup-lat').data('value'));
        let pickupLng = parseFloat($('#pickup-lng').data('value'));
        let destLat = parseFloat($('#dest-lat').data('value'));
        let destLng = parseFloat($('#dest-lng').data('value'));

        function initMap() {
            mapboxgl.accessToken = $('#mapbox-token').data('value');
            
            // Center map between provider and pickup
            let centerLng = (providerLng + pickupLng) / 2;
            let centerLat = (providerLat + pickupLat) / 2;
            
            map = new mapboxgl.Map({
                container: 'tracking-map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [centerLng || -74.0060, centerLat || 40.7128],
                zoom: 12
            });

            map.on('load', function() {
                // Add provider marker
                if (providerLat && providerLng) {
                    new mapboxgl.Marker({ color: '#007bff' })
                        .setLngLat([providerLng, providerLat])
                        .setPopup(new mapboxgl.Popup().setHTML('<b>{{ translate("provider") }}</b>'))
                        .addTo(map);
                }

                // Add pickup marker
                if (pickupLat && pickupLng) {
                    new mapboxgl.Marker({ color: '#28a745' })
                        .setLngLat([pickupLng, pickupLat])
                        .setPopup(new mapboxgl.Popup().setHTML('<b>{{ translate("pickup") }}</b>'))
                        .addTo(map);
                }

                // Add destination marker
                if (destLat && destLng) {
                    new mapboxgl.Marker({ color: '#dc3545' })
                        .setLngLat([destLng, destLat])
                        .setPopup(new mapboxgl.Popup().setHTML('<b>{{ translate("destination") }}</b>'))
                        .addTo(map);
                }

                // Draw route if both points exist
                if (providerLat && providerLng && pickupLat && pickupLng) {
                    getRoute([providerLng, providerLat], [pickupLng, pickupLat]);
                }
            });
        }

        function getRoute(start, end) {
            const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${start[0]},${start[1]};${end[0]},${end[1]}?steps=true&geometries=geojson&access_token=${mapboxgl.accessToken}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const route = data.routes[0].geometry;
                    if (map.getSource('route')) {
                        map.getSource('route').setData(route);
                    } else {
                        map.addLayer({
                            id: 'route',
                            type: 'line',
                            source: {
                                type: 'geojson',
                                data: route
                            },
                            layout: {
                                'line-join': 'round',
                                'line-cap': 'round'
                            },
                            paint: {
                                'line-color': '#007bff',
                                'line-width': 5,
                                'line-opacity': 0.8
                            }
                        });
                    }
                });
        }

        function refreshTracking() {
            $.ajax({
                url: $('#route-tracking-data').data('url'),
                type: 'GET',
                data: { trip_id: $('#trip-id').data('value') },
                success: function(response) {
                    if (response.success) {
                        // Update provider location on map
                        // Update ETA, etc.
                        location.reload(); // Simple reload for now
                    }
                }
            });
        }

        $(document).ready(function() {
            initMap();
            
            // Refresh every 30 seconds
            setInterval(refreshTracking, 30000);
        });
    </script>
@endpush