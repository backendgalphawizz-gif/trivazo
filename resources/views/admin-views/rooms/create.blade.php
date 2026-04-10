@extends('layouts.back-end.app')

@section('title', translate('add_new_room'))

@push('css_or_js')
<link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/select2.min.css') }}">
<style>
    .room-form-heading {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #e7eaf3;
        padding: 1rem 1.5rem;
    }

    .form-label {
        font-weight: 500;
        color: #334257;
    }

    .required-star {
        color: #dc3545;
    }

    .image-preview img {
        max-width: 100%;
        border-radius: 8px;
        border: 1px solid #e7eaf3;
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(110px, 110px));
        gap: 10px;
        margin-top: 10px;
        align-items: start;
    }

    .gallery-grid img {
        width: 100%;
        height: 82px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #e7eaf3;
    }

    .preview-card {
        position: relative;
        width: 110px;
    }

    .featured-preview-grid {
        display: grid;
        grid-template-columns: 110px;
        gap: 10px;
        margin-top: 10px;
    }

    .preview-remove {
        position: absolute;
        top: -6px;
        right: -6px;
        width: 22px;
        height: 22px;
        border: none;
        border-radius: 50%;
        background: #dc3545;
        color: #fff;
        font-size: 12px;
        line-height: 22px;
        padding: 0;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }

    .preview-name {
        font-size: 10px;
        color: #6c757d;
        margin-top: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .upload-hint {
        font-size: 12px;
        color: #6c757d;
        margin-top: 6px;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="mb-3">
        <h2 class="h1 mb-0">
            <i class="tio-home"></i> {{ translate('add_new_room') }}
        </h2>
    </div>

    <form action="{{ route('admin.rooms.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Basic Info -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('room_information') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Select Hotel -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">
                            {{ translate('select_hotel') }} <span class="required-star">*</span>
                        </label>

                        <select name="hotel_id" class="form-control select2" required>
                            <option value="">{{ translate('select_hotel') }}</option>

                            @foreach($hotels as $hotel)
                            <option value="{{ $hotel->id }}">
                                {{ $hotel->name }} ({{ $hotel->city }})
                            </option>
                            @endforeach
                        </select>
                    </div>


                    <!-- Room Type -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">
                            {{ translate('room_type') }} <span class="required-star">*</span>
                        </label>
                        <input type="text" name="room_type" class="form-control" required>
                    </div>

                    <!-- Room Size -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ translate('room_size_sqft') }}</label>
                        <input type="number" name="room_size" class="form-control">
                    </div>

                    <!-- Rooms Available -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ translate('rooms_available') }}</label>
                        <input type="number" name="rooms_available" class="form-control" min="0">
                    </div>

                </div>
            </div>
        </div>

        <!-- Pricing -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('pricing_details') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">

                    <!-- Single -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ translate('single_price') }}</label>
                        <input type="number" step="0.01" name="single_price" class="form-control">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ translate('single_sale_price') }}</label>
                        <input type="number" step="0.01" name="single_sale_price" class="form-control">
                    </div>

                    <!-- Double -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ translate('double_price') }}</label>
                        <input type="number" step="0.01" name="double_price" class="form-control">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ translate('double_sale_price') }}</label>
                        <input type="number" step="0.01" name="double_sale_price" class="form-control">
                    </div>

                    <!-- Extras -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ translate('extra_adult_price') }}</label>
                        <input type="number" step="0.01" name="extra_adult_price" class="form-control">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ translate('extra_child_price') }}</label>
                        <input type="number" step="0.01" name="extra_child_price" class="form-control">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ translate('gst_percent') }}</label>
                        <input type="number" step="0.01" name="gst" class="form-control">
                    </div>

                </div>
            </div>
        </div>

        <!-- Capacity -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('occupancy') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ translate('max_adults') }}</label>
                        <input type="number" name="max_adults" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ translate('max_children') }}</label>
                        <input type="number" name="max_children" class="form-control">
                    </div>

                </div>
            </div>
        </div>

        <!-- Images -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('room_images') }}</h5>
            </div>
            <div class="card-body">

                <!-- Featured -->
                <div class="mb-3">
                    <label class="form-label">{{ translate('featured_image') }}</label>
                    <input type="file" name="featured_image" id="featuredImageInput" accept=".jpg,.jpeg,.png,.webp,.jfif,.gif,.avif,image/*" class="form-control">
                    <div class="upload-hint">{{ translate('select_one_image_for_room_thumbnail') }}</div>
                    <div class="image-preview featured-preview-grid" id="featuredPreview" style="display:grid;grid-template-columns:110px;gap:10px;margin-top:10px;"></div>
                </div>

                <!-- Gallery -->
                <div class="mb-3">
                    <label class="form-label">{{ translate('gallery_images') }}</label>
                    <input type="file" name="gallery[]" id="galleryInput" accept=".jpg,.jpeg,.png,.webp,.jfif,.gif,.avif,image/*" class="form-control" multiple>
                    <div class="upload-hint">{{ translate('you_can_select_multiple_images') }}</div>
                    <div class="gallery-grid" id="galleryPreview" style="display:grid;grid-template-columns:repeat(auto-fill,110px);gap:10px;margin-top:10px;"></div>
                </div>

            </div>
        </div>

        <!-- Attributes -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('room_attributes') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach(['AC','WiFi','Balcony','TV','King Bed','Sea View'] as $attr)
                    <div class="col-md-3">
                        <label>
                            <input type="checkbox" name="attributes[]" value="{{ $attr }}"> {{ $attr }}
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="d-flex justify-content-end gap-3">
            <a href="{{ route('admin.rooms.all') }}" class="btn btn-secondary">
                {{ translate('cancel') }}
            </a>
            <button type="submit" class="btn btn--primary">
                {{ translate('save_room') }}
            </button>
        </div>

    </form>
