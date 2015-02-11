<?php
namespace drmagu\front_end_registration_login;
use \WP_Error;

class Model {
	private $wp_error;
	private $wp_info;
	private $post_array = array();
	private $get_array = array();
	
	private $username = "";
	
	private $email = "";
	private $email1 = "";
		
	public function __construct() {
		$this->wp_error = new WP_Error(null, null, null);		
		$this->wp_info = new WP_Error(null, null, null);		
	}
	
	/** allow the request data to be set **/
	public function set_request_params($post_array, $get_array) {
		$this->post_array = $post_array;
		$this->get_array = $get_array;
		if(isset($this->post_array['ferl_username'])) $this->username = $this->post_array['ferl_username'];
		if(isset($this->get_array['username'])) $this->username = $this->get_array['username'];
	}
	
	/** methods required by the View class **/
	public function get_error_codes() {
		return $this->wp_error->get_error_codes();	
	}
	
	public function get_info_codes() {
		return $this->wp_info->get_error_codes();	
	}
	
	public function get_error_message( $code ) {
		return $this->wp_error->get_error_message( $code );
	}
	
	public function get_info_message( $code ) {
		return $this->wp_info->get_error_message( $code );
	}
	
	public function get_username( ) {
		return $this->username;
	}
	
	public function get_email($idx) {
		$emails = array($this->email, $this->email1);
		return $emails[$idx];	
	}
	
	/** methods to handle form submissions **/
	public function login_user() {
		// make sure we have a username and the nonce is correct
		if(isset($this->post_array['ferl_username']) && wp_verify_nonce($this->post_array['ferl_login_nonce'], 'ferl-login-nonce'))
		{						
			$user_login = $this->post_array['ferl_username'];
			// this returns the user ID and other info from the user name
			$user = get_user_by('login',$user_login);
			
			if(! $user_login || $user_login == '') {
				// if no username was entered
				$this->wp_error->add('empty_username', __('Please enter a username'));
			} else {
				if(!$user) {
					// if the user name doesn't exist
					$this->wp_error->add('invalid_username', __('Invalid username'));
				}
			}
			
			if(!isset($this->post_array['ferl_user_pass']) || $this->post_array['ferl_user_pass'] == '') {
				// if no password was entered
				$this->wp_error->add('empty_password', __('Please enter a password'));
			} else {			
				if ($user) {
					// check the user's login with their password
					if(!wp_check_password($this->post_array['ferl_user_pass'], $user->user_pass, $user->ID)) {
						// if the password is incorrect for the specified user
						$this->wp_error->add('invalid_password', __('Incorrect password'));
					}
				}
			}
			
			// retrieve all error messages
			$errors = $this->wp_error->get_error_messages();
			// only log the user in if there are no errors
			if(empty($errors)) {
				$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; 
				$redirect_page = $current_url; 
				wp_set_auth_cookie($user->ID, true);
				wp_set_current_user($user->ID, $user_login);
				// trigger the action hook
				do_action('wp_login', $user_login);
				wp_redirect($redirect_page);exit;
			}
		}
	}
	
	public function logout_user() {
		wp_logout();
	}
	
	public function change_password() {
		$pw_chars = array('!', '@', '#', '?', '$', '%', '^', '&', '*', '_', '-');
		
		if ( ! empty( $this->post_array['username'] ) 
			&& wp_verify_nonce($this->post_array['ferl_password_nonce'], 'ferl-password-nonce') ) 
		{   
			$current_user = get_user_by('login',$this->post_array['username']);
			$pass1 = $this->post_array['ferl_user_pass1'];
			$pass2 = $this->post_array['ferl_user_pass2']; 

			/* Password validation */
			if ( strlen( $pass1 ) > 0 && strlen( $pass2 ) > 0 ) { 
				if ( $pass1 == $pass2 ) { 
					if ( strlen($pass1) < 6 ) {
						$this->wp_error->add('invalid_password',__('A password must be at least 6 characters long.  Your password was not updated.'));
					} else { 
						if ( ! ctype_alnum( str_replace($pw_chars, '', $pass1) ) ) {
							$this->wp_error->add('invalid_password',__("A password can only contain a alphanumeric characters and the following characters: <br />".implode (' ', $pw_chars)."  <br/>Your password was not updated."));	
						}
					}
				} else {
					$this->wp_error->add('invalid_password',__('The passwords you entered do not match.  Your password was not updated.'));
				}
			} else {
				$this->wp_error->add('invalid_password',__('A password must be at least 6 characters long.  Your password was not updated.'));
			}
			
			/* retrieve all error messages */
			$errors = $this->wp_error->get_error_messages();
			/* Update user password if there are no errors */
			if(empty($errors)) {
				wp_update_user( array( 'ID' => $current_user->ID, 'user_pass' => esc_attr( $pass1 ) ) );
				$this->wp_info->add('updated_password',__('Your password has been updated.'));
			}
		}
	}
	
