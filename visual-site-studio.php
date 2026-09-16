<?php
/**
 * Plugin Name: Visual Site Studio
 * Plugin URI: https://syedfaseeh.com/visual-site-studio
 * Description: Commercial visual editor for WordPress — inspect any element, restyle it without touching theme files, run a white-label client dashboard, and drop in scheduled dynamic content.
 * Version: 2.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Syed Faseeh Ul Hassan
 * Author URI: https://syedfaseeh.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: visual-site-studio
 *
 * @package VisualSiteStudio
 */

if (!defined('ABSPATH')) {
    exit;
}

define('VSS_VERSION', '2.0.0');
define('VSS_FILE', __FILE__);
define('VSS_DIR', plugin_dir_path(__FILE__));
define('VSS_URL', plugin_dir_url(__FILE__));

require_once VSS_DIR . 'includes/class-vss-sanitizer.php';
require_once VSS_DIR . 'includes/class-vss-store.php';
require_once VSS_DIR . 'includes/class-vss-rest.php';
require_once VSS_DIR . 'includes/class-vss-assets.php';
require_once VSS_DIR . 'includes/class-vss-admin.php';
require_once VSS_DIR . 'includes/class-vss-dashboard.php';
require_once VSS_DIR . 'includes/class-vss-dynamic-content.php';
require_once VSS_DIR . 'includes/class-vss-plugin.php';

register_activation_hook(__FILE__, array('VSS_Plugin', 'activate'));

add_action('plugins_loaded', static function () {
    VSS_Plugin::instance()->boot();
});
