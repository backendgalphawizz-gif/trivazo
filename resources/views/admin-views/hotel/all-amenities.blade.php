@extends('layouts.back-end.app')

@section('title', translate('amenities'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 d-flex gap-10">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/brand-setup.png') }}" alt="">
                {{ translate('amenities') }}
            </h2>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body text-start">
                        <form action="{{ route('admin.hotels.add-amenity') }}" 
                            method="POST" 
                            enctype="multipart/form-data">

                            @csrf

                            <div class="row">

                                <!-- Left Side -->
                                <div class="col-lg-6">

                                    <!-- Name -->
                                    <div class="form-group">
                                        <label class="title-color">
                                            {{ translate('name') }} 
                                            <span class="text-danger">*</span>
                                        </label>

                                        <input type="text" 
                                            class="form-control"
                                            name="name"
                                            placeholder="Enter amenity name"
                                            value="{{ old('name') }}"
                                            required>

                                        @error('name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>


                                    <!-- Category (TEXT TYPE as requested) -->
                                    <div class="form-group mt-3">
                                        <label class="title-color">
                                            {{ translate('category') }} 
                                            <span class="text-danger">*</span>
                                        </label>

                                        <input type="text"
                                            class="form-control"
                                            name="category"
                                            placeholder="Example: hotel / room / both"
                                            value="{{ old('category') }}"
                                            required>

                                        @error('category')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>


                                    <!-- Icon Upload -->
                                    <div class="from_part_2 mt-3">
                                        <label class="title-color">
                                            {{ translate('icon') }}
                                        </label>

                                        <span class="text-info d-block mb-2">
                                            (Recommended size: 1:1 ratio)
                                        </span>

                                        <div class="custom-file text-left">
                                            <input type="file"
                                                name="icon"
                                                id="amenity-icon"
                                                class="custom-file-input image-preview-before-upload"
                                                data-preview="#viewer"
                                                accept=".jpg, .png, .jpeg|image/*">

                                            <label class="custom-file-label"
                                                for="amenity-icon">
                                                {{ translate('choose_File') }}
                                            </label>
                                        </div>

                                        @error('icon')
                                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                                        @enderror
                                    </div>

                                </div>


                                <!-- Right Side Image Preview -->
                                <div class="col-lg-6 mt-4 mt-lg-0 from_part_2">
                                    <div class="form-group">
                                        <div class="text-center mx-auto">
                                            <img class="upload-img-view"
                                                id="viewer"
                                                alt="Preview"
                                                style="max-height: 200px;"
                                                src="{{ dynamicAsset(path: 'public/assets/back-end/img/image-place-holder.png') }}">
                                        </div>
                                    </div>
                                </div>

                            </div>


                            <!-- Buttons -->
                            <div class="d-flex flex-wrap gap-2 justify-content-end mt-4">
                                <button type="reset"
                                        class="btn btn-secondary">
                                    {{ translate('reset') }}
                                </button>

                                <button type="submit"
                                        class="btn btn--primary">
                                    {{ translate('submit') }}
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-20" id="cate-table">
            <div class="col-md-12">
                <div class="card">
                    <div class="px-3 py-4">
                        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center">
                            <div class="">
                                <h5 class="text-capitalize d-flex gap-1">
                                    {{ translate('Amenities') }}
                                    <span
                                        class="badge badge-soft-dark radius-50 fz-12">{{ $amenities->total() }}</span>
                                </h5>
                            </div>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <form action="{{ route('admin.hotels.all-amenities') }}" method="GET">
                                    <div class="input-group input-group-custom input-group-merge">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text">
                                                <i class="tio-search"></i>
                                            </div>
                                        </div>

                                        <input type="search"
                                            name="searchValue"
                                            class="form-control"
                                            placeholder="Search by name or category"
                                            value="{{ request('searchValue') }}">

                                        <button type="submit" class="btn btn--primary">
                                            Search
                                        </button>

                                        <a href="{{ route('admin.hotels.all-amenities') }}"
                                        class="btn btn-secondary">
                                            Reset
                                        </a>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>

                    <div class="table-responsive">
                        <table
                            class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100 text-start">
                            <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th>{{ translate('ID') }}</th>
                                <th class="text-center">{{ translate('icon') }}</th>
                                <th>{{ translate('name') }}</th>
                                <th class="text-center">{{ translate('category') }}</th>
                                <!-- <th class="text-center">{{ translate('status') }}</th> -->
                                <th class="text-center">{{ translate('action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($amenities as $key=>$amenity)
                                <tr>
                                    <td>{{ $amenity['id'] }}</td>
                                    <td class="d-flex justify-content-center">
                                        <div class="avatar-60 d-flex align-items-center rounded">
                                            <img class="img-fluid" alt=""
                                            src="{{ $amenity->icon ? dynamicAsset(path: '/public/storage/app/hotel/' . $amenity->icon) : dynamicAsset(path: 'public/assets/back-end/img/placeholder-hotel.png') }}">
                                        </div>
                                    </td>
                                    <td>{{ $amenity['name'] }}</td>
                                    <td>
                                        {{ $amenity['category'] }}
                                    </td>
                                   
                                    <td>
                                        <div class="d-flex justify-content-center gap-10">
                                            <a class="btn btn-outline-info btn-sm square-btn "
                                               title="{{ translate('edit') }}"
                                               href="{{ route('admin.hotels.edit-amenity',[$amenity['id']]) }}">
                                                <i class="tio-edit"></i>
                                            </a>
                                           
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="table-responsive mt-4">
                        <div class="d-flex justify-content-lg-end">
                            {{ $amenities->links() }}
                        </div>
                    </div>
                    @if($amenities->count() == 0)
                        @include('layouts.back-end._empty-state',['text'=>'no_amenities_found'],['image'=>'default'])
                    @endif
                </div>
            </div>
        </div>
    </div>
    <span id="route-admin-hotels-delete-amenities" data-url="{{ route('admin.hotels.delete-amenity') }}"></span>
   

@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/products-management.js') }}"></script>
@endpush
