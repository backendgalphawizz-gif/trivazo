@extends('layouts.back-end.app')

@section('title', translate('hotel_services'))

@php
	$hotelName = $hotel->name ?? translate('hotel');
@endphp

@push('css')
<style>
	.service-image-preview {
		width: 100%;
		min-height: 220px;
		border: 1px dashed #d9dee7;
		border-radius: 12px;
		background: #f8f9fb;
		display: flex;
		align-items: center;
		justify-content: center;
		overflow: hidden;
	}

	.service-image-preview img {
		width: 100%;
		height: 220px;
		object-fit: cover;
	}
</style>
@endpush

@section('content')
<div class="content container-fluid">
	<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
		<div>
			<h2 class="mb-1">{{ translate('hotel_services') }}</h2>
			<p class="text-muted mb-0">{{ $hotelName }}</p>
		</div>
		<div class="d-flex flex-wrap gap-2">
			<a href="{{ route('admin.hotels.view', $hotel->id) }}" class="btn btn-outline-primary">
				{{ translate('back_to_hotel') }}
			</a>
			<a href="{{ route('admin.hotels.all') }}" class="btn btn-outline-secondary">
				{{ translate('all_hotels') }}
			</a>
		</div>
	</div>

	<div class="row g-3">
		<div class="col-lg-4">
			<div class="card">
				<div class="card-header">
					<h4 class="mb-0">{{ translate('add_service') }}</h4>
				</div>
				<div class="card-body">
					<form action="{{ route('admin.hotels.add-service', $hotel->id) }}" method="post" enctype="multipart/form-data">
						@csrf

						<div class="form-group mb-3">
							<label class="form-label">{{ translate('title') }}</label>
							<input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="{{ translate('enter_title') }}" required>
						</div>

						<div class="form-group mb-3">
							<label class="form-label">{{ translate('short_description') }}</label>
							<textarea name="short_description" class="form-control" rows="4" placeholder="{{ translate('enter_short_description') }}">{{ old('short_description') }}</textarea>
						</div>

						<div class="row g-3">
							<div class="col-sm-6">
								<div class="form-group">
									<label class="form-label">{{ translate('service_type') }}</label>
									<select name="service_type" class="form-control">
										<option value="highlight" {{ old('service_type') === 'highlight' ? 'selected' : '' }}>{{ translate('highlight') }}</option>
										<option value="service" {{ old('service_type') === 'service' ? 'selected' : '' }}>{{ translate('service') }}</option>
										<option value="activity" {{ old('service_type') === 'activity' ? 'selected' : '' }}>{{ translate('activity') }}</option>
									</select>
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group">
									<label class="form-label">{{ translate('sort_order') }}</label>
									<input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}">
								</div>
							</div>
						</div>

						<div class="form-group mt-3 mb-4">
							<label class="form-label">{{ translate('image') }}</label>
							<input type="file" name="image" id="serviceImageInput" class="form-control" accept=".jpg,.jpeg,.png,.webp" onchange="previewServiceImage(event, 'serviceImagePreview', 'serviceImagePlaceholder')">
							<small class="text-muted d-block mt-1">{{ translate('recommended_size') }}: 300x200 | Max size 50MB</small>
							<div class="service-image-preview mt-3" id="serviceImagePreview">
								<div class="text-muted text-center px-3" id="serviceImagePlaceholder">{{ translate('image_preview_will_show_here') }}</div>
							</div>
						</div>

						<button type="submit" class="btn btn-primary w-100">
							{{ translate('submit') }}
						</button>
					</form>
				</div>
			</div>
		</div>

		<div class="col-lg-8">
			<div class="card">
				<div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
					<h4 class="mb-0">{{ translate('service_list') }}</h4>
					<form action="{{ route('admin.hotels.services', $hotel->id) }}" method="get" class="d-flex gap-2">
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
									<th>{{ translate('image') }}</th>
									<th>{{ translate('title') }}</th>
									<th>{{ translate('type') }}</th>
									<th>{{ translate('sort_order') }}</th>
									<th>{{ translate('status') }}</th>
									<th class="text-center">{{ translate('action') }}</th>
								</tr>
							</thead>
							<tbody>
								@forelse($services as $key => $service)
									@php
										$image = $service->image
											? (filter_var($service->image, FILTER_VALIDATE_URL)
												? $service->image
												: asset('public/storage/app/hotel/services/' . basename($service->image)))
											: null;
									@endphp
									<tr>
										<td>{{ $services->firstItem() + $key }}</td>
										<td>
											@if($image)
												<img src="{{ $image }}" alt="service" class="rounded border" style="width: 56px; height: 56px; object-fit: cover;">
											@else
												<div class="d-flex align-items-center justify-content-center rounded border bg-light" style="width: 56px; height: 56px;">
													<span class="text-muted small">{{ translate('n/a') }}</span>
												</div>
											@endif
										</td>
										<td>
											<div class="fw-semibold">{{ $service->title }}</div>
											<div class="text-muted small">{{ \Illuminate\Support\Str::limit($service->short_description, 60) }}</div>
										</td>
										<td>{{ ucfirst($service->service_type ?? 'highlight') }}</td>
										<td>{{ $service->sort_order }}</td>
										<td>
											<form action="{{ route('admin.hotels.service-status', $service->id) }}" method="post">
												@csrf
												<input type="hidden" name="status" value="{{ $service->status ? 0 : 1 }}">
												<button type="submit" class="btn btn-sm {{ $service->status ? 'btn-success' : 'btn-secondary' }}">
													{{ $service->status ? translate('active') : translate('inactive') }}
												</button>
											</form>
										</td>
										<td>
											<div class="d-flex justify-content-center gap-2">
												<a href="{{ route('admin.hotels.edit-service', $service->id) }}" class="btn btn-outline-primary btn-sm">
													{{ translate('edit') }}
												</a>
												<form action="{{ route('admin.hotels.delete-service', $service->id) }}" method="post" onsubmit="return confirm('{{ translate('want_to_delete_this_item') }}')">
													@csrf
													@method('DELETE')
													<button type="submit" class="btn btn-outline-danger btn-sm">
														{{ translate('delete') }}
													</button>
												</form>
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
				@if(method_exists($services, 'links'))
					<div class="card-footer">
						{{ $services->links() }}
					</div>
				@endif
			</div>
		</div>
	</div>
</div>
@endsection

@push('script')
<script>
	function previewServiceImage(event, previewId, placeholderId) {
		const preview = document.getElementById(previewId);
		const placeholder = document.getElementById(placeholderId);
		const file = event.target.files[0];

		preview.innerHTML = '';

		if (!file) {
			if (placeholder) {
				preview.appendChild(placeholder);
			}
			return;
		}

		const reader = new FileReader();
		reader.onload = function(loadEvent) {
			const image = document.createElement('img');
			image.src = loadEvent.target.result;
			image.alt = 'service preview';
			preview.appendChild(image);
		};
		reader.readAsDataURL(file);
	}
</script>
@endpush
