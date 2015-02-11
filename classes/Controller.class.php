<?php
namespace drmagu\front_end_registration_login;

/*
 * Handle the browser requests and route them
 * There are two requests types 
 * -> respond to a form submission (POST)
 * -> respond to a GET request
 */
class Controller {
	private $post_array;
	private $get_array;
	private $view;
	private $model;
	
	public function __construct($post_array, $get_array, Model $model, View $view) {
		$this->post_array = $post_array;
		$this->get_array = $get_array;
		$this->view = $view;
		$this->model = $model;
		
		$this->init();
	}

	private function init() {
		/* pass request parameters to the model */
		$this->model->set_request_params($this->post_array, $this->get_array);
		
		/* validate the reset-password request */
		add_action( 'template_redirect', array($this->model, 'validate_reset_request') ); 

		/* handle the form posts */
		if (isset($this->post_array['ferl_action'])): 
		switch ($this->post_array['ferl_action']):
			case "login":
				add_action( 'init', array( $this->model, 'login_user' ) );
			break;
			
			case "logout":
				add_action( 'init', array( $this->model, 'logout_user' ) );
			break;
			
			case "password":
				add_action( 'init', array( $this->model, 'change_password' ) );
			break;
			
			case "request_reset":
				add_action( 'init', array( $this->model, 'lost_password' ) );
			break;

			case "register":
				add_action( 'init', array( $this->model, 'register_user' ) );
			break;
		endswitch;
		endif; // $ferl_action 
	}
	
}
