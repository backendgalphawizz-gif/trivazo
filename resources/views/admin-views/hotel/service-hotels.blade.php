@extends('layouts.back-end.app')

@section('title', translate('hotel_services'))

@section('content')
<div class="content container-fluid">
	<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
		<div>
			<h2 class="mb-1">{{ translate('hotel_services') }}</h2>
			<p class="text-muted mb-0">{{ translate('select_a_hotel_to_manage_services') }}</p>
		</div>
		<a href="{{ route('admin.hotels.all') }}" class="btn btn-outline-primary">
			{{ translate('view_all_hotels') }}
		</a>
	</div>

	<div class="card">
		<div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
			<h4 class="mb-0">{{ translate('hotel_list') }}</h4>
			<form action="{{ route('admin.hotels.services-index') }}" method="get" class="d-flex gap-2">
				<input type="text" name="searchValue" class="form-control" value="{{ request('searchValue') }}" placeholder="{{ translate('search_here') }}">
				<button type="submit" class="btn btn-outline-primary">{{ translate('search') }}</button>
			</form>
		</div>
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-hover table-borderless mb-0">
					<thead class="thead-light">
						<tr>
							<th>{{ translate('sl') }}</th>
							<th>{{ translate('hotel_name') }}</th>
							<th>{{ translate('city') }}</th>
							<th>{{ translate('seller') }}</th>
							<th>{{ translate('total_services') }}</th>
							<th>{{ translate('active_services') }}</th>
							<th class="text-center">{{ translate('action') }}</th>
						</tr>
					</thead>
					<tbody>
						@forelse($hotels as $key => $hotel)
							@php($stats = $serviceStats->get($hotel->id))
							<tr>
								<td>{{ $hotels->firstItem() + $key }}</td>
								<td>
									<div class="fw-semibold">{{ $hotel->name }}</div>
									<div class="text-muted small">{{ $hotel->email ?? translate('n/a') }}</div>
								</td>
								<td>{{ $hotel->city ?? translate('n/a') }}</td>
								<td>{{ trim(($hotel->seller->f_name ?? '') . ' ' . ($hotel->seller->l_name ?? '')) ?: translate('n/a') }}</td>
								<td>{{ $stats->total_services ?? 0 }}</td>
								<td>{{ $stats->active_services ?? 0 }}</td>
								<td>
									<div class="d-flex justify-content-center gap-2">
										<a href="{{ route('admin.hotels.services', $hotel->id) }}" class="btn btn-primary btn-sm">
											{{ translate('manage_services') }}
										</a>
										<a href="{{ route('admin.hotels.view', $hotel->id) }}" class="d-none btn btn-outline-secondary btn-sm">
											{{ translate('view_hotel') }}
										</a>
									</div>
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="7" class="text-center py-5">
									<div class="text-muted">{{ translate('no_data_found') }}</div>
								</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</div>
		@if(method_exists($hotels, 'links'))
			<div class="card-footer">
				{{ $hotels->links() }}
			</div>
		@endif
	</div>
</div>
@endsection
