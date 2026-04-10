@extends('layouts.back-end.app')

@section('title', translate('add_new_hotel'))

@push('css')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/select2.min.css') }}">
    <!-- Simple Summernote CSS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
    <style>
        /* Global Styles */
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e7eaf3;
            padding: 1rem 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
            color: #334257;
            margin-bottom: 0.5rem;
        }
        
        .required-star {
            color: #dc3545;
        }
        
        /* Amenities Grid */
        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            max-height: 300px;
            overflow-y: auto;
            padding: 15px;
            border: 1px solid #e7eaf3;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .amenity-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .amenity-item label {
            margin-bottom: 0;
            cursor: pointer;
        }
        
        /* Image Gallery */
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .gallery-image-item {
            position: relative;
            border: 1px solid #e7eaf3;
            border-radius: 8px;
            padding: 5px;
            background: #fff;
        }
        
        .gallery-image-item img {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .gallery-image-item .remove-image {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #dc3545;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            border: 2px solid white;
        }
        
        /* Map Container */
        .map-container {
            height: 300px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
            border: 1px solid #e7eaf3;
        }
        
        /* Summernote Editor Fixes */
        .note-editor.note-frame {
            border: 1px solid #e7eaf3 !important;
            border-radius: 8px !important;
            margin-bottom: 10px !important;
        }
        
        .note-editor .note-toolbar {
            background: #f8f9fa !important;
            border-bottom: 1px solid #e7eaf3 !important;
            border-radius: 8px 8px 0 0 !important;
            padding: 8px !important;
        }
        
        .note-editor .note-statusbar {
            background: #f8f9fa !important;
            border-top: 1px solid #e7eaf3 !important;
            border-radius: 0 0 8px 8px !important;
        }
        
        .note-editor .note-editable {
            background: #fff !important;
            min-height: 250px !important;
            padding: 15px !important;
        }
        
        /* Custom File Input */
        .custom-file-label::after {
            content: "{{ translate('browse') }}";
        }
        
        /* Remove any unwanted header text */
        .note-editor * {
            font-family: inherit;
        }
        
        .note-editor .note-btn {
            background: #fff !important;
            border: 1px solid #e7eaf3 !important;
            color: #334257 !important;
            font-size: 14px !important;
            padding: 6px 12px !important;
        }
        
        .note-editor .note-btn:hover {
            background: #f0f0f0 !important;
        }
        
        .note-editor .note-dropdown-menu {
            min-width: 200px;
            z-index: 9999;
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 d-flex align-items-center gap-2">
            <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/hotel.png') }}" alt="">
            {{ translate('add_new_hotel') }}
        </h2>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.hotels.store') }}" method="post" enctype="multipart/form-data" id="hotelForm">
        @csrf
        
        <!-- Basic Information Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('basic_information') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Hotel Name -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            {{ translate('hotel_name') }} <span class="required-star">*</span>
                        </label>
                        <input type="text" name="name" class="form-control" 
                               value="{{ old('name') }}" placeholder="{{ translate('enter_hotel_name') }}" required>
                    </div>
                    
                    <!-- Select Seller -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            {{ translate('select_seller') }} <span class="required-star">*</span>
                        </label>
                        <select name="seller_id" class="form-control select2" required>
                            <option value="">{{ translate('select_seller') }}</option>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}" {{ old('seller_id') == $seller->id ? 'selected' : '' }}>
                                    {{ $seller->f_name }} {{ $seller->l_name }} ({{ $seller->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Email -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">
                            {{ translate('email') }} <span class="required-star">*</span>
                        </label>
                        <input type="email" name="email" class="form-control" 
                               value="{{ old('email') }}" placeholder="{{ translate('enter_email') }}" required>
                    </div>
                    
                    <!-- Phone -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">
                            {{ translate('phone') }} <span class="required-star">*</span>
                        </label>
                        <input type="text" name="phone" class="form-control" 
                               value="{{ old('phone') }}" placeholder="{{ translate('enter_phone') }}" required>
                    </div>
                    
                    <!-- Website -->
                    <div class="col-md-4 mb-3 d-none">
                        <label class="form-label">{{ translate('website') }}</label>
                        <input type="url" name="website" class="form-control" 
                               value="{{ old('website') }}" placeholder="{{ translate('enter_website_url') }}">
                    </div>
                    
                    <!-- Star Rating -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">
                            {{ translate('star_rating') }} <span class="required-star">*</span>
                        </label>
                        <select name="star_rating" class="form-control" required>
                            <option value="">{{ translate('select_rating') }}</option>
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" {{ old('star_rating') == $i ? 'selected' : '' }}>
                                    {{ $i }} {{ translate('star') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    
                    <!-- Total Rooms -->
                    <div class="col-md-4 mb-3 d-none">
                        <label class="form-label">
                            {{ translate('total_rooms') }} <span class="required-star">*</span>
                        </label>
                        <input type="number" name="total_rooms" class="form-control" 
                               value="{{ old('total_rooms', 0) }}" placeholder="{{ translate('enter_total_rooms') }}" min="0" >
                    </div>
                    
                    <!-- Base Price -->
                    <div class="col-md-4 mb-3 d-none">
                        <label class="form-label">
                            {{ translate('base_price') }} <span class="required-star">*</span>
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ getWebConfig(name: 'currency_symbol') ?? '$' }}</span>
                            </div>
                            <input type="number" name="base_price" class="form-control" 
                                   value="{{ old('base_price', 0) }}" placeholder="{{ translate('enter_price') }}" min="0" step="0.01" >
                        </div>
                    </div>
                    
                    <!-- Check In Time -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ translate('check_in_time') }}</label>
                        <input type="time" name="check_in_time" class="form-control" 
                               value="{{ old('check_in_time', '14:00') }}">
                    </div>
                    
                    <!-- Check Out Time -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ translate('check_out_time') }}</label>
                        <input type="time" name="check_out_time" class="form-control" 
                               value="{{ old('check_out_time', '12:00') }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- Location Information Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('location_information') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Address -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label">
                            {{ translate('address') }} <span class="required-star">*</span>
                        </label>
                        <textarea name="address" class="form-control" 
                                  rows="2" placeholder="{{ translate('enter_address') }}" required>{{ old('address') }}</textarea>
                    </div>
                    
                    <!-- City -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">
                            {{ translate('city') }} <span class="required-star">*</span>
                        </label>
                        <input type="text" name="city" class="form-control" 
                               value="{{ old('city') }}" placeholder="{{ translate('enter_city') }}" required>
                    </div>
                    
                    <!-- State -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ translate('state') }}</label>
                        <input type="text" name="state" class="form-control" 
                               value="{{ old('state') }}" placeholder="{{ translate('enter_state') }}">
                    </div>
                    
                    <!-- Country -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">
                            {{ translate('country') }} <span class="required-star">*</span>
                        </label>
                        <input type="text" name="country" class="form-control" 
                               value="{{ old('country') }}" placeholder="{{ translate('enter_country') }}" required>
                    </div>
                    
                    <!-- Postal Code -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ translate('postal_code') }}</label>
                        <input type="text" name="postal_code" class="form-control" 
                               value="{{ old('postal_code') }}" placeholder="{{ translate('enter_postal_code') }}">
                    </div>
                    
                    <!-- Latitude -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ translate('latitude') }}</label>
                        <input type="text" name="latitude" id="latitude" class="form-control" 
                               value="{{ old('latitude') }}" placeholder="{{ translate('enter_latitude') }}" readonly>
                    </div>
                    
                    <!-- Longitude -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ translate('longitude') }}</label>
                        <input type="text" name="longitude" id="longitude" class="form-control" 
                               value="{{ old('longitude') }}" placeholder="{{ translate('enter_longitude') }}" readonly>
                    </div>
                    
                    <!-- Map -->
                    <div class="col-md-12">
                        <label class="form-label">{{ translate('pick_location_on_map') }}</label>
                        <div id="map" class="map-container"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description Card - FIXED VERSION -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('description') }}</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label fw-bold mb-2">{{ translate('hotel_description') }}</label>
                    <!-- Simple textarea without summernote class -->
                    <textarea name="description" id="hotelDescription" class="form-control" rows="8">{{ old('description') }}</textarea>
                    <small class="text-muted mt-2 d-block">
                        <i class="tio-info"></i> {{ translate('provide_detailed_description_of_your_hotel') }}
                    </small>
                </div>
            </div>
        </div>

        <!-- Amenities Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('amenities') }}</h5>
            </div>
            <div class="card-body">
                <div class="amenities-grid">
                    @php
                        $commonAmenities = [
                            'Free WiFi', 'Swimming Pool', 'Restaurant', 'Room Service', 
                            'Air Conditioning', 'Parking', 'Gym', 'Spa', 'Airport Shuttle',
                            '24-hour Front Desk', 'Bar', 'Breakfast', 'Kitchen', 'Washer',
                            'Dryer', 'Heating', 'TV', 'Elevator', 'Wheelchair Accessible',
                            'Pet Friendly', 'Smoking Area', 'Business Center', 'Conference Room',
                            'Laundry Service', 'Concierge', 'Luggage Storage', 'Safe Deposit Box'
                        ];
                    @endphp
                    
                    @foreach($commonAmenities as $amenity)
                        <div class="amenity-item">
                            <input type="checkbox" name="amenities[]" value="{{ $amenity }}" 
                                   id="amenity_{{ Str::slug($amenity) }}"
                                   {{ in_array($amenity, old('amenities', [])) ? 'checked' : '' }}>
                            <label for="amenity_{{ Str::slug($amenity) }}">{{ $amenity }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">
                    <label class="form-label">{{ translate('or_add_custom_amenities') }}</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="customAmenity" placeholder="{{ translate('enter_amenity') }}">
                        <div class="input-group-append">
                            <button type="button" class="btn btn--primary" onclick="addCustomAmenity()">
                                <i class="tio-add"></i> {{ translate('add') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Policies Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('policies') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ translate('cancellation_policy') }}</label>
                        <textarea name="cancellation_policy" class="form-control" rows="3" placeholder="{{ translate('enter_cancellation_policy') }}">{{ old('cancellation_policy') }}</textarea>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ translate('child_policy') }}</label>
                        <textarea name="child_policy" class="form-control" rows="2" placeholder="{{ translate('enter_child_policy') }}">{{ old('child_policy') }}</textarea>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ translate('pet_policy') }}</label>
                        <textarea name="pet_policy" class="form-control" rows="2" placeholder="{{ translate('enter_pet_policy') }}">{{ old('pet_policy') }}</textarea>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ translate('payment_policy') }}</label>
                        <textarea name="payment_policy" class="form-control" rows="2" placeholder="{{ translate('enter_payment_policy') }}">{{ old('payment_policy') }}</textarea>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ translate('smoking_policy') }}</label>
                        <textarea name="smoking_policy" class="form-control" rows="2" placeholder="{{ translate('enter_smoking_policy') }}">{{ old('smoking_policy') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nearby Places Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('nearby_places') }}</h5>
            </div>
            <div class="card-body">
                <div id="nearbyPlaces">
                    <div class="row mb-2 nearby-place-row">
                        <div class="col-md-5">
                            <input type="text" name="nearby_places[0][name]" class="form-control" placeholder="{{ translate('place_name') }}">
                        </div>
                        <div class="col-md-5">
                            <input type="text" name="nearby_places[0][distance]" class="form-control" placeholder="{{ translate('distance') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-danger btn-block" onclick="removeNearbyPlace(this)">
                                <i class="tio-delete"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn--primary btn-sm mt-2" onclick="addNearbyPlace()">
                    <i class="tio-add"></i> {{ translate('add_more') }}
                </button>
            </div>
        </div>

        <!-- Images Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('images') }}</h5>
            </div>
            <div class="card-body">
                <!-- Featured Image -->
                <div class="form-group mb-4">
                    <label class="form-label">{{ translate('featured_image') }}</label>
                    <div class="custom-file">
                        <input type="file" name="featured_image" id="featuredImage" class="custom-file-input" 
                               accept=".jpg,.jpeg,.png" onchange="previewFeaturedImage(event)">
                        <label class="custom-file-label" for="featuredImage">{{ translate('choose_file') }}</label>
                    </div>
                    <small class="text-muted">{{ translate('image_format_jpg_png_jpeg') }} | Max size 50MB</small>
                    <div class="mt-2" id="featuredImagePreview"></div>
                </div>
                
                <!-- Image Alt Text -->
                <div class="form-group mb-4">
                    <label class="form-label">{{ translate('image_alt_text') }}</label>
                    <input type="text" name="image_alt_text" class="form-control" value="{{ old('image_alt_text') }}" 
                           placeholder="{{ translate('enter_image_alt_text') }}">
                </div>
                
                <!-- Gallery Images -->
                <div class="form-group">
                    <label class="form-label">{{ translate('gallery_images') }}</label>
                    <div class="custom-file">
                        <input type="file" name="gallery_images[]" id="galleryImages" class="custom-file-input" 
                               multiple accept=".jpg,.jpeg,.png" onchange="previewGalleryImages(event)">
                        <label class="custom-file-label" for="galleryImages">{{ translate('choose_multiple_files') }}</label>
                    </div>
                    <small class="text-muted">{{ translate('you_can_select_multiple_images') }}</small>
                    <div class="image-gallery" id="galleryPreview"></div>
                </div>
            </div>
        </div>

        <!-- Commission Settings Card -->
        <div class="card mb-3 d-none">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('commission_settings') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">{{ translate('commission_rate') }} (%)</label>
                            <input type="number" name="commission_rate" class="form-control" 
                                   value="{{ old('commission_rate', 0) }}" min="0" max="100" step="0.01">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex gap-3 justify-content-end mb-5">
            <a href="{{ route('admin.hotels.all') }}" class="btn btn-secondary px-4">
                <i class="tio-clear"></i> {{ translate('cancel') }}
            </a>
            <button type="submit" class="btn btn--primary px-4">
                <i class="tio-save"></i> {{ translate('submit') }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/select2.min.js') }}"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ getWebConfig(name: 'google_map_api_key') }}&libraries=places"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                width: '100%',
                placeholder: '{{ translate("select_seller") }}'
            });
        });

        // Google Maps
        let map;
        let marker;
        
        function initMap() {
            const defaultLocation = { lat: 40.7128, lng: -74.0060 };
            
            map = new google.maps.Map(document.getElementById('map'), {
                center: defaultLocation,
                zoom: 13
            });
            
            marker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                draggable: true
            });
            
            google.maps.event.addListener(marker, 'dragend', function() {
                const position = marker.getPosition();
                document.getElementById('latitude').value = position.lat();
                document.getElementById('longitude').value = position.lng();
            });
            
            // Search box
            const input = document.createElement('input');
            input.className = 'form-control';
            input.placeholder = '{{ translate("search_location") }}';
            map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
            
            const searchBox = new google.maps.places.SearchBox(input);
            
            map.addListener('bounds_changed', function() {
                searchBox.setBounds(map.getBounds());
            });
            
            searchBox.addListener('places_changed', function() {
                const places = searchBox.getPlaces();
                
                if (places.length == 0) return;
                
                const place = places[0];
                const location = place.geometry.location;
                
                marker.setPosition(location);
                map.setCenter(location);
                
                document.getElementById('latitude').value = location.lat();
                document.getElementById('longitude').value = location.lng();
            });
        }

        // Custom Amenities
        function addCustomAmenity() {
            const customAmenity = document.getElementById('customAmenity').value;
            if (customAmenity.trim() === '') {
                alert('{{ translate("please_enter_an_amenity") }}');
                return;
            }
            
            const amenitiesGrid = document.querySelector('.amenities-grid');
            const div = document.createElement('div');
            div.className = 'amenity-item';
            
            const id = 'amenity_' + customAmenity.replace(/\s+/g, '_').toLowerCase();
            
            div.innerHTML = `
                <input type="checkbox" name="amenities[]" value="${customAmenity}" id="${id}">
                <label for="${id}">${customAmenity}</label>
            `;
            
            amenitiesGrid.appendChild(div);
            document.getElementById('customAmenity').value = '';
        }

        // Nearby Places
        let placeIndex = 1;
        
        function addNearbyPlace() {
            const container = document.getElementById('nearbyPlaces');
            const div = document.createElement('div');
            div.className = 'row mb-2 nearby-place-row';
            div.innerHTML = `
                <div class="col-md-5">
                    <input type="text" name="nearby_places[${placeIndex}][name]" class="form-control" placeholder="{{ translate('place_name') }}">
                </div>
                <div class="col-md-5">
                    <input type="text" name="nearby_places[${placeIndex}][distance]" class="form-control" placeholder="{{ translate('distance') }}">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger btn-block" onclick="removeNearbyPlace(this)">
                        <i class="tio-delete"></i>
                    </button>
                </div>
            `;
            container.appendChild(div);
            placeIndex++;
        }
        
        function removeNearbyPlace(button) {
            button.closest('.nearby-place-row').remove();
        }

        // Image Preview
        function previewFeaturedImage(event) {
            const preview = document.getElementById('featuredImagePreview');
            preview.innerHTML = '';
            
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '200px';
                    img.style.maxHeight = '200px';
                    img.style.borderRadius = '8px';
                    img.className = 'mt-2';
                    preview.appendChild(img);
                }
                reader.readAsDataURL(file);
                
                // Update file label
                const label = event.target.nextElementSibling;
                label.innerHTML = file.name;
            }
        }
        
        function previewGalleryImages(event) {
            const preview = document.getElementById('galleryPreview');
            preview.innerHTML = '';
            
            const files = event.target.files;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'gallery-image-item';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    
                    const removeBtn = document.createElement('span');
                    removeBtn.className = 'remove-image';
                    removeBtn.innerHTML = '×';
                    removeBtn.onclick = function() {
                        div.remove();
                    };
                    
                    div.appendChild(img);
                    div.appendChild(removeBtn);
                    preview.appendChild(div);
                }
                
                reader.readAsDataURL(file);
            }
            
            // Update file label
            const label = event.target.nextElementSibling;
            label.innerHTML = files.length + ' {{ translate("files_selected") }}';
        }

        // Load map
        window.onload = initMap;
    </script>
@endpush