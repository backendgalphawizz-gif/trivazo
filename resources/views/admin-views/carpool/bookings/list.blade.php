@extends('layouts.back-end.app')

@section('title', translate('carpool_bookings'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="content container-fluid">

    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 d-flex gap-2 align-items-center">
            <i class="tio-receipt-outlined"></i>
            {{ translate('carpool_bookings') }}
        </h2>
        <div class="ml-auto">
            <a href="{{ route('admin.carpool.bookings.add') }}" class="btn btn--primary">
                <i class="tio-add"></i> {{ translate('add_booking') }}
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-2 mb-3">
        <div class="col-sm-6 col-md-3">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">{{ translate('total_bookings') }}</div>
                        <h2 class="mb-0">{{ $statistics['total'] }}</h2>
                    </div>
                    <span class="bg-soft-primary rounded-circle p-3"><i class="tio-receipt-outlined h2 mb-0"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">{{ translate('confirmed') }}</div>
                        <h2 class="mb-0 text-success">{{ $statistics['confirmed'] }}</h2>
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
            <form action="{{ route('admin.carpool.bookings.list') }}" method="GET">
                @if(!empty($filters['route_id']))
                    <input type="hidden" name="route_id" value="{{ $filters['route_id'] }}">
                @endif
                <div class="row g-3 align-items-end">
                    <div class="col-sm-6 col-md-2">
                        <label class="form-label small">{{ translate('booking_status') }}</label>
                        <select class="js-select2-custom form-control" name="status">
                            <option value="all" {{ ($filters['status'] ?? 'all') == 'all' ? 'selected' : '' }}>{{ translate('all') }}</option>
                            @foreach($statuses as $s)
                                <option value="{{ $s }}" {{ ($filters['status'] ?? '') == $s ? 'selected' : '' }}>
                                    {{ translate($s) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-2">
                        <label class="form-label small">{{ translate('payment_status') }}</label>
                        <select class="js-select2-custom form-control" name="payment_status">
                            <option value="all" {{ ($filters['payment_status'] ?? 'all') == 'all' ? 'selected' : '' }}>{{ translate('all') }}</option>
                            @foreach($paymentStatuses as $ps)
                                <option value="{{ $ps }}" {{ ($filters['payment_status'] ?? '') == $ps ? 'selected' : '' }}>
                                    {{ translate($ps) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
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
                                   placeholder="{{ translate('booking_code') }}"
                                   value="{{ request('search') }}">
                            <button type="submit" class="btn btn--primary px-3"><i class="tio-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <a href="{{ route('admin.carpool.bookings.list') }}" class="btn btn-outline-secondary btn-block">
                            {{ translate('reset') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Route filter badge --}}
    @if($filterRoute)
        <div class="alert alert-soft-info d-flex align-items-center gap-2 mb-3">
            <i class="tio-map-outlined"></i>
            <span>
                {{ translate('filtering_by_trip') }}:
                <strong>{{ $filterRoute->origin_name }} &rarr; {{ $filterRoute->destination_name }}</strong>
                @if($filterRoute->driver)
                    &mdash; {{ translate('driver') }}: <strong>{{ $filterRoute->driver->name }}</strong>
                @endif
                @if($filterRoute->departure_at)
                    &mdash; {{ $filterRoute->departure_at->format('d M Y H:i') }}
                @endif
            </span>
            <a href="{{ route('admin.carpool.bookings.list') }}" class="ml-auto btn btn-xs btn-outline-secondary">{{ translate('clear_filter') }}</a>
        </div>
    @endif

    {{-- Table --}}
    <div class="card">
        <div class="card-header d-flex align-items-center gap-2">
            <h5 class="mb-0">{{ translate('bookings_list') }}</h5>
            <span class="badge badge-soft-dark ml-2">{{ $bookings->total() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>{{ translate('booking_code') }}</th>
                            <th>{{ translate('passenger') }}</th>
                            <th>{{ translate('driver_route') }}</th>
                            <th>{{ translate('pickup_drop') }}</th>
                            <th>{{ translate('seats') }}</th>
                            <th>{{ translate('fare') }}</th>
                            <th>{{ translate('payment') }}</th>
                            <th>{{ translate('status') }}</th>
                            <th class="text-right">{{ translate('action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($bookings as $booking)
                        @php
                            $statusColors = [
                                'pending'   => 'secondary',
                                'confirmed' => 'success',
                                'departed'  => 'info',
                                'completed' => 'primary',
                                'cancelled' => 'danger',
                            ];
                            $payColors = ['paid' => 'success', 'unpaid' => 'warning', 'refunded' => 'info'];
                            $statusColor = $statusColors[$booking->status] ?? 'secondary';
                            $payColor    = $payColors[$booking->payment_status] ?? 'secondary';
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration + ($bookings->currentPage() - 1) * $bookings->perPage() }}</td>
                            <td>
                                <span class="badge badge-soft-dark font-mono">{{ $booking->booking_code }}</span>
                                <div class="text-muted x-small">{{ $booking->created_at->format('d M Y H:i') }}</div>
                            </td>
                            <td>
                                @if($booking->passenger)
                                    <div class="font-weight-bold">{{ $booking->passenger->f_name ?? '' }} {{ $booking->passenger->l_name ?? '' }}</div>
                                    <div class="text-muted small">{{ $booking->passenger->phone ?? '' }}</div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($booking->route)
                                    <div class="text-truncate" style="max-width:120px" title="{{ $booking->route->origin_name }} → {{ $booking->route->destination_name }}">
                                        <i class="tio-circle-outlined text-success small"></i> {{ $booking->route->origin_name }}<br>
                                        <i class="tio-circle text-danger small"></i> {{ $booking->route->destination_name }}
                                    </div>
                                    @if($booking->route->driver)
                                        <div class="text-muted small mt-1">
                                            <i class="tio-user small"></i> {{ $booking->route->driver->name }}
                                        </div>
                                    @endif
                                    @if($booking->route->departure_at)
                                        <div class="text-muted x-small">{{ $booking->route->departure_at->format('d M H:i') }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-truncate small" style="max-width:120px" title="{{ $booking->pickup_name }}">
                                    <i class="tio-circle-outlined text-success small"></i> {{ $booking->pickup_name }}
                                </div>
                                <div class="text-truncate small" style="max-width:120px" title="{{ $booking->drop_name }}">
                                    <i class="tio-circle text-danger small"></i> {{ $booking->drop_name }}
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-soft-secondary">{{ $booking->seat_count }}</span>
                            </td>
                            <td>
                                <div class="font-weight-bold">{{ number_format($booking->fare_total, 2) }}</div>
                                @if($booking->admin_commission_amount)
                                    <div class="text-muted x-small">{{ translate('comm') }}: {{ number_format($booking->admin_commission_amount, 2) }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-soft-{{ $payColor }}">
                                    {{ translate($booking->payment_status) }}
                                </span>
                                @if($booking->payment_method)
                                    <div class="text-muted x-small">{{ $booking->payment_method }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-soft-{{ $statusColor }}">
                                    {{ translate($booking->status) }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="d-flex align-items-center justify-content-end gap-1">
                                    <a href="{{ route('admin.carpool.bookings.show', $booking->id) }}"
                                       class="btn btn-xs btn--info" title="{{ translate('view') }}">
                                        <i class="tio-visible"></i>
                                    </a>
                                    @if(!in_array($booking->status, ['completed', 'cancelled']))
                                    <div class="dropdown">
                                        <button class="btn btn-xs btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                                            {{ translate('action') }}
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            @foreach(['confirmed', 'departed', 'completed', 'cancelled'] as $s)
                                                @if($booking->status !== $s)
                                                <form action="{{ route('admin.carpool.bookings.status', $booking->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="status" value="{{ $s }}">
                                                    @if($s === 'cancelled')
                                                        <input type="hidden" name="reason" value="Cancelled by admin">
                                                    @endif
                                                    <button type="submit"
                                                            class="dropdown-item {{ $s === 'cancelled' ? 'text-danger' : '' }}"
                                                            onclick="return confirm('{{ translate('are_you_sure') }}?')">
                                                        {{ translate('mark_as_' . $s) }}
                                                    </button>
                                                </form>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <img src="{{ dynamicAsset('public/assets/back-end/img/empty.png') }}" height="80" alt="" class="mb-2 d-block mx-auto">
                                {{ translate('no_bookings_found') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($bookings->hasPages())
            <div class="card-footer d-flex justify-content-center">
                {{ $bookings->appends(request()->all())->links() }}
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
