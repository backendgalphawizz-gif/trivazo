@extends('layouts.back-end.app')

@section('title', translate('edit amenities'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 d-flex gap-10">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/brand-setup.png') }}" alt="">
                {{ translate('edit amenities') }}
            </h2>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body text-start">
                        <form action="{{ route('admin.hotels.update-amenity', [$amenity['id']]) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="title-color" for="name">{{ translate('name') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name" value="{{ old('name'),$amenity['name'] }}" />
                                    </div>
                                    <div class="form-group">
                                        <label class="title-color" for="category">{{ translate('category') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="category" value="{{ old('category'),$amenity['category'] }}" />
                                    </div>
                                    <div class="from_part_2">
                                        <label class="title-color">{{ translate('icon') }}</label>
                                        <span class="text-info"><span class="text-danger">*</span> 1:1 </span>
                                        <div class="custom-file text-left">
                                            <input type="file" name="icon" id="category-image"
                                                   class="custom-file-input image-preview-before-upload"
                                                   data-preview="#viewer"
                                                   accept=".jpg, .png, .jpeg|image/*"
                                                   required>
                                            <label class="custom-file-label"
                                                   for="category-image">{{ translate('choose_File') }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mt-4 mt-lg-0 from_part_2">
                                    <div class="form-group">
                                        <div class="text-center mx-auto">
                                            <img class="upload-img-view" id="viewer" alt=""
                                                 src="{{ dynamicAsset(path: 'public/assets/back-end/img/image-place-holder.png') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                <button type="reset" id="reset"
                                        class="btn btn-secondary">{{ translate('reset') }}</button>
                                <button type="submit" class="btn btn--primary">{{ translate('submit') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>   

@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/products-management.js') }}"></script>
@endpush
