@extends('layouts.back-end.app')

@section('title', translate('edit_hotel_service'))

@php
	$serviceImage = $service->image
		? (filter_var($service->image, FILTER_VALIDATE_URL)
			? $service->image
			: asset('public/storage/app/hotel/services/' . basename($service->image)))
		: null;
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
			<h2 class="mb-1">{{ translate('edit_hotel_service') }}</h2>
			<p class="text-muted mb-0">{{ $service->hotel->name ?? translate('hotel_service') }}</p>
		</div>
		<a href="{{ route('admin.hotels.services', $service->hotel_id) }}" class="btn btn-outline-primary">
			{{ translate('back_to_services') }}
		</a>
	</div>

	<div class="card">
		<div class="card-body">
			<form action="{{ route('admin.hotels.update-service', $service->id) }}" method="post" enctype="multipart/form-data">
				@csrf
				@method('PUT')

				<div class="row g-3">
					<div class="col-lg-8">
						<div class="form-group mb-3">
							<label class="form-label">{{ translate('title') }}</label>
							<input type="text" name="title" class="form-control" value="{{ old('title', $service->title) }}" required>
						</div>

						<div class="form-group mb-3">
							<label class="form-label">{{ translate('short_description') }}</label>
							<textarea name="short_description" class="form-control" rows="5">{{ old('short_description', $service->short_description) }}</textarea>
						</div>

						<div class="row g-3">
							<div class="col-sm-6">
								<div class="form-group">
									<label class="form-label">{{ translate('service_type') }}</label>
									<select name="service_type" class="form-control">
										<option value="highlight" {{ old('service_type', $service->service_type) === 'highlight' ? 'selected' : '' }}>{{ translate('highlight') }}</option>
										<option value="service" {{ old('service_type', $service->service_type) === 'service' ? 'selected' : '' }}>{{ translate('service') }}</option>
										<option value="activity" {{ old('service_type', $service->service_type) === 'activity' ? 'selected' : '' }}>{{ translate('activity') }}</option>
									</select>
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group">
									<label class="form-label">{{ translate('sort_order') }}</label>
									<input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', $service->sort_order) }}">
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-4">
						<div class="form-group mb-3">
							<label class="form-label">{{ translate('image') }}</label>
							<input type="file" name="image" id="serviceImageInput" class="form-control" accept=".jpg,.jpeg,.png,.webp" onchange="previewServiceImage(event)">
							<small class="text-muted d-block mt-1">{{ translate('image_format_jpg_png_jpeg') }} | Max size 50MB</small>
						</div>

						<div class="service-image-preview p-3 text-center" id="serviceImagePreview">
							@if($serviceImage)
								<img src="{{ $serviceImage }}" alt="service" class="img-fluid rounded" style="max-height: 220px; object-fit: cover;">
							@else
								<div class="text-muted py-5" id="serviceImagePlaceholder">{{ translate('no_image_available') }}</div>
							@endif
						</div>
					</div>
				</div>

				<div class="d-flex flex-wrap gap-2 justify-content-end mt-4">
					<a href="{{ route('admin.hotels.services', $service->hotel_id) }}" class="btn btn-outline-secondary">
						{{ translate('cancel') }}
					</a>
					<button type="submit" class="btn btn-primary">
						{{ translate('update') }}
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
@endsection

@push('script')
<script>
	function previewServiceImage(event) {
		const preview = document.getElementById('serviceImagePreview');
		const file = event.target.files[0];

		preview.innerHTML = '';

		if (!file) {
			const placeholder = document.createElement('div');
			placeholder.className = 'text-muted py-5';
			placeholder.id = 'serviceImagePlaceholder';
			placeholder.textContent = '{{ translate('no_image_available') }}';
			preview.appendChild(placeholder);
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