	function lost_password() {
		global $wpdb;

		if (isset( $this->post_array["submit"] ) && wp_verify_nonce($this->post_array['ferl_request_password_reset_nonce'], 'ferl-request-password-reset-nonce')) {

			$username  	= $this->post_array["ferl_username"];	
			
			if(trim($username) == '') {
				// empty username
				$this->wp_error->add('username_empty', __('Please enter your Username'));
			} else {
				$user_login = strtolower($username);
			}
			if( ! username_exists($user_login) ) {
				// Username not registered at all
				$this->wp_error->add('username_not_registered', __('No such user is registered'));
			} else {
				// get the user object
				$the_user = get_user_by( 'login', $user_login );
			}
			
			$errors = $this->wp_error->get_error_messages();

			// only send the message if no errors
			if(empty($errors)) {
				// set the user activation key	
				$key = wp_generate_password(20, false);
				$wpdb->update($wpdb->users, array('user_activation_key' => $key), array('user_login' => $user_login));
				/* " .. sending email .. to " . $the_user->user_email */
				$to = $the_user->user_email;
				$body = "..";
				$body .= "\nHello ".$the_user->display_name." !";
				$body .= "\nSomeone requested that the password be reset for your \"".get_bloginfo('name')."\" website account.";
				$body .= "\nIf this was a mistake, just ignore this message and nothing will happen.";
				$body .= "\nTo reset your password, visit the following address:";
				$body .= "\n ".site_url()."/reset-password?action=reset-password&username=".$user_login."&key=".$key." "; 
				$body .= "\n";
				wp_mail($to, "[".get_bloginfo( 'name' )."] Password reset request for ".$username,$body);
				
				wp_redirect(site_url().'/email-message-sent'); exit;
			}
		}	
	}
	
	public function validate_reset_request() {
		
		// only keep going if it is the "reset-password" page
		if ( !is_page( 'reset-password' ) ) return;
		
		// need some WP globals
		global $current_user;
		global $wpdb;
		
		$dead_message = "<h1>Invalid request.  You can use a password reset link only once.</h1>";
		$dead_message .= '<h2><a href="'.home_url().'">'.get_bloginfo( 'name' ).'</a></h2>';
		
		// logged in ?  I don't think so !!
		if (is_user_logged_in()) { 
			// kill the key .. this page should not have been requested
			$user_id = $current_user->ID; 
			$wpdb->update( $wpdb->users, array('user_activation_key' => ""), array('ID' => $user_id) );
			wp_redirect(home_url()); exit; 
		}
			
		// first see if we have a "GET" or .. it could be a "POST" from the form on this page 
		if (! isset($this->get_array['action']) && ! isset($this->post_array['dbs_action'])) { wp_redirect(home_url()); exit; }
		
		// handle the "GET" request
		if ( isset($this->get_array['action']) && ! isset($this->post_array['ferl_action']) ) {
			// make sure all fields are there 
			if (sizeof($this->get_array) != 3) die ($dead_message);
			if (! isset($this->get_array['username']) || ! isset($this->get_array['key'])) die ($dead_message);
			$user_login = $this->get_array['username'];
			$reset_key = $this->get_array['key'];
			if(! $reset_key) die ($dead_message);
			if ($this->get_array['action'] != 'reset-password') die ($dead_message);
			// get user information 
			$user_data = $wpdb->get_row($wpdb->prepare("SELECT ID, user_login, user_activation_key FROM $wpdb->users WHERE user_login = %s", $user_login));
			
			if(! $user_data) die ($dead_message);
			if ($reset_key != $user_data->user_activation_key) die ($dead_message);
		
			// ok .. reset the key .. only one time usage
			$user_id = $user_data->ID;
			$wpdb->update( $wpdb->users, array('user_activation_key' => ""), array('ID' => $user_id) );	
		}
			
		// we are good to go
		return;
	}
	
