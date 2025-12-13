<?php
/*
Plugin Name: Quick Email Capture
Plugin URI: https://github.com/aetta/quick-email-capture
Description: Simple, fast and lightweight email capture. No bloat.
Version: 1.0.1
Requires at least: 6.0
Requires PHP: 7.4
Author: aetta
Author URI: https://github.com/aetta
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: quick-email-capture
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('QEC_VERSION', '1.0.1');
define('QEC_PLUGIN_FILE', __FILE__);
define('QEC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QEC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QEC_TEXTDOMAIN', 'quick-email-capture');

require_once QEC_PLUGIN_DIR . 'includes/class-qec-plugin.php';

register_activation_hook(__FILE__, ['QEC_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['QEC_Plugin', 'deactivate']);

add_action('plugins_loaded', function () {
    QEC_Plugin::instance();
});
