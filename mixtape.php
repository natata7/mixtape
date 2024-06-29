<?php
/**
 * Plugin Name: Mixtape
 * Description: Mixtape allows visitors to effortlessly notify site staff about found spelling errors.
 * Version: 1.2
 * License: MIT License
 * License URI: http://opensource.org/licenses/MIT
 * Text Domain: mixtape
 * Domain Path: /languages
 *
 * @package mixtape
 */

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MIXTAPE__VERSION', '1.0.0' );
define( 'MIXTAPE__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MIXTAPE__PLUGIN_FILE', __FILE__ );
define( 'MIXTAPE__PLUGIN_FOLDER', basename( MIXTAPE__PLUGIN_DIR ) );
define( 'MIXTAPE__PLUGIN_URL', WP_PLUGIN_URL . '/' . MIXTAPE__PLUGIN_FOLDER );

require_once( MIXTAPE__PLUGIN_DIR . 'src/class-mixtape-abstract.php' );
require_once( MIXTAPE__PLUGIN_DIR . 'src/class-mixtape-admin.php' );
require_once( MIXTAPE__PLUGIN_DIR . 'src/class-mixtape-ajax.php' );

register_activation_hook( __FILE__, 'Mixtape_Admin::activation' );
register_deactivation_hook( __FILE__, 'Mixtape_Admin::deactivate_addons' );

add_action( 'plugins_loaded', 'mixtape_init' );
function mixtape_init() {
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		// load ajax-related class
		Mixtape_Ajax::maybe_instantiate();
	} elseif ( is_admin() ) {
		// conditionally load admin-related class
		Mixtape_Admin::get_instance();
	} else {
		// or frontend class
		require_once( MIXTAPE__PLUGIN_DIR . 'src/class-mixtape-front.php' );
		Mixtape::get_instance();
	}
}
