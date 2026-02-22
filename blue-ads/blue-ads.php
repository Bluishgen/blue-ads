<?php
/*
Plugin Name: Blue Ads
Description: A plugin to manage image slides (adding links and conntrolling the margins).
Version: 1.0.7
Author: Divine Negedu
Update URI: https://raw.githubusercontent.com/Bluishgen/blue-ads/refs/heads/main/update.json
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add a "Check for Updates" button to the plugin action links
function blue_ads_plugin_action_links($links) {
    // Add the "Check for Updates" link
    $check_updates_link = '<a href="' . esc_url(admin_url('plugins.php?blue_ads_check_updates=1')) . '">Check for Updates</a>';
    array_unshift($links, $check_updates_link);

    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'blue_ads_plugin_action_links');

// Force WordPress to check for updates when the "Check for Updates" button is clicked
function blue_ads_check_for_updates_manual() {
    if (isset($_GET['blue_ads_check_updates'])) {
        // Clear the update transient to force a fresh check
        delete_site_transient('update_plugins');

        // Redirect back to the plugins page
        wp_redirect(admin_url('plugins.php'));
        exit;
    }
}
add_action('admin_init', 'blue_ads_check_for_updates_manual');

// Display a success message after checking for updates
function blue_ads_admin_notices() {
    if (isset($_GET['blue_ads_check_updates'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Updates checked successfully!</p></div>';
    }
}
add_action('admin_notices', 'blue_ads_admin_notices');



function blue_ads_check_for_updates($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    // Get the latest version information
    $response = wp_remote_get('https://raw.githubusercontent.com/Bluishgen/blue-ads/refs/heads/main/update.json');

    if (is_wp_error($response)) {
        return $transient;
    }

    $plugin_info = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($plugin_info['version'])) {
        return $transient;
    }

    // Check if the plugin needs an update
    if (version_compare($transient->checked['blue-ads/blue-ads.php'], $plugin_info['version'], '<')) {
        $transient->response['blue-ads/blue-ads.php'] = (object) [
            'slug' => 'blue-ads',
            'new_version' => $plugin_info['version'],
            'package' => $plugin_info['download_url'],
            'tested' => $plugin_info['tested'],
            'requires' => $plugin_info['requires'],
            'requires_php' => $plugin_info['requires_php']
        ];
    }

    return $transient;
}
add_filter('pre_set_site_transient_update_plugins', 'blue_ads_check_for_updates');














// Remote server URL for validation
define('BLUE_ADS_API_URL', 'https://bluishgen.com.ng/validate-key.php');

// ======================================================
// Activation System
// ======================================================
function blue_ads_is_activated() {
    $site_url = get_site_url();

    // Log the site URL being sent
    error_log('Site URL: ' . $site_url);

    // Send request to remote server
    $response = wp_remote_post(BLUE_ADS_API_URL, [
        'body' => [
            'site_url' => $site_url,
            'action' => 'validate'
        ]
    ]);

    if (is_wp_error($response)) {
        error_log('Server connection error: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    error_log('Server response: ' . $body); // Log the server's response

    $data = json_decode($body, true);
    return isset($data['valid']) && $data['valid'] === true;
}

// ======================================================
// Admin Menu
// ======================================================
function blue_ads_menu() {
    add_menu_page(
        'Blue Ads', 
        'Blue Ads', 
        'manage_options', 
        'blue-ads', 
        'blue_ads_activation_page', 
        'dashicons-images-alt2', 
        100
    );

    // Always show the settings page, but restrict access if not activated
    add_submenu_page(
        'blue-ads', 
        'Slider Settings', 
        'Slider Settings', 
        'manage_options', 
        'blue-ads-settings', 
        'blue_ads_settings_page'
    );
}
add_action('admin_menu', 'blue_ads_menu');

// ======================================================
// Activation Page
// ======================================================
function blue_ads_activation_page() {
    if (blue_ads_is_activated()) {
        echo '<div class="notice notice-success"><p>Plugin is activated for this site!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>This site is not authorized to use the plugin.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Blue Ads Activation</h1>
        <p>This plugin is automatically activated for authorized sites.</p>
        <br>
        <br>
        <p>If your site isnt activated and you have paid for the plugin, kindly contact admin (click below)</p>
        <button onclick="window.location.href='https://wa.me/9043040188';" class="button button-primary">Contact Plugin Admin</button>
        
        <h2>FRONTEND CODE</h2>
        <p>Click below to download the code you can use to echo the slider to any page of your choice. If you can't do this, seek help from the plugin admin.</p>
        <a href="<?php echo plugins_url('frontpage-code.txt', __FILE__); ?>" download>📥 Download Code</a>


    </div>
    <?php
}

// ======================================================
// Slider Settings Page
// ======================================================
function blue_ads_settings_page() {
    // Check activation status
    if (!blue_ads_is_activated()) {
        echo '<div class="notice notice-error"><p>This site is not authorized to use the plugin.</p></div>';
        return; // Stop further execution
    }

    if (isset($_POST['blue_ads_options'])) {
        // Validate and sanitize input
        $options = [
            'image_1' => esc_url_raw($_POST['blue_ads_options']['image_1']),
            'image_2' => esc_url_raw($_POST['blue_ads_options']['image_2']),
            'image_3' => esc_url_raw($_POST['blue_ads_options']['image_3']),
            'link_1' => esc_url_raw($_POST['blue_ads_options']['link_1']),
            'link_2' => esc_url_raw($_POST['blue_ads_options']['link_2']),
            'link_3' => esc_url_raw($_POST['blue_ads_options']['link_3']),
            'margin_top' => absint($_POST['blue_ads_options']['margin_top']),
            'margin_bottom' => absint($_POST['blue_ads_options']['margin_bottom']),
            'margin_left' => absint($_POST['blue_ads_options']['margin_left']),
            'margin_right' => absint($_POST['blue_ads_options']['margin_right']),
            'slider_enabled' => isset($_POST['blue_ads_options']['slider_enabled']) ? 1 : 0
        ];
        update_option('blue_ads_options', $options);
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }

    $options = get_option('blue_ads_options', [
        'image_1' => '',
        'image_2' => '',
        'image_3' => '',
        'link_1' => '',
        'link_2' => '',
        'link_3' => '',
        'margin_top' => 0,
        'margin_bottom' => 0,
        'margin_left' => 0,
        'margin_right' => 0,
        'slider_enabled' => 1
    ]);
    ?>
    <div class="wrap">
        <h1>Slider Settings</h1>
        <form method="post">
            <?php for ($i = 1; $i <= 3; $i++) : ?>
                <p>
                    <label for="image_<?php echo $i; ?>">Slide <?php echo $i; ?> Image URL:</label>
                    <input type="text" name="blue_ads_options[image_<?php echo $i; ?>]" 
                           value="<?php echo esc_url($options['image_' . $i]); ?>" class="regular-text">
                </p>
                <p>
                    <label for="link_<?php echo $i; ?>">Slide <?php echo $i; ?> Link:</label>
                    <input type="text" name="blue_ads_options[link_<?php echo $i; ?>]" 
                           value="<?php echo esc_url($options['link_' . $i]); ?>" class="regular-text">
                </p>
            <?php endfor; ?>

            <h2>Margins</h2>
            <div class="margin-controls">
                <p>
                    <label>Top:</label>
                    <input type="number" name="blue_ads_options[margin_top]" 
                           value="<?php echo esc_attr($options['margin_top']); ?>">px
                </p>
                <p>
                    <label>Bottom:</label>
                    <input type="number" name="blue_ads_options[margin_bottom]" 
                           value="<?php echo esc_attr($options['margin_bottom']); ?>">px
                </p>
                <p>
                    <label>Left:</label>
                    <input type="number" name="blue_ads_options[margin_left]" 
                           value="<?php echo esc_attr($options['margin_left']); ?>">px
                </p>
                <p>
                    <label>Right:</label>
                    <input type="number" name="blue_ads_options[margin_right]" 
                           value="<?php echo esc_attr($options['margin_right']); ?>">px
                </p>
            </div>

            <h2>Visibility</h2>
            <p>
                <label>
                    <input type="checkbox" name="blue_ads_options[slider_enabled]" 
                           value="1" <?php checked($options['slider_enabled'], 1); ?>>
                    Enable Slider on Homepage
                </label>
            </p>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// ======================================================
// Frontend Display
// ======================================================
function blue_ads_display_slider() {
    if (!blue_ads_is_activated()) return;

    $options = get_option('blue_ads_options', [
        'image_1' => '',
        'image_2' => '',
        'image_3' => '',
        'link_1' => '',
        'link_2' => '',
        'link_3' => '',
        'margin_top' => 0,
        'margin_bottom' => 0,
        'margin_left' => 0,
        'margin_right' => 0,
        'slider_enabled' => 1
    ]);

    if (!$options['slider_enabled']) return;

    $margins = "{$options['margin_top']}px {$options['margin_right']}px {$options['margin_bottom']}px {$options['margin_left']}px";
    ?>
    <style>
        #blue-ads-slider .carousel-inner {
            overflow: hidden;
        }
        #blue-ads-slider .carousel-item {
            transition: transform 0.2s ease-in-out;
        }
        #blue-ads-slider .carousel-item img {
            width: 100%;
            display: block;
        }
        #blue-ads-slider .carousel-control-prev,
        #blue-ads-slider .carousel-control-next {
            background-color: rgba(0, 0, 0, 0.5);
            width: 40px;
            height: 40px;
            top: 50%;
            transform: translateY(-50%);
            border-radius: 50%;
        }
        #blue-ads-slider .carousel-control-prev-icon,
        #blue-ads-slider .carousel-control-next-icon {
            filter: invert(1);
        }
    </style>
    <div id="blue-ads-slider" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000" style="margin: <?php echo esc_attr($margins); ?>;">
        <div class="carousel-inner">
            <?php for ($i = 1; $i <= 3; $i++) : ?>
                <?php if (!empty($options['image_' . $i])) : ?>
                    <div class="carousel-item <?php echo $i === 1 ? 'active' : ''; ?>">
                        <?php if (!empty($options['link_' . $i])) : ?>
                            <a href="<?php echo esc_url($options['link_' . $i]); ?>" target="_blank">
                                <img src="<?php echo esc_url($options['image_' . $i]); ?>" class="d-block w-100" alt="Slide <?php echo $i; ?>">
                            </a>
                        <?php else : ?>
                            <img src="<?php echo esc_url($options['image_' . $i]); ?>" class="d-block w-100" alt="Slide <?php echo $i; ?>">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <a class="carousel-control-prev" href="#blue-ads-slider" role="button" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        </a>
        <a class="carousel-control-next" href="#blue-ads-slider" role="button" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
        </a>
    </div>
    <?php
}
add_shortcode('blue_ads_slider', 'blue_ads_display_slider');

// ======================================================
// Enqueue Assets
// ======================================================
function blue_ads_enqueue_assets() {
    // Bootstrap (if not already in theme)
    if (!wp_style_is('bootstrap')) {
        wp_enqueue_style(
            'bootstrap-css', 
            'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css'
        );
        wp_enqueue_script(
            'bootstrap-js', 
            'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js',
            ['jquery'], 
            '5.1.3', 
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'blue_ads_enqueue_assets');