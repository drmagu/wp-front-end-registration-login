<?php
/*
Plugin Name: Front end wp registration & login
Plugin URI: http://www.drmagu.com/model-view-controller-example-wordpress-plugin-1049.htm
Description: Allows front-end registration and login
Version: 0.9
Author: DrMagu
Author URI: http://www.drmagu.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
namespace drmagu\front_end_registration_login;

/* only allow WP environment */
defined('ABSPATH') or die("Oops.  Not here buddy!");

/*
 * Setup the autoloader
 * Looks for class files in the "classes/" directory
 */
 
require_once (__DIR__.'/classes/Autoloader.class.php');
new Autoloader(__DIR__.'/classes/');

/* run the plugin */
new Main();  

/*
 * Main Plugin Class 
 */
 
class Main {
	
	public function	__construct() {
		$this->main();
	}
		
	private function main() {
		
		/* limit the access */
		new LimitAdminAccess();

		/* login & logout functionality and more */
		$the_model = new Model();
		$the_view = new View($the_model, plugin_dir_url( __FILE__ ));
		new Controller($_POST, $_GET, $the_model, $the_view);
	}
 	
}

if (false):
function test_me() {
	echo "Hello Cutie";	
}
endif;
