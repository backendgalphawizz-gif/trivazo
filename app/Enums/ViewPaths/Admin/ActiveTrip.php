<?php

namespace App\Enums\ViewPaths\Admin;

enum ActiveTrip
{
    const LIST = [
        'URI' => 'list',
        'VIEW' => 'admin-views.tow-management.active-trips.list'
    ];
    const DETAILS = [
        'URI' => 'details/{id}',
        'VIEW' => 'admin-views.tow-management.active-trips.details'
    ];
    const ASSIGN = [
        'URI' => 'assign',
        'VIEW' => 'admin-views.tow-management.active-trips.assign'
    ];
    const REASSIGN = [
        'URI' => 'reassign',
        'VIEW' => 'admin-views.tow-management.active-trips.reassign'
    ];
    const UPDATE_STATUS = [
        'URI' => 'update-status',
        'VIEW' => ''
    ];
    const LIVE_TRACKING = [
        'URI' => 'live-tracking/{id}',
        'VIEW' => 'admin-views.tow-management.active-trips.live-tracking'
    ];
    const GET_TRACKING_DATA = [
        'URI' => 'get-tracking-data',
        'VIEW' => ''
    ];
}