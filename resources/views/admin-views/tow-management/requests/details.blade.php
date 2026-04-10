@extends('layouts.back-end.app')

@section('title', translate('request_details'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/css/tags-input.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/plugins/summernote/summernote.min.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h2 class="h1 mb-0 d-flex gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/tow-requests.png') }}" alt="">
                {{ translate('request_details') }} #{{ $towRequest->id }}
            </h2>
            <div class="ml-auto d-flex gap-2">
                <span class="badge badge-{{ $towRequestService->getStatusBadge($towRequest->status) }} p-2">
                    {{ translate($towRequest->status) }}
                </span>
                <span class="badge badge-{{ $towRequestService->getPriorityBadge($towRequest->priority) }} p-2">
                    {{ translate($towRequest->priority) }}
                </span>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Request Info -->
            <div class="col-lg-8">
                <!-- Customer Information -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h4 class="mb-0">{{ translate('customer_information') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="text-muted">{{ translate('name') }}</label>
                                    <p class="font-weight-bold">{{ $towRequest->customer->f_name }} {{ $towRequest->customer->l_name }}</p>
                                </div>
                                <div class="info-group">
                                    <label class="text-muted">{{ translate('phone') }}</label>
                                    <p class="font-weight-bold">{{ $towRequest->customer->phone }}</p>
                                </div>
                                <div class="info-group">
                                    <label class="text-muted">{{ translate('email') }}</label>
                                    <p class="font-weight-bold">{{ $towRequest->customer->email }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="text-muted">{{ translate('total_requests') }}</label>
                                    <p class="font-weight-bold">{{ $towRequest->customer->towRequests->count() }}</p>
                                </div>
                                <div class="info-group">
                                    <label class="text-muted">{{ translate('member_since') }}</label>
                                    <p class="font-weight-bold">{{ $towRequest->customer->created_at->format('d M Y') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Request Details -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h4 class="mb-0">{{ translate('request_details') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="text-muted">{{ translate('service_type') }}</label>
                                    <p class="font-weight-bold">{{ translate(str_replace('_', ' ', $towRequest->service_type)) }}</p>
                                </div>
                                <div class="info-group">
                                    <label class="text-muted">{{ translate('vehicle_info') }}</label>
                                    <p class="font-weight-bold">{{ $towRequest->vehicle_info ?: translate('not_specified') }}</p>
                                </div>
                                <div class="info-group">
                                    <label class="text-muted">{{ translate('description') }}</label>
                                    <p class="font-weight-bold">{{ $towRequest->description ?: translate('no_description') }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="text-muted">{{ translate('pickup_location') }}</label>
                                    <p class="font-weight-bold">{{ $towRequest->pickup_location }}</p>
                                    @if($towRequest->pickup_latitude && $towRequest->pickup_longitude)
                                        <small class="text-info">
                                            {{ number_format($towRequest->pickup_latitude, 6) }}, 
                                            {{ number_format($towRequest->pickup_longitude, 6) }}
                                        </small>
                                    @endif
                                </div>
                                <div class="info-group">
                                    <label class="text-muted">{{ translate('destination') }}</label>
                                    <p class="font-weight-bold">{{ $towRequest->destination ?: translate('not_specified') }}</p>
                                    @if($towRequest->destination_latitude && $towRequest->destination_longitude)
                                        <small class="text-info">
                                            {{ number_format($towRequest->destination_latitude, 6) }}, 
                                            {{ number_format($towRequest->destination_longitude, 6) }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map View -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h4 class="mb-0">{{ translate('location_map') }}</h4>
                    </div>
                    <div class="card-body">
                        <div id="request-map" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Assignment & Status -->
            <div class="col-lg-4">
                <!-- Status Timeline -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h4 class="mb-0">{{ translate('status_timeline') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-badge bg-success"></div>
                                <div class="timeline-content">
                                    <span class="timeline-time">{{ $towRequest->created_at->format('d M, H:i') }}</span>
                                    <p class="mb-0">{{ translate('request_created') }}</p>
                                </div>
                            </div>
                            @if($towRequest->activeTrip)
                                <div class="timeline-item">
                                    <div class="timeline-badge bg-info"></div>
                                    <div class="timeline-content">
                                        <span class="timeline-time">{{ $towRequest->activeTrip->acceptance_time?->format('d M, H:i') ?? '--' }}</span>
                                        <p class="mb-0">{{ translate('provider_accepted') }}</p>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-badge bg-primary"></div>
                                    <div class="timeline-content">
                                        <span class="timeline-time">{{ $towRequest->activeTrip->en_route_time?->format('d M, H:i') ?? '--' }}</span>
                                        <p class="mb-0">{{ translate('provider_en_route') }}</p>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-badge bg-warning"></div>
                                    <div class="timeline-content">
                                        <span class="timeline-time">{{ $towRequest->activeTrip->arrival_time?->format('d M, H:i') ?? '--' }}</span>
                                        <p class="mb-0">{{ translate('provider_arrived') }}</p>
                                    </div>
                                </div>
                            @endif
                            @if($towRequest->status == 'completed')
                                <div class="timeline-item">
                                    <div class="timeline-badge bg-success"></div>
                                    <div class="timeline-content">
                                        <span class="timeline-time">{{ $towRequest->updated_at->format('d M, H:i') }}</span>
                                        <p class="mb-0">{{ translate('completed') }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Assigned Provider (if any) -->
                @if($towRequest->activeTrip && $towRequest->activeTrip->provider)
                    <div class="card mb-3">
                        <div class="card-header">
                            <h4 class="mb-0">{{ translate('assigned_provider') }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img src="{{ $towRequest->activeTrip->provider->user->image_full_url['path'] ?? dynamicAsset('public/assets/back-end/img/provider.png') }}"
                                     class="rounded-circle" width="60" height="60" alt="">
                                <div>
                                    <h5>{{ $towRequest->activeTrip->provider->company_name }}</h5>
                                    <p class="mb-1">{{ $towRequest->activeTrip->provider->owner_name }}</p>
                                    <p class="mb-0"><i class="tio-call"></i> {{ $towRequest->activeTrip->provider->user->phone }}</p>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="bg-light p-2 rounded">
                                        <small class="text-muted">{{ translate('rating') }}</small>
                                        <p class="mb-0">⭐ {{ number_format($towRequest->activeTrip->provider->rating, 1) }}</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-light p-2 rounded">
                                        <small class="text-muted">{{ translate('trips') }}</small>
                                        <p class="mb-0">🚗 {{ $towRequest->activeTrip->provider->total_completed_trips }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('admin.tow-management.active-trips.details', ['id' => $towRequest->activeTrip->id]) }}" 
                                   class="btn btn-outline-primary btn-block">
                                    {{ translate('view_trip_details') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Nearby Providers (if pending) -->
                @if($towRequest->status == 'pending')
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">{{ translate('nearby_providers') }}</h4>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @forelse($nearbyProviders as $provider)
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">{{ $provider->company_name }}</h6>
                                                <p class="mb-1 small">
                                                    ⭐ {{ number_format($provider->rating, 1) }} | 
                                                    📍 {{ number_format($provider->distance, 1) }} km
                                                </p>
                                                <p class="mb-0 small">
                                                    <i class="tio-call"></i> {{ $provider->user->phone }}
                                                </p>
                                            </div>
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick="assignProvider({{ $towRequest->id }}, {{ $provider->id }})">
                                                {{ translate('assign') }}
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="list-group-item text-center text-muted">
                                        {{ translate('no_nearby_providers') }}
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-end gap-3 mt-3">
            <a href="{{ route('admin.tow-management.requests.list') }}" class="btn btn-secondary px-5">
                {{ translate('back') }}
            </a>
            @if($towRequest->status == 'pending')
                <button class="btn btn--primary px-5" onclick="openAssignModal({{ $towRequest->id }})">
                    {{ translate('assign_provider') }}
                </button>
            @endif
        </div>
    </div>

    <!-- Hidden data for JS -->
    <span id="pickup-lat" data-value="{{ $towRequest->pickup_latitude }}"></span>
    <span id="pickup-lng" data-value="{{ $towRequest->pickup_longitude }}"></span>
    <span id="dest-lat" data-value="{{ $towRequest->destination_latitude }}"></span>
    <span id="dest-lng" data-value="{{ $towRequest->destination_longitude }}"></span>
    <span id="mapbox-token" data-value="{{ env('MAPBOX_API_KEY') }}"></span>
    <span id="route-assign-provider" data-url="{{ route('admin.tow-management.active-trips.assign') }}"></span>
@endsection

@push('script')
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.js"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.css" rel="stylesheet">
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/tow-management/request-details.js') }}"></script>
@endpush