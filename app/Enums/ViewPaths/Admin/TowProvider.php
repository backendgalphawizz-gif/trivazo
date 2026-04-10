<?php

namespace App\Enums\ViewPaths\Admin;

enum TowProvider
{
    const LIST = [
        'URI' => 'list',
        'VIEW' => 'admin-views.tow-management.providers.list'
    ];
    const ADD = [
        'URI' => 'add',
        'VIEW' => 'admin-views.tow-management.providers.add'
    ];
    const UPDATE = [
        'URI' => 'update/{id}',
        'VIEW' => 'admin-views.tow-management.providers.edit'
    ];
    const DETAILS = [
        'URI' => 'details/{id}',
        'VIEW' => 'admin-views.tow-management.providers.details'
    ];
    const TRIPS = [
        'URI' => 'trips/{id}',
        'VIEW' => 'admin-views.tow-management.providers.trips'
    ];
    const STATUS = [
        'URI' => 'status',
        'VIEW' => ''
    ];
    const AVAILABILITY = [
        'URI' => 'availability',
        'VIEW' => ''
    ];
    const UPDATE_LOCATION = [
        'URI' => 'update-location',
        'VIEW' => ''
    ];
    const EXPORT = [
        'URI' => 'export',
        'VIEW' => ''
    ];
    const DELETE = [
        'URI' => 'delete',
        'VIEW' => ''
    ];
}