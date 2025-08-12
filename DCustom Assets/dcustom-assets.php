<?php
/**
 * Plugin Name:       DCustom Assets
 * Description:       Manage custom functions and assets with advanced controls. A creation by a passionate PHP developer.
 * Version:           2.2 
 * Author:            Danniesoft
 * Author URI:        https://danniesoft.com
 * License:           GPL2
 * Text Domain:       dcustom-assets
 */

defined('ABSPATH') || exit;

class DCustomAssets {
    private $snippets = [];
    private $capability = 'manage_options';
    private $settings_page_slug = 'dcustom-assets';
    private $settings_option_name = 'dcustom_assets_settings';
    private $includes_dir;
    private $plugin_url;
    private $plugin_path;

    public function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);
        $this->includes_dir = $this->plugin_path . 'includes/';

        $this->initialize_snippets();

        // Core hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        add_action('admin_menu', [$this, 'setup_admin_menu']);
        add_action('admin_init', [$this, 'handle_messages']);
        add_action('init', [$this, 'load_active_snippets']);
        
        // Custom form handlers
        add_action('admin_post_dcustom_assets_save_settings', [$this, 'handle_save_settings']);
        add_action('admin_post_dcustom_assets_delete_snippet', [$this, 'handle_delete_snippet']);

        // Additional features
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_plugin_action_links']);
    }

    public function activate() {
        $options = get_option($this->settings_option_name, []);
        foreach ($this->snippets as $snippet_id => $snippet) {
            if (!isset($options[$snippet_id])) {
                $options[$snippet_id] = $snippet['default_active'] ?? false;
            }
        }
        update_option($this->settings_option_name, $options);
    }

    private function initialize_snippets() {
        $all_snippets = [];
        $saved_options = get_option($this->settings_option_name, []);

        // Load File-Based Snippets only
        $files = glob($this->includes_dir . '*.{php,js,css,html}', GLOB_BRACE);
        if ($files) {
            foreach ($files as $file_path) {
                $file_name = basename($file_path);
                $snippet_id = 'file_' . sanitize_key(str_replace('.', '-', $file_name));
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                
                $file_headers = get_file_data($file_path, [
                    'name' => 'Name',
                    'description' => 'Description',
                    'location' => 'Location',
                    'default_active' => 'Default Active'
                ]);

                $name = !empty($file_headers['name']) ? $file_headers['name'] : ucwords(str_replace(['-', '_', '.' . $file_extension], [' ', ' ', ''], $file_name));
                $description = !empty($file_headers['description']) ? $file_headers['description'] : "File-based asset: {$file_name}";
                $default_active = strtolower($file_headers['default_active']) === 'true';
                $location = strtolower($file_headers['location'] ?? 'footer');

                switch ($file_extension) {
                    case 'php': $type = 'php'; $location = 'N/A'; break;
                    case 'js': $type = 'js'; $location = ($location === 'head') ? 'head' : 'footer'; break;
                    case 'css': $type = 'css'; $location = 'head'; break;
                    case 'html': $type = 'html'; $location = ($location === 'head') ? 'head' : 'footer'; break;
                    default: continue 2;
                }

                $all_snippets[$snippet_id] = [
                    'id' => $snippet_id,
                    'source' => 'file',
                    'name' => $name,
                    'type' => $type,
                    'file' => $file_name,
                    'location' => $location,
                    'description' => $description,
                    'default_active' => $default_active,
                    'active' => isset($saved_options[$snippet_id]) ? (bool)$saved_options[$snippet_id] : $default_active,
                ];
            }
        }

        uasort($all_snippets, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
        $this->snippets = $all_snippets;
    }

    public function setup_admin_menu() {
        add_menu_page('DCustom Assets', 'DCustom Assets', $this->capability, $this->settings_page_slug, [$this, 'render_admin_page'], 'dashicons-editor-code', 2);
        add_submenu_page($this->settings_page_slug, 'Manage Assets', 'Manage Assets', $this->capability, $this->settings_page_slug, [$this, 'render_admin_page']);
        add_submenu_page($this->settings_page_slug, 'About Developer', 'About Developer', $this->capability, 'dcustom-assets-about', [$this, 'render_about_page']);
        add_submenu_page($this->settings_page_slug, 'Support & Donate', 'Donate', $this->capability, 'dcustom-assets-donate', [$this, 'render_donate_page']);
    }

    public function handle_messages() {
        if (!isset($_GET['page']) || strpos($_GET['page'], 'dcustom-assets') === false) return;
        if (isset($_GET['message'])) {
            $message_code = sanitize_key($_GET['message']);
            $type = 'updated'; // Default to success
            $message = '';

            switch ($message_code) {
                case 'settings_saved': $message = __('Settings saved successfully!', 'dcustom-assets'); break;
                case 'snippet_deleted': $message = __('File snippet deleted successfully.', 'dcustom-assets'); break;
                case 'delete_error': $message = __('Error: The file could not be deleted. Check permissions.', 'dcustom-assets'); $type = 'error'; break;
                case 'invalid_action': $message = __('Invalid action.', 'dcustom-assets'); $type = 'error'; break;
            }
            if ($message) {
                add_settings_error('dcustom_assets_messages', $message_code, $message, $type);
            }
        }
    }

    public function render_admin_page() {
        if (!current_user_can($this->capability)) wp_die('Access Denied.');
        $this->initialize_snippets(); // Refresh snippets
        ?>
        <div class="wrap dcustom-assets-wrapper">
            <h1><span class="dashicons dashicons-editor-code"></span> DCustom Assets</h1>
            <?php settings_errors('dcustom_assets_messages'); ?>
            <div class="dcustom-assets-container">
                <div class="dcustom-assets-main">
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="dcustom_assets_save_settings">
                        <?php wp_nonce_field('dcustom_assets_save_settings_nonce', 'dcustom_assets_nonce'); ?>
                        
                        <div class="dcustom-assets-card">
                            <table class="wp-list-table widefat fixed striped dcustom-assets-table">
                                <thead>
                                <tr>
                                    <th width="25%">Asset Name</th>
                                    <th width="45%">Description</th>
                                    <th width="8%">Type</th>
                                    <th width="8%">Location</th>
                                    <th width="12%">Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($this->snippets)): ?>
                                    <tr><td colspan="5">No assets found. Upload files to the `includes` folder.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($this->snippets as $id => $snippet): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($snippet['name']); ?></strong>
                                            <div class="row-actions">
                                                 <span class="file-name">File: <code><?php echo esc_html($snippet['file']); ?></code></span>
                                                 <span class="delete"> | <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=dcustom_assets_delete_snippet&snippet_id=' . $id), 'dcustom_assets_delete_nonce_' . $id)); ?>" class="submitdelete">Delete</a></span>
                                            </div>
                                        </td>
                                        <td><?php echo esc_html($snippet['description']); ?></td>
                                        <td><span class="asset-type type-<?php echo esc_attr($snippet['type']); ?>"><?php echo esc_html(strtoupper($snippet['type'])); ?></span></td>
                                        <td><?php echo esc_html(ucfirst($snippet['location'])); ?></td>
                                        <td>
                                            <label class="dcustom-switch">
                                                <input type="checkbox" name="<?php echo esc_attr($this->settings_option_name); ?>[<?php echo esc_attr($id); ?>]" value="1" <?php checked($snippet['active']); ?> data-asset-name="<?php echo esc_attr($snippet['name']); ?>" data-asset-type="<?php echo esc_attr($snippet['type']); ?>">
                                                <span class="slider round"></span>
                                            </label>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                            <div class="dcustom-assets-footer">
                                <?php submit_button('Save Changes', 'primary', 'submit-settings', false); ?>
                                <span class="description">Toggle switches and click "Save Changes" to apply.</span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="dcustom-assets-sidebar">
                    <div class="dcustom-assets-card">
                        <h3>Plugin Info</h3>
                        <ul class="dcustom-system-info">
                            <li><strong>Total Assets:</strong> <?php echo count($this->snippets); ?></li>
                            <li><strong>Active Assets:</strong> <?php echo count(array_filter($this->snippets, fn($s) => $s['active'])); ?></li>
                            <li><strong>Plugin Version:</strong> <?php echo esc_html($this->get_plugin_version()); ?></li>
                            <li><strong>PHP Version:</strong> <?php echo esc_html(phpversion()); ?></li>
                        </ul>
                    </div>
                     <div class="dcustom-assets-card">
                        <h3>Quick Links</h3>
                        <ul class="dcustom-quick-actions">
                            <li><a href="<?php echo esc_url(admin_url('admin.php?page=dcustom-assets-about')); ?>"><span class="dashicons dashicons-info-outline"></span> About The Developer</a></li>
                            <li><a href="mailto:danniesoft@gmail.com?subject=DCustom%20Assets%20Support"><span class="dashicons dashicons-email"></span> Get Support</a></li>
                            <li><a href="<?php echo esc_url('https://danniesoft.com/donate'); ?>" target="_blank"><span class="dashicons dashicons-heart"></span> Donate to Danniesoft</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_about_page() {
        if (!current_user_can($this->capability)) wp_die('Access Denied.');
        // Use the external image URL provided by the user
        $developer_image_url = 'https://danniesoft.com/wp-content/uploads/2023/11/danniesoft-profile-picture.jpg';
        ?>
        <div class="wrap about-wrap dcustom-assets-about-page">
            <h1>About the Developer</h1>
            <p class="about-text">This plugin was crafted with passion by Danniesoft.</p>
            <div class="dcustom-assets-card">
                <div class="developer-profile">
                    <div class="developer-avatar">
                        <img src="<?php echo esc_url($developer_image_url); ?>" alt="Danniesoft Avatar">
                    </div>
                    <div class="developer-info">
                        <h2>Hi, I'm Danniesoft!</h2>
                        <p>I am a young, dedicated PHP and WordPress developer from Abuja, Nigeria, with a passion for building clean, efficient, and user-friendly web solutions. My journey into the world of code started with a deep curiosity for how websites work, and it has since evolved into a professional pursuit of excellence.</p>
                        <p>With a strong foundation in PHP, JavaScript, HTML, and CSS, I specialize in creating custom WordPress plugins and themes that solve real-world problems. This very plugin, <strong>DCustom Assets</strong>, is a testament to my commitment to providing powerful tools that are both flexible for developers and easy for users to manage.</p>
                        <h3>Looking for a Developer?</h3>
                        <p>I am currently available for freelance projects. Whether you need a custom plugin, a unique theme, or modifications to your existing website, I would be thrilled to bring your vision to life. Let's build something amazing together!</p>
                        <div class="developer-contact">
                            <a href="mailto:danniesoft@gmail.com?subject=Project Inquiry from DCustom Assets User" class="button button-primary">Email Me</a>
                            <a href="https://danniesoft.com" target="_blank" class="button button-secondary">Visit My Website</a>
                            <a href="tel:+2347065254824" class="button button-secondary">Call Me</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_donate_page() {
        if (!current_user_can($this->capability)) wp_die('Access Denied.');
        ?>
        <div class="wrap">
            <h1>Support DCustom Assets</h1>
            <div class="dcustom-assets-card" style="text-align: center; max-width: 600px; margin: 20px auto;">
                <h2>Help Keep This Plugin Awesome!</h2>
                <p>If you find DCustom Assets useful, please consider making a donation to support its continued development, maintenance, and user support.</p>
                <p>Your generosity is greatly appreciated and helps me dedicate more time to creating valuable tools for the WordPress community. Thank you!</p>
                <p><a href="<?php echo esc_url('https://danniesoft.com/donate'); ?>" class="button button-primary button-hero" target="_blank"><span class="dashicons dashicons-heart" style="vertical-align: middle;"></span> Donate Now</a></p>
                <p class="description">You will be redirected to my website to complete your donation securely.</p>
            </div>
        </div>
        <?php
    }
    
    public function handle_save_settings() {
        // Check nonce for security
        if (!isset($_POST['dcustom_assets_nonce']) || !wp_verify_nonce($_POST['dcustom_assets_nonce'], 'dcustom_assets_save_settings_nonce')) {
            wp_die('Nonce verification failed. Action aborted for security reasons.');
        }
        // Check user capabilities
        if (!current_user_can($this->capability)) {
            wp_die('Access Denied. You do not have sufficient permissions to perform this action.');
        }

        $new_options = [];
        // Ensure $_POST[$this->settings_option_name] is an array before using it
        $submitted_options = isset($_POST[$this->settings_option_name]) && is_array($_POST[$this->settings_option_name]) ? $_POST[$this->settings_option_name] : [];

        // Iterate through all known snippets to ensure all are processed
        // This prevents orphaned settings if a file is removed manually
        $this->initialize_snippets(); // Ensure $this->snippets is up-to-date

        foreach ($this->snippets as $snippet_id => $snippet_data) {
            // A snippet is active if its ID is present as a key in the submitted options for that group
            $new_options[$snippet_id] = isset($submitted_options[$snippet_id]);
        }
        
        update_option($this->settings_option_name, $new_options);

        // Redirect back to the settings page with a success message
        wp_safe_redirect(admin_url('admin.php?page=' . $this->settings_page_slug . '&message=settings_saved'));
        exit;
    }

    public function handle_delete_snippet() {
        $snippet_id = sanitize_key($_GET['snippet_id'] ?? '');

        // Verify nonce for security
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'dcustom_assets_delete_nonce_' . $snippet_id)) {
             wp_die('Nonce verification failed. Action aborted for security reasons.');
        }
        // Check user capabilities
        if (!current_user_can($this->capability)) {
            wp_die('Access Denied. You do not have sufficient permissions to perform this action.');
        }

        $message = 'snippet_deleted'; // Default success message
        
        // Ensure $this->snippets is initialized before accessing it
        $this->initialize_snippets(); 

        // Ensure it's a file snippet we're trying to delete and it exists in our known snippets
        if (strpos($snippet_id, 'file_') === 0 && isset($this->snippets[$snippet_id])) {
            $file_path = $this->includes_dir . $this->snippets[$snippet_id]['file'];
            
            // Check if file exists and its directory is writable
            if (file_exists($file_path) && is_writable(dirname($file_path))) {
                if (is_writable($file_path)) { // Also check if the file itself is writable
                    unlink($file_path); // Delete the file
                    
                    // Also remove it from the settings option to deactivate it
                    $options = get_option($this->settings_option_name, []);
                    unset($options[$snippet_id]);
                    update_option($this->settings_option_name, $options);
                } else {
                     $message = 'delete_error'; // File not writable
                }
            } else {
                $message = 'delete_error'; // File doesn't exist or directory not writable
            }
        } else {
            $message = 'invalid_action'; // Not a file snippet or snippet ID not found
        }

        // Redirect back to the settings page
        wp_safe_redirect(admin_url('admin.php?page=' . $this->settings_page_slug . '&message=' . $message));
        exit;
    }

    public function enqueue_admin_assets($hook) {
        // Only load assets on our plugin's pages
        if (strpos($hook, 'dcustom-assets') === false) return;

        // Enqueue admin stylesheet
        wp_enqueue_style(
            'dcustom-assets-admin', 
            $this->plugin_url . 'assets/css/admin.css', 
            [], 
            filemtime($this->plugin_path . 'assets/css/admin.css') // Versioning based on file modification time
        );
        // Enqueue admin JavaScript
        wp_enqueue_script(
            'dcustom-assets-admin', 
            $this->plugin_url . 'assets/js/admin.js', 
            ['jquery'], // Dependency
            filemtime($this->plugin_path . 'assets/js/admin.js'), // Versioning
            true // Load in footer
        );
        
        // Localize script with translatable strings for JavaScript
        wp_localize_script('dcustom-assets-admin', 'DCustomAssets', [
            'confirm_deactivate_message' => __("Are you sure you want to disable \"%s\"?", 'dcustom-assets'),
            'confirm_php_deactivate_message' => __("WARNING: Deactivating a PHP snippet (\"%s\") can affect site functionality or cause fatal errors. Are you absolutely sure?", 'dcustom-assets'),
            'confirm_delete_message' => __("Are you sure you want to permanently delete this file? This cannot be undone.", 'dcustom-assets'),
        ]);
    }

    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=' . $this->settings_page_slug)) . '">' . __('Manage Assets', 'dcustom-assets') . '</a>';
        $donate_link = '<a href="' . esc_url('https://danniesoft.com/donate') . '" target="_blank" style="color:#dc3232;font-weight:bold;">' . __('Donate', 'dcustom-assets') . '</a>';
        // Add links to the beginning of the array
        array_unshift($links, $settings_link, $donate_link);
        return $links;
    }

    public function load_active_snippets() {
        // Ensure snippets are initialized before iterating
        $this->initialize_snippets();
        foreach ($this->snippets as $id => $snippet) {
            if (!empty($snippet['active'])) {
                $this->load_snippet($id, $snippet);
            }
        }
    }
    
    private function load_snippet($id, $snippet) {
        // This function will only process file-based snippets now
        $handle = 'dcustom-asset-' . sanitize_key($id); // Sanitize ID for handle
        $file_path = $this->includes_dir . $snippet['file'];

        // Ensure file exists before trying to load it
        if (!file_exists($file_path)) return;

        $version = filemtime($file_path); // Use file modification time for cache busting
        $content = trim(file_get_contents($file_path)); // Get file content

        switch ($snippet['type']) {
            case 'php':
                // For PHP, check if it already has an opening tag.
                // If not, eval() might be used, but include_once is generally safer if the file is a standard PHP file.
                // Using include_once directly is preferable for standalone PHP files.
                // The eval approach is for code snippets that are not full files.
                // Since these are files, include_once is better.
                // A try-catch block can help manage errors from included PHP files.
                try {
                    // Check if the content starts with <?php, if not, it might be a raw code snippet.
                    // However, for file-based snippets, they should ideally be complete PHP files.
                    if (strpos($content, '<?php') === 0 || strpos($content, '<?') === 0 ) {
                         include_once $file_path;
                    } else {
                        // This case is less ideal for file-based PHP, but handles raw code if present
                        eval('?>' . $content); 
                    }
                } catch (Throwable $e) {
                    // Log error or handle it gracefully
                    error_log("Error loading PHP snippet {$snippet['file']}: " . $e->getMessage());
                }
                break;
            case 'js':
                $in_footer = ($snippet['location'] === 'footer');
                wp_enqueue_script($handle, $this->plugin_url . 'includes/' . $snippet['file'], [], $version, $in_footer);
                // WordPress handles JS files correctly, no need to check for <script> tags here.
                break;
            case 'css':
                 wp_enqueue_style($handle, $this->plugin_url . 'includes/' . $snippet['file'], [], $version);
                 // WordPress handles CSS files correctly.
                break;
            case 'html':
                // For HTML, add action to wp_head or wp_footer
                $hook = ($snippet['location'] === 'footer') ? 'wp_footer' : 'wp_head';
                // Use a higher priority (e.g., 999) to ensure it's loaded late if needed
                add_action($hook, function() use ($content) {
                    echo $content;
                }, 999);
                break;
        }
    }
    
    private function get_plugin_version() {
        // Ensure get_plugin_data function is available
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $plugin_data = get_plugin_data(__FILE__);
        return $plugin_data['Version'] ?? 'N/A'; // Fallback if version not found
    }
}

// Instantiate the plugin class
new DCustomAssets();
