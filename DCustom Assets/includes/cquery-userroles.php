<?php

/**
 * Get user roles by user ID
 */
function get_user_roles_by_id($user_id = null) {
    if (!$user_id) {
        return ''; // Return empty if no valid user_id is provided
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return ''; // Return empty if the user ID is invalid
    }

    $roles = (array) $user->roles;

    if (!empty($roles)) {
        $primary_role = array_shift($roles); // Get the first role as the primary role
        $other_roles = !empty($roles) ? ', ' . implode(', ', $roles) : '';

        return $primary_role . $other_roles; // Role IDs are already lowercase
    }

    return ''; // Return empty if the user has no roles assigned
}

/**
 * Shortcode for logged-in user's role
 */
function current_user_roles_shortcode() {
    if (!is_user_logged_in()) {
        return 'guest';
    }
    $user = wp_get_current_user();
    return get_user_roles_by_id($user->ID);
}

/**
 * Shortcode for role based on URL query (?user_id=3) - Returns empty if invalid
 */
function query_user_roles_shortcode() {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
    return get_user_roles_by_id($user_id);
}

/**
 * Shortcode for role based on URL query (?user_id=3) - Falls back to logged-in user role if invalid
 */
function query_user_roles_fallback_shortcode() {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
    $role = get_user_roles_by_id($user_id);

    if ($role === '') {
        return current_user_roles_shortcode(); // Fallback to logged-in user role
    }

    return $role;
}

/**
 * Shortcode for JetEngine Profile Page (gets user ID from profile URL like /user/6/)
 */
function jetengine_profile_user_roles_shortcode() {
    // Get the user ID from the JetEngine profile URL (e.g., /user/6/)
    $url_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $url_parts = explode('/', $url_path);
    
    // Check if the URL matches the JetEngine profile pattern (e.g., 'user/6')
    if (count($url_parts) >= 2 && $url_parts[0] === 'user') {
        $user_id = intval($url_parts[1]);
        return get_user_roles_by_id($user_id);
    }
    
    return ''; // Return empty if not on a JetEngine profile page
}

/**
 * Shortcode for JetEngine User Listings/Grid (gets user ID from listing context)
 */
function jetengine_listing_user_roles_shortcode() {
    // Check if we're in a JetEngine listing context and get the user ID
    if (function_exists('jet_engine') && jet_engine()->listings) {
        $user_id = jet_engine()->listings->data->get_current_object_id();
        
        // For user listings, the object might be the user object itself
        if (empty($user_id)) {
            $object = jet_engine()->listings->data->get_current_object();
            if ($object instanceof WP_User) {
                $user_id = $object->ID;
            }
        }
        
        if ($user_id) {
            return get_user_roles_by_id($user_id);
        }
    }
    
    return ''; // Return empty if not in a listing context or user ID not found
}

// Register all shortcodes
add_shortcode('user_roles', 'current_user_roles_shortcode'); // Logged-in user role
add_shortcode('query_user_role', 'query_user_roles_shortcode'); // Role from query var, empty if invalid
add_shortcode('query_user_role_fallback', 'query_user_roles_fallback_shortcode'); // Role from query var, fallback to logged-in user role
add_shortcode('jetengine_profile_user_role', 'jetengine_profile_user_roles_shortcode'); // For JetEngine profile pages
add_shortcode('jetengine_listing_user_role', 'jetengine_listing_user_roles_shortcode'); // For JetEngine user listings/grids