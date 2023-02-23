<?php
if(!defined('ABSPATH')){die('Do not open this file directly.');}
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/post.php');

add_action('rest_api_init', 'open_brain_api');
function open_brain_api()
{
	register_rest_route('open-brain/v1', 'api', [
	'methods'  => 'POST',
	'callback' => 'open_brain_api_result'
	]);
	register_rest_route('open-brain/v1', 'image', [
	'methods'  => 'POST',
	'callback' => 'open_brain_save_image'
	]);
	register_rest_route('open-brain/v1', 'content', [
	'methods'  => 'POST',
	'callback' => 'open_brain_save_content'
	]);
}
function open_brain_save_image($data)
{
	$response = array('status'=>"false", "location"=>"");
	$body = $data->get_body();
	$body = stripslashes($body);
	$body = json_decode($body);
	if(isset($body->src))
	{
		$image_url = $body->src;
		$image_title = "OPEN-BRAIN ".$body->title;
		$url_with_pseudo_extension = sanitize_url($image_url . '&?ext=.jpeg', array('http', 'https'));
		$media_id = media_sideload_image("$url_with_pseudo_extension",0,"$image_title","src");
		if (!is_wp_error( $media_id ) ) {
			$response = array('status'=>"true", "location"=>$media_id);
		}
	}

	return $response;
}
function open_brain_save_content($data)
{
	$response = array('status'=>"false", "location"=>"");
	$parameters = $data->get_params();
	$action = "";
	if(isset($parameters['action'])){$action = $parameters['action'];}
	$body = $data->get_body();
	$body = stripslashes($body);
	$body = json_decode($body);
	if(isset($body->src))
	{
		$content = $body->content;
		$title = esc_html($body->title);
		$status = $body->status;
		$type = $body->type;
		$prompt = $body->prompt;
		$post_data = array(
			'tags_input' => array("open-brain"),
			'post_content_filtered'=> sanitize_textarea_field(wp_strip_all_tags($content)),
			'post_excerpt' => $prompt,
			'post_title' => wp_strip_all_tags($title),
			'post_content' => $content,
			'post_status' => $status,
			'post_type' => $type
		);
		$post_id  = wp_insert_post($post_data, true);
		if (!is_wp_error($post_id) ) {
			$response = array('status'=>"true", "location"=>$post_id);
		}
	}
	return $response;
}
function open_brain_api_result($data)
{
	$parameters = $data->get_params();
	$action = "";
	if(isset($parameters['action'])){$action = $parameters['action'];}
	$body = $data->get_body();
  	switch ($action)
	{
		case 'images_generations':
			$url = "/images/generations";
			break;
		case 'completions':
			$url = "/completions";
			break;
		case 'completions_title':
			$url = "/completions";
			break;
		default:
			$url = "";
			wp_die(__('Error.',  'General'));
			break;
	}
	$open_brain_plugin = new open_brain_plugin();
	$open_brain_debug_mode = $open_brain_plugin->plugin_option_debug;
	$open_header =  array('Authorization' => 'Bearer '.$open_brain_plugin->plugin_option_api_key,'Content-Type' => 'application/json','Accept' => 'application/json');
	$open_body = $body;
	$open_option = array
	(
		'method'      => 'POST',
		'timeout'     => 45,
		'redirection' => 5,
		'httpversion' => '1.0',
		'blocking'    => true,
		'headers'     => $open_header,
		'body'        => $open_body,
		'cookies'     => array()
	);
	$response = wp_remote_post($open_brain_plugin->plugin_base_url.$url, $open_option);
	print_r($response['body']);
	//the_content(($response['body']));
}
?>