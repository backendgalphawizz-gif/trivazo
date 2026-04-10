@extends('layouts.back-end.app')

@section('title', translate('active_trips'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/css/tags-input.min.css') }}" rel="stylesheet">
    <link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h2 class="h1 mb-0 d-flex gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/active-trips.png') }}" alt="">
                {{ translate('active_trips') }}
            </h2>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <div class="card card-statistic">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <h4 class="mb-2">{{ translate('total_active') }}</h4>
                            <h2 class="mb-0">{{ $statistics['total'] }}</h2>
                        </div>
                        <div class="bg-soft-primary rounded-circle p-3">
                            <i class="tio-bus"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-statistic">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <h4 class="mb-2">{{ translate('en_route') }}</h4>
                            <h2 class="mb-0 text-primary">{{ $statistics['en_route'] }}</h2>
                        </div>
                        <div class="bg-soft-primary rounded-circle p-3">
                            <i class="tio-navigation"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-statistic">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <h4 class="mb-2">{{ translate('arrived') }}</h4>
                            <h2 class="mb-0 text-warning">{{ $statistics['arrived'] }}</h2>
                        </div>
                        <div class="bg-soft-warning rounded-circle p-3">
                            <i class="tio-flag"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-statistic">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <h4 class="mb-2">{{ translate('in_progress') }}</h4>
                            <h2 class="mb-0 text-success">{{ $statistics['in_progress'] }}</h2>
                        </div>
                        <div class="bg-soft-success rounded-circle p-3">
                            <i class="tio-wrench"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('admin.tow-management.active-trips.list') }}" method="GET">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <select class="js-select2-custom form-control" name="status">
                                <option value="all" {{ $filters['status'] == 'all' ? 'selected' : '' }}>
                                    {{ translate('all_status') }}
                                </option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ $filters['status'] == $status ? 'selected' : '' }}>
                                        {{ translate($status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="js-select2-custom form-control" name="provider_id">
                                <option value="all" {{ !$filters['provider_id'] ? 'selected' : '' }}>
                                    {{ translate('all_providers') }}
                                </option>
                                @foreach($providers as $provider)
                                    <option value="{{ $provider->id }}" {{ $filters['provider_id'] == $provider->id ? 'selected' : '' }}>
                                        {{ $provider->company_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-2">
                                <input type="text" name="searchValue" class="form-control" 
                                       placeholder="{{ translate('search_by_customer_or_provider') }}" 
                                       value="{{ request('searchValue') }}">
                                <button type="submit" class="btn btn--primary">
                                    <i class="tio-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('admin.tow-management.active-trips.map-view') }}" class="btn btn-outline-primary btn-block">
                                <i class="tio-map"></i> {{ translate('map_view') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Active Trips Cards -->
        <div class="row g-3">
            @forelse($activeTrips as $trip)
                <div class="col-md-6 col-xl-4">
                    <div class="card trip-card status-{{ $trip->current_status }}">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">#{{ $trip->id }}</h5>
                            <span class="badge badge-{{ $activeTripService->getStatusBadge($trip->current_status) }} p-2">
                                {{ translate($trip->current_status) }}
                            </span>
                        </div>
                        <div class="card-body">
                            <!-- Customer Info -->
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div class="bg-soft-primary rounded-circle p-2">
                                    <i class="tio-user"></i>
                                </div>
                                <div>
                                    <p class="mb-0 font-weight-bold">
                                        {{ $trip->request->customer->f_name }} {{ $trip->request->customer->l_name }}
                                    </p>
                                    <small class="text-muted">
                                        <i class="tio-call"></i> {{ $trip->request->customer->phone }}
                                    </small>
                                </div>
                            </div>

                            <!-- Provider Info -->
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div class="bg-soft-info rounded-circle p-2">
                                    <i class="tio-truck"></i>
                                </div>
                                <div>
                                    <p class="mb-0 font-weight-bold">{{ $trip->provider->company_name }}</p>
                                    <small class="text-muted">
                                        ⭐ {{ number_format($trip->provider->rating, 1) }}
                                    </small>
                                </div>
                            </div>

                            <!-- Trip Details -->
                            <div class="bg-light p-2 rounded mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">{{ translate('service') }}:</small>
                                    <span>{{ translate(str_replace('_', ' ', $trip->request->service_type)) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">{{ translate('pickup') }}:</small>
                                    <span class="text-truncate" style="max-width: 200px;">{{ $trip->request->pickup_location }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">{{ translate('eta') }}:</small>
                                    <span class="text-primary">{{ $trip->estimated_arrival_minutes }} {{ translate('min') }}</span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="progress mb-2" style="height: 5px;">
                                <div class="progress-bar bg-primary" 
                                     style="width: {{ $activeTripService->getProgressPercentage($trip->current_status) }}%"></div>
                            </div>

                            <!-- Time Info -->
                            <div class="d-flex justify-content-between small text-muted mb-3">
                                <span><i class="tio-clock"></i> {{ $trip->created_at->diffForHumans() }}</span>
                                @if($trip->estimated_arrival_minutes)
                                    <span><i class="tio-flag"></i> {{ $trip->estimated_arrival_minutes }} min ETA</span>
                                @endif
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.tow-management.active-trips.details', ['id' => $trip->id]) }}" 
                                   class="btn btn-sm btn-outline-primary flex-grow-1">
                                    <i class="tio-visible"></i> {{ translate('track') }}
                                </a>
                                <button class="btn btn-sm btn-outline-warning" 
                                        onclick="openReassignModal({{ $trip->id }})"
                                        title="{{ translate('reassign_provider') }}">
                                    <i class="tio-change"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="emergencyContact({{ $trip->id }})"
                                        title="{{ translate('emergency') }}">
                                    <i class="tio-alert"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/no-data.png') }}" 
                                 width="100" alt="">
                            <h4 class="mt-3">{{ translate('no_active_trips_found') }}</h4>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-end mt-3">
            {{ $activeTrips->links() }}
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
                        <input type="hidden" name="trip_id" id="reassign-trip-id">
                        
                        <div class="form-group">
                            <label class="title-color">{{ translate('select_new_provider') }} <span class="input-required-icon">*</span></label>
                            <select class="js-select2-custom form-control" name="new_provider_id" required>
                                <option value="" selected disabled>{{ translate('select_provider') }}</option>
                                @foreach($providers as $provider)
                                    <option value="{{ $provider->id }}" {{ !$provider->is_available ? 'disabled' : '' }}>
                                        {{ $provider->company_name }} - ⭐ {{ number_format($provider->rating, 1) }}
                                        @if(!$provider->is_available) ({{ translate('not_available') }}) @endif
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
    <span id="route-reassign" data-url="{{ route('admin.tow-management.active-trips.reassign') }}"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/tow-management/active-trips.js') }}"></script>
    <script>
        function openReassignModal(tripId) {
            $('#reassign-trip-id').val(tripId);
            $('#reassignModal').modal('show');
        }

        function emergencyContact(tripId) {
            // Emergency contact logic
        }
    </script>
@endpush