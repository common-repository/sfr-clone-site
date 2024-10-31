<?php
/*
Plugin Name: SFR Clone Site
Plugin URI: http://sfroy.com/
Description: Clone site for WordPress 3.0 and above.
Author: Mathieu Hallé
Version: 1.1.1
Author URI: http://plugin.sfroy.net
*/


class sfr_clone_site {

	/* Init plugin */
	function sfr_clone_site() {
	
	

		
		
/* ------------- ADMIN INTERFACE INIT ------------- */		
	
		
		// Add admin script and css - not needed for now
		// add_action( 'admin_print_scripts', array(&$this, 'sfr_clone_site_admin_scripts') );
		// add_action( 'admin_print_styles', array(&$this, 'sfr_clone_site_admin_styles') );

		// Add admin menu
		add_action('admin_menu', 'sfr_clone_site_menu_option_page');

	}
	
	
	
	
	function sfr_clone_site_admin_scripts() {
		wp_enqueue_script( 'sfr_clone_site_js',
			WP_PLUGIN_URL . '/sfr-clone-site/_js/sfr_clone_site_js.js',
			array( 'jquery' )
		);
	}
	
	function sfr_clone_site_admin_styles() {
		wp_enqueue_style( 'sfr_clone_site_css',
			WP_PLUGIN_URL . '/sfr-clone-site/_css/sfr_clone_site_css.css'
		);
	}






}




/* INITIATE THE PLUGING */
add_action("init", "sfr_clone_site_init");
function sfr_clone_site_init() { global $sfr_clone_site; $sfr_clone_site = new sfr_clone_site(); }



/* OPTION PAGE  */
require_once(WP_PLUGIN_DIR."/sfr-clone-site/_src/sfr_clone_site_options.php");



?>