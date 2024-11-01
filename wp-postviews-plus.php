<?php
/*
Plugin Name: WP-PostViews Plus
Plugin URI: https://richer.tw/wp-postviews-plus
Description: Enables You To Display How Many Times A Post Had Been Viewed By User Or Bot.
Version: 2.1.2
Author: Richer Yang
Author URI: https://richer.tw/
Text Domain: wp-postviews-plus
Domain Path: /languages
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.txt
*/

function_exists('plugin_dir_url') or exit('No direct script access allowed');

define('WP_PVP_VERSION', '2.1.2');
define('WP_PVP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_PVP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_PVP_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once(WP_PVP_PLUGIN_DIR . 'class.wp-pvp.php');
require_once(WP_PVP_PLUGIN_DIR . 'class.wp-pvp.widget.php');

register_activation_hook(__FILE__, ['WP_PVP', 'plugin_activation']);
register_deactivation_hook(__FILE__, ['WP_PVP', 'plugin_deactivation']);

add_action('init', ['WP_PVP', 'init']);
add_action('widgets_init', ['WP_PVP_widget', 'init']);
