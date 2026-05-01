@extends('layouts.back-end.app')

@section('title', translate('edit_driver'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="content container-fluid">

    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 d-flex gap-2 align-items-center">
            <i class="tio-car-outlined"></i>
            {{ translate('edit_driver') }}: <span class="text-primary ml-1">{{ $driver->name }}</span>
        </h2>
        <div class="ml-auto">
            <a href="{{ route('admin.carpool.drivers.list') }}" class="btn btn-outline-primary">
                <i class="tio-arrow-back-ios"></i> {{ translate('back_to_drivers') }}
            </a>
        </div>
    </div>

    <form action="{{ route('admin.carpool.drivers.update', $driver->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row">
            {{-- Left: Personal & Account --}}
            <div class="col-lg-8">

                {{-- Personal Info --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="tio-user mr-1"></i> {{ translate('personal_information') }}</h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="country_code" value="+91">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('full_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $driver->name) }}" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('mobile_no') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone', $driver->phone) }}" required inputmode="numeric" autocomplete="tel-national">
                                    <small class="form-text text-muted">{{ translate('country_code') }} +91</small>
                                    @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('gender') }} <span class="text-danger">*</span></label>
                                    <select name="gender" class="form-control @error('gender') is-invalid @enderror" required>
                                        <option value="" disabled {{ old('gender', $driver->gender) ? '' : 'selected' }}>{{ translate('select') }}</option>
                                        <option value="male" {{ old('gender', $driver->gender) === 'male' ? 'selected' : '' }}>{{ translate('male') }}</option>
                                        <option value="female" {{ old('gender', $driver->gender) === 'female' ? 'selected' : '' }}>{{ translate('female') }}</option>
                                        <option value="other" {{ old('gender', $driver->gender) === 'other' ? 'selected' : '' }}>{{ translate('other') }}</option>
                                    </select>
                                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('email_ID') }}</label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email', $driver->email) }}">
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('status') }}</label>
                                    <select name="status" class="js-select2-custom form-control">
                                        <option value="active"    {{ old('status', $driver->status) == 'active'    ? 'selected' : '' }}>{{ translate('active') }}</option>
                                        <option value="inactive"  {{ old('status', $driver->status) == 'inactive'  ? 'selected' : '' }}>{{ translate('inactive') }}</option>
                                        <option value="suspended" {{ old('status', $driver->status) == 'suspended' ? 'selected' : '' }}>{{ translate('suspended') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="w-100"></div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('new_password') }}
                                        <small class="text-muted">({{ translate('leave_blank_to_keep') }})</small>
                                    </label>
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                           placeholder="{{ translate('min_6_characters') }}" autocomplete="new-password">
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('confirm_new_password') }}</label>
                                    <input type="password" name="password_confirmation" class="form-control"
                                           placeholder="{{ translate('re_enter_password') }}" autocomplete="new-password">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Vehicle Info --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="tio-car mr-1"></i> {{ translate('vehicle_information') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('vehicle_category') }} <span class="text-danger">*</span></label>
                                    <select name="vehicle_category_id" class="js-select2-custom form-control @error('vehicle_category_id') is-invalid @enderror" required>
                                        <option value="" disabled>{{ translate('select') }}</option>
                                        @foreach($vehicleCategories ?? [] as $vc)
                                            <option value="{{ $vc->id }}" {{ (string) old('vehicle_category_id', $suggestedVehicleCategoryId ?? '') === (string) $vc->id ? 'selected' : '' }}>
                                                {{ $vc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('vehicle_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('vehicle_number') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="vehicle_number" class="form-control @error('vehicle_number') is-invalid @enderror"
                                           value="{{ old('vehicle_number', $driver->vehicle_number) }}" required>
                                    @error('vehicle_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('vehicle_model') }}</label>
                                    <input type="text" name="vehicle_model" class="form-control"
                                           value="{{ old('vehicle_model', $driver->vehicle_model) }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('vehicle_color') }}</label>
                                    <input type="text" name="vehicle_color" class="form-control"
                                           value="{{ old('vehicle_color', $driver->vehicle_color) }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('seat_capacity') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="vehicle_capacity" class="form-control @error('vehicle_capacity') is-invalid @enderror"
                                           value="{{ old('vehicle_capacity', $driver->vehicle_capacity) }}" min="1" max="20" required>
                                    @error('vehicle_capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- License --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="tio-document-text mr-1"></i> {{ translate('license_information') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('license_number') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="license_number" class="form-control @error('license_number') is-invalid @enderror"
                                           value="{{ old('license_number', $driver->license_number) }}" required>
                                    @error('license_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('license_document') }}</label>

                                    {{-- Existing document preview --}}
                                    @if($driver->license_doc)
                                        @php
                                            $licExt    = strtolower(pathinfo($driver->license_doc, PATHINFO_EXTENSION));
                                            $licIsImg  = in_array($licExt, ['jpg','jpeg','png']);
                                            $licUrl    = dynamicStorage(path: 'storage/app/public/' . $driver->license_doc);
                                        @endphp
                                        <div class="mb-2 p-2 border rounded bg-light">
                                            <small class="text-muted d-block mb-1"><i class="tio-document-text mr-1"></i>{{ translate('current_document') }}</small>
                                            @if($licIsImg)
                                                <a href="{{ $licUrl }}" target="_blank">
                                                    <img src="{{ $licUrl }}" alt="License"
                                                         class="img-thumbnail" style="max-width:220px;max-height:160px;object-fit:contain;">
                                                </a>
                                            @else
                                                <a href="{{ $licUrl }}" target="_blank" class="btn btn-sm btn-outline-danger">
                                                    <i class="tio-file-text mr-1"></i>{{ translate('view_pdf_document') }}
                                                </a>
                                            @endif
                                        </div>
                                    @endif

                                    <input type="file" name="license_doc" id="license-doc-input"
                                           class="form-control @error('license_doc') is-invalid @enderror"
                                           accept=".pdf,.jpg,.jpeg,.png"
                                           onchange="previewLicenseDoc(this)">
                                    <small class="text-muted">{{ translate('pdf_jpg_png_max_5mb') }} ({{ translate('leave_blank_to_keep') }})</small>
                                    @error('license_doc')<div class="invalid-feedback">{{ $message }}</div>@enderror

                                    {{-- Live preview for newly selected file --}}
                                    <div id="license-doc-preview" class="mt-2 d-none border rounded p-2 bg-light">
                                        <small class="text-muted d-block mb-1"><i class="tio-eye-outlined mr-1"></i>{{ translate('new_document_preview') }}</small>
                                        <img id="license-doc-img" src="" alt="Preview"
                                             class="img-thumbnail d-none" style="max-width:220px;max-height:160px;object-fit:contain;">
                                        <div id="license-doc-pdf" class="d-none text-center py-2">
                                            <i class="tio-file-text" style="font-size:36px;color:#dc3545;"></i>
                                            <div id="license-doc-filename" class="small text-muted mt-1 text-truncate" style="max-width:200px;"></div>
                                            <span class="badge badge-success mt-1"><i class="tio-checkmark-circle"></i> PDF ready to upload</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Stats (read-only info) --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="tio-chart-bar-3 mr-1"></i> {{ translate('driver_stats') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="h4 text-primary mb-0">{{ number_format($driver->rating ?? 0, 1) }}</div>
                                <small class="text-muted">{{ translate('rating') }}</small>
                            </div>
                            <div class="col-4">
                                <div class="h4 text-success mb-0">{{ $driver->total_completed_rides ?? 0 }}</div>
                                <small class="text-muted">{{ translate('completed_rides') }}</small>
                            </div>
                            <div class="col-4">
                                <div class="h4 mb-0">
                                    @if($driver->is_verified)
                                        <span class="text-success"><i class="tio-checkmark-circle"></i></span>
                                    @else
                                        <span class="text-warning"><i class="tio-time"></i></span>
                                    @endif
                                </div>
                                <small class="text-muted">{{ translate('verified') }}</small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Right: Profile Photo + Vehicle Photo + Actions --}}
            <div class="col-lg-4">

                {{-- Profile Photo --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">{{ translate('profile_photo') }}</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <img id="preview-photo"
                                 src="{{ $driver->profile_image ? dynamicStorage(path: 'storage/app/public/' . $driver->profile_image) : dynamicAsset('public/assets/back-end/img/160x160/img1.jpg') }}"
                                 class="rounded-circle img-thumbnail" style="width:120px;height:120px;object-fit:cover;"
                                 onerror="this.src='{{ dynamicAsset('public/assets/back-end/img/160x160/img1.jpg') }}'">
                        </div>
                        <div class="form-group">
                            <label class="btn btn-outline-primary btn-sm cursor-pointer">
                                <i class="tio-upload mr-1"></i>{{ translate('change_photo') }}
                                <input type="file" name="profile_image" class="d-none"
                                       accept="image/jpg,image/jpeg,image/png"
                                       onchange="previewImage(this, 'preview-photo')">
                            </label>
                            <small class="d-block text-muted mt-1">{{ translate('jpg_png_max_2mb') }}</small>
                            @error('profile_image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Vehicle Photo --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="tio-car mr-1"></i>{{ translate('vehicle_photo') }}</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <img id="preview-vehicle"
                                 src="{{ $driver->vehicle_image ? dynamicStorage(path: 'storage/app/public/' . $driver->vehicle_image) : '' }}"
                                 class="img-thumbnail rounded {{ $driver->vehicle_image ? '' : 'd-none' }}"
                                 style="width:100%;height:120px;object-fit:cover;">
                        </div>
                        <div class="form-group">
                            <label class="btn btn-outline-secondary btn-sm cursor-pointer">
                                <i class="tio-upload mr-1"></i>{{ translate($driver->vehicle_image ? 'change_vehicle_photo' : 'upload_vehicle_photo') }}
                                <input type="file" name="vehicle_image" class="d-none"
                                       accept="image/jpg,image/jpeg,image/png"
                                       onchange="previewImage(this, 'preview-vehicle')">
                            </label>
                            <small class="d-block text-muted mt-1">{{ translate('jpg_png_max_3mb') }}</small>
                            @error('vehicle_image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Save / Cancel --}}
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex gap-2 flex-column">
                            <button type="submit" class="btn btn--primary btn-block">
                                <i class="tio-save mr-1"></i> {{ translate('update_driver') }}
                            </button>
                            <a href="{{ route('admin.carpool.drivers.list') }}" class="btn btn-outline-danger btn-block">
                                {{ translate('cancel') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('script')
<script src="{{ dynamicAsset(path: 'public/assets/select2/js/select2.min.js') }}"></script>
<script>
    $(document).ready(function () { $('.js-select2-custom').select2(); });

    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                var img = document.getElementById(previewId);
                img.src = e.target.result;
                img.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function previewLicenseDoc(input) {
        var wrapper  = document.getElementById('license-doc-preview');
        var imgEl    = document.getElementById('license-doc-img');
        var pdfEl    = document.getElementById('license-doc-pdf');
        var nameEl   = document.getElementById('license-doc-filename');

        if (input.files && input.files[0]) {
            var file = input.files[0];
            wrapper.classList.remove('d-none');

            if (file.type.startsWith('image/')) {
                imgEl.classList.remove('d-none');
                pdfEl.classList.add('d-none');
                var reader = new FileReader();
                reader.onload = function (e) { imgEl.src = e.target.result; };
                reader.readAsDataURL(file);
            } else {
                imgEl.classList.add('d-none');
                pdfEl.classList.remove('d-none');
                nameEl.textContent = file.name;
            }
        } else {
            wrapper.classList.add('d-none');
        }
    }
</script>
@endpush
