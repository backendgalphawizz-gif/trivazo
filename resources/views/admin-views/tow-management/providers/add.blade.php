@extends('layouts.back-end.app')

@section('title', translate('add_new_provider'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/css/tags-input.min.css') }}" rel="stylesheet">
    <link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h2 class="h1 mb-0 d-flex gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/tow-providers.png') }}" alt="">
                {{ translate('add_new_provider') }}
            </h2>
        </div>

        <form action="{{ route('admin.tow-management.providers.add') }}" method="POST" 
              enctype="multipart/form-data" id="provider-form">
            @csrf

            <!-- Basic Information -->
            <div class="card mt-3">
                <div class="card-header">
                    <div class="d-flex gap-2">
                        <i class="tio-user-big"></i>
                        <h4 class="mb-0">{{ translate('basic_information') }}</h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color">
                                    {{ translate('select_user') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <span class="input-label-secondary cursor-pointer" data-toggle="tooltip"
                                      title="{{ translate('select_an_existing_user_to_become_a_tow_provider') }}">
                                    <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                </span>
                                <select class="js-select2-custom form-control" name="user_id" required>
                                    <option value="" selected disabled>{{ translate('select_user') }}</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->f_name }} {{ $user->l_name }} ({{ $user->phone }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color">
                                    {{ translate('company_name') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <input type="text" name="company_name" class="form-control" 
                                       value="{{ old('company_name') }}" 
                                       placeholder="{{ translate('enter_company_name') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color">
                                    {{ translate('service_area') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <input type="text" name="service_area" class="form-control" 
                                       value="{{ old('service_area') }}" 
                                       placeholder="{{ translate('e.g._Downtown,_Suburbs') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color">
                                    {{ translate('max_simultaneous_trips') }}
                                </label>
                                <span class="input-label-secondary cursor-pointer" data-toggle="tooltip"
                                      title="{{ translate('maximum_number_of_trips_a_provider_can_handle_at_once') }}">
                                    <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                </span>
                                <input type="number" name="max_simultaneous_trips" class="form-control" 
                                       value="{{ old('max_simultaneous_trips', 3) }}" min="1" max="10">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            <div class="card mt-3">
                <div class="card-header">
                    <div class="d-flex gap-2">
                        <i class="tio-file"></i>
                        <h4 class="mb-0">{{ translate('documents') }}</h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color">
                                    {{ translate('business_license') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <span class="input-label-secondary cursor-pointer" data-toggle="tooltip"
                                      title="{{ translate('upload_business_license_in_pdf_format') }}">
                                    <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                </span>
                                
                                <div class="custom_upload_input">
                                    <input type="file" name="business_license" 
                                           class="custom-upload-input-file" 
                                           accept=".pdf,.doc,.docx" required>
                                    <span class="delete_file_input btn btn-outline-danger btn-sm square-btn d--none">
                                        <i class="tio-delete"></i>
                                    </span>
                                    <div class="position-relative">
                                        <div class="d-flex flex-column justify-content-center align-items-center py-3">
                                            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/pdf-upload-icon.svg') }}" 
                                                 width="40" alt="">
                                            <h5 class="text-muted mb-0">{{ translate('upload_license') }}</h5>
                                            <small class="text-muted">{{ translate('PDF, DOC up to 5MB') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color">
                                    {{ translate('insurance_document') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <span class="input-label-secondary cursor-pointer" data-toggle="tooltip"
                                      title="{{ translate('upload_insurance_document_in_pdf_format') }}">
                                    <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                </span>
                                
                                <div class="custom_upload_input">
                                    <input type="file" name="insurance_info" 
                                           class="custom-upload-input-file" 
                                           accept=".pdf,.doc,.docx" required>
                                    <span class="delete_file_input btn btn-outline-danger btn-sm square-btn d--none">
                                        <i class="tio-delete"></i>
                                    </span>
                                    <div class="position-relative">
                                        <div class="d-flex flex-column justify-content-center align-items-center py-3">
                                            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/pdf-upload-icon.svg') }}" 
                                                 width="40" alt="">
                                            <h5 class="text-muted mb-0">{{ translate('upload_insurance') }}</h5>
                                            <small class="text-muted">{{ translate('PDF, DOC up to 5MB') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row justify-content-end gap-3 mt-3 mx-1">
                <a href="{{ route('admin.tow-management.providers.list') }}" class="btn btn-secondary px-5">
                    {{ translate('cancel') }}
                </a>
                <button type="submit" class="btn btn--primary px-5">
                    {{ translate('save_provider') }}
                </button>
            </div>
        </form>
    </div>

    <!-- Hidden data for JS -->
    <span id="message-file-size-too-big" data-text="{{ translate('file_size_too_big') }}"></span>
    <span id="message-invalid-file-type" data-text="{{ translate('invalid_file_type') }}"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/tow-management/provider-add.js') }}"></script>
@endpush