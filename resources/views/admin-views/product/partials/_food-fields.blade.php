@php($foodProduct = $product ?? null)
@php($foodAddons = old('food_addons', $foodProduct?->food_addons ?? []))
@php($availableFrom = old('available_from', $foodProduct?->available_from ? substr((string) $foodProduct->available_from, 0, 5) : ''))
@php($availableTo = old('available_to', $foodProduct?->available_to ? substr((string) $foodProduct->available_to, 0, 5) : ''))
@php($isFoodEnabled = old('is_food', isset($foodProduct) ? ($foodProduct?->is_food ? 'on' : null) : 'on'))

<div class="card mt-3 rest-part physical_product_show" id="food-settings-card">
    <div class="card-header">
        <div class="d-flex gap-2 align-items-center">
            <i class="tio-restaurant"></i>
            <h4 class="mb-0">Food Settings</h4>
        </div>
    </div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="form-group">
                    <div class="form-control h-auto min-form-control-height d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <label class="title-color mb-0">Enable food fields</label>
                            <p class="text-muted fz-12 mb-0">Turn this on to configure food-specific details and add-ons.</p>
                        </div>
                        <label class="switcher mb-0">
                            <input type="checkbox" class="switcher_input" name="is_food" id="is_food" {{ $isFoodEnabled ? 'checked' : '' }}>
                            <span class="switcher_control"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3 food-settings-field">
                <div class="form-group">
                    <label class="title-color">Food type</label>
                    <select name="food_type" class="form-control">
                        <option value="" disabled {{ old('food_type', $foodProduct?->food_type) ? '' : 'selected' }}>---Select---</option>
                        <option value="veg" {{ old('food_type', $foodProduct?->food_type) === 'veg' ? 'selected' : '' }}>Veg</option>
                        <option value="non_veg" {{ old('food_type', $foodProduct?->food_type) === 'non_veg' ? 'selected' : '' }}>Non Veg</option>
                        <option value="egg" {{ old('food_type', $foodProduct?->food_type) === 'egg' ? 'selected' : '' }}>Egg</option>
                        <option value="jain" {{ old('food_type', $foodProduct?->food_type) === 'jain' ? 'selected' : '' }}>Jain</option>
                        <option value="beverage" {{ old('food_type', $foodProduct?->food_type) === 'beverage' ? 'selected' : '' }}>Beverage</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3 food-settings-field">
                <div class="form-group">
                    <label class="title-color">Preparation time (minutes)</label>
                    <input type="number" min="1" max="1440" class="form-control" name="prep_time" value="{{ old('prep_time', $foodProduct?->prep_time) }}" placeholder="e.g. 20">
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3 food-settings-field">
                <div class="form-group">
                    <label class="title-color">Available from</label>
                    <input type="time" class="form-control" name="available_from" value="{{ $availableFrom }}">
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3 food-settings-field">
                <div class="form-group">
                    <label class="title-color">Available to</label>
                    <input type="time" class="form-control" name="available_to" value="{{ $availableTo }}">
                </div>
            </div>
        </div>

        <div class="food-settings-field mt-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Food add-ons</h5>
                    <p class="text-muted fz-12 mb-0">Create optional or required addon groups like toppings, extras, drinks, or dips.</p>
                </div>
                <button type="button" class="btn btn-outline--primary btn-sm" id="add-food-addon-group">Add addon group</button>
            </div>

            <div id="food-addon-groups" data-next-index="{{ count($foodAddons) }}">
                @foreach($foodAddons as $groupIndex => $addonGroup)
                    <div class="card card-body mb-3 food-addon-card" data-group-index="{{ $groupIndex }}" data-next-option-index="{{ count($addonGroup['options'] ?? []) }}">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <h5 class="mb-0">Addon Group</h5>
                            <button type="button" class="btn btn-outline-danger btn-sm remove-food-addon-group">Remove group</button>
                        </div>
                        <div class="row align-items-end">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="title-color">Group name</label>
                                    <input type="text" class="form-control" name="food_addons[{{ $groupIndex }}][name]" value="{{ $addonGroup['name'] ?? '' }}" placeholder="e.g. Extra Toppings">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-2">
                                <div class="form-group">
                                    <label class="title-color">Min select</label>
                                    <input type="number" min="0" class="form-control" name="food_addons[{{ $groupIndex }}][min_select]" value="{{ $addonGroup['min_select'] ?? 0 }}">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-2">
                                <div class="form-group">
                                    <label class="title-color">Max select</label>
                                    <input type="number" min="1" class="form-control" name="food_addons[{{ $groupIndex }}][max_select]" value="{{ $addonGroup['max_select'] ?? max(1, count($addonGroup['options'] ?? [])) }}">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-2">
                                <div class="form-group">
                                    <div class="form-control h-auto min-form-control-height d-flex align-items-center justify-content-between gap-2">
                                        <span class="title-color mb-0">Required</span>
                                        <label class="switcher mb-0">
                                            <input type="checkbox" class="switcher_input" name="food_addons[{{ $groupIndex }}][is_required]" value="1" {{ !empty($addonGroup['is_required']) ? 'checked' : '' }}>
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="food-addon-options">
                            @foreach(($addonGroup['options'] ?? []) as $optionIndex => $option)
                                <div class="row align-items-end mb-2 food-addon-option-row" data-option-index="{{ $optionIndex }}">
                                    <div class="col-md-7 col-lg-8">
                                        <div class="form-group mb-0">
                                            <label class="title-color {{ $optionIndex > 0 ? 'd-none d-md-inline-block invisible' : '' }}">Option name</label>
                                            <input type="text" class="form-control" name="food_addons[{{ $groupIndex }}][options][{{ $optionIndex }}][name]" value="{{ $option['name'] ?? '' }}" placeholder="e.g. Cheese Burst">
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-lg-2">
                                        <div class="form-group mb-0">
                                            <label class="title-color {{ $optionIndex > 0 ? 'd-none d-md-inline-block invisible' : '' }}">Price</label>
                                            <input type="number" min="0" step="0.01" class="form-control" name="food_addons[{{ $groupIndex }}][options][{{ $optionIndex }}][price]" value="{{ $option['price'] ?? 0 }}" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm w-100 remove-food-addon-option">Remove</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline--primary btn-sm add-food-addon-option">Add option</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>