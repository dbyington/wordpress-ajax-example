<?php
/**
 *
 * A simple ajax example for using ajax calls in WordPress.
 *
 * This is just one of many ways to make use of ajax calls withing WordPress.
 * This just happens to be the way I got ajax to work.
 *
 * License: GPLv2 or later
 *
 *
 * @package Ajax_Example
 * @version 0.3
 * @author dbyington
 *
 */
/*
Plugin Name: Ajax Example
Plugin URI: https://github.com/dbyington/code-help/ajax-example
Description: A simple ajax example for using ajax calls in WordPress.
Author: Don Byington
Version: 0.3
Author URI: http://wordpress.org/support/profile/dbyington
License: GPLv2 or later
*/ 

if ( ! class_exists( 'Ajax_Example' ) ) :
	class Ajax_Example {

		/**
		 * The base init function to add/load what we need.
		 *
		 * @param	none
		 * @return	none
		 */
		function init() {

			/**
			 * Load the javascript inside the <head> tag
			 *
			 * Be aware of where your scripts are being loaded.
			 * Make sure they only load when they are needed.
			 */
			add_action( 'wp_head', array( __CLASS__, 'load_jquery' ) );
			add_action( 'wp_head', array( __CLASS__, 'ajax_handler' ) );
			add_action( 'admin_head', array( __CLASS__, 'ajax_handler' ) );


			/**
			 * Add our function to handle ajax calls with an action of 'ajax_example_admin_optins',
			 * 'ajax_example_update_widget_text', and 'ajax_example_widget'.
			 * Technically a call back used by admin-ajax.php
			 */
			add_action( 'wp_ajax_ajax_example_admin_options', array( __CLASS__, 'handle_example_call' ) );
			add_action( 'wp_ajax_ajax_example_update_widget_text', array( __CLASS__, 'handle_example_call' ) );
			add_action( 'wp_ajax_ajax_example_widget', array( __CLASS__, 'handle_example_call' ) );

			/**
			 * For visitors we need to add a 'nopriv' one too.
			 */
			add_action( 'wp_ajax_nopriv_ajax_example_widget', array( __CLASS__, 'handle_example_call' ) );


			/**
			 * Add our menu in the admin page.
			 */
			add_action( 'admin_menu', array( __CLASS__, 'add_ajax_example_admin_options' ) );

		}


		/**
		 * An install to create our options in the database
		 *
		 * Create an 'option' in the wp_options table with a null default value.
		 * It does not need to be autoloaded.
		 *
		 * @param	none
		 * @return	none
		 */
		function install() {
			add_option( 'ajax_example', null, null, 'no' );
		}


		/**
		 * Deinstall to clean up our entry in wp_options when deactivated.
		 *
		 * @param	none
		 * @return	none
		 */
		function deinstall() {
			delete_option( 'ajax_example' );
		}


		/**
		 * Make sure jQuery and the jQuery Form plugin are available
		 *
		 * @param	none
		 * @return	none
		 */
		function load_jquery() {
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-form');
		}
		
		/**
		 * Create a custom admin options page.
		 *
		 * We'll put this in the admin page, under 'Settings.'
		 * The page title will be 'Ajax Example Options' and the
		 * menu item under 'Settings' will be 'Ajax Example'.
		 * I guess it's typical to manage options you'll need
		 * the 'manage_options' capability.
		 *
		 * @param	none
		 * @return	none
		 */
		function add_ajax_example_admin_options() {
			add_options_page( 
				__( 'Ajax Example Options', 'ajax_example' ),
				__( 'Ajax Example', 'ajax_example' ),
				'manage_options',
				'ajax_example_options',
				array( __CLASS__, 'print_options_page' ) );

				wp_enqueue_script('jquery-form');
		}

		


		/**
		 * Create a custom menu page.
		 *
		 * We'll put this in the admin page, down at the bottom.
		 * We don't care about capability, so we're leaving that blank.
		 *
		 * @param	none
		 * @return	none
		 */
		function add_ajax_example_menu() {
			add_menu_page( __( 'Ajax Example Menu', 'ajax_example' ),
				__( 'Ajax Example Page', 'ajax_example' ),
				'',
				__FILE__,
				'',
				'',
				99 );
		}


		/**
		 * Retrieve our option from wp-options.
		 *
		 * @param	none
		 * @return	string	The text from the last insert or NULL
		 *			if nothing is there.
		 */
		function get_option_value() {
			$text = get_option( 'ajax_example' );
			if ( isset( $text ) ) {
				return $text;
			}
			return NULL;
		}

		/**
		 * Print the options page html
		 *
		 * @since 0.1
		 *
		 * @param    none
		 * @return   none
		 */
		function print_options_page() {
			echo self::build_example_form();

		}


		/**
		 * AJAX call handler
		 * 
		 * This handles all AJAX calls using the WordPress admin-ajax.php. 
		 *
		 * Form data is passed through, serialized to the appropriate wp_ajax_ function.
		 * AJAX response, if required is handled based on the requested action.
		 * This should be loaded in the <head> so that when $(document).ready happens your
		 * script will be ready to send requests when triggered.
		 * In the second $(document) call I use '.on' because some of my ajax actions
		 * dynamically add html which contains ajax event triggers.
		 *
		 * @since 0.1
		 *
		 * @param    none
		 * @return   none
		 */
		function ajax_handler() {
			$ajax_nonce = wp_create_nonce('ajax_example');
			?>
			<script type="text/javascript">
			var $jQ = jQuery.noConflict();
			$jQ(document).ready( function( $ ) {
				$jQ(document).on('click', '.ajax-trigger', function ($) {

					/**
					 * Get the action from the value of 'name=' in the submit input
					 * that has the class 'action'.
					 */
					var $action = $jQ(this).find('input.action').attr('name'); 

					/**
					 * Derive the form id from the action.
					 */
					var $form = '#'+$action+'-form';
					
					/**
					 * Serialize the form data to then extract from $_POST with parse_str() 
					 */
					var $data = $jQ($form).serialize();

					/**
					 * We can go ahead and reset the form fields.
					 */
					$jQ($form).resetForm();
					
					/**
					 * 'ajaxurl' is defined by default in WordPress,and points to 
					 * admin-ajax.php. However, below you'll se we need to define it
					 * in the Widget, because it only gets defined in the admin pages.
					 */
					$jQ.post(ajaxurl, {
					
						/**
						 * Send our key value pairs as a POST to admin-ajax.php.
						 * It will handle calling the functions we defined above for
						 * the given action.
						 * The security value will be derived and matched in the
						 * callback function.
						 * 'data' is our form data in serialized format.
						 */
						action: $action,
						security: '<?php echo $ajax_nonce; ?>',
						data: $data
						}, function( response ) {
						
							/**
							 * Unserialize the response into an object.
							 */
							var $json_response = $jQ.parseJSON( response );

							/**
							 * In here you have lots of options for what you want to do.
							 * I'm just testing for a 'print_output' var and if it is 'yes',
							 * Then I determine if it goes into an alert or an id tag somewhere.
							 */
							if ( $json_response.print_output == 'yes' ) {
								if ( $json_response.print_where == 'alert' ) {
									alert( $json_response.output );
								} else {
									$jQ('#'+$json_response.print_where).html($json_response.output);
								}
							}
						} 
					 );
					 

				} );
			} );
			</script>
			<?php
		}
			
		/**
		 * Build our example form and return it in a string variable
		 *
		 * This could be any form. If your page has multiple forms they
		 * all need their own unique id. Then you'll need to figure out
		 * which form was submitted.
		 *
		 * There are four important pieces to notice; the form 'id', the
		 * '<span class="ajax-trigger">', the submit input's class,
		 * being "action", and the submit input's name, being the action
		 *  as defined in the "add_action( 'wp_ajax_<action>'...." above.
		 * 
		 * As the form will be submitted through ajax I prevent the form
		 * from actually going anywhere by setting 'onclick="return false;"'.
		 *
		 * @since 0.1
		 *
		 * @param    none
		 * @return   string	The contents of our form.
		 */
		function build_example_form() {

			/**
			 * Buffer the form so we can return it in a variable.
			 */
			ob_start();
			?>

			<form id="ajax_example_admin_options-form" action="" method="post">
				<p>Type something in here:
				<input type="text" name="example-text" size="40" placeholder="This is the example-text field" />
				</p>

				<p>
				<select name="example-select">
					<option disabled selected value="">Example Select</option>
					<option value="bicycle">Bicycle</option>
					<option value="car">Car</option>
					<option value="train">Train</option>
				</select>
				</p>

				<p>
				Example Checkbox
				<input type="checkbox" name="example-checkbox" />
				</p>

				<p>
				<input type="hidden" name="caller" value="admin-options" />
				<input type="hidden" name="print_output" value="yes" />
				<input type="hidden" name="print_where" value="ajax-example-output" />
				<span class="ajax-trigger">
					<input type="submit" class="action" name="ajax_example_admin_options" value="Submit" onclick="return false;"/>
				</span>
				</p>
			</form>

			<p>
			<b>Response will go below here...</b><br /><br />
			<span id="ajax-example-output">
			</span>
			<br /><b>and above here.</b>
			</p>
<div>
			<form id="ajax_example_update_widget_text-form" action="return false;" method="post">
				<input type="hidden" name="caller" value="update-widget-text" />
				<input type="hidden" name="print_output" value="yes" />
				<input type="hidden" name="print_where" value="ajax-example-widget-output" />
				<span class="ajax-trigger">
					<input type="submit" class="action" name="ajax_example_update_widget_text" value="Update Widget Text" onclick="return false;"/>
				</span>
			</form>
</div>		
			<p>
			<b>Widget text from last widget submit goes here...</b><br /><br />
			<span id="ajax-example-widget-output">
			<?php echo self::get_option_value(); ?>
			</span>
			<br /><br /><b>and above here.</b>
			</p>

			<?php
			
			/**
			 * Grab the buffer, then return.
			 */
			$form = ob_get_clean();

			return( $form );
		}
					

		/**
		 * This is where we handle the ajax call.
		 *
		 * The admin-ajax.php script will call this function because we've added it to
		 * handle calls with action of 'example_call'.
		 *
		 * @since 0.1
		 *
		 * @param    none
		 * @return   none	Technically none. If you count a return from die() then
		 *			a string.
		 */
		function handle_example_call() {

			/**
			 * First, verify who's making the call with check_ajax_referer()
			 * We use the same string when we created the nonce above.
			 */
			if ( ! check_ajax_referer( 'ajax_example', 'security' ) ) {
				/**
				 * If we're in here things don't check out, so bail.
				 * The message is optional. :-)
				 */
				die( json_encode( array( 'message' => 'DOING IT WRONG!' ) ) );
			}

			/**
			 * Create an associative array containing the form data.
			 * With it we'll be able to get to the form data by using
			 * $post_data['<form field name>'] to get the value.
			 * With <input type="checkbox"> the value is either on or 
			 * the variable is not defined.
			 */
			parse_str( $_POST['data'], $post_data );

			/**
			 * This is where you'd do something interesting with the data.
			 *
			 * If the call came from the widget I'll push the text from
			 * the widget form into the database using 'update_option()'.
			 * Then I'll return the status; passed or failed and don't 
			 * print the output.
			 * 
			 * If the call came from the admin options page I'll just send 
			 * the post data back in a JSON response with a status
			 * and tell it to print the output ('print_output' => 'yes')
			 * and feed it the post data, <pre>formatted withe print_r();, in
			 * 'output'.
			 */

			/**
			 * Initialize a set of variables using data submitted from the
			 * form, if set.
			 */
			$action 		= isset( $post_data['action'] ) ? $post_data['action'] : '';
			$caller 		= isset( $post_data['caller'] ) ? $post_data['caller'] : '';
			$print_output 	= isset( $post_data['print_output'] ) ? $post_data['print_output'] : 'no';
			$print_where 	= isset( $post_data['print_where'] ) ? $post_data['print_where'] : '';
			$output 		= isset( $post_data['output'] ) ? $post_data['output'] : '';
			$status 		= isset( $post_data['status'] ) ? $post_data['status'] : 'failed';


			/**
			 * Called by the widget so just update the option in the database
			 * and return 'passed' if the update succeeded.
			 */
			if ( $caller == 'ajax-example-widget' ) {
				if ( isset($post_data['example-data'] ) ) {
					if ( update_option( 'ajax_example', $post_data['example-data'] ) )
						$status = 'passed';
				
				}
			}

			/**
			 * Called by the admin options form. I'm just going to return the
			 * post data array within <pre></pre> to get a nice view of what
			 * was submitted to this function.
			 */
			if ( $caller == 'admin-options' ) {
				$print_output = 'yes';
				$status = 'passed';
				
				/**
				 * Again, buffering the output.
				 */
				ob_start(); ?>
<pre>
<?php print_r( $post_data ); ?>
</pre>
				<?php
				$output = ob_get_clean();
			}
			
			/**
			 * Called by the update widget text button so set status passed and
			 * get the option value from the database.
			 */
			if ( $caller == 'update-widget-text' ) {
				$status = 'passed';
				$output = self::get_option_value();
			}
				

			/**
			 * Here I'm building an array to encode for a JSON response back to
			 * the ajax caller.
			 */
			$return = array( 
						'caller'		=> $caller,
						'status' 		=> $status, 
						'print_output'	=> $print_output,
						'print_where'	=> $print_where,
						'output'		=> $output );
						
			/**
			 * Using die sends the die statement and stops exits our script.
			 */
			die( json_encode( $return ) );

		}

			





	} // End Ajax_Example

