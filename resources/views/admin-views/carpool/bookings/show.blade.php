@extends('layouts.back-end.app')

@section('title', translate('booking_details'))

@section('content')
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

<div class="content container-fluid">

    {{-- Page Header --}}
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 d-flex gap-2 align-items-center">
            <i class="tio-receipt-outlined"></i>
            {{ translate('booking_details') }}
        </h2>
        <div class="ml-auto d-flex gap-2 align-items-center">
            <span class="badge badge-pill badge-soft-{{ $statusColor }} px-3 py-2 font-size-sm">
                {{ translate($booking->status) }}
            </span>
            <a href="{{ route('admin.carpool.bookings.list') }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-arrow-backward"></i> {{ translate('back_to_list') }}
            </a>
        </div>
    </div>

    <div class="row g-3">

        {{-- LEFT COLUMN --}}
        <div class="col-lg-8">

            {{-- Booking Overview Card --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="tio-receipt-outlined mr-1"></i> {{ translate('booking_overview') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">{{ translate('booking_code') }}</div>
                            <span class="badge badge-dark font-size-sm px-3 py-2">{{ $booking->booking_code }}</span>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">{{ translate('booked_on') }}</div>
                            <div class="font-weight-bold">{{ $booking->created_at->format('d M Y, H:i') }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">{{ translate('seats_booked') }}</div>
                            <span class="badge badge-soft-secondary px-3">{{ $booking->seat_count }}</span>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">{{ translate('payment_status') }}</div>
                            <span class="badge badge-soft-{{ $payColor }}">{{ translate($booking->payment_status) }}</span>
                            @if($booking->payment_method)
                                <div class="text-muted x-small mt-1">{{ $booking->payment_method }}</div>
                            @endif
                        </div>
                        @if($booking->gateway_reference)
                        <div class="col-12">
                            <div class="text-muted small mb-1">{{ translate('gateway_reference') }}</div>
                            <code>{{ $booking->gateway_reference }}</code>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Pickup / Drop Card --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="tio-map-outlined mr-1"></i> {{ translate('pickup_drop_points') }}</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="d-flex flex-column align-items-center" style="padding-top:3px">
                            <i class="tio-circle-outlined text-success h4 mb-0"></i>
                            <div style="width:2px;height:40px;background:#dee2e6;margin:4px 0"></div>
                            <i class="tio-circle text-danger h4 mb-0"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="mb-3">
                                <div class="text-muted small">{{ translate('pickup') }}</div>
                                <div class="font-weight-bold">{{ $booking->pickup_name }}</div>
                                @if($booking->pickup_lat && $booking->pickup_lng)
                                    <div class="text-muted x-small">{{ $booking->pickup_lat }}, {{ $booking->pickup_lng }}</div>
                                @endif
                            </div>
                            <div>
                                <div class="text-muted small">{{ translate('drop') }}</div>
                                <div class="font-weight-bold">{{ $booking->drop_name }}</div>
                                @if($booking->drop_lat && $booking->drop_lng)
                                    <div class="text-muted x-small">{{ $booking->drop_lat }}, {{ $booking->drop_lng }}</div>
                                @endif
                            </div>
                        </div>
                        @if($booking->pickup_lat && $booking->pickup_lng && $booking->drop_lat && $booking->drop_lng)
                        <div class="ml-auto">
                            <a href="https://www.google.com/maps/dir/{{ $booking->pickup_lat }},{{ $booking->pickup_lng }}/{{ $booking->drop_lat }},{{ $booking->drop_lng }}"
                               target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                <i class="tio-map-outlined"></i> {{ translate('view_on_map') }}
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Trip / Route Card --}}
            @if($booking->route)
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center gap-2">
                    <h5 class="mb-0"><i class="tio-car mr-1"></i> {{ translate('trip_details') }}</h5>
                    <a href="{{ route('admin.carpool.trips.edit', $booking->route->id) }}"
                       class="ml-auto btn btn-xs btn-outline-secondary">
                        {{ translate('view_trip') }}
                    </a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ translate('route') }}</div>
                            <div>
                                <i class="tio-circle-outlined text-success small"></i>
                                <strong>{{ $booking->route->origin_name }}</strong>
                            </div>
                            <div class="ml-3"><i class="tio-arrow-downward x-small text-muted"></i></div>
                            <div>
                                <i class="tio-circle text-danger small"></i>
                                <strong>{{ $booking->route->destination_name }}</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ translate('departure') }}</div>
                            <div class="font-weight-bold">
                                {{ $booking->route->departure_at ? $booking->route->departure_at->format('d M Y, H:i') : '-' }}
                            </div>
                            @if($booking->route->estimated_duration_min)
                                @php
                                    $dh = intdiv($booking->route->estimated_duration_min, 60);
                                    $dm = $booking->route->estimated_duration_min % 60;
                                @endphp
                                <div class="text-muted small mt-1">
                                    {{ translate('duration') }}: {{ $dh > 0 ? $dh . 'h ' : '' }}{{ $dm > 0 ? $dm . 'min' : '' }}
                                </div>
                            @endif
                            @if($booking->route->estimated_distance_km)
                                <div class="text-muted small">{{ translate('distance') }}: {{ $booking->route->estimated_distance_km }} km</div>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ translate('seats') }}</div>
                            <div>{{ translate('total') }}: <strong>{{ $booking->route->total_seats }}</strong></div>
                            <div>{{ translate('available') }}: <strong>{{ $booking->route->available_seats }}</strong></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ translate('price_per_seat') }}</div>
                            <div class="font-weight-bold h5 mb-0">{{ number_format($booking->route->price_per_seat, 2) }}</div>
                            <span class="badge badge-soft-{{ $booking->route->route_status === 'open' ? 'success' : ($booking->route->route_status === 'completed' ? 'primary' : 'warning') }} mt-1">
                                {{ translate($booking->route->route_status) }}
                            </span>
                        </div>
                        @if($booking->route->ride_type)
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ translate('vehicle_type') }}</div>
                            <div>{{ ucfirst($booking->route->ride_type) }}</div>
                        </div>
                        @endif
                        @if($booking->route->note)
                        <div class="col-12">
                            <div class="text-muted small">{{ translate('note') }}</div>
                            <div>{{ $booking->route->note }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Co-passengers (if any) --}}
            @if($booking->passengers->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="tio-group mr-1"></i> {{ translate('co_passengers') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ translate('name') }}</th>
                                    <th>{{ translate('phone') }}</th>
                                    <th>{{ translate('gender') }}</th>
                                    <th>{{ translate('age') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($booking->passengers as $i => $p)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $p->name ?? '-' }}</td>
                                    <td>{{ $p->phone ?? '-' }}</td>
                                    <td>{{ $p->gender ? ucfirst($p->gender) : '-' }}</td>
                                    <td>{{ $p->age ?? '-' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

        </div>{{-- /col-lg-8 --}}

        {{-- RIGHT COLUMN --}}
        <div class="col-lg-4">

            {{-- Passenger Info --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="tio-user mr-1"></i> {{ translate('passenger') }}</h5>
                </div>
                <div class="card-body">
                    @if($booking->passenger)
                        <div class="font-weight-bold h5 mb-1">
                            {{ $booking->passenger->f_name ?? '' }} {{ $booking->passenger->l_name ?? '' }}
                        </div>
                        @if($booking->passenger->phone)
                            <div class="text-muted mb-1"><i class="tio-android-phone-vs small"></i> {{ $booking->passenger->phone }}</div>
                        @endif
                        @if($booking->passenger->email)
                            <div class="text-muted mb-1"><i class="tio-email small"></i> {{ $booking->passenger->email }}</div>
                        @endif
                        <div class="text-muted small">{{ translate('user_id') }}: #{{ $booking->passenger_id }}</div>
                    @else
                        <span class="text-muted">{{ translate('passenger_not_found') }}</span>
                    @endif
                </div>
            </div>

            {{-- Driver Info --}}
            @if($booking->route && $booking->route->driver)
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="tio-steering-wheel mr-1"></i> {{ translate('driver') }}</h5>
                </div>
                <div class="card-body">
                    @php $driver = $booking->route->driver; @endphp
                    <div class="font-weight-bold h5 mb-1">{{ $driver->name }}</div>
                    @if($driver->phone)
                        <div class="text-muted mb-1"><i class="tio-android-phone-vs small"></i> {{ $driver->phone }}</div>
                    @endif
                    @if($driver->email)
                        <div class="text-muted mb-1"><i class="tio-email small"></i> {{ $driver->email }}</div>
                    @endif
                    @if($driver->vehicle_type)
                        <div class="text-muted small">{{ translate('vehicle') }}: {{ ucfirst($driver->vehicle_type) }}
                            @if($driver->vehicle_plate) &bull; {{ $driver->vehicle_plate }} @endif
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Fare Breakdown --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="tio-money mr-1"></i> {{ translate('fare_breakdown') }}</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted">{{ translate('fare_total') }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($booking->fare_total ?? 0, 2) }}</td>
                        </tr>
                        @if($booking->tax_amount)
                        <tr>
                            <td class="text-muted">{{ translate('tax') }}</td>
                            <td class="text-right">{{ number_format($booking->tax_amount, 2) }}</td>
                        </tr>
                        @endif
                        @if($booking->final_amount)
                        <tr class="border-top">
                            <td class="font-weight-bold">{{ translate('final_amount') }}</td>
                            <td class="text-right font-weight-bold text-primary h5 mb-0">{{ number_format($booking->final_amount, 2) }}</td>
                        </tr>
                        @endif
                        @if($booking->admin_commission_amount)
                        <tr class="border-top">
                            <td class="text-muted small">{{ translate('admin_commission') }}</td>
                            <td class="text-right text-muted small">{{ number_format($booking->admin_commission_amount, 2) }}</td>
                        </tr>
                        @endif
                        @if($booking->driver_amount)
                        <tr>
                            <td class="text-muted small">{{ translate('driver_amount') }}</td>
                            <td class="text-right text-muted small">{{ number_format($booking->driver_amount, 2) }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Timeline --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="tio-time mr-1"></i> {{ translate('timeline') }}</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0" style="border-left:2px solid #dee2e6;padding-left:1rem">
                        <li class="mb-2 position-relative" style="margin-left:-1.25rem">
                            <span class="bg-white border rounded-circle d-inline-block" style="width:10px;height:10px;margin-right:8px;vertical-align:middle"></span>
                            <span class="text-muted small">{{ translate('created') }}</span>
                            <div class="ml-4 text-muted x-small">{{ $booking->created_at->format('d M Y H:i') }}</div>
                        </li>
                        @if($booking->confirmed_at)
                        <li class="mb-2 position-relative" style="margin-left:-1.25rem">
                            <span class="bg-success border border-success rounded-circle d-inline-block" style="width:10px;height:10px;margin-right:8px;vertical-align:middle"></span>
                            <span class="small">{{ translate('confirmed') }}</span>
                            <div class="ml-4 text-muted x-small">{{ $booking->confirmed_at->format('d M Y H:i') }}</div>
                        </li>
                        @endif
                        @if($booking->departed_at)
                        <li class="mb-2 position-relative" style="margin-left:-1.25rem">
                            <span class="bg-info border border-info rounded-circle d-inline-block" style="width:10px;height:10px;margin-right:8px;vertical-align:middle"></span>
                            <span class="small">{{ translate('departed') }}</span>
                            <div class="ml-4 text-muted x-small">{{ $booking->departed_at->format('d M Y H:i') }}</div>
                        </li>
                        @endif
                        @if($booking->completed_at)
                        <li class="mb-2 position-relative" style="margin-left:-1.25rem">
                            <span class="bg-primary border border-primary rounded-circle d-inline-block" style="width:10px;height:10px;margin-right:8px;vertical-align:middle"></span>
                            <span class="small">{{ translate('completed') }}</span>
                            <div class="ml-4 text-muted x-small">{{ $booking->completed_at->format('d M Y H:i') }}</div>
                        </li>
                        @endif
                        @if($booking->cancelled_at)
                        <li class="mb-2 position-relative" style="margin-left:-1.25rem">
                            <span class="bg-danger border border-danger rounded-circle d-inline-block" style="width:10px;height:10px;margin-right:8px;vertical-align:middle"></span>
                            <span class="small text-danger">{{ translate('cancelled') }}</span>
                            <div class="ml-4 text-muted x-small">{{ $booking->cancelled_at->format('d M Y H:i') }}</div>
                            @if($booking->cancelled_by)
                                <div class="ml-4 text-muted x-small">{{ translate('by') }}: {{ $booking->cancelled_by }}</div>
                            @endif
                            @if($booking->cancellation_reason)
                                <div class="ml-4 text-muted x-small">{{ $booking->cancellation_reason }}</div>
                            @endif
                        </li>
                        @endif
                    </ul>
                </div>
            </div>

            {{-- Status Action --}}
            @if(!in_array($booking->status, ['completed', 'cancelled']))
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="tio-edit mr-1"></i> {{ translate('update_status') }}</h5>
                </div>
                <div class="card-body">
                    @foreach(['confirmed', 'departed', 'completed', 'cancelled'] as $s)
                        @if($booking->status !== $s)
                        <form action="{{ route('admin.carpool.bookings.status', $booking->id) }}" method="POST" class="mb-2">
                            @csrf
                            <input type="hidden" name="status" value="{{ $s }}">
                            @if($s === 'cancelled')
                                <input type="hidden" name="reason" value="Cancelled by admin">
                            @endif
                            <button type="submit"
                                    class="btn btn-block btn-sm {{ $s === 'cancelled' ? 'btn-soft-danger' : 'btn-soft-secondary' }}"
                                    onclick="return confirm('{{ translate('are_you_sure') }}?')">
                                {{ translate('mark_as_' . $s) }}
                            </button>
                        </form>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

        </div>{{-- /col-lg-4 --}}

    </div>{{-- /row --}}
</div>
@endsection
