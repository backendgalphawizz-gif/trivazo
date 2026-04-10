@extends('layouts.back-end.app')

@section('title', translate('View All Hotels'))

@push('css')
    <style>
        .star-rating i {
            font-size: 14px;
        }
        .status-badge {
            min-width: 80px;
            text-align: center;
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <h2 class="h1 mb-0 d-flex align-items-center gap-2">
            <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/hotel.png') }}" alt="">
            {{ translate('view_all_hotels') }}
            <span class="badge badge-soft-dark radius-50 fz-14">{{ $hotels->total() }}</span>
        </h2>
        
        <!-- Add Hotel Button -->
        <a href="{{ route('admin.hotels.create') }}" class="btn btn--primary">
            <i class="tio-add"></i> {{ translate('add_new_hotel') }}
        </a>
    </div>

    <!-- Status Tabs -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-3">
                <a class="btn btn-sm btn--primary">
                <!-- <a href="{{ route('admin.hotels.all') }}" class="btn btn-sm {{ request()->routeIs('admin.hotels.all') ? 'btn--primary' : 'btn-outline--primary' }}"> -->
                    <i class="tio-home"></i> {{ translate('all') }}
                    <span class="badge badge-light ml-1">{{ $totalHotels }}</span>
                </a>
                <a class="btn btn-sm btn-warning">
                <!-- <a href="{{ route('admin.hotels.pending') }}" class="btn btn-sm {{ request()->routeIs('admin.hotels.pending') ? 'btn-warning' : 'btn-outline-warning' }}"> -->
                    <i class="tio-time"></i> {{ translate('pending') }}
                    <span class="badge badge-light ml-1">{{ $pendingCount }}</span>
                </a>
                <a class="btn btn-sm btn-success">
                <!-- <a href="{{ route('admin.hotels.approved') }}" class="btn btn-sm {{ request()->routeIs('admin.hotels.approved') ? 'btn-success' : 'btn-outline-success' }}"> -->
                    <i class="tio-checkmark-circle"></i> {{ translate('approved') }}
                    <span class="badge badge-light ml-1">{{ $approvedCount }}</span>
                </a>
                <a class="btn btn-sm btn-danger">
                <!-- <a href="{{ route('admin.hotels.rejected') }}" class="btn btn-sm {{ request()->routeIs('admin.hotels.rejected') ? 'btn-danger' : 'btn-outline-danger' }}"> -->
                    <i class="tio-block"></i> {{ translate('rejected') }}
                    <span class="badge badge-light ml-1">{{ $rejectedCount }}</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ url()->current() }}" method="GET" class="filter-form">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">{{ translate('search') }}</label>
                        <div class="input-group input-group-custom">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="tio-search"></i></span>
                            </div>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="{{ translate('search_by_hotel_name_city_email') }}" 
                                   value="{{ request('search') }}">
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">{{ translate('seller') }}</label>
                        <select name="seller_id" class="form-control js-select2-custom">
                            <option value="">{{ translate('all_sellers') }}</option>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}" {{ request('seller_id') == $seller->id ? 'selected' : '' }}>
                                    {{ $seller->f_name }} {{ $seller->l_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">{{ translate('city') }}</label>
                        <select name="city" class="form-control js-select2-custom">
                            <option value="">{{ translate('all_cities') }}</option>
                            @foreach($cities as $city)
                                @if($city)
                                    <option value="{{ $city }}" {{ request('city') == $city ? 'selected' : '' }}>{{ $city }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">{{ translate('star_rating') }}</label>
                        <select name="star_rating" class="form-control">
                            <option value="">{{ translate('all_ratings') }}</option>
                            @foreach($starRatings as $rating)
                                <option value="{{ $rating }}" {{ request('star_rating') == $rating ? 'selected' : '' }}>
                                    {{ $rating }} {{ translate('star') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn--primary w-100">
                            <i class="tio-filter"></i> {{ translate('filter') }}
                        </button>
                        <a href="{{ url()->current() }}" class="btn btn-outline-secondary w-100">
                            <i class="tio-clear"></i> {{ translate('reset') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Hotels Table -->
    <div class="card">
        <div class="card-header border-0">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center w-100">
                <h5 class="mb-0">{{ translate('hotels_list') }}</h5>
                <div class="dropdown">
                    <button type="button" class="btn btn-outline--primary text-nowrap dropdown-toggle" data-toggle="dropdown">
                        <img width="14" src="{{ dynamicAsset(path: 'public/assets/back-end/img/excel.png') }}" alt="">
                        <span class="ps-2">{{ translate('export') }}</span>
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{ route('admin.hotels.export', 'excel') }}">
                            <img width="14" src="{{ dynamicAsset(path: 'public/assets/back-end/img/excel.png') }}" alt="">
                            {{ translate('excel') }}
                        </a>
                        <a class="dropdown-item" href="{{ route('admin.hotels.export', 'pdf') }}">
                            <i class="tio-file-pdf"></i>
                            {{ translate('pdf') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                    <thead class="thead-light thead-50">
                        <tr>
                            <th class="text-center">#</th>
                            <th>{{ translate('hotel') }}</th>
                            <th>{{ translate('seller') }}</th>
                            <th>{{ translate('location') }}</th>
                            <th class="text-center">{{ translate('star_rating') }}</th>
                            <th class="text-center">{{ translate('total_rooms') }}</th>
                            <th class="text-center">{{ translate('status') }}</th>
                            <th class="text-center">{{ translate('action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hotels as $key => $hotel)
                        <tr>
                            <td class="text-center">{{ $hotels->firstItem() + $key }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-60 avatar-circle overflow-hidden">
                                            <img class="img-fluid" alt=""
                                                 src="{{ $hotel->featured_image ? dynamicAsset(path: '/public/storage/app/hotel/hotel/' . $hotel->featured_image) : dynamicAsset(path: 'public/assets/back-end/img/placeholder-hotel.png') }}">
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $hotel->name }}</h6>
                                        <small class="text-muted">{{ $hotel->email }}</small>
                                        <br>
                                        <small class="text-muted">{{ $hotel->phone }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($hotel->seller)
                                    <a href="{{ route('admin.vendors.view', $hotel->seller_id) }}" class="text-primary">
                                        {{ $hotel->seller->f_name ?? '' }} {{ $hotel->seller->l_name ?? '' }}
                                    </a>
                                    <br>
                                    <small class="text-muted">{{ $hotel->seller->phone ?? '' }}</small>
                                @else
                                    <span class="text-muted">{{ translate('seller_not_found') }}</span>
                                @endif
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $hotel->city }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $hotel->country }}</small>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="star-rating d-flex justify-content-center">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="tio-star {{ $i <= $hotel->star_rating ? 'text-warning' : 'text-muted' }}"></i>
                                    @endfor
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-soft-info">
                                    {{ $hotel->total_rooms ?? 0 }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($hotel->status == 0)
                                    <span class="badge badge-soft-warning status-badge">
                                        <i class="tio-time"></i> {{ translate('pending') }}
                                    </span>
                                @elseif($hotel->status == 1)
                                    <span class="badge badge-soft-success status-badge">
                                        <i class="tio-checkmark-circle"></i> {{ translate('approved') }}
                                    </span>
                                @else
                                    <span class="badge badge-soft-danger status-badge">
                                        <i class="tio-block"></i> {{ translate('rejected') }}
                                    </span>
                                @endif
                                
                                @if($hotel->is_featured)
                                    <br>
                                    <span class="badge badge-soft-info mt-1">
                                        <i class="tio-star"></i> {{ translate('featured') }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <!-- View Button -->
                                    <a class="btn btn-outline-info btn-sm square-btn" 
                                       href="{{ route('admin.hotels.view', $hotel->id) }}"
                                       title="{{ translate('view_details') }}">
                                        <i class="tio-visible"></i>
                                    </a>
                                    
                                    <!-- Edit Button -->
                                    <a class="btn btn-outline-primary btn-sm square-btn" 
                                       href="{{ route('admin.hotels.edit', $hotel->id) }}"
                                       title="{{ translate('edit') }}">
                                        <i class="tio-edit"></i>
                                    </a>
                                    
                                    <!-- Approve/Reject for pending hotels -->
                                    @if($hotel->status == 0)
                                        <form action="{{ route('admin.hotels.approve', $hotel->id) }}" method="post" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-success btn-sm square-btn" 
                                                    title="{{ translate('approve') }}"
                                                    onclick="return confirm('{{ translate('are_you_sure_to_approve_this_hotel') }}')">
                                                <i class="tio-checkmark-circle"></i>
                                            </button>
                                        </form>
                                        
                                        <button type="button" class="btn btn-outline-danger btn-sm square-btn" 
                                                title="{{ translate('reject') }}"
                                                data-toggle="modal" 
                                                data-target="#rejectModal{{ $hotel->id }}">
                                            <i class="tio-block"></i>
                                        </button>
                                    @endif
                                    
                                    <!-- Featured Toggle -->
                                    <button type="button" class="btn btn-outline-warning btn-sm square-btn toggle-featured" 
                                            title="{{ translate('toggle_featured') }}"
                                            data-id="{{ $hotel->id }}"
                                            data-featured="{{ $hotel->is_featured }}">
                                        <i class="tio-star {{ $hotel->is_featured ? 'text-warning' : '' }}"></i>
                                    </button>
                                    
                                    <!-- Delete Button -->
                                    @if($hotel->status != 0)
                                    <form action="{{ route('admin.hotels.delete', $hotel->id) }}" method="post" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm square-btn" 
                                                title="{{ translate('delete') }}"
                                                onclick="return confirm('{{ translate('are_you_sure_to_delete_this_hotel') }}')">
                                            <i class="tio-delete"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($hotels->isEmpty())
            <div class="text-center p-5">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/no-data.svg') }}" alt="" width="200">
                <h5 class="mt-3">{{ translate('no_hotels_found') }}</h5>
                <p class="text-muted">{{ translate('please_try_again_with_different_filters') }}</p>
                <a href="{{ route('admin.hotels.all') }}" class="btn btn--primary mt-2">
                    <i class="tio-clear"></i> {{ translate('clear_filters') }}
                </a>
            </div>
        @endif

        <div class="card-footer border-0">
            <div class="d-flex justify-content-end">
                {{ $hotels->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Reject Modals -->
@foreach($hotels as $hotel)
<div class="modal fade" id="rejectModal{{ $hotel->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('admin.hotels.reject', $hotel->id) }}" method="post">
                @csrf
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <div class="avatar avatar-xl bg-soft-danger rounded-circle mb-3">
                            <i class="tio-block fs-1 text-danger"></i>
                        </div>
                        <h4 class="mb-2">{{ translate('reject_hotel') }}</h4>
                        <p class="text-muted">{{ translate('please_provide_reason_for_rejection') }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">{{ translate('rejection_reason') }} <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="4" required 
                                  placeholder="{{ translate('enter_rejection_reason') }}"></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            {{ translate('cancel') }}
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="tio-block"></i> {{ translate('reject_hotel') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<span id="route-admin-hotels-status" data-url="{{ route('admin.hotels.status', 0) }}"></span>
@endsection

@push('script')
<script>
    // Toggle Featured Status
    $('.toggle-featured').on('click', function() {
        let button = $(this);
        let hotelId = button.data('id');
        let currentFeatured = button.data('featured');
        let newFeatured = currentFeatured ? 0 : 1;
        
        $.ajax({
            url: '{{ route("admin.hotels.status", "") }}/' + hotelId,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                is_featured: newFeatured
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('{{ translate("featured_status_updated") }}');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                }
            },
            error: function() {
                toastr.error('{{ translate("error_updating_status") }}');
            }
        });
    });

    // Initialize Select2
    $('.js-select2-custom').select2({
        width: '100%',
        minimumResultsForSearch: Infinity
    });
</script>
@endpush