	public function register_user() {		
		if(isset($this->post_array['ferl_username']) && wp_verify_nonce($this->post_array['ferl_register_nonce'], 'ferl-register-nonce') ) {

			$username = $this->post_array["ferl_username"];
			$email = $this->post_array["ferl_email"];
			$email1 = $this->post_array["ferl_email1"];
			
			$this->email= $email;
			$this->email1= $email1;
			
			// validate username
			if(trim($username) == '') {
				// empty username
				$this->wp_error->add('username_empty', __('Please enter a username'));
			} else {
				if ( ! $this->is_valid_username($username) ) {
					$this->wp_error->add('username_invalid', __('Invalid username'));
				} else {
					if(username_exists($username)) {
						// Username already registered
						$this->wp_error->add('username_unavailable', __('Username already taken'));
					}	
				}
			}
			
			// validate email
			if(trim($email) == '') {
				// empty email
				$this->wp_error->add('email_empty', __('Please enter an email address'));
			} else {
				// email fields not the same 
				if ( $email != $email1 ) {
					$this->wp_error->add('email_fields_unequal', __('Please enter the same email address twice'));	
				} else 				{
					if ( ! is_email($email) ) {  // uses the WP is_email function
						$this->wp_error->add('email_invalid', __('Please enter a valid email address'));
					}
				}
			}

			// retrieve all error messages
			$errors = $this->wp_error->get_error_messages();
			// only create the user in if there are no errors
			if(empty($errors)) {
				$new_password = wp_generate_password(12, false, false);
				$new_user_record = array(
						'user_login'		=> $username,
						'user_pass'	 		=> $new_password,
						'user_registered'	=> date('Y-m-d H:i:s'),
						'role'				=> 'subscriber'
					);
	
				$new_user_id = wp_insert_user($new_user_record);
				// if it is NOT a number .. something went very wrong
				if (! is_numeric($new_user_id) ) { 
					var_dump($new_user_id); echo "<br />";
					die('Fatal error in user registration.  <a href="'.site_url().'">Please contact us.</a>');
				} else {
					// update the email (this allows for duplicate email addresses
					$new_user_id = wp_update_user( array('ID' => $new_user_id, 'user_email' => $email) );
				}

				if (! is_numeric($new_user_id) ) { 
					var_dump($new_user_id); echo "<br />";
					die('Fatal error in user registration (2).  <a href="'.site_url().'">Please contact us.</a>');
				}
				
				if($new_user_id) {
					wp_new_user_notification($new_user_id);
					/* send an email to the newly registered user */
					$to = $email;
					$body = "..";
					$body .= "\nHello ".$username." !";
					$body .= "\n\nWelcome to the \"".get_bloginfo('name')."\" website and thank you for registering";
					$body .= "\n\nYour username is ".$new_user_record['user_login']."\n";
					$body .= "\nYour new password is ".$new_user_record['user_pass']."\n";
					$body .= "\nPlease Sign In to your account with your Username and the Password provided.";
					$body .= "\n".site_url()."/sign-in "; 
					$body .= "\n\nAfter you are signed in, change your password :) ";
					$body .= "\n\nDo not reply to this message as replies are not monitored.";
					$body .= "\n\n";
					wp_mail($to, "[".get_bloginfo( 'name' )."] Thank you for registering ".$username,$body);
					/* send the newly created user to the Sign In after registration */
					wp_redirect(site_url().'/email-message-sent'); exit;
				}
			}
		}
	}
	
	private function is_valid_username( $un ) {
		$un_chars = array('.', '-', '_');
		$valid = true;
		$valid = $valid && ctype_alpha( substr($un,0,1) );
		$valid = $valid && (strlen( $un ) >= 4);
		$valid = $valid && $this->is_valid_string( $un, $un_chars );	
		return $valid;
	}
	
	private function is_valid_string ($s, $chars = array() ) {
		return ctype_alnum( str_replace($chars, '', $s) );
	}

}
