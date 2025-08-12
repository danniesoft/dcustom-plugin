<?php

// Helper function to get the current page URL
function get_current_page_url() {
    return (is_ssl() ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// 1. Direct Logout (No Confirmation) - Stay on Current Page
function logout_current_page_shortcode() {
    if (is_user_logged_in()) {
        $logout_url = wp_logout_url(get_current_page_url());
        return '<a href="' . esc_url($logout_url) . '">Logout</a>';
    }
    return '';
}
add_shortcode('logout_current', 'logout_current_page_shortcode');

// 2. Direct Logout (No Confirmation) - Redirect to Homepage
function logout_home_shortcode() {
    if (is_user_logged_in()) {
        $logout_url = wp_logout_url(home_url());
        return '<a href="' . esc_url($logout_url) . '">Logout</a>';
    }
    return '';
}
add_shortcode('logout_home', 'logout_home_shortcode');

// 3. Direct Logout (No Confirmation) - Redirect to Custom URL
function logout_custom_shortcode($atts) {
    $atts = shortcode_atts(['redirect' => home_url()], $atts, 'logout_custom');
    
    if (is_user_logged_in()) {
        $logout_url = wp_logout_url($atts['redirect']);
        return '<a href="' . esc_url($logout_url) . '">Logout</a>';
    }
    return '';
}
add_shortcode('logout_custom', 'logout_custom_shortcode');

// 4. Logout URL Only (No Confirmation) - Stay on Current Page
function logout_url_current_shortcode() {
    if (is_user_logged_in()) {
        return esc_url(wp_logout_url(get_current_page_url()));
    }
    return '';
}
add_shortcode('logout_url_current', 'logout_url_current_shortcode');

// 5. Logout URL Only (No Confirmation) - Redirect to Homepage
function logout_url_home_shortcode() {
    if (is_user_logged_in()) {
        return esc_url(wp_logout_url(home_url()));
    }
    return '';
}
add_shortcode('logout_url_home', 'logout_url_home_shortcode');

// 6. Logout URL Only (No Confirmation) - Redirect to Custom URL
function logout_url_custom_shortcode($atts) {
    $atts = shortcode_atts(['redirect' => home_url()], $atts, 'logout_url_custom');
    
    if (is_user_logged_in()) {
        return esc_url(wp_logout_url($atts['redirect']));
    }
    return '';
}
add_shortcode('logout_url_custom', 'logout_url_custom_shortcode');