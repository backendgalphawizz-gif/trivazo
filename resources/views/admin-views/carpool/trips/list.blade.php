@extends('layouts.back-end.app')

@section('title', translate('carpool_trips'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="content container-fluid">

    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 d-flex gap-2 align-items-center">
            <i class="tio-map"></i>
            {{ translate('carpool_trips') }}
        </h2>
        <div class="ml-auto">
            <a href="{{ route('admin.carpool.trips.add') }}" class="btn btn--primary">
                <i class="tio-add"></i> {{ translate('add_trip') }}
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-2 mb-3">
        <div class="col-sm-6 col-md-3">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">{{ translate('total_trips') }}</div>
                        <h2 class="mb-0">{{ $statistics['total'] }}</h2>
                    </div>
                    <span class="bg-soft-primary rounded-circle p-3"><i class="tio-map h2 mb-0"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">{{ translate('open') }}</div>
                        <h2 class="mb-0 text-success">{{ $statistics['open'] }}</h2>
                    </div>
                    <span class="bg-soft-success rounded-circle p-3"><i class="tio-checkmark-circle h2 mb-0"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">{{ translate('completed') }}</div>
                        <h2 class="mb-0 text-primary">{{ $statistics['completed'] }}</h2>
                    </div>
                    <span class="bg-soft-primary rounded-circle p-3"><i class="tio-flag h2 mb-0"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">{{ translate('cancelled') }}</div>
                        <h2 class="mb-0 text-danger">{{ $statistics['cancelled'] }}</h2>
                    </div>
                    <span class="bg-soft-danger rounded-circle p-3"><i class="tio-clear-circle h2 mb-0"></i></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('admin.carpool.trips.list') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-sm-6 col-md-2">
                        <label class="form-label small">{{ translate('status') }}</label>
                        <select class="js-select2-custom form-control" name="status">
                            <option value="all" {{ ($filters['route_status'] ?? 'all') == 'all' ? 'selected' : '' }}>{{ translate('all') }}</option>
                            @foreach($statuses as $s)
                                <option value="{{ $s }}" {{ ($filters['route_status'] ?? '') == $s ? 'selected' : '' }}>
                                    {{ translate($s) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if(count($rideTypes))
                    <div class="col-sm-6 col-md-2">
                        <label class="form-label small">{{ translate('ride_type') }}</label>
                        <select class="js-select2-custom form-control" name="ride_type">
                            <option value="">{{ translate('all') }}</option>
                            @foreach($rideTypes as $type)
                                <option value="{{ $type }}" {{ ($filters['ride_type'] ?? '') == $type ? 'selected' : '' }}>
                                    {{ translate($type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-sm-6 col-md-2">
                        <label class="form-label small">{{ translate('from_date') }}</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-sm-6 col-md-2">
                        <label class="form-label small">{{ translate('to_date') }}</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">{{ translate('search') }}</label>
                        <div class="d-flex gap-2">
                            <input type="text" name="search" class="form-control"
                                   placeholder="{{ translate('origin_or_destination') }}"
                                   value="{{ request('search') }}">
                            <button type="submit" class="btn btn--primary px-3"><i class="tio-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <a href="{{ route('admin.carpool.trips.list') }}" class="btn btn-outline-secondary btn-block">
                            {{ translate('reset') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-header d-flex align-items-center gap-2">
            <h5 class="mb-0">{{ translate('trips_list') }}</h5>
            <span class="badge badge-soft-dark ml-2">{{ $trips->total() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>{{ translate('driver') }}</th>
                            <th>{{ translate('route') }}</th>
                            <th>{{ translate('departure') }}</th>
                            <th>{{ translate('seats') }}</th>
                            <th>{{ translate('price_per_seat') }}</th>
                            <th>{{ translate('bookings') }}</th>
                            <th>{{ translate('status') }}</th>
                            <th class="text-right">{{ translate('action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($trips as $trip)
                        @php
                            $statusColors = [
                                'open'      => 'success',
                                'full'      => 'warning',
                                'departed'  => 'info',
                                'completed' => 'primary',
                                'cancelled' => 'danger',
                            ];
                            $color = $statusColors[$trip->route_status] ?? 'secondary';
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration + ($trips->currentPage() - 1) * $trips->perPage() }}</td>
                            <td>
                                @if($trip->driver)
                                    <div class="font-weight-bold">{{ $trip->driver->name }}</div>
                                    <div class="text-muted small">{{ $trip->driver->phone }}</div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-start gap-1">
                                    <div>
                                        <div class="font-weight-semibold text-truncate" style="max-width:160px" title="{{ $trip->origin_name }}">
                                            <i class="tio-circle-outlined text-success small"></i> {{ $trip->origin_name }}
                                        </div>
                                        <div class="text-muted small text-truncate" style="max-width:160px" title="{{ $trip->destination_name }}">
                                            <i class="tio-circle text-danger small"></i> {{ $trip->destination_name }}
                                        </div>
                                        @if($trip->estimated_distance_km)
                                            <div class="text-muted x-small">{{ number_format($trip->estimated_distance_km, 1) }} km</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="font-weight-semibold">{{ $trip->departure_at ? $trip->departure_at->format('d M Y') : '-' }}</div>
                                <div class="text-muted small">{{ $trip->departure_at ? $trip->departure_at->format('H:i') : '' }}</div>
                            </td>
                            <td>
                                <span class="text-success font-weight-bold">{{ $trip->available_seats }}</span>
                                <span class="text-muted"> / {{ $trip->total_seats }}</span>
                            </td>
                            <td>
                                <span class="font-weight-bold">{{ number_format($trip->price_per_seat, 2) }}</span>
                                @if($trip->currency)
                                    <span class="text-muted small">{{ $trip->currency }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-soft-info">{{ $trip->bookings->count() }}</span>
                            </td>
                            <td>
                                <span class="badge badge-soft-{{ $color }}">{{ translate($trip->route_status) }}</span>
                            </td>
                            <td class="text-right">
                                <div class="dropdown">
                                    <button class="btn btn-xs btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                                        {{ translate('action') }}
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="{{ route('admin.carpool.trips.edit', $trip->id) }}"
                                           class="dropdown-item">
                                            <i class="tio-edit mr-1"></i> {{ translate('edit') }}
                                        </a>
                                        <a href="{{ route('admin.carpool.bookings.list', ['route_id' => $trip->id]) }}"
                                           class="dropdown-item">
                                            <i class="tio-receipt-outlined mr-1"></i> {{ translate('view_bookings') }}
                                        </a>
                                        @if($trip->route_status === 'open' && $trip->available_seats > 0)
                                        <a href="{{ route('admin.carpool.bookings.add', ['trip_id' => $trip->id]) }}"
                                           class="dropdown-item text-success">
                                            <i class="tio-add mr-1"></i> {{ translate('book_seat') }}
                                        </a>
                                        @endif
                                        <div class="dropdown-divider"></div>
                                        @foreach(['cancelled', 'completed'] as $s)
                                            @if($trip->route_status !== $s)
                                            <form action="{{ route('admin.carpool.trips.status', $trip->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="status" value="{{ $s }}">
                                                <button type="submit" class="dropdown-item text-{{ $s === 'cancelled' ? 'danger' : 'primary' }}"
                                                        onclick="return confirm('{{ translate('are_you_sure') }}?')">
                                                    {{ translate('mark_as_' . $s) }}
                                                </button>
                                            </form>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <img src="{{ dynamicAsset('public/assets/back-end/img/empty.png') }}" height="80" alt="" class="mb-2 d-block mx-auto">
                                {{ translate('no_trips_found') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($trips->hasPages())
            <div class="card-footer d-flex justify-content-center">
                {{ $trips->appends(request()->all())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('script')
<script src="{{ dynamicAsset(path: 'public/assets/select2/js/select2.min.js') }}"></script>
<script>
    $(document).ready(function () { $('.js-select2-custom').select2(); });
</script>
@endpush
