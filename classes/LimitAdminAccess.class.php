<?php
namespace drmagu\front_end_registration_login;

class LimitAdminAccess {
		
	public function __construct() {
		$this->add_actions();
	}
	
	private function add_actions() {
		add_action( 'login_head', array( $this, 'no_wp_login' ) );
		add_action( 'admin_init', array( $this, 'restrict_admin_with_redirect' ) );
		add_action( 'init', array( $this , 'disable_adminbar' ) );
		add_action( 'wp_logout', array( $this, 'go_home' ) );
	}

	/*
	* Redirect All trying to access native wp-login pages directly
	*/
	public function no_wp_login() {
		wp_redirect(home_url());
		exit;
	}

	/*
	* Restrict access to backend for all except ADMIN
	*/
	public function restrict_admin_with_redirect() {		
		if( ! current_user_can('manage_options') && $_SERVER['PHP_SELF'] != '/wp-admin/admin-ajax.php' ) {
			wp_redirect( home_url() ); 
			exit;
		}
	}

	/*
	*  Removes Admin bar for all Except ADMIN
	*/			
	public function disable_adminbar(){		
		if( ! current_user_can('manage_options') ){
			show_admin_bar(false);
		}
	}
	
	/*
	* Redirect to home page at logout
	*/
	public function go_home(){
	  wp_redirect( home_url() );
	  exit();
	}
}

