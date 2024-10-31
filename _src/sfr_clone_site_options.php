<?php



// add page to Super Admin menu
function sfr_clone_site_menu_option_page() {
	add_submenu_page( 'wpmu-admin.php', 'Clone site', 'Clone site', 'manage_options', 'sfr_clone_site', 'sfr_clone_site_options' );
	add_action( 'admin_init', 'register_sfr_clone_site_settings' );
}

// whitelist options
function register_sfr_clone_site_settings() { 
	register_setting( 'sfr_clone_site_option_group', 'sfr_clone_site_sql_backup_dir' );
	register_setting( 'sfr_clone_site_option_group', 'sfr_clone_site_file_backup_dir' );
	register_setting( 'sfr_clone_site_option_group', 'sfr_clone_site_wp_root' );  
}






// 
function sfr_clone_site_options() {

  	if (!current_user_can('manage_options'))  {
   		wp_die( __('You do not have sufficient permissions to access this page.') );
  	}
	  	
  	global $wpdb;
	global $table_prefix;
	
	
	require_once(WP_PLUGIN_DIR."/sfr-clone-site/_src/sfr_clone_site_functions.php");	
	
	$network_site_query = $wpdb->get_results( "SELECT * FROM `". $table_prefix ."blogs`", 'ARRAY_A' );

/* THIS IS WHERE STUFF HAPPEN */
  	if ( $_REQUEST['action'] == '_sfr_clone_site' ) { 
  		$nonce = $_REQUEST['_wpnonce'];
		if (! wp_verify_nonce( $nonce, '_sfr_clone_site-action' ) ) die( 'Security check' );
		


	$os_id = empty($_POST['old_site_id']) ? false : $_POST['old_site_id'];
	$ns_id = empty($_POST['new_site_id']) ? false : $_POST['new_site_id'];
	
	if ($os_id && $ns_id) {
		
		//	Check if those site ID are real site on the network
		$os_check = false;
		$ns_check = false;
			
		foreach ($network_site_query as $site) {
  			
  			if ($site['blog_id'] == $os_id) {
  				$os_check = true;
  			}
  			
  			if ($site['blog_id'] == $ns_id) {
  				$ns_check = true;
  			}
	  	}
	  	
	  	if ($os_id == $ns_id) {
	  		$ns_check = false;
	  	}
	  	
		
		if ($os_check && $ns_check) {
			//	Site exist, let's start to clone	
			// 	First, let's get some info
			
			$sfr_clone_site_sql_backup_dir = get_option('sfr_clone_site_sql_backup_dir'). '/';
			$sfr_clone_site_file_backup_dir = get_option('sfr_clone_site_file_backup_dir'). '/';
		 	$sfr_clone_site_file_backup_file = $sfr_clone_site_file_backup_dir. $ns_id . "--" . date( "Y-M-D-H-i" , time()) . ".tar";
			$sfr_clone_site_wp_root = get_option('sfr_clone_site_wp_root') . '/';

			$os_theme_query = $wpdb->prepare("SELECT option_value FROM ". $table_prefix . $os_id ."_options WHERE option_name = %s OR option_name = %s OR option_name = %s;", 'stylesheet', 'template' , 'current_theme' );
			$os_theme = $wpdb->get_results($os_theme_query);
			//print_r($os_theme);
			
			$ns_theme_query = $wpdb->prepare("SELECT option_value FROM ". $table_prefix . $ns_id ."_options WHERE option_name = %s OR option_name = %s OR option_name = %s;", 'stylesheet', 'template' , 'current_theme' );
			$ns_theme = $wpdb->get_results($ns_theme_query);
			//print_r($ns_theme);
		
			$os_url_query = $wpdb->prepare("SELECT option_value FROM ". $table_prefix . $os_id ."_options WHERE option_name = %s;", 'siteurl' );
			$os_url = $wpdb->get_var($os_url_query);
			$os_url = str_replace(array('http://', '/'), "", $os_url);
			//print_r($os_url);
			
			
			$ns_url_query = $wpdb->prepare("SELECT option_value FROM ". $table_prefix . $ns_id ."_options WHERE option_name = %s;", 'siteurl' );
			$ns_url = $wpdb->get_var($ns_url_query);
			$ns_url = str_replace(array('http://', '/'), "", $ns_url);
			//print_r($ns_url);
			
				

		
		
		
/*	CREATE NEW SITE SQL BACKUP 	*/	

			$ns_table_to_dump_query = "SHOW TABLES FROM ". DB_NAME ." WHERE Tables_in_". DB_NAME ." LIKE '%". $table_prefix . $ns_id ."%'; ";
			$ns_table_to_dump = $wpdb->get_results($ns_table_to_dump_query);
			$ns_table_to_dump_array = array();
			
			foreach ($ns_table_to_dump as $table ) { $ns_table_to_dump_array[] = $table->Tables_in_mu_wp_db; }

			$new_site_sql_file =  $sfr_clone_site_sql_backup_dir . $ns_id . '-' . date( "Y-m-d_H_i-s-" ) . 'backup.sql';
			
			$dump_exec = "mysqldump -h". DB_HOST ." -u". DB_USER ." -p". DB_PASSWORD ." ". DB_NAME ." " .implode( " ", $ns_table_to_dump_array ) . " > $new_site_sql_file";
			exec("$dump_exec");
			
		
/*	DROP NEW SITE SQL 	*/	
			sfr_clone_site_mysql_drop( DB_NAME, $ns_id, $table_prefix );
		
			
/*	CLONE DATABASE	*/
			sfr_clone_site_mysql_clone( DB_NAME, $ns_id, $os_id,  $table_prefix );
			
		
/*	UPDATE NEW SITE DB */
			sfr_clone_site_mysql_update( DB_NAME, $ns_id, $os_id,  $table_prefix, $os_url, $ns_url, $os_theme, $ns_theme);
		
		
/* BACKUP FILES */
			$to_back_up = array(
					$sfr_clone_site_wp_root . "wp-content/themes/". $ns_theme[1]->option_value
					, $sfr_clone_site_wp_root . "wp-content/blogs.dir/". $ns_id
					, $new_site_sql_file
			);
		
			exec( "tar cf $sfr_clone_site_file_backup_file ". implode( " ", $to_back_up ) );
		
		  
			/* DELETE TEMPLATE FILES */
		
			/* keep theme screenshot */
			$ns_screenshot_src = $sfr_clone_site_wp_root . "wp-content/themes/". $ns_theme[1]->option_value . "/screenshot.png";
			$ns_screenshot_temp_src = $sfr_clone_site_wp_root . "screenshot.png";	
			if ( is_file( $ns_screenshot_src ) ) {
				$c = copy($ns_screenshot_src, $ns_screenshot_temp_src);
			}
			
			
			/*	delete them files */
			deleter( $sfr_clone_site_wp_root . "wp-content/themes/". $ns_theme[1]->option_value );
			deleter( $sfr_clone_site_wp_root . "wp-content/blogs.dir/". $ns_id );
		
		
			/*	COPY THEME FILE AND SITE FILES	*/ 
		  	copyr( $sfr_clone_site_wp_root . "wp-content/themes/". $os_theme[1]->option_value , $sfr_clone_site_wp_root . "wp-content/themes/". $ns_theme[1]->option_value );
			copyr( $sfr_clone_site_wp_root . "wp-content/blogs.dir/". $os_id , $sfr_clone_site_wp_root . "wp-content/blogs.dir/". $ns_id ) ;	
		  
		  	/* copy theme screenshot back */
			if ( is_file( $ns_screenshot_temp_src ) ) {
				$c = copy($ns_screenshot_temp_src, $ns_screenshot_src);
				unlink($ns_screenshot_temp_src);
			}

			
			$update_msg = 'Done ! <a href="http://'.$ns_url.'" target="_blank">look here</a>';
			
		} else {
		
			$update_msg = 'no go .. site ID not found or your trying to clone a site on it\'s self ...';
			
		}

		
		
		
	}
}


		if ( $_GET['updated'] == 'true' ) {
			$update_msg =  'Options updeate';
		}


?>



<div class="wrap">
<h2>SFR Clone site Option</h2>

<?php 	
		if ( $update_msg ) {
			echo "<div class='updated fade'><p>" .  __( $update_msg ) . "</p></div>";

		}


?>


<form method="post" action="options.php">
<?php wp_nonce_field('update-options'); ?>
<?php settings_fields( 'sfr_clone_site_option_group' ); ?>

<table class="form-table">

<tr valign="top">
<th scope="row"><?php _e('Database backup folder'); ?></th>
<td><input type="text" name="sfr_clone_site_sql_backup_dir" style="width:450px;" value="<?php echo get_option('sfr_clone_site_sql_backup_dir'); ?>" /><br />
<?php _e( "ei:" ); ?> <?php echo $_SERVER['DOCUMENT_ROOT']; ?>/sql_backup</td>
</tr>
 
<tr valign="top">
<th scope="row"><?php _e('File backup folder'); ?></th>
<td><input type="text" name="sfr_clone_site_file_backup_dir" style="width:450px;" value="<?php echo get_option('sfr_clone_site_file_backup_dir'); ?>" /><br />
<?php _e( "ei:" ); ?> <?php echo $_SERVER['DOCUMENT_ROOT']; ?>/backup</td>
</tr>

<tr valign="top">
<th scope="row"><?php _e('wp root folder'); ?></th>
<td><input type="text" name="sfr_clone_site_wp_root" style="width:450px;" value="<?php echo get_option('sfr_clone_site_wp_root'); ?>" /><br />
<?php _e( "ei:" ); ?> <?php echo $_SERVER['DOCUMENT_ROOT']; ?>
</td>
</tr>

</table>
<input type="hidden" name="update_sfr_clone_site_option" value="true" />

<input type="hidden" name="action" value="update" />

<p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>

 

</form>
</div>



<div class="wrap">
<h2><?php _e( "Clone a site" ); ?></h2>

<form method="post" action="">
<?php wp_nonce_field('_sfr_clone_site-action'); ?>

<table class="form-table">

<tr valign="top">
<th scope="row"><?php _e( "Take this site:" ); ?></th>
<td><?php echo build_site_list('old_site_id', $network_site_query); ?></td>
</tr>
 
<tr valign="top">
<th scope="row"><?php _e( "And put it on this space:" ); ?></th>
<td><?php echo build_site_list('new_site_id', $network_site_query); ?></td>
</tr>

</table>

<input type="hidden" name="action" value="_sfr_clone_site" />

<p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Clone that site baby !') ?>" />
</p>

 

</form>
</div>







<?php








}



function build_site_list($name, $site_list, $selected = false) {
	global $wpdb, $table_prefix;
	
	$code = '<select name="'. $name .'" id="'. $name .'" style="width:450px;">';
	foreach($site_list as $site) {
		if ($site['blog_id'] == '1') {
			continue;
		}
		$site_theme_folder = $wpdb->get_results("SELECT option_value as theme FROM ". $table_prefix . $site['blog_id'] . "_options WHERE option_name = 'stylesheet'; ");
		$code .= '<option value="'. $site['blog_id'] .'">'. $site['domain'] .' - '. $site_theme_folder[0]->theme .'</option>';		
	}
	$code .= '</select>';
	
	
	
	return $code;
}






?>