@extends('layouts.back-end.app')

@section('title', translate('edit_provider'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/css/tags-input.min.css') }}" rel="stylesheet">
    <link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h2 class="h1 mb-0 d-flex gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/tow-providers.png') }}" alt="">
                {{ translate('edit_provider') }} - {{ $provider->company_name }}
            </h2>
            <div class="ml-auto">
                <span class="badge badge-{{ $providerService->getStatusBadge($provider->status) }} p-2">
                    {{ translate($provider->status) }}
                </span>
            </div>
        </div>

        <form action="{{ route('admin.tow-management.providers.update', ['id' => $provider->id]) }}" 
              method="POST" enctype="multipart/form-data" id="provider-form">
            @csrf
            @method('PUT')

            <!-- Basic Information -->
            <div class="card mt-3">
                <div class="card-header">
                    <div class="d-flex gap-2">
                        <i class="tio-user-big"></i>
                        <h4 class="mb-0">{{ translate('basic_information') }}</h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color">{{ translate('company_name') }}</label>
                                <input type="text" name="company_name" class="form-control" 
                                       value="{{ old('company_name', $provider->company_name) }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color">{{ translate('service_area') }}</label>
                                <input type="text" name="service_area" class="form-control" 
                                       value="{{ old('service_area', $provider->service_area) }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color">{{ translate('max_simultaneous_trips') }}</label>
                                <input type="number" name="max_simultaneous_trips" class="form-control" 
                                       value="{{ old('max_simultaneous_trips', $provider->max_simultaneous_trips) }}" 
                                       min="1" max="10">
                            </div>
                        </div>
                    </div>

                    <!-- Provider Stats -->
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <div class="bg-light p-3 rounded text-center">
                                <small class="text-muted">{{ translate('current_trips') }}</small>
                                <h4 class="mb-0">{{ $provider->current_trips_count }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bg-light p-3 rounded text-center">
                                <small class="text-muted">{{ translate('total_completed') }}</small>
                                <h4 class="mb-0">{{ $provider->total_completed_trips }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bg-light p-3 rounded text-center">
                                <small class="text-muted">{{ translate('rating') }}</small>
                                <h4 class="mb-0">⭐ {{ number_format($provider->rating, 1) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bg-light p-3 rounded text-center">
                                <small class="text-muted">{{ translate('availability_slots') }}</small>
                                <h4 class="mb-0">{{ $provider->availability_slots }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            <div class="card mt-3">
                <div class="card-header">
                    <div class="d-flex gap-2">
                        <i class="tio-file"></i>
                        <h4 class="mb-0">{{ translate('documents') }}</h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color">{{ translate('business_license') }}</label>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    @if($provider->business_license)
                                        <a href="{{ $provider->license_document_url['path'] }}" 
                                           target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="tio-file"></i> {{ translate('view_current_license') }}
                                        </a>
                                        <span class="text-success">
                                            <i class="tio-checkmark-circle"></i> {{ translate('uploaded') }}
                                        </span>
                                    @endif
                                </div>
                                
                                <div class="custom_upload_input">
                                    <input type="file" name="business_license" 
                                           class="custom-upload-input-file" 
                                           accept=".pdf,.doc,.docx">
                                    <span class="delete_file_input btn btn-outline-danger btn-sm square-btn d--none">
                                        <i class="tio-delete"></i>
                                    </span>
                                    <div class="position-relative">
                                        <div class="d-flex flex-column justify-content-center align-items-center py-3">
                                            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/pdf-upload-icon.svg') }}" 
                                                 width="40" alt="">
                                            <h5 class="text-muted mb-0">{{ translate('upload_new_license') }}</h5>
                                            <small class="text-muted">{{ translate('leave_empty_to_keep_current') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color">{{ translate('insurance_document') }}</label>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    @if($provider->insurance_info)
                                        <a href="{{ $provider->insurance_document_url['path'] }}" 
                                           target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="tio-file"></i> {{ translate('view_current_insurance') }}
                                        </a>
                                        <span class="text-success">
                                            <i class="tio-checkmark-circle"></i> {{ translate('uploaded') }}
                                        </span>
                                    @endif
                                </div>
                                
                                <div class="custom_upload_input">
                                    <input type="file" name="insurance_info" 
                                           class="custom-upload-input-file" 
                                           accept=".pdf,.doc,.docx">
                                    <span class="delete_file_input btn btn-outline-danger btn-sm square-btn d--none">
                                        <i class="tio-delete"></i>
                                    </span>
                                    <div class="position-relative">
                                        <div class="d-flex flex-column justify-content-center align-items-center py-3">
                                            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/pdf-upload-icon.svg') }}" 
                                                 width="40" alt="">
                                            <h5 class="text-muted mb-0">{{ translate('upload_new_insurance') }}</h5>
                                            <small class="text-muted">{{ translate('leave_empty_to_keep_current') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status & Settings -->
            <div class="card mt-3">
                <div class="card-header">
                    <div class="d-flex gap-2">
                        <i class="tio-settings"></i>
                        <h4 class="mb-0">{{ translate('status_&_settings') }}</h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color">{{ translate('provider_status') }}</label>
                                <select class="form-control" name="status">
                                    <option value="available" {{ $provider->status == 'available' ? 'selected' : '' }}>
                                        {{ translate('available') }}
                                    </option>
                                    <option value="busy" {{ $provider->status == 'busy' ? 'selected' : '' }}>
                                        {{ translate('busy') }}
                                    </option>
                                    <option value="offline" {{ $provider->status == 'offline' ? 'selected' : '' }}>
                                        {{ translate('offline') }}
                                    </option>
                                    <option value="on_break" {{ $provider->status == 'on_break' ? 'selected' : '' }}>
                                        {{ translate('on_break') }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color">{{ translate('current_location') }}</label>
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control" 
                                           value="{{ $provider->current_latitude }}, {{ $provider->current_longitude }}" 
                                           readonly disabled>
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="updateLocation({{ $provider->id }})">
                                        <i class="tio-refresh"></i>
                                    </button>
                                </div>
                                <small class="text-muted">
                                    {{ translate('last_updated') }}: {{ $provider->last_location_update?->diffForHumans() ?? translate('never') }}
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color d-flex align-items-center gap-2">
                                    {{ translate('account_status') }}
                                </label>
                                <div class="d-flex align-items-center gap-3">
                                    <label class="switcher">
                                        <input type="checkbox" class="switcher_input" name="is_active" 
                                               {{ $provider->user->is_active ? 'checked' : '' }}>
                                        <span class="switcher_control"></span>
                                    </label>
                                    <span>{{ $provider->user->is_active ? translate('active') : translate('inactive') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row justify-content-end gap-3 mt-3 mx-1">
                <a href="{{ route('admin.tow-management.providers.list') }}" class="btn btn-secondary px-5">
                    {{ translate('cancel') }}
                </a>
                <button type="submit" class="btn btn--primary px-5">
                    {{ translate('update_provider') }}
                </button>
            </div>
        </form>
    </div>

    <!-- Location Update Modal -->
    <div class="modal fade" id="locationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('update_location') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="location-map" style="height: 300px; width: 100%;"></div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label>{{ translate('latitude') }}</label>
                            <input type="text" class="form-control" id="modal-latitude" readonly>
                        </div>
                        <div class="col-md-6">
                            <label>{{ translate('longitude') }}</label>
                            <input type="text" class="form-control" id="modal-longitude" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('close') }}</button>
                    <button type="button" class="btn btn--primary" id="save-location">{{ translate('save_location') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden data for JS -->
    <span id="provider-id" data-value="{{ $provider->id }}"></span>
    <span id="current-lat" data-value="{{ $provider->current_latitude }}"></span>
    <span id="current-lng" data-value="{{ $provider->current_longitude }}"></span>
    <span id="route-update-location" data-url="{{ route('admin.tow-management.providers.update-location') }}"></span>
    <span id="mapbox-token" data-value="{{ env('MAPBOX_API_KEY') }}"></span>
@endsection

@push('script')
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.js"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.css" rel="stylesheet">
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/tow-management/provider-edit.js') }}"></script>
    <script>
        function updateLocation(providerId) {
            $('#locationModal').modal('show');
            
            // Initialize map if not already done
            if (!window.locationMap) {
                mapboxgl.accessToken = $('#mapbox-token').data('value');
                window.locationMap = new mapboxgl.Map({
                    container: 'location-map',
                    style: 'mapbox://styles/mapbox/streets-v11',
                    center: [$('#current-lng').data('value') || -74.0060, $('#current-lat').data('value') || 40.7128],
                    zoom: 12
                });
                
                window.locationMarker = new mapboxgl.Marker({draggable: true})
                    .setLngLat([$('#current-lng').data('value') || -74.0060, $('#current-lat').data('value') || 40.7128])
                    .addTo(window.locationMap);
                
                window.locationMarker.on('dragend', function() {
                    const lngLat = window.locationMarker.getLngLat();
                    $('#modal-longitude').val(lngLat.lng);
                    $('#modal-latitude').val(lngLat.lat);
                });
            }
            
            $('#save-location').off('click').on('click', function() {
                const lngLat = window.locationMarker.getLngLat();
                
                $.ajax({
                    url: $('#route-update-location').data('url'),
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        provider_id: providerId,
                        latitude: lngLat.lat,
                        longitude: lngLat.lng
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('#locationModal').modal('hide');
                        }
                    }
                });
            });
        }
    </script>
@endpush