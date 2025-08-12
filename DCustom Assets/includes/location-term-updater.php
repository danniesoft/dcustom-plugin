<?php

// ===== PART 1: SERVER-SIDE PHP FUNCTIONS (Unchanged) =====

/**
 * 1A: AJAX handler to get child terms (e.g., districts of a state).
 */
add_action('wp_ajax_get_qpost_child_terms', 'get_qpost_child_terms_ajax_handler');
add_action('wp_ajax_nopriv_get_qpost_child_terms', 'get_qpost_child_terms_ajax_handler');
function get_qpost_child_terms_ajax_handler() {
    if (!check_ajax_referer('qpost_terms_nonce', 'security', false)) {
        wp_send_json_error(['message' => 'Invalid security token.'], 403);
        return;
    }
    $parent_term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
    $child_terms_data = [];
    $child_terms = get_terms([
        'taxonomy'   => 'locations',
        'parent'     => $parent_term_id,
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);
    if (!is_wp_error($child_terms) && !empty($child_terms)) {
        foreach ($child_terms as $child_term) {
            $child_terms_data[] = ['id' => $child_term->term_id, 'name' => $child_term->name];
        }
    }
    wp_send_json_success($child_terms_data);
}

/**
 * 1B: AJAX handler to get the full location hierarchy from a single ID.
 */
add_action('wp_ajax_get_location_hierarchy_from_term_id', 'get_location_hierarchy_from_term_id_ajax_handler');
add_action('wp_ajax_nopriv_get_location_hierarchy_from_term_id', 'get_location_hierarchy_from_term_id_ajax_handler');
function get_location_hierarchy_from_term_id_ajax_handler() {
    if (!check_ajax_referer('qpost_terms_nonce', 'security', false)) {
        wp_send_json_error(['message' => 'Invalid security token.'], 403);
        return;
    }
    $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
    if (!$term_id) {
        wp_send_json_error(['message' => 'No Term ID provided.'], 400);
        return;
    }
    $state_id = null; $district_id = null; $city_id = null;
    $term = get_term($term_id, 'locations');
    if ($term && !is_wp_error($term)) {
        $ancestors = get_ancestors($term->term_id, 'locations');
        if (count($ancestors) == 2) { // City
            $city_id = $term->term_id;
            $district_id = $ancestors[0];
            $state_id = $ancestors[1];
        } elseif (count($ancestors) == 1) { // District
            $district_id = $term->term_id;
            $state_id = $ancestors[0];
        } elseif ($term->parent == 0) { // State
            $state_id = $term->term_id;
        }
    }
    $response = ['state_id' => $state_id, 'district_id' => $district_id, 'city_id' => $city_id];
    wp_send_json_success($response);
}


// ===== PART 2: CLIENT-SIDE JAVASCRIPT (Adjusted Logic) =====

/**
 * Injects the JavaScript into the website's footer to control the dropdowns.
 */
add_action('wp_footer', 'qpost_add_term_script_to_footer_adjusted');
function qpost_add_term_script_to_footer_adjusted() {
    $terms_nonce = wp_create_nonce('qpost_terms_nonce');
    ?>
    <script type="text/javascript" id="qpost-term-hierarchy-script-adjusted">
    (function($) {
        'use strict';
        
        const stateSelectSelector = '.sterm_id';
        const districtSelectSelector = '.dterm_id';
        const citySelectSelector = '.cterm_id';

        const $stateSelect = $(stateSelectSelector);
        const $districtSelect = $(districtSelectSelector);
        const $citySelect = $(citySelectSelector);
        
        const updateChildDropdown = (parentId, $childSelect, childType) => {
            $childSelect.empty().append($('<option>', { value: '', text: `Loading ${childType}s...` })).prop('disabled', true);
            
            if (parentId === null || parentId === '') {
                $childSelect.empty().append($('<option>', { value: '', text: `Select a ${childType === 'district' ? 'state' : 'district'} first` })).prop('disabled', true);
                $childSelect.trigger('change');
                return $.Deferred().resolve().promise();
            }

            return $.ajax({
                url: "<?php echo admin_url('admin-ajax.php'); ?>",
                type: 'POST',
                dataType: 'json',
                data: { action: 'get_qpost_child_terms', term_id: parentId, security: "<?php echo $terms_nonce; ?>" },
                success: function(response) {
                    $childSelect.prop('disabled', false);
                    $childSelect.empty().append($('<option>', { value: '', text: `Select a ${childType}` }));
                    if (response.success && response.data.length > 0) {
                        $.each(response.data, function(index, term) {
                            $childSelect.append($('<option>', { value: term.id, text: term.name }));
                        });
                    } else {
                        $childSelect.append($('<option>', { value: '', text: `No ${childType}s found` }));
                    }
                },
                error: function() {
                    $childSelect.empty().append($('<option>', { value: '', text: 'Error loading data' })).prop('disabled', true).trigger('change');
                }
            });
        };

        // Define the event handler functions
        const stateChangeHandler = function() {
            const stateId = $(this).val();
            updateChildDropdown(stateId, $districtSelect, 'district').done(() => $districtSelect.trigger('change'));
            updateChildDropdown('', $citySelect, 'city').done(() => $citySelect.trigger('change'));
        };

        const districtChangeHandler = function() {
            const districtId = $(this).val();
            updateChildDropdown(districtId, $citySelect, 'city').done(() => $citySelect.trigger('change'));
        };

        // Attach event handlers for manual user interaction
        $stateSelect.on('change', stateChangeHandler);
        $districtSelect.on('change', districtChangeHandler);
        
        $(document).ready(function() {
            // --- ADJUSTMENT: We only need the selector for the city ID now ---
            const defaultCityIdSelector = '.default_city_id';

            // Always populate states first
            updateChildDropdown(0, $stateSelect, 'state').done(function() {
                // Use a delay to ensure all other page scripts (like JetForm) are finished
                setTimeout(function() {
                    
                    // --- ADJUSTMENT: Only look for the city ID to start the process ---
                    const termToLookup = ($(defaultCityIdSelector).val() || '').trim();
                    
                    if (!termToLookup) {
                        $stateSelect.trigger('change');
                        return;
                    }

                    // The Fix: Detach handlers, set values in a chain, then re-attach handlers
                    console.log('QPOST Script: Temporarily detaching event handlers.');
                    $stateSelect.off('change', stateChangeHandler);
                    $districtSelect.off('change', districtChangeHandler);

                    const reattachHandlers = () => {
                        $stateSelect.on('change', stateChangeHandler);
                        $districtSelect.on('change', districtChangeHandler);
                        console.log('QPOST Script: Event handlers re-attached.');
                    };

                    $.ajax({
                        url: "<?php echo admin_url('admin-ajax.php'); ?>",
                        type: 'POST',
                        dataType: 'json',
                        data: { action: 'get_location_hierarchy_from_term_id', term_id: termToLookup, security: "<?php echo $terms_nonce; ?>" },
                        success: function(response) {
                            if (!response.success || !response.data) {
                                reattachHandlers();
                                return;
                            }
                            const { state_id, district_id, city_id } = response.data;
                            if (state_id) {
                                // 1. Set State
                                $stateSelect.val(state_id);
                                // 2. Load Districts
                                updateChildDropdown(state_id, $districtSelect, 'district').done(function() {
                                    // 3. Set District
                                    if (district_id) {
                                        $districtSelect.val(district_id);
                                    }
                                    // 4. Load Cities
                                    updateChildDropdown(district_id, $citySelect, 'city').done(function() {
                                        // 5. Set City
                                        if (city_id) {
                                            $citySelect.val(city_id);
                                        }
                                        
                                        // 6. Trigger UI updates now that all data is set
                                        $stateSelect.trigger('change');
                                        $districtSelect.trigger('change');
                                        $citySelect.trigger('change');
                                        
                                        // 7. Re-attach the handlers for manual use
                                        reattachHandlers();
                                    });
                                });
                            } else {
                                reattachHandlers();
                            }
                        },
                        error: function() {
                           console.error('QPOST Script: AJAX error during default selection.');
                           reattachHandlers();
                        }
                    });
                }, 300);
            });
        });

    })(jQuery);
    </script>
    <?php
}