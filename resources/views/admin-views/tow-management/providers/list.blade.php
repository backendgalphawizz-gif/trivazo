@extends('layouts.back-end.app')

@section('title', translate('tow_providers'))

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
                {{ translate('tow_providers') }}
            </h2>
            <div class="ml-auto">
                <a href="{{ route('admin.tow-management.providers.add') }}" class="btn btn--primary">
                    <i class="tio-add"></i> {{ translate('add_new_provider') }}
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <div class="card card-statistic">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <h4 class="mb-2">{{ translate('total_providers') }}</h4>
                            <h2 class="mb-0">{{ $statistics['total'] }}</h2>
                        </div>
                        <div class="bg-soft-primary rounded-circle p-3">
                            <i class="tio-user-big"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-statistic">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <h4 class="mb-2">{{ translate('available') }}</h4>
                            <h2 class="mb-0 text-success">{{ $statistics['available'] }}</h2>
                        </div>
                        <div class="bg-soft-success rounded-circle p-3">
                            <i class="tio-checkmark-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-statistic">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <h4 class="mb-2">{{ translate('busy') }}</h4>
                            <h2 class="mb-0 text-warning">{{ $statistics['busy'] }}</h2>
                        </div>
                        <div class="bg-soft-warning rounded-circle p-3">
                            <i class="tio-bus"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-statistic">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <h4 class="mb-2">{{ translate('total_trips') }}</h4>
                            <h2 class="mb-0">{{ $statistics['total_trips'] }}</h2>
                        </div>
                        <div class="bg-soft-info rounded-circle p-3">
                            <i class="tio-home"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('admin.tow-management.providers.list') }}" method="GET">
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
                            <select class="js-select2-custom form-control" name="rating">
                                <option value="all" {{ $filters['rating'] == 'all' ? 'selected' : '' }}>
                                    {{ translate('all_ratings') }}
                                </option>
                                <option value="4" {{ $filters['rating'] == '4' ? 'selected' : '' }}>4+ ⭐</option>
                                <option value="3" {{ $filters['rating'] == '3' ? 'selected' : '' }}>3+ ⭐</option>
                                <option value="2" {{ $filters['rating'] == '2' ? 'selected' : '' }}>2+ ⭐</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-2">
                                <input type="text" name="searchValue" class="form-control" 
                                       placeholder="{{ translate('search_by_company_or_owner') }}" 
                                       value="{{ request('searchValue') }}">
                                <button type="submit" class="btn btn--primary">
                                    <i class="tio-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('admin.tow-management.providers.export', request()->all()) }}" 
                               class="btn btn-outline-primary btn-block">
                                <i class="tio-download"></i> {{ translate('export') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Providers Grid -->
        <div class="row g-3">
            @forelse($providers as $provider)
                <div class="col-md-6 col-xl-4">
                    <div class="card provider-card h-100">
                        <div class="card-body">
                            <!-- Header with Status -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $provider->user->image_full_url['path'] ?? dynamicAsset('public/assets/back-end/img/provider.png') }}"
                                         class="rounded-circle" width="50" height="50" alt="">
                                    <div>
                                        <h5 class="mb-1">{{ $provider->company_name }}</h5>
                                        <p class="mb-0 small text-muted">{{ $provider->owner_name }}</p>
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-end gap-2">
                                    <span class="badge badge-{{ $providerService->getStatusBadge($provider->status) }} p-2">
                                        {{ translate($provider->status) }}
                                    </span>
                                    @if($provider->is_available)
                                        <span class="badge badge-success">{{ translate('available') }}</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Rating and Stats -->
                            <div class="d-flex gap-3 mb-3">
                                <div class="bg-light p-2 rounded text-center flex-grow-1">
                                    <small class="text-muted">{{ translate('rating') }}</small>
                                    <p class="mb-0 font-weight-bold">⭐ {{ number_format($provider->rating, 1) }}</p>
                                </div>
                                <div class="bg-light p-2 rounded text-center flex-grow-1">
                                    <small class="text-muted">{{ translate('trips') }}</small>
                                    <p class="mb-0 font-weight-bold">{{ $provider->total_completed_trips }}</p>
                                </div>
                                <div class="bg-light p-2 rounded text-center flex-grow-1">
                                    <small class="text-muted">{{ translate('slots') }}</small>
                                    <p class="mb-0 font-weight-bold">{{ $provider->availability_slots }}/{{ $provider->max_simultaneous_trips }}</p>
                                </div>
                            </div>

                            <!-- Contact Info -->
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="tio-call text-muted"></i>
                                    <span>{{ $provider->user->phone }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="tio-email text-muted"></i>
                                    <span class="text-truncate">{{ $provider->user->email }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="tio-map text-muted"></i>
                                    <span class="text-truncate">{{ $provider->service_area ?: translate('all_areas') }}</span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.tow-management.providers.details', ['id' => $provider->id]) }}" 
                                   class="btn btn-outline-info btn-sm flex-grow-1">
                                    <i class="tio-visible"></i> {{ translate('details') }}
                                </a>
                                <a href="{{ route('admin.tow-management.providers.update', ['id' => $provider->id]) }}" 
                                   class="btn btn-outline-primary btn-sm flex-grow-1">
                                    <i class="tio-edit"></i> {{ translate('edit') }}
                                </a>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                            data-toggle="dropdown">
                                        <i class="tio-more"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="{{ route('admin.tow-management.providers.trips', ['id' => $provider->id]) }}">
                                            <i class="tio-bus"></i> {{ translate('view_trips') }}
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <label class="dropdown-item d-flex align-items-center justify-content-between">
                                            <span><i class="tio-toggle-on"></i> {{ translate('status') }}</span>
                                            <label class="switcher">
                                                <input type="checkbox" class="switcher_input provider-status-toggle" 
                                                       data-id="{{ $provider->id }}"
                                                       {{ $provider->status == 'available' ? 'checked' : '' }}>
                                                <span class="switcher_control"></span>
                                            </label>
                                        </label>
                                        <button class="dropdown-item text-danger delete-provider" 
                                                data-id="{{ $provider->id }}"
                                                data-name="{{ $provider->company_name }}">
                                            <i class="tio-delete"></i> {{ translate('delete') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/no-provider.png') }}" 
                                 width="100" alt="">
                            <h4 class="mt-3">{{ translate('no_providers_found') }}</h4>
                            <p class="text-muted">{{ translate('add_your_first_tow_provider') }}</p>
                            <a href="{{ route('admin.tow-management.providers.add') }}" class="btn btn--primary mt-2">
                                <i class="tio-add"></i> {{ translate('add_provider') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-end mt-3">
            {{ $providers->links() }}
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('delete_provider') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>{{ translate('are_you_sure_you_want_to_delete') }} <span id="delete-provider-name"></span>?</p>
                    <p class="text-warning"><small>{{ translate('this_action_cannot_be_undone') }}</small></p>
                </div>
                <div class="modal-footer">
                    <form id="delete-form" method="POST" action="">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('cancel') }}</button>
                        <button type="submit" class="btn btn-danger">{{ translate('delete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden data for JS -->
    <span id="route-status-update" data-url="{{ route('admin.tow-management.providers.status') }}"></span>
    <span id="route-delete" data-url="{{ route('admin.tow-management.providers.delete', ['id' => '']) }}"></span>
    <span id="message-status-updated" data-text="{{ translate('status_updated_successfully') }}"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/tow-management/providers.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Status toggle
            $('.provider-status-toggle').on('change', function() {
                let providerId = $(this).data('id');
                let status = $(this).is(':checked') ? 'available' : 'offline';
                
                $.ajax({
                    url: $('#route-status-update').data('url'),
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: providerId,
                        status: status
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                        }
                    }
                });
            });

            // Delete provider
            $('.delete-provider').on('click', function() {
                let id = $(this).data('id');
                let name = $(this).data('name');
                $('#delete-provider-name').text(name);
                $('#delete-form').attr('action', $('#route-delete').data('url') + '/' + id);
                $('#deleteModal').modal('show');
            });
        });
    </script>
@endpush