endif; // if ( ! class_exists( 'Ajax_Example' ) {


/**
 * Almost all of this was copied straight from the example;
 * http://codex.wordpress.org/Widgets_API#Example
 * My additions/changes are commented.
 */
if ( ! class_exists( 'Ajax_Example_Widget' ) ) :
	class Ajax_Example_Widget extends WP_Widget {

		/**
	 	 * Register widget with WordPress.
	 	 */
		public function __construct() {
			parent::__construct(
	 			'ajax_example_widget', // Base ID
				'Ajax_Example_Widget', // Name
				array( 'description' => __( 'An Ajax Example Widget', 'ajax_example' ), ) // Args
			);
			
			/**
			 * Here I have to ensure that jquery has been loaded into 
			 * the <head></head>. The plugin is taking care of the 
			 * javascript for the ajax caller. I've found that even though
			 * the script is being loaded jQuery doesn't get loaded without
			 * specifically loading it.
			 */
			wp_enqueue_script( 'jquery' );
			
			/**
			 * 'ajaxurl' is only defined by default in the admin <head></head>,
			 * so it needs to be defined if it will be used. The plugin above
			 * uses it so make sure it's there. Otherwise the javascript will
			 * fire, but it won't know where to send the data.
			 */
			add_action( 'wp_head', array( __CLASS__, 'define_ajaxurl' ) );

		}

		/**
	 	 * Front-end display of widget.
	 	 *
	 	 * @see WP_Widget::widget()
	 	 *
	 	 * @param array $args     Widget arguments.
	 	 * @param array $instance Saved values from database.
	 	 */
		public function widget( $args, $instance ) {
			extract( $args );
			$title = apply_filters( 'Ajax Example Widget', $instance['title'] );


			/**
			 * Again note the four items; form id, span class, and input submit 
			 * class and name.
			 */
			echo $before_widget;
			if ( ! empty( $title ) )
				echo $before_title . $title . $after_title;
				?>

				<form id="ajax_example_widget-form" action="" method="post">
					Ajax Example:
					<input type="text" name="example-data" placeholder="enter data" />
					<input type="hidden" name="caller" value="ajax-example-widget" />
					<input type="hidden" name="output" value="thank you" />
					<input type="hidden" name="print_output" value="yes" />
					<input type="hidden" name="print_where" value="alert" />
					<span class="ajax-trigger">
						<input type="submit" class="action" name="ajax_example_widget" value="Submit" onclick="return false;"/>
					</span>
				</form>

				<?php
			echo $after_widget;
		}

		/**
	 	 * Sanitize widget form values as they are saved.
	 	 *
	 	 * @see WP_Widget::update()
	 	 *
	 	 * @param array $new_instance Values just sent to be saved.
	 	 * @param array $old_instance Previously saved values from database.
	 	 *
	 	 * @return array Updated safe values to be saved.
	 	 */
		public function update( $new_instance, $old_instance ) {
			$instance = array();
			$instance['title'] = strip_tags( $new_instance['title'] );

			return $instance;
		}

		/**
	 	 * Back-end widget form.
	 	 *
	 	 * @see WP_Widget::form()
	 	 *
	 	 * @param array $instance Previously saved values from database.
	 	 */
		public function form( $instance ) {
			if ( isset( $instance[ 'title' ] ) ) {
				$title = $instance[ 'title' ];
			} else {
				$title = __( 'New title', 'text_domain' );
			}
			?>
			<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</p>
			<?php 
		}

			
		/**
		 * Make sure to define ajaxurl, need outside the admin area.
		 *
		 * Again this is just for our ajax caller to be able to use 'ajaxurl'.
		 *
		 * @param	none
		 * @return	none
		 */
		function define_ajaxurl() {
			?>
			<script type="text/javascript">
			var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>'
			</script>
			<?php
		}



	
	} // End Ajax_Example_Widget
endif;

/**
 * Since I'm using the options table I want to initialize my option.
 * And since it really don't need to be cached I say no. However,
 * if your plugin/widget does a lot of interacting with the options
 * or any other table you may want it cached.
 */
register_activation_hook( __FILE__, array('Ajax_Example', 'install') );

/**
 * A deinstall to remove my option from the options table.
 */
register_deactivation_hook( __FILE__, array('Ajax_Example', 'deinstall') );

/**
 * Call the plugin class init() function to get things going.
 */
Ajax_Example::init();

/**
 *
 */
add_action( 'widgets_init', create_function( '', 'register_widget( "Ajax_Example_Widget" );' ) );

?>
