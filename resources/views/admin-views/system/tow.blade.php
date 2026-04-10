@php use App\Utils\Helpers; @endphp
@extends('layouts.back-end.app')
@section('title', translate('dashboard'))

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')

@if (auth('admin')->user()->admin_role_id == 1 || Helpers::module_permission_check('dashboard'))

<div class="content container-fluid">

    <div class="page-header pb-0 mb-0 border-0">
        <div class="flex-between align-items-center">
            <div>
                <h1 class="page-header-title">
                    {{ translate('welcome') }} {{ auth('admin')->user()->name }}
                </h1>
                <p>{{ translate('monitor_your_tow_business_analytics_and_statistics') }}.</p>
            </div>
        </div>
    </div>

    <div class="card mb-2 remove-card-shadow">
        <div class="card-body">
            <div class="row flex-between align-items-center g-2 mb-3">
                <div class="col-sm-6">
                    <h4 class="d-flex align-items-center text-capitalize gap-10 mb-0">
                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/business_analytics.png') }}">
                        {{ translate('tow_business_analytics') }}
                    </h4>
                </div>
                <div class="col-sm-6 d-flex justify-content-sm-end">
                    <select class="custom-select w-auto" name="statistics_type" id="statistics_type">
                        <option value="overall">{{ translate('overall_statistics') }}</option>
                        <option value="today">{{ translate('todays_Statistics') }}</option>
                        <option value="this_month">{{ translate('this_Months_Statistics') }}</option>
                    </select>
                </div>
            </div>

            <div class="row g-2" id="order_stats">
                @include('admin-views.partials._dashboard-order-status', ['data' => $data])
            </div>
        </div>
    </div>

    <div class="card mb-3 remove-card-shadow">
        <div class="card-body">
            <h4 class="d-flex align-items-center text-capitalize gap-10 mb-3">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/admin-wallet.png') }}">
                {{ translate('tow_admin_wallet') }}
            </h4>

            <div class="row g-2">
                @include('admin-views.partials._dashboard-wallet-stats', ['data' => $data])
            </div>
        </div>
    </div>

    <div class="row g-1">
        <div class="col-lg-8">
            @include('admin-views.system.partials.order-statistics')
        </div>

        <div class="col-lg-4">
            <div class="card remove-card-shadow h-100">
                <div class="card-header">
                    <h4>{{ translate('user_overview') }}</h4>
                </div>
                <div class="card-body">
                    <div id="chart" class="apex-pie-chart"></div>
                </div>
            </div>
        </div>

        <div class="col-12">
            @include('admin-views.system.partials.earning-statistics')
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card h-100 remove-card-shadow">
                @include('admin-views.partials._top-customer', ['top_customer' => $data['top_customer']])
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card h-100 remove-card-shadow">
                @include('admin-views.partials._top-delivery-man', ['topRatedDeliveryMan' => $data['topRatedDeliveryMan']])
            </div>
        </div>

    </div>

</div>

@endif

@endsection

@push('script')
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/apexcharts.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/dashboard.js') }}"></script>
@endpush