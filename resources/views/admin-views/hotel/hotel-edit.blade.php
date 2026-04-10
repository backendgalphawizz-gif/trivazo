@extends('layouts.back-end.app')

@section('title', translate('edit_hotel'))

@push('css')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/select2.min.css') }}">
    <style>
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
        .map-container {
            height: 300px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    @php
        $resolveFeaturedImageUrl = function ($value) {
            if (!$value) {
                return null;
            }

            $path = trim((string) (parse_url($value, PHP_URL_PATH) ?: $value), '/');

            return asset('public/storage/app/hotel/hotel/' . basename($path));
        };

        $resolveGalleryImageUrl = function ($value) {
            if (!$value) {
                return null;
            }

            if (filter_var($value, FILTER_VALIDATE_URL)) {
                return $value;
            }

            $path = trim((string) (parse_url($value, PHP_URL_PATH) ?: $value), '/');

            if (str_starts_with($path, 'hotel/gallery/')) {
                return asset('public/storage/app/' . $path);
            }

            return asset('public/storage/app/hotel/gallery/' . basename($path));
        };
    @endphp

    <!-- Page Header -->
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 d-flex align-items-center gap-2">
            <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/hotel.png') }}" alt="">
            {{ translate('edit_hotel') }}: {{ $hotel->name }}
        </h2>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.hotels.update', $hotel->id) }}" method="post" enctype="multipart/form-data">
        @csrf
        
        <!-- Basic Information Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('basic_information') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Hotel Name -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="title-color">{{ translate('hotel_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $hotel->name) }}" placeholder="{{ translate('enter_hotel_name') }}" required>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- Seller Selection -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="title-color">{{ translate('select_seller') }} <span class="text-danger">*</span></label>
                            <select name="seller_id" class="form-control js-select2 @error('seller_id') is-invalid @enderror" required>
                                <option value="">{{ translate('select_seller') }}</option>
                                @foreach($sellers as $seller)
                                    <option value="{{ $seller->id }}" {{ old('seller_id', $hotel->seller_id) == $seller->id ? 'selected' : '' }}>
                                        {{ $seller->f_name }} {{ $seller->l_name }} ({{ $seller->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('seller_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="title-color">{{ translate('email') }} <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                   value="{{ old('email', $hotel->email) }}" placeholder="{{ translate('enter_email') }}" required>
                            @error('email')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- Phone -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="title-color">{{ translate('phone') }} <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                                   value="{{ old('phone', $hotel->phone) }}" placeholder="{{ translate('enter_phone') }}" required>
                            @error('phone')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- Website -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="title-color">{{ translate('website') }}</label>
                            <input type="url" name="website" class="form-control @error('website') is-invalid @enderror" 
                                   value="{{ old('website', $hotel->website) }}" placeholder="{{ translate('enter_website_url') }}">
                            @error('website')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- Star Rating -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="title-color">{{ translate('star_rating') }} <span class="text-danger">*</span></label>
                            <select name="star_rating" class="form-control @error('star_rating') is-invalid @enderror" required>
                                <option value="">{{ translate('select_rating') }}</option>
                                @for($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}" {{ old('star_rating', $hotel->star_rating) == $i ? 'selected' : '' }}>
                                        {{ $i }} {{ translate('star') }}
                                    </option>
                                @endfor
                            </select>
                            @error('star_rating')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- Check In Time -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="title-color">{{ translate('check_in_time') }}</label>
                            <input type="time" name="check_in_time" class="form-control" 
                                   value="{{ old('check_in_time', $hotel->check_in_time ? date('H:i', strtotime($hotel->check_in_time)) : '14:00') }}">
                        </div>
                    </div>
                    
                    <!-- Check Out Time -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="title-color">{{ translate('check_out_time') }}</label>
                            <input type="time" name="check_out_time" class="form-control" 
                                   value="{{ old('check_out_time', $hotel->check_out_time ? date('H:i', strtotime($hotel->check_out_time)) : '12:00') }}">
                        </div>
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
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="title-color">{{ translate('address') }} <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" 
                                      rows="2" placeholder="{{ translate('enter_address') }}" required>{{ old('address', $hotel->address) }}</textarea>
                            @error('address')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- City -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="title-color">{{ translate('city') }} <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" 
                                   value="{{ old('city', $hotel->city) }}" placeholder="{{ translate('enter_city') }}" required>
                            @error('city')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- State -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="title-color">{{ translate('state') }}</label>
                            <input type="text" name="state" class="form-control" 
                                   value="{{ old('state', $hotel->state) }}" placeholder="{{ translate('enter_state') }}">
                        </div>
                    </div>
                    
                    <!-- Country -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="title-color">{{ translate('country') }} <span class="text-danger">*</span></label>
                            <input type="text" name="country" class="form-control @error('country') is-invalid @enderror" 
                                   value="{{ old('country', $hotel->country) }}" placeholder="{{ translate('enter_country') }}" required>
                            @error('country')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- Postal Code -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="title-color">{{ translate('postal_code') }}</label>
                            <input type="text" name="postal_code" class="form-control" 
                                   value="{{ old('postal_code', $hotel->postal_code) }}" placeholder="{{ translate('enter_postal_code') }}">
                        </div>
                    </div>
                    
                    <!-- Latitude -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="title-color">{{ translate('latitude') }}</label>
                            <input type="text" name="latitude" id="latitude" class="form-control" 
                                   value="{{ old('latitude', $hotel->latitude) }}" placeholder="{{ translate('enter_latitude') }}" readonly>
                        </div>
                    </div>
                    
                    <!-- Longitude -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="title-color">{{ translate('longitude') }}</label>
                            <input type="text" name="longitude" id="longitude" class="form-control" 
                                   value="{{ old('longitude', $hotel->longitude) }}" placeholder="{{ translate('enter_longitude') }}" readonly>
                        </div>
                    </div>
                    
                    <!-- Map -->
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="title-color">{{ translate('pick_location_on_map') }}</label>
                            <div id="map" class="map-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('description') }}</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label fw-bold mb-2">{{ translate('hotel_description') }}</label>
                    <textarea name="description" id="hotelDescription" class="form-control" rows="8">{{ old('description', $hotel->description) }}</textarea>
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
                @php
                    $hotelAmenities = is_array($hotel->amenities) ? $hotel->amenities : json_decode($hotel->amenities, true) ?? [];
                    $commonAmenities = [
                        'Free WiFi', 'Swimming Pool', 'Restaurant', 'Room Service', 
                        'Air Conditioning', 'Parking', 'Gym', 'Spa', 'Airport Shuttle',
                        '24-hour Front Desk', 'Bar', 'Breakfast', 'Kitchen', 'Washer',
                        'Dryer', 'Heating', 'TV', 'Elevator', 'Wheelchair Accessible',
                        'Pet Friendly', 'Smoking Area', 'Business Center', 'Conference Room',
                        'Laundry Service', 'Concierge', 'Luggage Storage', 'Safe Deposit Box'
                    ];
                @endphp
                
                <div class="amenities-grid">
                    @foreach($commonAmenities as $amenity)
                        <div class="amenity-item">
                            <input type="checkbox" name="amenities[]" value="{{ $amenity }}" 
                                   id="amenity_{{ Str::slug($amenity) }}"
                                   {{ in_array($amenity, $hotelAmenities) ? 'checked' : '' }}>
                            <label for="amenity_{{ Str::slug($amenity) }}">{{ $amenity }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">
                    <label>{{ translate('or_add_custom_amenities') }}</label>
                    <input type="text" class="form-control" id="customAmenity" placeholder="{{ translate('enter_amenity') }}">
                    <button type="button" class="btn btn--primary btn-sm mt-2" onclick="addCustomAmenity()">
                        <i class="tio-add"></i> {{ translate('add') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Policies Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('policies') }}</h5>
            </div>
            <div class="card-body">
                @php
                    $policies = is_array($hotel->policies) ? $hotel->policies : json_decode($hotel->policies, true) ?? [];
                @endphp
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="title-color">{{ translate('cancellation_policy') }}</label>
                            <textarea name="cancellation_policy" class="form-control" rows="3">{{ old('cancellation_policy', $hotel->cancellation_policy) }}</textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="title-color">{{ translate('child_policy') }}</label>
                            <textarea name="child_policy" class="form-control" rows="2">{{ old('child_policy', $policies['child_policy'] ?? '') }}</textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="title-color">{{ translate('pet_policy') }}</label>
                            <textarea name="pet_policy" class="form-control" rows="2">{{ old('pet_policy', $policies['pet_policy'] ?? '') }}</textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="title-color">{{ translate('payment_policy') }}</label>
                            <textarea name="payment_policy" class="form-control" rows="2">{{ old('payment_policy', $policies['payment_policy'] ?? '') }}</textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="title-color">{{ translate('smoking_policy') }}</label>
                            <textarea name="smoking_policy" class="form-control" rows="2">{{ old('smoking_policy', $policies['smoking_policy'] ?? '') }}</textarea>
                        </div>
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
                @php
                    $nearbyPlaces = is_array($hotel->nearby_places) ? $hotel->nearby_places : json_decode($hotel->nearby_places, true) ?? [];
                @endphp
                <div id="nearbyPlaces">
                    @forelse($nearbyPlaces as $index => $place)
                        <div class="row mb-2 nearby-place-row">
                            <div class="col-md-5">
                                <input type="text" name="nearby_places[{{ $index }}][name]" class="form-control" 
                                       value="{{ $place['name'] ?? '' }}" placeholder="{{ translate('place_name') }}">
                            </div>
                            <div class="col-md-5">
                                <input type="text" name="nearby_places[{{ $index }}][distance]" class="form-control" 
                                       value="{{ $place['distance'] ?? '' }}" placeholder="{{ translate('distance') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger btn-block" onclick="removeNearbyPlace(this)">
                                    <i class="tio-delete"></i>
                                </button>
                            </div>
                        </div>
                    @empty
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
                    @endforelse
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
                <div class="form-group">
                    <label class="title-color">{{ translate('featured_image') }}</label>
                    @if($hotel->featured_image)
                        <div class="mb-2">
                            <img src="{{ $resolveFeaturedImageUrl($hotel->featured_image) }}"
                                 alt="{{ $hotel->name }}" style="max-width: 200px; max-height: 200px;" class="rounded">
                        </div>
                    @endif
                    <div class="custom-file">
                        <input type="file" name="featured_image" id="featuredImage" class="custom-file-input" 
                               accept=".jpg,.jpeg,.png" onchange="previewFeaturedImage(event)">
                        <label class="custom-file-label" for="featuredImage">{{ translate('choose_new_file') }}</label>
                    </div>
                    <small class="text-muted">{{ translate('image_format_jpg_png_jpeg') }} | Max size 50MB</small>
                    <div class="mt-2" id="featuredImagePreview"></div>
                </div>
                
                <!-- Image Alt Text -->
                <div class="form-group">
                    <label class="title-color">{{ translate('image_alt_text') }}</label>
                    <input type="text" name="image_alt_text" class="form-control" value="{{ old('image_alt_text', $hotel->image_alt_text) }}" 
                           placeholder="{{ translate('enter_image_alt_text') }}">
                </div>
                
                <!-- Gallery Images -->
                <div class="form-group">
                    <label class="title-color">{{ translate('gallery_images') }}</label>
                    
                    @if($hotel->gallery_images)
                        @php
                            $galleryImages = is_array($hotel->gallery_images) ? $hotel->gallery_images : json_decode($hotel->gallery_images, true);
                        @endphp
                        @if(!empty($galleryImages))
                            <div class="image-gallery mb-3" id="existingGallery">
                                @foreach($galleryImages as $image)
                                    <div class="gallery-image-item">
                                        <img src="{{ $resolveGalleryImageUrl($image) }}" alt="">
                                        <span class="remove-image" onclick="removeExistingImage(this, '{{ $image }}')">×</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                    
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
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('commission_settings') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="title-color">{{ translate('commission_rate') }} (%)</label>
                            <input type="number" name="commission_rate" class="form-control" 
                                   value="{{ old('commission_rate', $hotel->commission_rate ?? 0) }}" min="0" max="100" step="0.01">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex gap-3 justify-content-end">
            <a href="{{ route('admin.hotels.all') }}" class="btn btn-secondary">
                <i class="tio-clear"></i> {{ translate('cancel') }}
            </a>
            <button type="submit" class="btn btn--primary">
                <i class="tio-save"></i> {{ translate('update') }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/select2.min.js') }}"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ getWebConfig(name: 'google_map_api_key') }}&libraries=places"></script>
    
    <script>
        // Initialize Select2
        $('.js-select2').select2({
            width: '100%'
        });

        // Initialize Google Maps
        let map;
        let marker;
        
        function initMap() {
            const defaultLocation = { 
                lat: {{ $hotel->latitude ?: 40.7128 }}, 
                lng: {{ $hotel->longitude ?: -74.0060 }}
            };
            
            map = new google.maps.Map(document.getElementById('map'), {
                center: defaultLocation,
                zoom: 13
            });
            
            marker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                draggable: true
            });
            
            // Update latitude and longitude when marker is dragged
            google.maps.event.addListener(marker, 'dragend', function() {
                const position = marker.getPosition();
                document.getElementById('latitude').value = position.lat();
                document.getElementById('longitude').value = position.lng();
            });
            
            // Search box for places
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
        
        // Load map after Google Maps API is loaded
        window.onload = initMap;

        // Custom Amenities
        function addCustomAmenity() {
            const customAmenity = document.getElementById('customAmenity').value;
            if (customAmenity.trim() === '') return;
            
            const amenitiesGrid = document.querySelector('.amenities-grid');
            const div = document.createElement('div');
            div.className = 'amenity-item';
            
            const id = 'amenity_' + customAmenity.replace(/\s+/g, '_').toLowerCase();
            
            div.innerHTML = `
                <input type="checkbox" name="amenities[]" value="${customAmenity}" id="${id}" checked>
                <label for="${id}">${customAmenity}</label>
            `;
            
            amenitiesGrid.appendChild(div);
            document.getElementById('customAmenity').value = '';
        }

        // Nearby Places
        let placeIndex = {{ count($nearbyPlaces) }};
        
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
                    img.className = 'rounded mt-2';
                    preview.appendChild(img);
                }
                reader.readAsDataURL(file);
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
        }

        function removeExistingImage(button, imageName) {
            if (confirm('{{ translate("are_you_sure_to_remove_this_image") }}')) {
            button.closest('.gallery-image-item').remove();
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'removed_images[]';
                input.value = imageName;
                document.querySelector('form').appendChild(input);
            }
        }
    </script>
@endpush
