<?php
/*
Plugin Name: Mistape
Description: Mistape allows visitors to effortlessly notify site staff about found spelling errors.
Version: 1.0.0
License: MIT License
License URI: http://opensource.org/licenses/MIT
Text Domain: mistape
Domain Path: /languages
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MISTAPE__VERSION', '1.0.0' );
define( 'MISTAPE__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MISTAPE__PLUGIN_FILE', __FILE__ );
define( 'MISTAPE__PLUGIN_FOLDER', basename( MISTAPE__PLUGIN_DIR ) );
define( 'MISTAPE__PLUGIN_URL', WP_PLUGIN_URL . '/' . MISTAPE__PLUGIN_FOLDER );

require_once( MISTAPE__PLUGIN_DIR . 'src/class-mistape-abstract.php' );
require_once( MISTAPE__PLUGIN_DIR . 'src/class-mistape-admin.php' );
require_once( MISTAPE__PLUGIN_DIR . 'src/class-mistape-ajax.php' );

register_activation_hook( __FILE__, 'Mistape_Admin::activation' );
register_deactivation_hook( __FILE__, 'Mistape_Admin::deactivate_addons' );

add_action( 'plugins_loaded', 'mistape_init' );
function mistape_init() {
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		// load ajax-related class
		Mistape_Ajax::maybe_instantiate();
	} elseif ( is_admin() ) {
		// conditionally load admin-related class
		Mistape_Admin::get_instance();
	} else {
		// or frontend class
		require_once( MISTAPE__PLUGIN_DIR . 'src/class-mistape-front.php' );
		Mistape::get_instance();
	}
}