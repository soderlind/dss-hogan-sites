<?php
/**
 * Plugin Name: DSS Hogan Module: Sites
 * Plugin URI: https://github.com/soderlind//dss-hogan-sites
 * GitHub Plugin URI: https://github.com/soderlind//dss-hogan-sites
 * Description: List network sites. Require Network Portfolio plugin installed, activated and configured.
 * Version: 1.0.0
 * Author: Per Soderlind
 * Author URI: https://soderlind.no
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * Text Domain: dss-hogan-sites
 * Domain Path: /languages/
 *
 * @package Hogan
 * @author Dekode
 */

declare( strict_types = 1 );
namespace DSS\Hogan\Sites;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\hogan_sites_load_textdomain' );
add_action( 'hogan/include_modules', __NAMESPACE__ . '\\hogan_sites_register_module', 10, 1 );

add_filter( 'hogan/module/outer_wrapper_classes', __NAMESPACE__ . '\\on_hogan_outer_wrapper_classes', 10, 2 );
add_filter( 'hogan/module/inner_wrapper_classes', __NAMESPACE__ . '\\on_hogan_inner_wrapper_classes', 10, 2 );
add_filter( 'hogan/module/sites/heading/enabled', '__return_true' );

/**
 * Register module text domain
 *
 * @return void
 */
function hogan_sites_load_textdomain() {
	\load_plugin_textdomain( 'dss-hogan-sites', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Register module in Hogan
 *
 * @param \Dekode\Hogan\Core $core Hogan Core instance.
 * @return void
 */
function hogan_sites_register_module( \Dekode\Hogan\Core $core ) {
	// if ( ! class_exists( '\NetworkPortfolio\Shortcodes\Portfolio' )  ) {
	// 	add_action(
	// 		'admin_notices', function() {
	// 			echo '<div class="error notice"><p>Hogan Module, Sites: The Network Portfolio plugin must be installed, activated and configured</p></div>';
	// 		}
	// 	);
	// 	return;
	// }
	require_once 'class-sites.php';
	$core->register_module( new \DSS\Hogan\Sites() );
}


function on_hogan_outer_wrapper_classes( $classes, $module ) {

	if ( 'sites' == $module->name ) {
		array_push( $classes, 'module-bg', 'container-full-width', 'hogan-module-simple_posts' );
	}
	return $classes;
}

function on_hogan_inner_wrapper_classes( $classes, $module ) {

	if ( 'sites' == $module->name ) {
		array_push( $classes, 'container' );
	}
	return $classes;
}
