@extends('layouts.back-end.app')

@section('title', translate('vehicle_categories'))

@section('content')
<div class="content container-fluid">
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 d-flex gap-2 align-items-center">
            <i class="tio-gallery"></i>
            {{ translate('vehicle_categories') }}
        </h2>
        <div class="ml-auto">
            <a href="{{ route('admin.carpool.vehicle-categories.add') }}" class="btn btn--primary">
                <i class="tio-add"></i> {{ translate('add_vehicle_category') }}
            </a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>ID</th>
                        <th>{{ translate('name') }}</th>
                        <th>{{ translate('status') }}</th>
                        <th class="text-right">{{ translate('action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $c)
                        <tr>
                            <td>{{ $c->id }}</td>
                            <td class="font-weight-medium">{{ $c->name }}</td>
                            <td>
                                @if($c->is_active)
                                    <span class="badge badge-soft-success">{{ translate('active') }}</span>
                                @else
                                    <span class="badge badge-soft-secondary">{{ translate('inactive') }}</span>
                                @endif
                                <form action="{{ route('admin.carpool.vehicle-categories.toggle-active', $c->id) }}" method="POST" class="d-inline-block ml-2">
                                    @csrf
                                    @if($c->is_active)
                                        <button type="submit" class="btn btn-sm btn-soft-warning">{{ translate('set_inactive') }}</button>
                                    @else
                                        <button type="submit" class="btn btn-sm btn-soft-success">{{ translate('activate') }}</button>
                                    @endif
                                </form>
                            </td>
                            <td class="text-right">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.carpool.vehicle-categories.edit', $c->id) }}" title="{{ translate('edit') }}">
                                    <i class="tio-edit"></i>
                                </a>
                                @if($c->drivers_count == 0)
                                    <form action="{{ route('admin.carpool.vehicle-categories.delete', $c->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('{{ translate('are_you_sure') }}');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">{{ translate('no_data_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($categories instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="card-footer">{{ $categories->links() }}</div>
        @endif
    </div>
</div>
@endsection
