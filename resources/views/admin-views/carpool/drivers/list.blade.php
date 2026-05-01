@extends('layouts.back-end.app')

@section('title', translate('carpool_drivers'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="content container-fluid">

    {{-- Page Header --}}
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 d-flex gap-2 align-items-center">
            <i class="tio-car-outlined"></i>
            {{ translate('carpool_drivers') }}
        </h2>
        <div class="ml-auto">
            <a href="{{ route('admin.carpool.drivers.add') }}" class="btn btn--primary">
                <i class="tio-add"></i> {{ translate('add_driver') }}
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-2 mb-3">
        <div class="col-sm-6 col-md-3">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">{{ translate('total_drivers') }}</div>
                        <h2 class="mb-0">{{ $statistics['total'] }}</h2>
                    </div>
                    <span class="bg-soft-primary rounded-circle p-3"><i class="tio-user-big h2 mb-0"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">{{ translate('active') }}</div>
                        <h2 class="mb-0 text-success">{{ $statistics['active'] }}</h2>
                    </div>
                    <span class="bg-soft-success rounded-circle p-3"><i class="tio-checkmark-circle h2 mb-0"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">{{ translate('verified') }}</div>
                        <h2 class="mb-0 text-primary">{{ $statistics['verified'] }}</h2>
                    </div>
                    <span class="bg-soft-primary rounded-circle p-3"><i class="tio-verified h2 mb-0"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">{{ translate('online_now') }}</div>
                        <h2 class="mb-0 text-info">{{ $statistics['online'] }}</h2>
                    </div>
                    <span class="bg-soft-info rounded-circle p-3"><i class="tio-map h2 mb-0"></i></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('admin.carpool.drivers.list') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small">{{ translate('status') }}</label>
                        <select class="js-select2-custom form-control" name="status">
                            <option value="all" {{ ($filters['status'] ?? 'all') == 'all' ? 'selected' : '' }}>{{ translate('all') }}</option>
                            <option value="active"    {{ ($filters['status'] ?? '') == 'active'    ? 'selected' : '' }}>{{ translate('active') }}</option>
                            <option value="inactive"  {{ ($filters['status'] ?? '') == 'inactive'  ? 'selected' : '' }}>{{ translate('inactive') }}</option>
                            <option value="suspended" {{ ($filters['status'] ?? '') == 'suspended' ? 'selected' : '' }}>{{ translate('suspended') }}</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small">{{ translate('verified') }}</label>
                        <select class="js-select2-custom form-control" name="is_verified">
                            <option value="" {{ !request()->has('is_verified') ? 'selected' : '' }}>{{ translate('all') }}</option>
                            <option value="1" {{ request('is_verified') === '1' ? 'selected' : '' }}>{{ translate('verified') }}</option>
                            <option value="0" {{ request('is_verified') === '0' ? 'selected' : '' }}>{{ translate('unverified') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">{{ translate('search') }}</label>
                        <div class="d-flex gap-2">
                            <input type="text" name="search" class="form-control"
                                   placeholder="{{ translate('name_phone_email_vehicle') }}"
                                   value="{{ request('search') }}">
                            <button type="submit" class="btn btn--primary px-3"><i class="tio-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('admin.carpool.drivers.list') }}" class="btn btn-outline-secondary btn-block">
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
            <h5 class="mb-0">{{ translate('drivers_list') }}</h5>
            <span class="badge badge-soft-dark ml-2">{{ $drivers->total() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>{{ translate('driver') }}</th>
                            <th>{{ translate('vehicle') }}</th>
                            <th>{{ translate('rides') }}</th>
                            <th>{{ translate('rating') }}</th>
                            <th>{{ translate('status') }}</th>
                            <th>{{ translate('verified') }}</th>
                            <th>{{ translate('wallet') }}</th>
                            <th class="text-right">{{ translate('actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($drivers as $driver)
                        <tr>
                            <td>{{ $loop->iteration + ($drivers->currentPage() - 1) * $drivers->perPage() }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($driver->profile_image)
                                        <img src="{{ dynamicStorage(path: 'storage/app/public/' . $driver->profile_image) }}"
                                             class="rounded-circle" width="40" height="40" style="object-fit:cover;">
                                    @else
                                        <span class="bg-soft-primary rounded-circle d-flex align-items-center justify-content-center"
                                              style="width:40px;height:40px;font-size:1.1rem;">
                                            {{ strtoupper(substr($driver->name, 0, 1)) }}
                                        </span>
                                    @endif
                                    <div>
                                        <div class="font-weight-bold">{{ $driver->name }}</div>
                                        <div class="text-muted small">{{ $driver->phone }}</div>
                                        @if($driver->email)
                                            <div class="text-muted x-small">{{ $driver->email }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="font-weight-semibold">{{ $driver->vehicle_number }}</div>
                                <div class="text-muted small">{{ $driver->vehicle_type }}
                                    @if($driver->vehicle_model) &middot; {{ $driver->vehicle_model }}@endif
                                </div>
                                <div class="text-muted small">{{ translate('seats') }}: {{ $driver->vehicle_capacity }}</div>
                            </td>
                            <td>
                                <span class="badge badge-soft-info">{{ $driver->total_completed_rides ?? 0 }}</span>
                            </td>
                            <td>
                                @php($rating = $driver->rating ?? 0)
                                <span class="{{ $rating >= 4 ? 'text-success' : ($rating >= 2.5 ? 'text-warning' : 'text-danger') }} font-weight-bold">
                                    {{ number_format($rating, 1) }} &#9733;
                                </span>
                            </td>
                            <td>
                                <form action="{{ route('admin.carpool.drivers.status', $driver->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @php($statuses = ['active' => 'success', 'inactive' => 'warning', 'suspended' => 'danger'])
                                    <select name="status" class="form-control form-control-sm"
                                            onchange="this.form.submit()" style="width:110px">
                                        @foreach($statuses as $s => $color)
                                            <option value="{{ $s }}" {{ $driver->status === $s ? 'selected' : '' }}>
                                                {{ translate($s) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td>
                                @if($driver->is_verified)
                                    <span class="badge badge-soft-success"><i class="tio-checkmark-circle"></i> {{ translate('verified') }}</span>
                                @else
                                    <form action="{{ route('admin.carpool.drivers.verify', $driver->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-primary"
                                                onclick="return confirm('{{ translate('verify_this_driver') }}?')">
                                            {{ translate('verify') }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td>
                                @if($driver->wallet)
                                    <span class="font-weight-bold">
                                        {{ number_format($driver->wallet->balance ?? 0, 2) }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.carpool.drivers.edit', $driver->id) }}"
                                   class="btn btn-xs btn-outline-primary mr-1" title="{{ translate('edit') }}">
                                    <i class="tio-edit"></i>
                                </a>
                                <a href="{{ route('admin.carpool.trips.list', ['driver_id' => $driver->id]) }}"
                                   class="btn btn-xs btn-outline-info mr-1" title="{{ translate('view_trips') }}">
                                    <i class="tio-map"></i>
                                </a>
                                <a href="{{ route('admin.carpool.bookings.list', ['driver_id' => $driver->id]) }}"
                                   class="btn btn-xs btn-outline-secondary" title="{{ translate('view_bookings') }}">
                                    <i class="tio-receipt-outlined"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <img src="{{ dynamicAsset('public/assets/back-end/img/empty.png') }}" height="80" alt="" class="mb-2 d-block mx-auto">
                                {{ translate('no_drivers_found') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($drivers->hasPages())
            <div class="card-footer d-flex justify-content-center">
                {{ $drivers->appends(request()->all())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('script')
<script src="{{ dynamicAsset(path: 'public/assets/select2/js/select2.min.js') }}"></script>
<script>
    $(document).ready(function () {
        $('.js-select2-custom').select2();
    });
</script>
@endpush