</div>
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const featuredInput = document.getElementById('featuredImageInput');
        const galleryInput = document.getElementById('galleryInput');
        const featuredPreview = document.getElementById('featuredPreview');
        const galleryPreview = document.getElementById('galleryPreview');

        function createPreviewCard(src, fileName, onRemove) {
            const wrapper = document.createElement('div');
            wrapper.className = 'preview-card';
            wrapper.style.width = '110px';
            wrapper.style.position = 'relative';

            const image = document.createElement('img');
            image.src = src;
            image.alt = fileName || 'preview';
            image.style.width = '110px';
            image.style.height = '82px';
            image.style.objectFit = 'cover';
            image.style.borderRadius = '6px';
            image.style.border = '1px solid #e7eaf3';
            image.style.display = 'block';

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'preview-remove';
            removeButton.innerHTML = '&times;';
            removeButton.style.position = 'absolute';
            removeButton.style.top = '-6px';
            removeButton.style.right = '-6px';
            removeButton.style.width = '22px';
            removeButton.style.height = '22px';
            removeButton.style.border = 'none';
            removeButton.style.borderRadius = '50%';
            removeButton.style.background = '#dc3545';
            removeButton.style.color = '#fff';
            removeButton.style.fontSize = '12px';
            removeButton.style.lineHeight = '22px';
            removeButton.style.padding = '0';
            removeButton.style.cursor = 'pointer';
            removeButton.addEventListener('click', onRemove);

            const fileLabel = document.createElement('div');
            fileLabel.className = 'preview-name';
            fileLabel.textContent = fileName || 'image';
            fileLabel.style.fontSize = '10px';
            fileLabel.style.color = '#6c757d';
            fileLabel.style.marginTop = '4px';
            fileLabel.style.whiteSpace = 'nowrap';
            fileLabel.style.overflow = 'hidden';
            fileLabel.style.textOverflow = 'ellipsis';

            wrapper.appendChild(removeButton);
            wrapper.appendChild(image);
            wrapper.appendChild(fileLabel);

            return wrapper;
        }

        function previewSingleImage(input, container) {
            container.innerHTML = '';

            if (!input.files || !input.files[0]) {
                return;
            }

            const file = input.files[0];
            const previewUrl = URL.createObjectURL(file);
            const previewCard = createPreviewCard(previewUrl, file.name, function () {
                input.value = '';
                container.innerHTML = '';
            });
            const image = previewCard.querySelector('img');
            image.onload = function () {
                URL.revokeObjectURL(previewUrl);
            };
            container.appendChild(previewCard);
        }

        function previewMultipleImages(input, container) {
            container.innerHTML = '';

            if (!input.files || !input.files.length) {
                return;
            }

            Array.from(input.files).forEach(function (file, index) {
                const previewUrl = URL.createObjectURL(file);
                const previewCard = createPreviewCard(previewUrl, file.name, function () {
                    const dataTransfer = new DataTransfer();
                    Array.from(input.files).forEach(function (currentFile, currentIndex) {
                        if (currentIndex !== index) {
                            dataTransfer.items.add(currentFile);
                        }
                    });
                    input.files = dataTransfer.files;
                    previewMultipleImages(input, container);
                });
                const image = previewCard.querySelector('img');
                image.onload = function () {
                    URL.revokeObjectURL(previewUrl);
                };
                container.appendChild(previewCard);
            });
        }

        if (featuredInput) {
            featuredInput.addEventListener('change', function () {
                previewSingleImage(this, featuredPreview);
            });
        }

        if (galleryInput) {
            galleryInput.addEventListener('change', function () {
                previewMultipleImages(this, galleryPreview);
            });
        }
    });
</script>
@endpush