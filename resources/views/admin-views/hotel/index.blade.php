@extends('layouts.back-end.app')

@section('title', translate('Monitor Bookings & Cancellations'))

@section('content')
<div class="content container-fluid">
    <x-back-end.page-header 
        title="bookings_monitoring" 
        icon="booking.png" 
    />

    <!-- Statistics Cards -->
    <div class="row g-3 mb-3">
        <div class="col-lg-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-capitalize">{{ translate('total_bookings') }}</span>
                            <h2 class="mb-0">{{ $totalBookings }}</h2>
                        </div>
                        <div class="avatar avatar-lg bg-soft-primary">
                            <i class="tio-airport tio-font-size-24"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-capitalize">{{ translate('pending_bookings') }}</span>
                            <h2 class="mb-0 text-warning">{{ $pendingBookings }}</h2>
                        </div>
                        <div class="avatar avatar-lg bg-soft-warning">
                            <i class="tio-time"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-capitalize">{{ translate('confirmed_bookings') }}</span>
                            <h2 class="mb-0 text-success">{{ $confirmedBookings }}</h2>
                        </div>
                        <div class="avatar avatar-lg bg-soft-success">
                            <i class="tio-checkmark-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-capitalize">{{ translate('cancelled_bookings') }}</span>
                            <h2 class="mb-0 text-danger">{{ $cancelledBookings }}</h2>
                        </div>
                        <div class="avatar avatar-lg bg-soft-danger">
                            <i class="tio-block"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-capitalize">{{ translate('total_revenue') }}</span>
                            <h2 class="mb-0">{{ Helpers::currency_converter($totalRevenue) }}</h2>
                        </div>
                        <div class="avatar avatar-lg bg-soft-info">
                            <i class="tio-money"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ url()->current() }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ translate('From Date') }}</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ translate('To Date') }}</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ translate('Hotel') }}</label>
                        <select name="hotel_id" class="form-control">
                            <option value="">{{ translate('All Hotels') }}</option>
                            @foreach($hotels as $hotel)
                                <option value="{{ $hotel->id }}" {{ request('hotel_id') == $hotel->id ? 'selected' : '' }}>
                                    {{ $hotel->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ translate('Booking Status') }}</label>
                        <select name="booking_status" class="form-control">
                            <option value="">{{ translate('All') }}</option>
                            <option value="pending" {{ request('booking_status') == 'pending' ? 'selected' : '' }}>{{ translate('Pending') }}</option>
                            <option value="confirmed" {{ request('booking_status') == 'confirmed' ? 'selected' : '' }}>{{ translate('Confirmed') }}</option>
                            <option value="checked_in" {{ request('booking_status') == 'checked_in' ? 'selected' : '' }}>{{ translate('Checked In') }}</option>
                            <option value="checked_out" {{ request('booking_status') == 'checked_out' ? 'selected' : '' }}>{{ translate('Checked Out') }}</option>
                            <option value="cancelled" {{ request('booking_status') == 'cancelled' ? 'selected' : '' }}>{{ translate('Cancelled') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn--primary w-100">
                            <i class="tio-filter"></i> {{ translate('Filter') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bookings Table -->
    <div class="card">
        <div class="card-header border-0">
            <h5 class="mb-0">{{ translate('Recent Bookings') }}</h5>
            <div class="dropdown">
                <a href="{{ route('admin.hotel-bookings.export') }}" class="btn btn-outline--primary">
                    <img width="14" src="{{dynamicAsset('public/assets/back-end/img/excel.png')}}" alt="">
                    {{ translate('Export') }}
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Booking #') }}</th>
                            <th>{{ translate('Hotel') }}</th>
                            <th>{{ translate('Customer') }}</th>
                            <th>{{ translate('Check In') }}</th>
                            <th>{{ translate('Check Out') }}</th>
                            <th>{{ translate('Total') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                        <tr>
                            <td>
                                <strong>{{ $booking->booking_number }}</strong>
                                <br>
                                <small class="text-muted">{{ $booking->created_at->format('d M Y') }}</small>
                            </td>
                            <td>{{ $booking->hotel->name ?? 'N/A' }}</td>
                            <td>
                                {{ $booking->customer->f_name ?? '' }} {{ $booking->customer->l_name ?? '' }}
                                <br>
                                <small class="text-muted">{{ $booking->customer->phone ?? '' }}</small>
                            </td>
                            <td>{{ date('d M Y', strtotime($booking->check_in_date)) }}</td>
                            <td>{{ date('d M Y', strtotime($booking->check_out_date)) }}</td>
                            <td>{{ Helpers::currency_converter($booking->total_price) }}</td>
                            <td>
                                @if($booking->booking_status == 'pending')
                                    <span class="badge badge-soft-warning">{{ translate('Pending') }}</span>
                                @elseif($booking->booking_status == 'confirmed')
                                    <span class="badge badge-soft-success">{{ translate('Confirmed') }}</span>
                                @elseif($booking->booking_status == 'checked_in')
                                    <span class="badge badge-soft-info">{{ translate('Checked In') }}</span>
                                @elseif($booking->booking_status == 'checked_out')
                                    <span class="badge badge-soft-dark">{{ translate('Checked Out') }}</span>
                                @elseif($booking->booking_status == 'cancelled')
                                    <span class="badge badge-soft-danger">{{ translate('Cancelled') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2 justify-content-center">
                                    <a href="{{ route('admin.hotel-bookings.view', $booking->id) }}" class="btn btn-outline-info btn-sm">
                                        <i class="tio-visible"></i>
                                    </a>
                                    
                                    @if(!in_array($booking->booking_status, ['cancelled', 'checked_out']))
                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                data-toggle="modal" 
                                                data-target="#cancelBookingModal{{ $booking->id }}"
                                                title="{{ translate('Cancel Booking') }}">
                                            <i class="tio-block"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <!-- Cancel Modal -->
                        <div class="modal fade" id="cancelBookingModal{{ $booking->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('admin.hotel-bookings.cancel', $booking->id) }}" method="post">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ translate('Cancel Booking') }} #{{ $booking->booking_number }}</h5>
                                            <button type="button" class="close" data-dismiss="modal">
                                                <span>&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>{{ translate('Cancellation Reason') }} <span class="text-danger">*</span></label>
                                                <textarea name="cancellation_reason" class="form-control" rows="4" required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Close') }}</button>
                                            <button type="submit" class="btn btn-danger">{{ translate('Confirm Cancellation') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($bookings->isEmpty())
            <div class="text-center p-5">
                <img src="{{ dynamicAsset('public/assets/back-end/img/no-data.svg') }}" alt="" width="200">
                <h5 class="mt-3">{{ translate('No bookings found') }}</h5>
            </div>
        @endif

        <div class="p-3">
            {{ $bookings->links() }}
        </div>
    </div>
</div>
@endsection