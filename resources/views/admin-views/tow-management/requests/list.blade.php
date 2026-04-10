@extends('layouts.back-end.app')

@section('title', translate('tow_requests'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/css/tags-input.min.css') }}" rel="stylesheet">
    <link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/css/custom.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h2 class="h1 mb-0 d-flex gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/tow-requests.png') }}" alt="">
                {{ translate('tow_requests') }}
            </h2>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <div class="card card-statistic">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <h4 class="mb-2">{{ translate('total_requests') }}</h4>
                            <h2 class="mb-0">{{ $statistics['total'] }}</h2>
                        </div>
                        <div class="bg-soft-primary rounded-circle p-3">
                            <i class="tio-home"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-statistic">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <h4 class="mb-2">{{ translate('pending') }}</h4>
                            <h2 class="mb-0 text-warning">{{ $statistics['pending'] }}</h2>
                        </div>
                        <div class="bg-soft-warning rounded-circle p-3">
                            <i class="tio-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-statistic">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <h4 class="mb-2">{{ translate('in_progress') }}</h4>
                            <h2 class="mb-0 text-primary">{{ $statistics['in_progress'] }}</h2>
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
                            <h4 class="mb-2">{{ translate('completed') }}</h4>
                            <h2 class="mb-0 text-success">{{ $statistics['completed'] }}</h2>
                        </div>
                        <div class="bg-soft-success rounded-circle p-3">
                            <i class="tio-checkmark-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('admin.tow-management.requests.list') }}" method="GET">
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
                            <select class="js-select2-custom form-control" name="priority">
                                <option value="all" {{ $filters['priority'] == 'all' ? 'selected' : '' }}>
                                    {{ translate('all_priorities') }}
                                </option>
                                @foreach($priorities as $priority)
                                    <option value="{{ $priority }}" {{ $filters['priority'] == $priority ? 'selected' : '' }}>
                                        {{ translate($priority) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="js-select2-custom form-control" name="service_type">
                                <option value="all" {{ $filters['service_type'] == 'all' ? 'selected' : '' }}>
                                    {{ translate('all_services') }}
                                </option>
                                @foreach($serviceTypes as $type)
                                    <option value="{{ $type }}" {{ $filters['service_type'] == $type ? 'selected' : '' }}>
                                        {{ translate(str_replace('_', ' ', $type)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <input type="text" name="searchValue" class="form-control" 
                                       placeholder="{{ translate('search_by_customer') }}" 
                                       value="{{ request('searchValue') }}">
                                <button type="submit" class="btn btn--primary">
                                    <i class="tio-search"></i>
                                </button>
                                <a href="{{ route('admin.tow-management.requests.export', request()->all()) }}" 
                                   class="btn btn-outline-primary" title="{{ translate('export') }}">
                                    <i class="tio-download"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">{{ translate('tow_requests_list') }}</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-align-middle mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('id') }}</th>
                                <th>{{ translate('customer') }}</th>
                                <th>{{ translate('service_type') }}</th>
                                <th>{{ translate('pickup_location') }}</th>
                                <th>{{ translate('priority') }}</th>
                                <th>{{ translate('status') }}</th>
                                <th>{{ translate('waiting_time') }}</th>
                                <th>{{ translate('action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($towRequests as $request)
                                <tr>
                                    <td>#{{ $request->id }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span>{{ $request->customer->f_name }} {{ $request->customer->l_name }}</span>
                                            <small class="text-muted">{{ $request->customer->phone }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $towRequestService->getServiceTypeBadge($request->service_type) }}">
                                            {{ translate(str_replace('_', ' ', $request->service_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span>{{ Str::limit($request->pickup_location, 30) }}</span>
                                            @if($request->destination)
                                                <small class="text-muted">{{ translate('to') }}: {{ Str::limit($request->destination, 20) }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $towRequestService->getPriorityBadge($request->priority) }}">
                                            {{ translate($request->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $towRequestService->getStatusBadge($request->status) }}">
                                            {{ translate($request->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($request->status == 'pending' || $request->status == 'assigned')
                                            <span class="text-danger">{{ $towRequestService->formatWaitingTime($request->created_at) }}</span>
                                        @else
                                            {{ $towRequestService->formatWaitingTime($request->created_at) }}
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('admin.tow-management.requests.details', ['id' => $request->id]) }}" 
                                               class="btn btn-sm btn-outline-info" title="{{ translate('view_details') }}">
                                                <i class="tio-visible"></i>
                                            </a>
                                            @if($request->status == 'pending')
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="openAssignModal({{ $request->id }})" 
                                                        title="{{ translate('assign_provider') }}">
                                                    <i class="tio-add"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-end p-3">
                    {{ $towRequests->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Provider Modal -->
    <div class="modal fade" id="assignProviderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('assign_provider') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="assign-provider-modal-body">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden data for JS -->
    <span id="route-assign-provider" data-url="{{ route('admin.tow-management.active-trips.assign') }}"></span>
    <span id="message-select-provider" data-text="{{ translate('select_provider') }}"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/tow-management/requests.js') }}"></script>
    <!--  -->
@endpush