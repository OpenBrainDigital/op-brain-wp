<?php
/*
Plugin Name: OPEN-BRAIN
Plugin URI: https://openbrain.digital/services/wp_openbrain_plugin
Description: A WordPress plugin that uses OpenAI to create high-quality content and improve user experience.
Version: 0.5.0
Requires at least: 6.0.0
Requires PHP: 7.0.0
Author: OpenBrain
Author URI: https://openbrain.digital
Developer: Farid SanieePour
Developer URI: https://www.linkedin.com/in/faridsaniee
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
if(!defined('ABSPATH')){die('Do not open this file directly.');}
require_once( plugin_dir_path( __FILE__ ) . 'api.inc.php' );
if(!class_exists('open_brain_plugin') )
{
	class open_brain_plugin
	{
		protected static $instance = null;
		public $version = 0;
		public $plugin_title = 0;
		public $plugin_url = '';
		public $plugin_dir = '';
		public $plugin_name = '';
		public $plugin_site = "";
		public $plugin_translate_scope = '';
		public $plugin_base_url = "";
		public $plugin_base_url_openbrain = "https://api.openbrain.digital/v1";
		public $plugin_base_url_openai = "https://api.openai.com/v1";
		public $plugin_instant = "";
		public $plugin_option_api_key = "";
		public $plugin_option_debug = "";
		public $plugin_option_direct = "";
		public $plugin_icon_white = "";
		public $plugin_icon_black = "";
		public function __construct()
		{

			$this->version = $this->get_plugin_data('version');
			$this->plugin_name = strtolower($this->get_plugin_data('name'));
			$this->plugin_site = $this->get_plugin_data('site');
			$this->plugin_dir = plugin_dir_path(__FILE__);
			$this->plugin_url = plugin_dir_url(__FILE__);
			$this->plugin_translate_scope = strtolower($this->plugin_name)."-plugin";
			$this->plugin_instant = strtolower($this->plugin_name)."-plugin";
			$this->plugin_title = __($this->get_plugin_data('name'), $this->plugin_translate_scope);
			$this->load_options();
			add_action('admin_menu', array($this, 'admin_menu'));
			add_action('admin_init', array($this, 'do_all_actions'));
			add_filter('plugin_row_meta', array($this, 'plugin_link_meta'), 10, 2);
			add_filter('plugin_action_links', array($this, 'plugin_link_action'), 10, 2);
	  		add_action('init', array($this, 'plugin_style_js'));
			$svg_white = base64_encode(file_get_contents($this->plugin_url .'assets/img/icon-orange.svg'));
			$svg_black = base64_encode(file_get_contents($this->plugin_url .'assets/img/icon-black.svg'));
			$this->plugin_icon_white = "data:image/svg+xml;base64,$svg_white";
			$this->plugin_icon_black = "data:image/svg+xml;base64,$svg_black";
		}
		function plugin_link_action( $actions, $plugin_file ) 
		{
		   	static $plugin;
		   	if (!isset($plugin)){$plugin = plugin_basename(__FILE__);}
		   	if ($plugin == $plugin_file)
		   	{
		 	  	$site_web = array('settings' => '<a href="admin.php?page=open-brain">' . __('Settings') . '</a>');
		      	$actions = array_merge($actions,$site_web);
		 	  	$site_web = array('Donate' => '<a style="color:#FF6058" target="_blank" href="https://openbrain.digital/?utm_source=donate_link&utm_medium=web&utm_campaign=plugin_'.$this->version.'"><strong>' . __('Donate', 'General') . '</strong></a>');
		      	$actions = array_merge($actions,$site_web);
			}
		 	return $actions;
		}
		static function getInstance()
		{
			if (!is_a(self::$instance, "open_brain_plugin_instanse"))
			{
				self::$instance = new open_brain_plugin();
			}
			return self::$instance;
		}
		function plugin_link_meta( $actions, $plugin_file ) 
		{
		   	static $plugin;
		   	if (!isset($plugin)){$plugin = plugin_basename(__FILE__);}
		   	if ($plugin == $plugin_file)
		   	{
		 	  	$site_web = array('Panel' => '<a target="_blank"  href="https://panel.openbrain.digital/?utm_source=panel_link&utm_medium=web&utm_campaign=plugin_'.$this->version.'">Panel</a>');
		      	$actions = array_merge($actions,$site_web);
		 	  	$site_web = array('Developer' => '<a target="_blank" href="https://www.linkedin.com/in/faridsaniee/">About Farid SanieePour</a>');
		      	$actions = array_merge($actions,$site_web);

			}
		 	return $actions;
		}
		static function get_plugin_data($type = "")
		{
			$type = strtolower($type);
			$result = array();
			switch ($type)
			{
				case 'version':
					$plugin_data = get_file_data(__FILE__, array('version' => 'Version'), '');
					$result = $plugin_data['version'];
					break;
				case 'name':
					$plugin_data = get_file_data(__FILE__, array('name' => 'Plugin Name'), '');
					$result = $plugin_data['name'];
					break;
				case 'site':
					$plugin_data = get_file_data(__FILE__, array('site' => 'Plugin URI'), '');
					$result = $plugin_data['site'];
					break;
				default:
					$result = "undefined";
					break;
			}
			return $result;
		}
	    function plugin_style_js()
	    {
			wp_enqueue_style($this->plugin_name, $this->plugin_url .'assets/css/style.css');
			wp_enqueue_script($this->plugin_name, $this->plugin_url .'assets/js/script.js',"",$this->version,true);
	    }
		private function load_options()
		{
			$open_brain_data = get_option($this->plugin_name."-data");
		    if(isset($open_brain_data['api_key'])){$this->plugin_option_api_key = (sanitize_text_field($open_brain_data['api_key']));}
		    if(isset($open_brain_data['debug']))
		    {
		    	$this->plugin_option_debug = $open_brain_data['debug'];
			}
		    if(isset($open_brain_data['direct']))
		    {
		    	$this->plugin_option_direct = $open_brain_data['direct'];
		    }

			$options = get_option($this->plugin_instant, array());
			$change = false;
			if (!isset($options['meta']))
			{
				$options['meta'] = array('first_version' => $this->version, 'first_install' => current_time('timestamp', true), 'reset_count' => 0);
			  	$change = true;
			}
			if (!isset($options['dismissed_notices'])) 
			{
				$options['dismissed_notices'] = array();
				$change = true;
			}
			if (!isset($options['last_run'])) 
			{
				$options['last_run'] = array();
				$change = true;
			}
			if (!isset($options['options'])) 
			{
				$options['options'] = array();
				$change = true;
			}
			if ($change) 
			{
				update_option($this->plugin_instant, $options, true);
			}
			$this->options = $options;
			return $options;
		}
		function load_textdomain()
		{
			load_plugin_textdomain($this->plugin_translate_scope);
		}
		function admin_menu()
		{
			add_menu_page($this->plugin_title,$this->plugin_title,'manage_options', $this->plugin_name, array($this,'open_brain_main'),$this->plugin_icon_white,100);
			add_submenu_page($this->plugin_name, $this->plugin_name, 'Settings', 'manage_options', $this->plugin_name, array($this,'open_brain_main'));
			add_submenu_page($this->plugin_name, $this->plugin_name, 'Image', 'manage_options', 'open_brain_image', array($this,'open_brain_main'));	
			add_submenu_page($this->plugin_name, $this->plugin_name, 'Content', 'manage_options', 'open_brain_content', array($this,'open_brain_main'));
		}
	  	function open_brain_main()
	  	{
		    if (!current_user_can('administrator')) {wp_die(__('Sorry, you are not allowed to access this page.',  'General'));}
			$current_screen = get_current_screen();
			if (!empty($current_screen->base))
			{
				switch ($current_screen->base)
				{
					case 'toplevel_page_open-brain':
						$plugin_page_title = __('Settings');
						$plugin_page_icon = "dashicons-admin-generic";
						break;
					case 'open-brain_page_open_brain_image':
						$plugin_page_title = __('Images');
						$plugin_page_icon = "dashicons-format-image";
						break;
					case 'open-brain_page_open_brain_content':
						$plugin_page_title = __('Content');
						$plugin_page_icon = "dashicons-format-aside";
						break;
				}
			}
		    $output = 
		    '
				<div class="wrap">
					<div class="text-center" id="wpbrain_title">
						<p style="margin:0px" class="plugin_icon_orange page_title_icon"></p>
						<h1 style="padding:0px" >'.$this->plugin_title.'</h1>
						<h6 style="padding:0px; margin:0px"> Version:' . $this->version . '</h6>
					</div>
					<div>
						<p class="description">
							<span class="dashicons '.$plugin_page_icon.'"></span>
							'.$plugin_page_title. '
						</p>
					</div>
					<hr/>
				</div>
			';
			if($current_screen->base == "toplevel_page_open-brain")
			{
				$output .= $this->func_page_main();
			}
			if($current_screen->base == "open-brain_page_open_brain_image")
			{
				$output .= $this->func_page_image();
			}
			if($current_screen->base == "open-brain_page_open_brain_content")
			{
				$output .= $this->func_page_content();
			}
			print_r($output);
  		}
  		function func_page_main()
  		{
		    $open_brain_debug_mode_checked = "";
		    $open_brain_direct_mode_checked = "";
		    if($this->plugin_option_debug){$open_brain_debug_mode_checked = "checked";}
		    if($this->plugin_option_direct){$open_brain_direct_mode_checked = "checked";}
		    if($_POST)
		    {
		    	$open_brain_debug_mode_checked = "";
		    	$open_brain_direct_mode_checked = "";
		    	$this->plugin_option_debug = 0;
		    	$this->plugin_option_direct = 0;
				if (isset($_POST['open_brain_debug_mode'])) {
					$this->plugin_option_debug = intval($_POST['open_brain_debug_mode']);
				    if ($this->plugin_option_debug == 1) {$open_brain_debug_mode_checked = "checked";}

				}
				if (isset($_POST['open_brain_direct_mode'])) {
					$this->plugin_option_direct = intval($_POST['open_brain_direct_mode']);
				    if ($this->plugin_option_direct == 1) {$open_brain_direct_mode_checked = "checked";}
				}
		    	$this->plugin_option_api_key = sanitize_text_field($_POST['open_brain_api_key']);
				update_option($this->plugin_name."-data", array(
				  'api_key' => $this->plugin_option_api_key,
				  'debug' => $this->plugin_option_debug,
				  'direct' => $this->plugin_option_direct,
				));

		    }
		    $caption_savechange = __("Save Changes");
  			$caption_debug = __('Debug');
			$output = '
				<div class="wrap">
					<form method="post" novalidate="novalidate">
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="open_brain_api_key">API Key</label>
									</th>
									<td>
										<input name="open_brain_api_key" type="text" id="open_brain_api_key" value="'.$this->plugin_option_api_key.'" class="regular-text">
										<p class="description" id="tagline-description">
											Enter the API KEY here if you want to get new one 
											<a href="https://openbrain.digital/?utm_source=settings_link&utm_medium=web&utm_campaign=plugin_'.$this->version.'">
												click here!
											</a>
										</p>
									</td>
								</tr>
								<tr>
									<th scope="row">'.$caption_debug.'</th>
									<td> 
										<fieldset>
											<legend class="screen-reader-text">
												<span>Debug</span>
											</legend>
											<label for="open_brain_debug_mode">
												<input value="1" name="open_brain_debug_mode" type="checkbox" id="open_brain_debug_mode" '.$open_brain_debug_mode_checked.'>
												Enable Debug mode
											</label>
										</fieldset>
									</td>
								</tr>
								<tr>
									<th scope="row">Direct</th>
									<td> 
										<fieldset>
											<legend class="screen-reader-text">
												<span>Direct</span>
											</legend>
											<label for="open_brain_direct_mode">
												<input value="1" name="open_brain_direct_mode" type="checkbox" id="open_brain_direct_mode" '.$open_brain_direct_mode_checked.'>
												Enable Direct mode
											</label>
										</fieldset>
									</td>
								</tr>
							</tbody>
						</table>
						<p class="submit">
							<input type="submit" name="submit" id="submit" class="button button-primary" value="'.$caption_savechange.'">
						</p>
					</form>
				</div>
			';
			return $output;
  		}
  		function func_page_image()
  		{
  			$site_title = get_bloginfo('name');
  			$open_brain_image_prompt = "$site_title";
  			$open_brain_image_number = 3;
  			$caption_description = __('Description');
  			$caption_fetch = __('Search');
  			$caption_size = __('Size');
  			$caption_number = __('Number');
			$output = '
				<div class="wrap">
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="open_brain_image_prompt">'.$caption_description.'</label>
								</th>
								<td>
									<input name="open_brain_image_prompt" type="text" id="open_brain_image_prompt" value="'.$open_brain_image_prompt.'" class="regular-text">
									<p class="description" id="tagline-description">
										Enter the Keyword or Description
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="open_brain_image_prompt">'.$caption_number.'</label>
								</th>
								<td>
									<input type="number" name="open_brain_image_prompt" id="open_brain_image_number" value="'.$open_brain_image_number.'" class="regular-text">
									<p class="description" id="tagline-description">
										Enter number of picture that you want
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="open_brain_image_prompt">'.$caption_size.'</label>
								</th>
								<td>
									<select name="open_brain_image_size" id="open_brain_image_size">
										<option selected="selected" value="256x256">256x256</option>
										<option value="512x512">512x512</option>
										<option value="1024x1024">1024x1024</option>
									</select>
									<p class="description" id="tagline-description">
										Select Size
									</p>
								</td>
							</tr>
						</tbody>
					</table>
					<p class="submit">
						<input type="button" id="fetch_image" class="button button-primary" value="'.$caption_fetch.'">
					</p>
				</div>
				<div class="wrap" id="open_brain_result_image">
				</div>
			';
			return $output;
  		}
  		function func_page_content()
  		{
			$post_statuses = get_post_stati();
			$output_page_status = "";
			foreach ($post_statuses as $post_status) {
			  $output_page_status.= '<option value="'.$post_status.'">'.$post_status.'</option>';}
			$page_types = get_post_types(array('labels' => true,'public'   => true,));
			$output_page_types = "";
			foreach ($page_types as $page_type){$output_page_types.= '<option value="'.$page_type.'">'.$page_type.'</option>';}
  			$open_brain_content_title = "";
  			$site_title = get_bloginfo('name');
  			$site_description = get_bloginfo('description');
  			$open_brain_content_prompt = "Write a post about $site_title";
  			$open_brain_content_token = 200;
  			$open_brain_content_temperatures = 0.5;
  			$caption_description = __('Description');
  			$caption_fetch = __('Search');
  			$caption_view = __('View');
  			$caption_save = __('Save');
  			$caption_title = __('Title');
  			$caption_status = __('Status');
  			$caption_type = __('Type');
  			$caption_excerpt = __('Excerpt');
  			$caption_body = __('Text');
			$output = '
				<div class="wrap">
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="open_brain_content_prompt">'.$caption_description.'</label>
								</th>
								<td>
									<input name="open_brain_content_prompt" type="text" id="open_brain_content_prompt" value="'.$open_brain_content_prompt.'" class="regular-text">
									<p class="description" id="tagline-description">
										Enter the Keyword or Description
									</p>
								</td>
							</tr>
							<tr class="d-none" style="display:none">
								<th scope="row">
									<label for="open_brain_content_token">Number of word</label>
								</th>
								<td>
									<input type="number" name="open_brain_content_token" id="open_brain_content_token" value="'.$open_brain_content_token.'" class="regular-text">
									<p class="description" id="tagline-description">
										Enter number of picture that you want
									</p>
								</td>
							</tr>
							<tr class="d-none" style="display:none">
								<th scope="row">
									<label for="open_brain_content_temperatures">Creativity</label>
								</th>
								<td>
									<input type="range" min="0" max="2" name="open_brain_content_temperatures" id="open_brain_content_temperatures" step="0.1" value="'.$open_brain_content_temperatures.'" class="regular-text">
									<p class="description" id="tagline-description">
										Select range of creativity
									</p>
								</td>
							</tr>
						</tbody>
					</table>
					<p class="submit" style="margin:0px;">
						<input type="button" id="fetch_content" class="button button-primary" value="'.$caption_fetch.'">
					</p>
				</div>
				<div class="wrap d-none" id="open_brain_result_content">
					<div class="bg-white" style="padding:10px">
						<div class="box_button">

							<label for="open_brain_content_status">'.$caption_status.'</label>
							<select name="open_brain_content_status" id="open_brain_content_status">
								'.$output_page_status.'
							</select>
							<label for="open_brain_content_type">'.$caption_type.'</label>
							<select name="open_brain_content_type" id="open_brain_content_type">
								'.$output_page_types.'
							</select>
							<input type="button" class="button button-primary add_content" value="'.$caption_save.'">
							<a href="" target="_blank" class="button button-primary view_content d-none">'.$caption_view.'</a>
						</div>
						<div>
							<label for="open_brain_content_title">'.$caption_title.'</label>
							<input name="open_brain_content_title" type="text" id="open_brain_content_title" value="'.$open_brain_content_title.'" class="large-text">
							<input type="button" class="button button-primary suggest_title" value="Suggest Title by AI">
							<p class="description" id="tagline-description">
							Enter Title of Post
							</p>
						</div>
						<div class="excerpt">
							<label for="open_brain_excerpt">'.$caption_excerpt.'</label>
							<textarea class="large-text code" rows ="3" cols="50" name="open_brain_excerpt" id="open_brain_excerpt"></textarea>
						</div>
						<label for="open_brain_body">'.$caption_body.'</label>
						<div class="content large-text" rows ="10" cols="50" name="open_brain_body" id="open_brain_body"></div>
					</div>
				</div>
			';

			return $output;
  		}

		function do_all_actions()
		{
			if(!current_user_can('administrator')) {return;}
		}
	}
}
if (is_admin())
{
	global $open_brain_plugin;
	$open_brain_plugin = open_brain_plugin::getInstance();
	add_action('plugins_loaded', array($open_brain_plugin, 'load_textdomain'));
	add_filter('plugin_action_links_', array($open_brain_plugin, 'plugin_link_action'));
	register_uninstall_hook(__FILE__, array('open_brain_plugin', 'uninstall'));
}
