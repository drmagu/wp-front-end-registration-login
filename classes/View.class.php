<?php
namespace drmagu\front_end_registration_login;

class View {
	
	private $load_css = false;
	private $model;
	private $plugin_dir_url;
	
	public function  __construct(Model $model, $plugin_dir_url) {
		$this->model = $model;
		$this->plugin_dir_url = $plugin_dir_url;
		
		$this->init();
	}
	
	private function init() {
		add_action('init', array( $this, 'register_css' ) );
		add_action('wp_footer', array( $this, 'print_css' ) );
		$this->add_shortcodes();
	}
	
	// register our form css
	public function register_css() {
		wp_register_style('ferl-form-css', $this->plugin_dir_url . '/css/forms.css');
	}
	
	// load our form css
	public function print_css() {
		// this variable is set to TRUE if the short code is used on a page/post
		if ( ! $this->load_css )
			return; // this means that the short code is not present, so we get out of here
		wp_print_styles('ferl-form-css');
	}
	
	private function add_shortcodes() {
		add_shortcode('ferl_login_form', array( $this,'login_form' ) );
		add_shortcode('ferl_password_form', array( $this,'password_form' ) );
		add_shortcode('ferl_request_password_reset_form', array( $this,'request_password_reset_form' ) );
		add_shortcode('ferl_reset_password_form', array( $this,'reset_password_form' ) );
		add_shortcode('ferl_register_form', array( $this,'register_form' ) );
	}
	
	public function login_form() {
		// set this to true so the CSS is loaded
		$this->load_css = true;
		$output = '';
		if(!is_user_logged_in()) {
			$output = $this->login_form_view();
		} else {
			// could show some logged in user info here and offer to logout
			$current_user = wp_get_current_user();
			$output = $this->logout_form_view($current_user);
		}
		return $output;
	}
	
	public function password_form() {
		$this->load_css = true;
		$output = '';
		if(!is_user_logged_in()) {
			$output = __('You must be signed in to change your password');
		} else {
			// show the password form
			$current_user = wp_get_current_user();
			$output = $this->password_form_view($current_user->user_login);
		}
		return $output;
	}
	
	public function request_password_reset_form() {
		$this->load_css = true;
		$output = '';
		if(!is_user_logged_in()) {
			$output = $this->request_password_reset_form_view();
		} else {
			// could show some logged in user info here
			$current_user = wp_get_current_user();
			$output = $this->logout_form_view($current_user);
		}
		return $output;
	}
	
	public function reset_password_form() {
		$this->load_css = true;
		$output = '';
		if( !is_user_logged_in() ) {
			// we do have a valid username from the get array
			$output = $this->password_form_view($this->model->get_username());
		} else {
			// could show some logged in user info here
			$current_user = wp_get_current_user();
			$output = $this->logout_form_view($current_user);
		}
		return $output;
	}
	
	public function register_form() {
		$this->load_css = true;
		$output = '';
		if(!is_user_logged_in()) {
						
			// check to make sure user registration is enabled
			$registration_enabled = get_option('users_can_register');
	
			// only show the registration form if allowed
			if($registration_enabled) {
				$output = $this->register_form_view();
			} else {
				$output = __('User registration is not enabled');
			}
			
		} else {
			// could show some logged in user info here
			$current_user = wp_get_current_user();
			$output = $this->logout_form_view($current_user);
		}
		return $output;
	}

	
/**
 * ==============================================
 * HTML Markup and Stuff 
 * ==============================================
 **/
 
 	/* displays error messages from form submissions */
	private function show_error_messages() {
		if($codes = $this->model->get_error_codes()) {
			echo '<div class="ferl_errors">';
				// Loop error codes and display errors
			   foreach($codes as $code){
					$message = $this->model->get_error_message($code);
					echo '<span class="error"><strong>' . __('Error') . '</strong>: ' . $message . '</span><br/>';
				}
			echo '</div>';
		}	
	}
	
 	/* displays info messages from form submissions */
	private function show_info_messages() {
		if($codes = $this->model->get_info_codes()) {
			echo '<div class="ferl_info">';
				// Loop codes and display
			   foreach($codes as $code){
					$message = $this->model->get_info_message($code);
					echo '<span class="error"><strong>' . __('Info') . '</strong>: ' . $message . '</span><br/>';
				}
			echo '</div>';
		}	
	}
	
	/* -----------------------------------------------
	 *  The "HTML" views
	 * -----------------------------------------------
	 */
	
