<?php
/**
 * Helper function to find the terms for a specific post and identify their levels.
 * This is an internal function to avoid repeating code.
 *
 * @param int $post_id The ID of the post to check.
 * @return array An associative array with 'state', 'district', and 'city' term objects.
 */
function get_post_location_hierarchy_by_id($post_id) {
    // Return a cached result if we've already figured it out for this post
    static $location_hierarchy = [];
    if (isset($location_hierarchy[$post_id])) {
        return $location_hierarchy[$post_id];
    }

    if (empty($post_id) || !is_numeric($post_id)) {
        return ['state' => null, 'district' => null, 'city' => null];
    }

    $terms = get_the_terms($post_id, 'locations');
    $result = ['state' => null, 'district' => null, 'city' => null];

    if (empty($terms) || is_wp_error($terms)) {
        $location_hierarchy[$post_id] = $result;
        return $result;
    }

    foreach ($terms as $term) {
        $ancestors = get_ancestors($term->term_id, 'locations');
        $level = count($ancestors);

        if ($level == 2) { // This is a City
            $result['city'] = $term;
        } elseif ($level == 1) { // This is a District
            $result['district'] = $term;
        } elseif ($level == 0) { // This is a State
            $result['state'] = $term;
        }
    }

    // If only a city was assigned, find its parents
    if ($result['city'] && !$result['district']) {
        $ancestors = get_ancestors($result['city']->term_id, 'locations', 'taxonomy');
        if (!empty($ancestors)) {
            $result['district'] = get_term($ancestors[0], 'locations');
            if (isset($ancestors[1])) {
                $result['state'] = get_term($ancestors[1], 'locations');
            }
        }
    }
    // If only a district was assigned, find its state
    elseif ($result['district'] && !$result['state']) {
        $ancestors = get_ancestors($result['district']->term_id, 'locations', 'taxonomy');
        if (!empty($ancestors)) {
            $result['state'] = get_term($ancestors[0], 'locations');
        }
    }

    // Cache the result for this post ID
    $location_hierarchy[$post_id] = $result;

    return $result;
}

/**
 * Helper function for the current post. Uses the more generic function.
 */
function get_current_post_location_hierarchy() {
    global $post;
    if (isset($post)) {
        return get_post_location_hierarchy_by_id($post->ID);
    }
    return ['state' => null, 'district' => null, 'city' => null];
}


// --- POST LOCATION SHORTCODES ---

// Shortcode to display the State for the current post
add_shortcode('current_post_state', 'display_current_post_state_term');
add_shortcode('current_post_state_name', 'display_current_post_state_term'); // ALIAS
function display_current_post_state_term() {
    $hierarchy = get_current_post_location_hierarchy();
    return ($hierarchy['state']) ? esc_html($hierarchy['state']->name) : '';
}

// Shortcode to display the District for the current post
add_shortcode('current_post_district', 'display_current_post_district_term');
add_shortcode('current_post_district_name', 'display_current_post_district_term'); // ALIAS
function display_current_post_district_term() {
    $hierarchy = get_current_post_location_hierarchy();
    return ($hierarchy['district']) ? esc_html($hierarchy['district']->name) : '';
}

// Shortcode to display the City for the current post
add_shortcode('current_post_city', 'display_current_post_city_term');
add_shortcode('current_post_city_name', 'display_current_post_city_term'); // ALIAS
function display_current_post_city_term() {
    $hierarchy = get_current_post_location_hierarchy();
    return ($hierarchy['city']) ? esc_html($hierarchy['city']->name) : '';
}

// --- QUERY POST SHORTCODES ---

// Query Post State from URL
add_shortcode('query_post_state', 'display_query_post_state');
add_shortcode('query_post_state_name', 'display_query_post_state'); // ALIAS
function display_query_post_state() {
    if (isset($_GET['post_id'])) {
        $post_id = intval($_GET['post_id']);
        $hierarchy = get_post_location_hierarchy_by_id($post_id);
        return ($hierarchy['state']) ? esc_html($hierarchy['state']->name) : '';
    }
    return '';
}

// Query Post District from URL
add_shortcode('query_post_district', 'display_query_post_district');
add_shortcode('query_post_district_name', 'display_query_post_district'); // ALIAS
function display_query_post_district() {
    if (isset($_GET['post_id'])) {
        $post_id = intval($_GET['post_id']);
        $hierarchy = get_post_location_hierarchy_by_id($post_id);
        return ($hierarchy['district']) ? esc_html($hierarchy['district']->name) : '';
    }
    return '';
}

// Query Post City from URL
add_shortcode('query_post_city', 'display_query_post_city');
add_shortcode('query_post_city_name', 'display_query_post_city'); // ALIAS
function display_query_post_city() {
    if (isset($_GET['post_id'])) {
        $post_id = intval($_GET['post_id']);
        $hierarchy = get_post_location_hierarchy_by_id($post_id);
        return ($hierarchy['city']) ? esc_html($hierarchy['city']->name) : '';
    }
    return '';
}


// --- USER LOCATION SHORTCODES ---

// Shortcode to display the current logged-in user's state
add_shortcode('current_user_state', 'display_current_user_state');
add_shortcode('current_user_state_name', 'display_current_user_state'); // ALIAS
function display_current_user_state() {
    $user_id = get_current_user_id();
    return ($user_id) ? esc_html(get_user_meta($user_id, 'user_state', true)) : '';
}

// Shortcode to display the current logged-in user's district
add_shortcode('current_user_district', 'display_current_user_district');
add_shortcode('current_user_district_name', 'display_current_user_district'); // ALIAS
function display_current_user_district() {
    $user_id = get_current_user_id();
    return ($user_id) ? esc_html(get_user_meta($user_id, 'user_district', true)) : '';
}

// Shortcode to display the current logged-in user's city
add_shortcode('current_user_city', 'display_current_user_city');
add_shortcode('current_user_city_name', 'display_current_user_city'); // ALIAS
function display_current_user_city() {
    $user_id = get_current_user_id();
    return ($user_id) ? esc_html(get_user_meta($user_id, 'user_city', true)) : '';
}

// --- QUERY USER SHORTCODES ---

// Query User State from URL
add_shortcode('query_user_state', 'display_query_user_state');
add_shortcode('query_user_state_name', 'display_query_user_state'); // ALIAS
function display_query_user_state() {
    if (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
        return ($user_id) ? esc_html(get_user_meta($user_id, 'user_state', true)) : '';
    }
    return '';
}

// Query User District from URL
add_shortcode('query_user_district', 'display_query_user_district');
add_shortcode('query_user_district_name', 'display_query_user_district'); // ALIAS
function display_query_user_district() {
    if (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
        return ($user_id) ? esc_html(get_user_meta($user_id, 'user_district', true)) : '';
    }
    return '';
}

// Query User City from URL
add_shortcode('query_user_city', 'display_query_user_city');
add_shortcode('query_user_city_name', 'display_query_user_city'); // ALIAS
function display_query_user_city() {
    if (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
        return ($user_id) ? esc_html(get_user_meta($user_id, 'user_city', true)) : '';
    }
    return '';
}
?>