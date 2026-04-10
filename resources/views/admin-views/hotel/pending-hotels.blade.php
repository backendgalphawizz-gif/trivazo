@extends('layouts.back-end.app')

@section('title', translate('Pending Hotels - Approval Required'))

@section('content')
<div class="content container-fluid">
    <x-back-end.page-header 
        title="pending_hotels" 
        icon="hotel.png" 
        :count="$pendingCount"
    />

    <div class="card">
        <div class="card-header border-0">
            <form action="{{ url()->current() }}" method="GET" class="w-100">
                <div class="row g-2">
                    <div class="col-md-4">
                        <div class="input-group input-group-custom">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="tio-search"></i></span>
                            </div>
                            <input type="search" name="search" class="form-control" 
                                   placeholder="{{ translate('Search by hotel name') }}" 
                                   value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn--primary">
                            <i class="tio-filter"></i> {{ translate('Search') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Hotel Details') }}</th>
                            <th>{{ translate('Seller') }}</th>
                            <th>{{ translate('Submitted On') }}</th>
                            <th>{{ translate('Documents') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hotels as $hotel)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-80">
                                        <img class="img-fluid rounded" 
                                             src="{{ getStorageImages(path: $hotel->featured_image, type: 'hotel') }}"
                                             alt="">
                                    </div>
                                    <div>
                                        <h6 class="mb-1">{{ $hotel->name }}</h6>
                                        <p class="mb-0 text-muted small">{{ $hotel->address }}</p>
                                        <p class="mb-0 text-muted small">{{ $hotel->city }}, {{ $hotel->country }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <p class="mb-0">{{ $hotel->seller->f_name }} {{ $hotel->seller->l_name }}</p>
                                    <p class="mb-0 text-muted small">{{ $hotel->seller->phone }}</p>
                                    <p class="mb-0 text-muted small">{{ $hotel->seller->email }}</p>
                                </div>
                            </td>
                            <td>
                                {{ date('d M Y', strtotime($hotel->created_at)) }}
                                <br>
                                <small class="text-muted">{{ $hotel->created_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                <a href="#" class="btn btn-outline-info btn-sm" data-toggle="modal" data-target="#documentsModal{{ $hotel->id }}">
                                    <i class="tio-file-text"></i> {{ translate('View') }}
                                </a>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <a class="btn btn-outline-info btn-sm" href="{{ route('admin.hotels.view', $hotel->id) }}" title="{{ translate('view_details') }}">
                                        <i class="tio-visible"></i>
                                    </a>
                                    
                                    <form action="{{ route('admin.hotels.approve', $hotel->id) }}" method="post" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success btn-sm" 
                                                title="{{ translate('approve') }}"
                                                onclick="return confirm('{{ translate('Approve this hotel?') }}')">
                                            <i class="tio-checkmark-circle"></i> {{ translate('Approve') }}
                                        </button>
                                    </form>
                                    
                                    <button type="button" class="btn btn-outline-danger btn-sm" 
                                            title="{{ translate('reject') }}"
                                            data-toggle="modal" 
                                            data-target="#rejectModal{{ $hotel->id }}">
                                        <i class="tio-block"></i> {{ translate('Reject') }}
                                    </button>
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
                <img src="{{ dynamicAsset('public/assets/back-end/img/no-data.svg') }}" alt="" width="200">
                <h5 class="mt-3">{{ translate('No pending hotels found') }}</h5>
                <p class="text-muted">{{ translate('All hotels have been reviewed') }}</p>
            </div>
        @endif

        <div class="p-3">
            {{ $hotels->links() }}
        </div>
    </div>
</div>

<!-- Reject Modals -->
@foreach($hotels as $hotel)
<div class="modal fade" id="rejectModal{{ $hotel->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.hotels.reject', $hotel->id) }}" method="post">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Reject Hotel:') }} {{ $hotel->name }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ translate('Rejection Reason')