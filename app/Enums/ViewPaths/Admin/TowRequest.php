<?php

namespace App\Enums\ViewPaths\Admin;

enum TowRequest
{
    const LIST = [
        'URI' => 'list',
        'VIEW' => 'admin-views.tow-management.requests.list'
    ];
    const DETAILS = [
        'URI' => 'details/{id}',
        'VIEW' => 'admin-views.tow-management.requests.details'
    ];
    const UPDATE_STATUS = [
        'URI' => 'update-status',
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