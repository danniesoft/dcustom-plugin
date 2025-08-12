<?php

// Get subscription product by selectedplan or fallback to current post
function get_selected_subscription_product() {
    $selected_id = isset($_GET['selectedplan']) ? intval($_GET['selectedplan']) : 0;

    // Try the selectedplan from URL
    if ($selected_id > 1) {
        $product = wc_get_product($selected_id);
        if ($product && $product->get_type() === 'subscription') {
            return $product;
        }
    }

    // Fallback: check current post
    $current_id = get_the_ID();
    $current_product = wc_get_product($current_id);
    if ($current_product && $current_product->get_type() === 'subscription') {
        return $current_product;
    }

    // Not a valid subscription
    return false;
}

// Shortcode: [selected_plan_id]
function shortcode_selected_plan_id() {
    $product = get_selected_subscription_product();
    return $product ? $product->get_id() : '0';
}
add_shortcode('selected_plan_id', 'shortcode_selected_plan_id');

// Shortcode: [selected_plan_title]
function shortcode_selected_plan_title() {
    $product = get_selected_subscription_product();
    return $product ? $product->get_title() : '';
}
add_shortcode('selected_plan_title', 'shortcode_selected_plan_title');
