<?php
namespace drmagu\front_end_registration_login;

class Autoloader {

	private $class_path;
	
	public function __construct($class_path) {
		$this->class_path = $class_path;
		
		$this->init();
	}
	
	private function init() {

		spl_autoload_register(array($this, 'require_class'));
	}
	
	public function require_class($class) {
			/* strip any namespaces */							
			$arr_class = explode('\\', $class);
			$class = end($arr_class);

			if ( is_file( $this->class_path.$class.'.class.php' ) ) 
			{  
				require_once( $this->class_path.$class.'.class.php' ); 								
			}
	}

}