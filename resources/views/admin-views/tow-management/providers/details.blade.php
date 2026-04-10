@extends('layouts.back-end.app')

@section('title', translate('provider_details'))

@section('content')
    <div class="content container-fluid">
        <!-- Back button and header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <a href="{{ route('admin.tow-management.providers.list') }}" class="btn btn-outline-primary">
                <i class="tio-arrow-backward"></i> {{ translate('back') }}
            </a>
            <h2 class="h1 mb-0 d-flex gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/tow-providers.png') }}" alt="">
                {{ translate('provider_details') }} - {{ $provider->company_name }}
            </h2>
        </div>

        <!-- Provider Overview Card -->
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <!-- Profile Section -->
                    <div class="col-md-3 text-center">
                        <img src="{{ $provider->user->image_full_url['path'] ?? dynamicAsset('public/assets/back-end/img/provider.png') }}"
                             class="rounded-circle" width="120" height="120" alt="">
                        <h4 class="mt-3">{{ $provider->company_name }}</h4>
                        <span class="badge badge-{{ $providerService->getStatusBadge($provider->status) }} p-2">
                            {{ translate($provider->status) }}
                        </span>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="col-md-9">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="bg-light p-3 rounded text-center">
                                    <small class="text-muted">{{ translate('rating') }}</small>
                                    <h3>⭐ {{ number_format($provider->rating, 1) }}</h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-light p-3 rounded text-center">
                                    <small class="text-muted">{{ translate('total_trips') }}</small>
                                    <h3>{{ $provider->total_completed_trips }}</h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-light p-3 rounded text-center">
                                    <small class="text-muted">{{ translate('member_since') }}</small>
                                    <h3>{{ $provider->created_at->format('M Y') }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Information -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h5>{{ translate('contact_information') }}</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td width="30%">{{ translate('owner_name') }}:</td>
                                <td><strong>{{ $provider->owner_name }}</strong></td>
                            </tr>
                            <tr>
                                <td>{{ translate('phone') }}:</td>
                                <td><strong>{{ $provider->owner_phone }}</strong></td>
                            </tr>
                            <tr>
                                <td>{{ translate('email') }}:</td>
                                <td><strong>{{ $provider->owner_email }}</strong></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>{{ translate('business_information') }}</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td width="30%">{{ translate('service_area') }}:</td>
                                <td><strong>{{ $provider->service_area ?: translate('all_areas') }}</strong></td>
                            </tr>
                            <tr>
                                <td>{{ translate('capacity') }}:</td>
                                <td><strong>{{ $provider->current_trips_count }}/{{ $provider->max_simultaneous_trips }}</strong></td>
                            </tr>
                            <tr>
                                <td>{{ translate('last_active') }}:</td>
                                <td><strong>{{ $provider->last_location_update?->diffForHumans() ?? translate('never') }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Documents -->
                <div class="row mt-3">
                    <div class="col-12">
                        <h5>{{ translate('documents') }}</h5>
                        <div class="d-flex gap-3">
                            @if($provider->business_license)
                                <a href="{{ $provider->license_document_url['path'] }}" 
                                   target="_blank" class="btn btn-outline-primary">
                                    <i class="tio-file"></i> {{ translate('view_license') }}
                                </a>
                            @endif
                            @if($provider->insurance_info)
                                <a href="{{ $provider->insurance_document_url['path'] }}" 
                                   target="_blank" class="btn btn-outline-primary">
                                    <i class="tio-file"></i> {{ translate('view_insurance') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection