@php($foodAddonGroups = $foodProduct?->food_addons ?? [])

@if($foodProduct?->is_food && !empty($foodAddonGroups))
    <div class="food-addon-selector border rounded bg-light p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h6 class="mb-1">Food add-ons</h6>
                <small class="text-muted">Choose extras for this item.</small>
            </div>
        </div>

        <div class="d-flex flex-column gap-3">
            @foreach($foodAddonGroups as $groupIndex => $addonGroup)
                @php($options = array_values($addonGroup['options'] ?? []))
                @php($isRequired = !empty($addonGroup['is_required']))
                @php($minSelect = max(0, (int)($addonGroup['min_select'] ?? 0)))
                @php($maxSelect = min(max(1, (int)($addonGroup['max_select'] ?? count($options))), count($options)))
                @php($defaultSelectionCount = min(count($options), $maxSelect, max($isRequired ? 1 : 0, $minSelect)))
                @php($selectedIndexes = $defaultSelectionCount > 0 ? range(0, $defaultSelectionCount - 1) : [])
                @php($useRadio = $maxSelect === 1 && ($isRequired || $minSelect > 0))

                @if(!empty($options))
                    <div class="food-addon-group" data-group-index="{{ $groupIndex }}" data-max-select="{{ $maxSelect }}">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <div>
                                <div class="font-weight-bold">{{ $addonGroup['name'] ?? 'Add-on' }}</div>
                                <small class="text-muted">
                                    @if($isRequired || $minSelect > 0)
                                        Select at least {{ max(1, $minSelect) }}
                                    @else
                                        Optional
                                    @endif
                                    @if($maxSelect > 1)
                                        , up to {{ $maxSelect }}
                                    @endif
                                </small>
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-2">
                            @foreach($options as $optionIndex => $option)
                                @php($checked = in_array($optionIndex, $selectedIndexes, true))
                                <label class="d-flex justify-content-between align-items-center gap-3 border rounded bg-white px-3 py-2 mb-0 cursor-pointer">
                                    <span class="d-flex align-items-center gap-2">
                                        <input
                                            type="{{ $useRadio ? 'radio' : 'checkbox' }}"
                                            class="food-addon-input"
                                            name="selected_food_addons[{{ $groupIndex }}][]"
                                            value="{{ $optionIndex }}"
                                            data-max-select="{{ $maxSelect }}"
                                            {{ $checked ? 'checked' : '' }}
                                        >
                                        <span>{{ $option['name'] ?? 'Option' }}</span>
                                    </span>
                                    <span class="text-nowrap text-muted">+ {{ webCurrencyConverter(amount: (float)($option['price'] ?? 0)) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endif