	private function login_form_view() {
			
		ob_start(); 
			
			// show any error messages after form submission
			$this->show_info_messages();
			$this->show_error_messages(); ?>
			
			<form id="ferl_login_form"  class="ferl_form" action="" method="post">
				<fieldset>
					<p>
						<label for="ferl_username">Username</label>
						<input name="ferl_username" id="ferl_user_name" class="required" 
                        	type="text" value="<?= $this->model->get_username() ?>"/>
					</p>
					<p>
						<label for="ferl_user_pass">Password</label>
						<input name="ferl_user_pass" id="ferl_user_pass" class="required" type="password"/>
					</p>
					<p>
						<input type="hidden" name="ferl_action" value="login" />
						<input type="hidden" name="ferl_login_nonce" value="<?php echo wp_create_nonce('ferl-login-nonce'); ?>"/>
						<input id="ferl_login_submit" type="submit" value="Login" />
					</p>
				</fieldset>
			</form>
			<a href="/lost-password">Click here if you forgot your password</a>

		<?php
		return ob_get_clean();
	}
	
	private function logout_form_view($current_user) {
		ob_start();
		?>
        
        You are currently signed in as "<span style="color:#0A0"><?= $current_user->user_login ?></span>"
		
		<form id="ferl_logout_form"  class="ferl_form" action="" method="post">
        	<fieldset>
                <input type="hidden" name="ferl_action" value="logout" />
                <input type="hidden" name="ferl_logout_nonce" value="<?php echo wp_create_nonce('ferl-logout-nonce'); ?>"/>
                <div>
                <input id="ferl_logout_submit" type="submit" value="Logout"/>
                </div>
        	</fieldset>
        </form>
        
		<?php
		return ob_get_clean();
	}
	
	private function password_form_view($username) {
		ob_start(); 	
		?>
   		<h3 class="ferl_header">Account: <?= $username ?></h3>

		<?php	
		// show any error messages after form submission
		$this->show_error_messages(); 
		$this->show_info_messages();
		?>
        
		<form id="ferl_password_form"  class="ferl_form" action="" method="post">
			<fieldset>
				<p>
					<label for="ferl_user_pass">Password</label>
					<input name="ferl_user_pass1" id="ferl_user_pass1" class="required" type="password"/>
				</p>
				<p>
					<label for="ferl_user_pass">Repeat Password</label>
					<input name="ferl_user_pass2" id="ferl_user_pass2" class="required" type="password"/>
				</p>
				<p>
					<input type="hidden" name="ferl_action" value="password" />
					<input type="hidden" name="username" value="<?= $username ?>" />
					<input type="hidden" name="ferl_password_nonce" value="<?php echo wp_create_nonce('ferl-password-nonce'); ?>"/>
					<input id="ferl_password_submit" type="submit" value="Change Password"/>
				</p>
			</fieldset>
		</form>
		
		<?php
		return ob_get_clean();
	}

	private function request_password_reset_form_view () {
		ob_start(); 
			
		// show any error messages after form submission
		$this->show_error_messages(); 
		$this->show_info_messages();
		?>
        
		<form id="ferl_request_password_reset_form" class="ferl_form" action="" method="POST">
			<fieldset>
				<p>
					<label for="ferl_username"><?php _e('Username'); ?></label>
					<input name="ferl_username" id="ferl_username" class="required" type="text" value="<?= $this->model->get_username() ?>" />
				</p>
				<p>
					<input type="hidden" name="ferl_action" value="request_reset" />
					<input type="hidden" name="ferl_request_password_reset_nonce" value="<?php echo wp_create_nonce('ferl-request-password-reset-nonce'); ?>"/>
					<input type="submit" value="<?php _e('Request password reset'); ?>" name="submit" />
				</p>
			</fieldset>
		</form>	
        
		<?php
		return ob_get_clean();
	}
	
	private function register_form_view() {
		ob_start(); 
			
		// show any error messages after form submission
		$this->show_error_messages(); 
		$this->show_info_messages();
		?>
		<form id="ferl_registration_form" class="ferl_form" action="" method="POST">
			<fieldset>
				<p>
					<label for="ferl_username"><?php _e('Desired username'); ?></label>
					<input name="ferl_username" id="ferl_username" 
                    	class="required" type="text" 
                        value="<?= $this->model->get_username() ?>" />
				</p>
				<p>
					<label for="ferl_email"><?php _e('Your e-mail'); ?></label>
					<input name="ferl_email" id="ferl_email" 
                    	class="required" type="text" 
                        value="<?= $this->model->get_email(0) ?>" />
				</p>
				<p>
					<label for="ferl_email1"><?php _e('Your e-mail *repeat'); ?></label>
					<input name="ferl_email1" id="ferl_email1" 
                    	class="required" type="text" 
                        value="<?= $this->model->get_email(1) ?>" />
				</p>
				<p>
					<input type="hidden" name="ferl_action" value="register" />
					<input type="hidden" name="ferl_register_nonce" value="<?php echo wp_create_nonce('ferl-register-nonce'); ?>"/>
					<input type="submit" value="<?php _e('Register Your Account'); ?>" name="submit" />
				</p>
			</fieldset>
		</form>
			
		<?php
		return ob_get_clean();
	}
	

}
	
