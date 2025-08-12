<?php

/**
 * Creates a single dynamic shortcode [user_subscription_status_dynamic]
 * that returns a specific value based on the current user's subscription status.
 * This version correctly handles checkbox user meta fields that store values as an array.
 *
 * How to use:
 * Simply place the shortcode [user_subscription_status_dynamic] on any page, post, or widget.
 */
add_shortcode('user_subscription_status_dynamic', 'get_dynamic_user_status_shortcode_corrected');

function get_dynamic_user_status_shortcode_corrected() {

    // 1. If current user is not logged in, return 'guest'
    if (!is_user_logged_in()) {
        return 'guest';
    }

    // Get the current user's ID and all their subscriptions
    $user_id = get_current_user_id();
    $subscriptions = wcs_get_users_subscriptions($user_id);

    // If the user is logged in but has never had a subscription...
    if (empty($subscriptions)) {
        
        // --- Start of Corrected Checkbox Logic ---

        // Get the raw meta value for the checkbox field.
        // The 'true' gets a single value, which could be a string or an array for checkboxes.
        $terms_meta = get_user_meta($user_id, 'signup_terms_and_conditions', true);

        // Check if the checkbox is marked as "checked".
        // A checked box might have the value '1' or an array containing '1'. This checks for both.
        if (is_array($terms_meta) && in_array('1', $terms_meta)) {
            // This handles cases where the value is ['1']
            return 'starter';
        } elseif ($terms_meta === '1') {
            // This handles cases where the value is just the string '1'
            return 'starter';
        } else {
            // Otherwise, the box is unchecked. Return 'newuser'.
            return 'newuser';
        }

        // --- End of Corrected Checkbox Logic ---
    }

    // If the user has subscriptions, the following logic runs (this is unchanged).

    // 2. Check for an active subscription first.
    foreach ($subscriptions as $subscription) {
        if ($subscription->has_status('active')) {
            return 'subscribed';
        }
    }

    // If no active subscription was found, check the status of the MOST RECENT one.
    usort($subscriptions, function($a, $b) {
        return $b->get_date_created() <=> $a->get_date_created();
    });
    $latest_subscription = $subscriptions[0];

    // 4. If the latest subscription is expired or pending cancellation, return 'renew'
    if ($latest_subscription->has_status(array('expired', 'pending-cancel'))) {
        return 'renew';
    }
    
    // 3. If none of the above, the user has subscriptions but none are active.
    return 'unsubscribed';
}

?>