@extends('layouts.back-end.app')

@section('title', translate('edit_room'))

@push('css_or_js')
<style>
    .room-image-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(110px, 110px));
        gap: 10px;
        align-items: start;
    }

    .room-image-card {
        position: relative;
        border: 1px solid #e7eaf3;
        border-radius: 10px;
        padding: 6px;
        background: #fff;
        width: 110px;
    }

    .room-image-card img {
        width: 100%;
        height: 82px;
        object-fit: cover;
        border-radius: 8px;
        display: block;
    }

    .room-image-label {
        font-size: 10px;
        color: #6c757d;
        margin-top: 4px;
        text-align: center;
    }

    .upload-hint {
        font-size: 12px;
        color: #6c757d;
        margin-top: 6px;
    }

    .live-preview-wrap {
        margin-top: 14px;
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
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <h2 class="h1 mb-3">
        <i class="tio-edit"></i> {{ translate('edit_room') }}
    </h2>

    <form action="{{ route('admin.rooms.update', $room->id) }}"
          method="POST"
          enctype="multipart/form-data">
        @csrf

        @php
            $galleryImages = is_array($room->gallery) ? $room->gallery : json_decode($room->gallery ?? '[]', true);
            $featuredImageUrl = null;

            if ($room->featured_image) {
                if (filter_var($room->featured_image, FILTER_VALIDATE_URL)) {
                    $featuredImageUrl = $room->featured_image;
                } elseif (strpos($room->featured_image, 'storage/app/hotel/hotel/') === 0) {
                    $featuredImageUrl = asset('public/' . ltrim($room->featured_image, '/'));
                } else {
                    $featuredImageUrl = asset('storage/' . ltrim($room->featured_image, '/'));
                }
            }
        @endphp

        <!-- Room Info -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>{{ translate('room_information') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ translate('room_type') }}</label>
                        <input type="text" name="room_type"
                               value="{{ $room->room_type }}"
                               class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ translate('room_size') }}</label>
                        <input type="number" name="room_size"
                               value="{{ $room->room_size }}"
                               class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ translate('rooms_available') }}</label>
                        <input type="number" name="rooms_available"
                               value="{{ $room->rooms_available }}"
                               class="form-control">
                    </div>

                </div>
            </div>
        </div>

        <!-- Pricing -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>{{ translate('pricing') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-md-3 mb-3">
                        <label>{{ translate('single_price') }}</label>
                        <input type="number" step="0.01"
                               name="single_price"
                               value="{{ $room->single_price }}"
                               class="form-control">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>{{ translate('single_sale_price') }}</label>
                        <input type="number" step="0.01"
                               name="single_sale_price"
                               value="{{ $room->single_sale_price }}"
                               class="form-control">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>{{ translate('double_price') }}</label>
                        <input type="number" step="0.01"
                               name="double_price"
                               value="{{ $room->double_price }}"
                               class="form-control">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>{{ translate('double_sale_price') }}</label>
                        <input type="number" step="0.01"
                               name="double_sale_price"
                               value="{{ $room->double_sale_price }}"
                               class="form-control">
                    </div>

                </div>
            </div>
        </div>

        <!-- Extras -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>{{ translate('extra_charges') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label>{{ translate('extra_adult_price') }}</label>
                        <input type="number" step="0.01"
                               name="extra_adult_price"
                               value="{{ $room->extra_adult_price }}"
                               class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>{{ translate('extra_child_price') }}</label>
                        <input type="number" step="0.01"
                               name="extra_child_price"
                               value="{{ $room->extra_child_price }}"
                               class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>{{ translate('gst') }} (%)</label>
                        <input type="number" step="0.01"
                               name="gst"
                               value="{{ $room->gst }}"
                               class="form-control">
                    </div>

                </div>
            </div>
        </div>

        <!-- Attributes -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>{{ translate('attributes') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach(['AC','WiFi','Balcony','TV','King Bed','Sea View'] as $attr)
                        <div class="col-md-3">
                            <label>
                                <input type="checkbox"
                                       name="attributes[]"
                                       value="{{ $attr }}"
                                       {{ in_array($attr, is_array($room->attributes) ? $room->attributes : json_decode($room->attributes ?? '[]', true)) ? 'checked' : '' }}>
                                {{ $attr }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5>{{ translate('room_images') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ translate('featured_image') }}</label>
                        <input type="file"
                               name="featured_image"
                               id="featuredImageInput"
                               accept=".jpg,.jpeg,.png,.webp,.jfif,.gif,.avif,image/*"
                               class="form-control">
                        <div class="upload-hint">{{ translate('select_one_image_for_room_thumbnail') }}</div>

                        @if($room->featured_image)
                            <div class="live-preview-wrap room-image-grid" id="currentFeaturedPreview" style="display:grid;grid-template-columns:110px;gap:10px;margin-top:14px;">
                                <div class="room-image-card" style="width:110px;position:relative;border:1px solid #e7eaf3;border-radius:10px;padding:6px;background:#fff;">
                                    <img src="{{ $featuredImageUrl }}"
                                         alt="{{ $room->room_type }}"
                                         style="width:110px;height:82px;object-fit:cover;border-radius:8px;display:block;">
                                    <div class="room-image-label" style="font-size:10px;color:#6c757d;margin-top:4px;text-align:center;">{{ translate('current_featured_image') }}</div>
                                </div>
                            </div>
                        @endif

                        <div class="live-preview-wrap image-preview room-image-grid" id="featuredPreview" style="display:grid;grid-template-columns:110px;gap:10px;margin-top:14px;"></div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ translate('gallery_images') }}</label>
                        <input type="file"
                               name="gallery[]"
                               id="galleryInput"
                               accept=".jpg,.jpeg,.png,.webp,.jfif,.gif,.avif,image/*"
                               multiple
                               class="form-control">
                        <div class="upload-hint">{{ translate('you_can_select_multiple_images') }}</div>

                        @if(!empty($galleryImages))
                            <div class="live-preview-wrap">
                                <div class="room-image-grid" id="currentGalleryPreview" style="display:grid;grid-template-columns:repeat(auto-fill,110px);gap:10px;">
                                @foreach($galleryImages as $image)
                                    @php
                                        if (filter_var($image, FILTER_VALIDATE_URL)) {
                                            $galleryImageUrl = $image;
                                        } elseif (strpos($image, 'storage/app/hotel/hotel/') === 0) {
                                            $galleryImageUrl = asset('public/' . ltrim($image, '/'));
                                        } else {
                                            $galleryImageUrl = asset('storage/' . ltrim($image, '/'));
                                        }
                                    @endphp
                                    <div class="room-image-card" style="width:110px;position:relative;border:1px solid #e7eaf3;border-radius:10px;padding:6px;background:#fff;">
                                        <img src="{{ $galleryImageUrl }}"
                                             alt="gallery"
                                             style="width:110px;height:82px;object-fit:cover;border-radius:8px;display:block;">
                                        <div class="room-image-label" style="font-size:10px;color:#6c757d;margin-top:4px;text-align:center;">{{ translate('current_gallery_image') }}</div>
                                    </div>
                                @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="live-preview-wrap room-image-grid" id="galleryPreview" style="display:grid;grid-template-columns:repeat(auto-fill,110px);gap:10px;margin-top:14px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="d-flex justify-content-end gap-3">
            <a href="{{ route('admin.rooms.all') }}" class="btn btn-secondary">
                {{ translate('cancel') }}
            </a>
            <button type="submit" class="btn btn--primary">
                {{ translate('update') }}
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

        function buildPreviewCard(src, labelText, onRemove) {
            const wrapper = document.createElement('div');
            wrapper.className = 'room-image-card';
            wrapper.style.width = '110px';
            wrapper.style.position = 'relative';
            wrapper.style.border = '1px solid #e7eaf3';
            wrapper.style.borderRadius = '10px';
            wrapper.style.padding = '6px';
            wrapper.style.background = '#fff';

            const image = document.createElement('img');
            image.src = src;
            image.alt = labelText;
            image.style.width = '110px';
            image.style.height = '82px';
            image.style.objectFit = 'cover';
            image.style.borderRadius = '8px';
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

            const label = document.createElement('div');
            label.className = 'room-image-label';
            label.textContent = labelText;
            label.style.fontSize = '10px';
            label.style.color = '#6c757d';
            label.style.marginTop = '4px';
            label.style.textAlign = 'center';

            wrapper.appendChild(removeButton);
            wrapper.appendChild(image);
            wrapper.appendChild(label);

            return wrapper;
        }

        if (featuredInput) {
            featuredInput.addEventListener('change', function () {
                featuredPreview.innerHTML = '';

                if (!this.files || !this.files[0]) {
                    return;
                }

                const previewUrl = URL.createObjectURL(this.files[0]);
                const card = buildPreviewCard(previewUrl, '{{ translate('selected_featured_image') }}', function () {
                    featuredInput.value = '';
                    featuredPreview.innerHTML = '';
                });
                const img = card.querySelector('img');
                img.onload = function () {
                    URL.revokeObjectURL(previewUrl);
                };
                featuredPreview.appendChild(card);
            });
        }

        if (galleryInput) {
            galleryInput.addEventListener('change', function () {
                galleryPreview.innerHTML = '';

                if (!this.files || !this.files.length) {
                    return;
                }

                Array.from(this.files).forEach(function (file, index) {
                    const previewUrl = URL.createObjectURL(file);
                    const card = buildPreviewCard(previewUrl, '{{ translate('selected_image') }} ' + (index + 1), function () {
                        const dataTransfer = new DataTransfer();
                        Array.from(galleryInput.files).forEach(function (currentFile, currentIndex) {
                            if (currentIndex !== index) {
                                dataTransfer.items.add(currentFile);
                            }
                        });
                        galleryInput.files = dataTransfer.files;
                        galleryInput.dispatchEvent(new Event('change'));
                    });
                    const img = card.querySelector('img');
                    img.onload = function () {
                        URL.revokeObjectURL(previewUrl);
                    };
                    galleryPreview.appendChild(card);
                });
            });
        }
    });
</script>
@endpush