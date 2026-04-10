<script>
    "use strict";

    function getFoodAddonOptionRow(groupIndex, optionIndex) {
        return `
            <div class="row align-items-end mb-2 food-addon-option-row" data-option-index="${optionIndex}">
                <div class="col-md-7 col-lg-8">
                    <div class="form-group mb-0">
                        <label class="title-color d-none d-md-inline-block invisible">Option name</label>
                        <input type="text" class="form-control" name="food_addons[${groupIndex}][options][${optionIndex}][name]" placeholder="e.g. Cheese Burst">
                    </div>
                </div>
                <div class="col-md-3 col-lg-2">
                    <div class="form-group mb-0">
                        <label class="title-color d-none d-md-inline-block invisible">Price</label>
                        <input type="number" min="0" step="0.01" class="form-control" name="food_addons[${groupIndex}][options][${optionIndex}][price]" value="0" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100 remove-food-addon-option">Remove</button>
                </div>
            </div>`;
    }

    function getFoodAddonGroupCard(groupIndex) {
        return `
            <div class="card card-body mb-3 food-addon-card" data-group-index="${groupIndex}" data-next-option-index="1">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <h5 class="mb-0">Addon Group</h5>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-food-addon-group">Remove group</button>
                </div>
                <div class="row align-items-end">
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <label class="title-color">Group name</label>
                            <input type="text" class="form-control" name="food_addons[${groupIndex}][name]" placeholder="e.g. Extra Toppings">
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="form-group">
                            <label class="title-color">Min select</label>
                            <input type="number" min="0" class="form-control" name="food_addons[${groupIndex}][min_select]" value="0">
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="form-group">
                            <label class="title-color">Max select</label>
                            <input type="number" min="1" class="form-control" name="food_addons[${groupIndex}][max_select]" value="1">
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="form-group">
                            <div class="form-control h-auto min-form-control-height d-flex align-items-center justify-content-between gap-2">
                                <span class="title-color mb-0">Required</span>
                                <label class="switcher mb-0">
                                    <input type="checkbox" class="switcher_input" name="food_addons[${groupIndex}][is_required]" value="1">
                                    <span class="switcher_control"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="food-addon-options">
                    ${getFoodAddonOptionRow(groupIndex, 0)}
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-outline--primary btn-sm add-food-addon-option">Add option</button>
                </div>
            </div>`;
    }

    function toggleFoodSettingsVisibility() {
        const shouldShow = $('#product_type').val() === 'physical' && $('#is_food').is(':checked');
        $('.food-settings-field').toggle(shouldShow);
        $('#add-food-addon-group').prop('disabled', !shouldShow);
        $('#food-addon-groups').toggle(shouldShow);
    }

    $(function () {
        toggleFoodSettingsVisibility();

        $(document).on('change', '#is_food, #product_type', function () {
            toggleFoodSettingsVisibility();
        });

        $(document).on('click', '#add-food-addon-group', function () {
            const container = $('#food-addon-groups');
            const groupIndex = Number(container.attr('data-next-index') || 0);
            container.append(getFoodAddonGroupCard(groupIndex));
            container.attr('data-next-index', groupIndex + 1);
        });

        $(document).on('click', '.remove-food-addon-group', function () {
            $(this).closest('.food-addon-card').remove();
        });

        $(document).on('click', '.add-food-addon-option', function () {
            const card = $(this).closest('.food-addon-card');
            const groupIndex = card.data('group-index');
            const optionIndex = Number(card.attr('data-next-option-index') || 0);
            card.find('.food-addon-options').append(getFoodAddonOptionRow(groupIndex, optionIndex));
            card.attr('data-next-option-index', optionIndex + 1);
        });

        $(document).on('click', '.remove-food-addon-option', function () {
            const optionsContainer = $(this).closest('.food-addon-options');
            $(this).closest('.food-addon-option-row').remove();
            if (!optionsContainer.find('.food-addon-option-row').length) {
                const card = optionsContainer.closest('.food-addon-card');
                const groupIndex = card.data('group-index');
                const optionIndex = Number(card.attr('data-next-option-index') || 0);
                optionsContainer.append(getFoodAddonOptionRow(groupIndex, optionIndex));
                card.attr('data-next-option-index', optionIndex + 1);
            }
        });
    });
</script>