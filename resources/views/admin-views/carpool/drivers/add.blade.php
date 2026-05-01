@extends('layouts.back-end.app')

@section('title', translate('add_driver'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="content container-fluid">

    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 d-flex gap-2 align-items-center">
            <i class="tio-car-outlined"></i>
            {{ translate('add_new_driver') }}
        </h2>
        <div class="ml-auto">
            <a href="{{ route('admin.carpool.drivers.list') }}" class="btn btn-outline-primary">
                <i class="tio-arrow-back-ios"></i> {{ translate('back_to_drivers') }}
            </a>
        </div>
    </div>

    <form action="{{ route('admin.carpool.drivers.store') }}" method="POST" enctype="multipart/form-data">
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
                                           value="{{ old('name') }}" placeholder="{{ translate('enter_full_name') }}" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('mobile_no') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone') }}" placeholder="9876543210" required
                                           inputmode="numeric" autocomplete="tel-national">
                                    <small class="form-text text-muted">{{ translate('country_code') }} +91</small>
                                    @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('gender') }} <span class="text-danger">*</span></label>
                                    <select name="gender" class="form-control @error('gender') is-invalid @enderror" required>
                                        <option value="" disabled {{ old('gender') ? '' : 'selected' }}>{{ translate('select') }}</option>
                                        <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>{{ translate('male') }}</option>
                                        <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>{{ translate('female') }}</option>
                                        <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>{{ translate('other') }}</option>
                                    </select>
                                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('email_ID') }}</label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email') }}" placeholder="{{ translate('optional') }}">
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('password') }} <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                           placeholder="{{ translate('min_6_characters') }}" required autocomplete="new-password">
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('confirm_password') }} <span class="text-danger">*</span></label>
                                    <input type="password" name="password_confirmation" class="form-control"
                                           placeholder="{{ translate('re_enter_password') }}" required autocomplete="new-password">
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
                                        <option value="" disabled {{ old('vehicle_category_id') ? '' : 'selected' }}>{{ translate('select') }}</option>
                                        @foreach($vehicleCategories ?? [] as $vc)
                                            <option value="{{ $vc->id }}" {{ (string) old('vehicle_category_id') === (string) $vc->id ? 'selected' : '' }}>
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
                                           value="{{ old('vehicle_number') }}" placeholder="{{ translate('plate_number') }}" required>
                                    @error('vehicle_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('vehicle_model') }}</label>
                                    <input type="text" name="vehicle_model" class="form-control"
                                           value="{{ old('vehicle_model') }}" placeholder="{{ translate('e.g. Toyota Corolla 2022') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('vehicle_color') }}</label>
                                    <input type="text" name="vehicle_color" class="form-control"
                                           value="{{ old('vehicle_color') }}" placeholder="{{ translate('e.g. White') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('seat_capacity') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="vehicle_capacity" class="form-control @error('vehicle_capacity') is-invalid @enderror"
                                           value="{{ old('vehicle_capacity', 4) }}" min="1" max="20" required>
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
                                           value="{{ old('license_number') }}" required>
                                    @error('license_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color">{{ translate('license_document') }}</label>
                                    <input type="file" name="license_doc" id="license-doc-input"
                                           class="form-control @error('license_doc') is-invalid @enderror"
                                           accept=".pdf,.jpg,.jpeg,.png"
                                           onchange="previewLicenseDoc(this)">
                                    <small class="text-muted">{{ translate('pdf_jpg_png_max_5mb') }}</small>
                                    @error('license_doc')<div class="invalid-feedback">{{ $message }}</div>@enderror

                                    {{-- Live Preview --}}
                                    <div id="license-doc-preview" class="mt-2 d-none border rounded p-2 bg-light">
                                        <small class="text-muted d-block mb-1"><i class="tio-eye-outlined mr-1"></i>{{ translate('preview') }}</small>
                                        <img id="license-doc-img" src="" alt="License Preview"
                                             class="img-thumbnail d-none" style="max-width:220px;max-height:160px;object-fit:contain;">
                                        <div id="license-doc-pdf" class="d-none text-center py-2">
                                            <i class="tio-file-text" style="font-size:36px;color:#dc3545;"></i>
                                            <div id="license-doc-filename" class="small text-muted mt-1 text-truncate" style="max-width:200px;"></div>
                                            <span class="badge badge-success mt-1"><i class="tio-checkmark-circle"></i> PDF selected</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Right: Profile Photo --}}
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">{{ translate('profile_photo') }}</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <img id="preview-photo"
                                 src="{{ dynamicAsset('public/assets/back-end/img/160x160/img1.jpg') }}"
                                 class="rounded-circle img-thumbnail" style="width:120px;height:120px;object-fit:cover;"
                                 onerror="this.src='https://ui-avatars.com/api/?name=Driver&size=120'">
                        </div>
                        <div class="form-group">
                            <label class="btn btn-outline-primary btn-sm cursor-pointer">
                                <i class="tio-upload mr-1"></i>{{ translate('upload_photo') }}
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
                                 src="{{ dynamicAsset('public/assets/back-end/img/160x160/img1.jpg') }}"
                                 class="img-thumbnail rounded" style="width:100%;height:120px;object-fit:cover;">
                        </div>
                        <div class="form-group">
                            <label class="btn btn-outline-secondary btn-sm cursor-pointer">
                                <i class="tio-upload mr-1"></i>{{ translate('upload_vehicle_photo') }}
                                <input type="file" name="vehicle_image" class="d-none"
                                       accept="image/jpg,image/jpeg,image/png"
                                       onchange="previewImage(this, 'preview-vehicle')">
                            </label>
                            <small class="d-block text-muted mt-1">{{ translate('jpg_png_max_3mb') }}</small>
                            @error('vehicle_image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex gap-2 flex-column">
                            <button type="submit" class="btn btn--primary btn-block">
                                <i class="tio-save mr-1"></i> {{ translate('save_driver') }}
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
