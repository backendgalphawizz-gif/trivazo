@extends('layouts.back-end.app')

@section('title', translate('banner'))

@section('content')
    @php
        $allowedBannerTypes = collect($bannerTypes);
        $defaultCategoryId = $banner['resource_type'] === 'category'
            ? $banner['resource_id']
            : ($categories->first()['id'] ?? '');
        $currentBannerCategory = strtolower($banner['banner_category'] ?? '');
        if ($currentBannerCategory !== 'food' && $currentBannerCategory !== 'normal') {
            $currentBannerCategory = str_contains(strtolower($banner['banner_type'] ?? ''), 'food') ? 'food' : 'normal';
        }
        $currentMediaType = !empty($banner['video']) ? 'video' : ($banner['media_type'] ?? 'image');
        if ($currentBannerCategory !== 'normal' || strtolower((string) ($banner['banner_type'] ?? '')) !== 'main banner') {
            $currentMediaType = 'image';
        }
        $currentVideoSource = !empty($banner['video'])
            ? $banner['video_full_url']
            : null;
        $currentPhotoSource = data_get($banner, 'photo_full_url.path')
            ? getStorageImages(path:$banner['photo_full_url'],type: 'banner' )
            : (!empty($banner['photo'])
                ? asset('storage/banner/' . basename(parse_url($banner['photo'], PHP_URL_PATH) ?: $banner['photo']))
                : null);
    @endphp
    <div class="content container-fluid">

        <div class="d-flex justify-content-between mb-3">
            <div>
                <h2 class="h1 mb-1 text-capitalize d-flex align-items-center gap-2">
                    <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/banner.png') }}" alt="">
                    {{ translate('banner_update_form') }}
                </h2>
            </div>
            <div>
                <a class="btn btn--primary text-white" href="{{ route('admin.banner.list') }}">
                    <i class="tio-chevron-left"></i> {{ translate('back') }}</a>
            </div>
        </div>

        <div class="row text-start">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.banner.update', [$banner['id']]) }}" method="post" enctype="multipart/form-data"
                              class="banner_form">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input type="hidden" id="id" name="id">

                                    <div class="form-group">
                                        <label for="banner_category_select" class="title-color text-capitalize">Banner Category</label>
                                        <select class="form-control w-100" id="banner_category_select" name="banner_category">
                                            <option value="normal" {{ $currentBannerCategory === 'normal' ? 'selected' : '' }}>Normal</option>
                                            <option value="food" {{ $currentBannerCategory === 'food' ? 'selected' : '' }}>Food</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="name" class="title-color text-capitalize">{{ translate('banner_type') }}</label>
                                        <select class="js-example-responsive form-control w-100" name="banner_type" required id="banner_type_select">
                                            @foreach($allowedBannerTypes as $key => $singleBanner)
                                                <option value="{{ $key }}" {{ $banner['banner_type'] == $key ? 'selected':''}}>{{ $singleBanner }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <input type="hidden" name="url" id="url" value="#">

                                    <div class="form-group mb-3">
                                        <label for="media_type" class="title-color text-capitalize">{{ translate('media_type') }}</label>
                                        <select class="form-control w-100" name="media_type" id="media_type">
                                            <option value="image" {{ $currentMediaType === 'image' ? 'selected' : '' }}>{{ translate('image') }}</option>
                                            <option value="video" {{ $currentMediaType === 'video' ? 'selected' : '' }}>{{ translate('video') }}</option>
                                        </select>
                                        <small class="text-muted d-block mt-2 {{ $currentBannerCategory === 'normal' && strtolower((string) ($banner['banner_type'] ?? '')) === 'main banner' ? '' : 'd-none' }}" id="media_type_note">Video is available only for normal main banner.</small>
                                    </div>

                                    <input type="hidden" name="resource_type" value="category">
                                    <input type="hidden" name="category_id" value="{{ $defaultCategoryId }}">

                                    <div class="form-group mb-0 d-none" id="resource-product">
                                        <label for="product_id" class="title-color text-capitalize">{{ translate('food') }}</label>
                                        <select class="js-example-responsive form-control w-100"
                                                disabled
                                                name="product_id">
                                            @foreach($products as $product)
                                                <option value="{{$product['id']}}" {{$banner['resource_id']==$product['id']?'selected':''}}>{{$product['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group mb-0 d-none" id="resource-category">
                                        <label for="name" class="title-color text-capitalize">{{ translate('category') }}</label>
                                        <select class="js-example-responsive form-control w-100"
                                                disabled
                                                name="category_id">
                                            @foreach($categories as $category)
                                                <option value="{{$category['id']}}" {{ $defaultCategoryId == $category['id'] ? 'selected' : '' }}>{{$category['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group mb-0 d-none" id="resource-shop">
                                        <label for="shop_id" class="title-color text-capitalize">{{ translate('shop') }}</label>
                                        <select class="js-example-responsive form-control w-100"
                                                disabled
                                                name="shop_id">
                                            @foreach($shops as $shop)
                                                <option value="{{$shop['id']}}" {{$banner['resource_id']==$shop['id']?'selected':''}}>{{$shop['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group mb-0 d-none" id="resource-brand">
                                        <label for="brand_id" class="title-color text-capitalize">{{ translate('brand') }}</label>
                                        <select class="js-example-responsive form-control w-100"
                                                disabled
                                                name="brand_id">
                                            @foreach($brands as $brand)
                                                <option value="{{$brand['id']}}" {{$banner['resource_id']==$brand['id']?'selected':''}}>{{$brand['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    @if(theme_root_path() == 'theme_fashion')
                                    <div class="form-group mt-4 input-field-for-main-banner {{$banner['banner_type'] !='Main Banner'?'d-none':''}}">
                                        <label for="button_text" class="title-color text-capitalize">{{ translate('Button_Text') }}</label>
                                        <input type="text" name="button_text" class="form-control" id="button_text" placeholder="{{ translate('Enter_button_text') }}" value="{{$banner['button_text']}}">
                                    </div>
                                    <div class="form-group mt-4 mb-0 input-field-for-main-banner {{$banner['banner_type'] !='Main Banner'?'d-none':''}}">
                                        <label for="background_color" class="title-color text-capitalize">{{ translate('background_color') }}</label>
                                        <input type="color" name="background_color" class="form-control form-control_color w-100" id="background_color" value="{{$banner['background_color']}}">
                                    </div>
                                    @endif

                                </div>
                                <div class="col-md-6 d-flex flex-column justify-content-center">
                                    <div>
                                        <div id="banner-image-wrapper" class="{{ $currentMediaType === 'video' ? 'd-none' : '' }}">
                                            <div class="mx-auto text-center">
                                                <div class="uploadDnD">
                                                    <div class="form-group inputDnD input_image input_image_edit"
                                                         data-bg-img="{{ $currentPhotoSource }}"
                                                         data-title="{{ !empty($banner['photo']) ? '' : 'Drag and drop file or Browse file' }}">
                                                        <input type="file" name="image" class="form-control-file text--primary font-weight-bold" id="banner" accept=".jpg, .png, .jpeg, .gif, .bmp, .webp |image/*" {{ $currentMediaType === 'video' ? 'disabled' : '' }}>
                                                    </div>
                                                </div>
                                            </div>
                                            <label for="name" class="title-color text-capitalize">
                                                <span class="input-label-secondary cursor-pointer" data-toggle="tooltip" data-placement="right" title="" data-original-title="{{ translate('banner_image_ratio_is_not_same_for_all_sections_in_app').' '.translate('Please_review_the_ratio_before_upload') }}">
                                                    <img alt="" width="16" src={{dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }} alt="" class="m-1">
                                                </span>
                                                {{ translate('banner_image') }}
                                            </label>
                                            <span class="text-info" id="theme_ratio">( {{ translate('ratio') }} {{ "4:1" }} )</span>
                                            <p>{{ translate('banner_Image_ratio_is_not_same_for_all_sections_in_app') }}. {{ translate('please_review_the_ratio_before_upload') }}</p>
                                        </div>

                                        <div id="banner-video-wrapper" class="{{ $currentMediaType === 'video' ? '' : 'd-none' }}">
                                            <div class="form-group mb-3">
                                                <label for="video" class="title-color text-capitalize">{{ translate('banner_video') }}</label>
                                                <input type="file" name="video" class="form-control-file text--primary font-weight-bold" id="video" accept="video/mp4,video/webm,video/ogg,video/quicktime" {{ $currentMediaType === 'image' ? 'disabled' : '' }}>
                                            </div>
                                            @if($currentVideoSource)
                                                <div class="mb-3">
                                                    <video class="rounded w-100" controls preload="metadata" style="max-height: 260px;">
                                                        <source src="{{ $currentVideoSource }}">
                                                        {{ translate('video_preview_is_not_supported_in_this_browser') }}
                                                    </video>
                                                </div>
                                            @endif
                                            <p>{{ translate('upload_mp4_webm_or_ogg_video_for_banner') }}</p>
                                        </div>

                                         @if(theme_root_path() == 'theme_fashion')
                                         <div class="form-group mt-4 input-field-for-main-banner {{$banner['banner_type'] !='Main Banner'?'d-none':''}}">
                                             <label for="title" class="title-color text-capitalize">{{ translate('Title') }}</label>
                                             <input type="text" name="title" class="form-control" id="title" placeholder="{{ translate('Enter_banner_title') }}" value="{{$banner['title']}}">
                                         </div>
                                         <div class="form-group mb-0 input-field-for-main-banner {{$banner['banner_type'] !='Main Banner'?'d-none':''}}">
                                             <label for="sub_title" class="title-color text-capitalize">{{ translate('Sub_Title') }}</label>
                                             <input type="text" name="sub_title" class="form-control" id="sub_title" placeholder="{{ translate('Enter_banner_sub_title') }}" value="{{$banner['sub_title']}}">
                                         </div>
                                         @endif
                                    </div>
                                </div>

                                <div class="col-md-12 d-flex justify-content-end gap-3">
                                    <button type="submit" class="btn btn--primary px-4">{{ translate('update') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/banner.js') }}"></script>
    <script>
        "use strict";

        $(document).on('ready', function () {
            initializeBannerTypeOptions();
            getThemeWiseRatio();
            syncBannerRestrictions();
            toggleBannerMediaInput();
        });

        let elementBannerTypeSelect = $('#banner_type_select');
        let elementMediaTypeSelect = $('#media_type');
        let elementBannerCategorySelect = $('#banner_category_select');
        let allBannerTypeOptions = [];

        function initializeBannerTypeOptions() {
            allBannerTypeOptions = elementBannerTypeSelect.find('option').map(function () {
                return {
                    value: $(this).attr('value'),
                    text: $(this).text()
                };
            }).get();

            syncBannerTypeOptions();
        }

        function syncBannerTypeOptions() {
            let currentValue = elementBannerTypeSelect.val();
            let selectedCategory = elementBannerCategorySelect.val();
            let filteredOptions = selectedCategory === 'food'
                ? allBannerTypeOptions.filter(function (option) {
                    return option.value === 'Main Banner';
                })
                : allBannerTypeOptions;

            elementBannerTypeSelect.empty();

            filteredOptions.forEach(function (option) {
                elementBannerTypeSelect.append(new Option(option.text, option.value));
            });

            if (filteredOptions.some(function (option) { return option.value === currentValue; })) {
                elementBannerTypeSelect.val(currentValue);
            } else if (filteredOptions.length > 0) {
                elementBannerTypeSelect.val(filteredOptions[0].value);
            }
        }

        elementBannerTypeSelect.on('change',function(){
            getThemeWiseRatio();
            syncBannerRestrictions();
        });

        function getThemeWiseRatio(){
            let bannerType = elementBannerTypeSelect.val();
            let theme = '{{ theme_root_path() }}';
            let themeRatio = {!! json_encode(THEME_RATIO) !!};
            let getRatio = themeRatio[theme] && themeRatio[theme][bannerType] ? themeRatio[theme][bannerType] : '4:1';
            $('#theme_ratio').text(getRatio);
        }

        function toggleBannerMediaInput() {
            let mediaType = elementMediaTypeSelect.val();
            let imageInput = $('#banner');
            let videoInput = $('#video');

            if (mediaType === 'video') {
                $('#banner-image-wrapper').addClass('d-none');
                $('#banner-video-wrapper').removeClass('d-none');
                imageInput.prop('disabled', true).val('');
                videoInput.prop('disabled', false);
            } else {
                $('#banner-image-wrapper').removeClass('d-none');
                $('#banner-video-wrapper').addClass('d-none');
                videoInput.prop('disabled', true).val('');
                imageInput.prop('disabled', false);
            }
        }

        function bannerAllowsVideo() {
            return elementBannerCategorySelect.val() === 'normal' && elementBannerTypeSelect.val() === 'Main Banner';
        }

        function syncBannerRestrictions() {
            let allowsVideo = bannerAllowsVideo();

            if (allowsVideo) {
                $('#media_type_note').removeClass('d-none');
                elementMediaTypeSelect.find('option[value="video"]').prop('disabled', false);
            } else {
                elementMediaTypeSelect.val('image');
                elementMediaTypeSelect.find('option[value="video"]').prop('disabled', true);
                $('#media_type_note').addClass('d-none');
            }

            toggleBannerMediaInput();
        }

        elementBannerCategorySelect.on('change', function () {
            syncBannerTypeOptions();
            getThemeWiseRatio();
            syncBannerRestrictions();
        });

        elementMediaTypeSelect.on('change', function () {
            toggleBannerMediaInput();
        });

        $(document).on('change', '#video', function () {
            if (this.files && this.files.length > 0) {
                elementMediaTypeSelect.val('video').trigger('change');
            }
        });

        $(document).on('change', '#banner', function () {
            if (this.files && this.files.length > 0) {
                elementMediaTypeSelect.val('image').trigger('change');
            }
        });
    </script>
@endpush
