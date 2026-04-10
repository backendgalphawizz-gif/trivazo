@php($selectedFoodAddons = $selectedFoodAddons ?? [])

@if(!empty($selectedFoodAddons))
    <div class="mt-2 food-addon-summary">
        <div class="fs-12 text-muted mb-1">Add-ons</div>
        <div class="d-flex flex-column gap-1">
            @foreach($selectedFoodAddons as $addonGroup)
                @if(!empty($addonGroup['options']))
                    <div class="fs-12 text-muted">
                        <span class="font-weight-bold text-dark">{{ $addonGroup['name'] ?? 'Add-on' }}:</span>
                        {{ collect($addonGroup['options'])->map(function ($option) {
                            return ($option['name'] ?? 'Option') . ' (+ ' . webCurrencyConverter(amount: (float)($option['price'] ?? 0)) . ')';
                        })->implode(', ') }}
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endif
