<?php

/*

Plugin Name: WP Post Control

Plugin URI: https://github.com/jjshaffer/wp_post_control/

Description: Plugin for user management. Allows for restricting non-administrative users posts to specific categories.

Version: 1.0

Author: Joe Shaffer

Author URI: 

License: BSD 3.0

Text Domain: wp_post_control

*/

function restrict_user_categories() { 
	$exclusions = '';
	$user_string = get_user_meta(get_current_user_id(), 'allowed_categories', TRUE);
	//echo "<script>console.log('Debug Exclusions2: " . $user_string . "' );</script>";
	
	if($user_string != ""){
		if (!in_array('administrator',  wp_get_current_user()->roles)) {
			$exclusions = ' AND t.term_id IN (' . $user_string . ')';
		}
	}    
	//echo "<script>console.log('Debug Exclusions: " . $exclusions . "' );</script>";
	return $exclusions;	
}
add_filter('list_terms_exclusions', 'restrict_user_categories', 10);


function add_category_restrictions_to_user_panel($user){
	$categories = get_categories();
	$user_string = get_user_meta($user->ID, 'allowed_categories', TRUE);
	$user_categories = explode(',', $user_string);

	$output = '
		<h3>User Post Restrictions</h3>

		<table class="form-table">
		<tr>
		<th><label for="allowed_categories">Allowed Post Categories</label></th>
		</tr>';

		foreach ($categories as $category){
			
			if ($user_string === ""){
				
				$output .= '<tr><td><input type="checkbox" id="' . $category->term_id . '_' . $category->name . '" name="allowedFormCheckbox[]" value="' . $category->term_id .'" checked>' . $category->name .'</td></tr>';
			}
			elseif (in_array($category->term_id,$user_categories)){
				$output .= '<tr><td><input type="checkbox" id="' . $category->term_id . '_' . $category->name . '" name="allowedFormCheckbox[]" value="' . $category->term_id .'" checked>' . $category->name .'</td></tr>';
			}
			else {
				$output .= '<tr><td><input type="checkbox" id="' . $category->term_id . '_' . $category->name . '" name="allowedFormCheckbox[]" value="' . $category->term_id .'">' . $category->name .'</td></tr>';
			}
		}
	
	$output .='</table>';
																									 
	echo $output;
}
add_action('edit_user_profile', 'add_category_restrictions_to_user_panel', 10, 2);




function save_category_restrictions_to_user_panel($user_id){
	$allowed_categories = array();
	
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update-user_' . $user_id ) ) {
        return;
    }
    
    if (!current_user_can( 'edit_user', $user_id ) ) { 
        return false; 
    }
	
	$checkboxes = $_POST['allowedFormCheckbox'];
	
	$N = count($checkboxes);
	for($i=0; $i < $N; $i++){
		array_push($allowed_categories, $checkboxes[$i]);
	}
	
	if($allowed_categories){
    	update_user_meta( $user_id, 'allowed_categories', implode(',', $allowed_categories) );
	}
}
add_action('profile_update', 'save_category_restrictions_to_user_panel', 10, 2);

function add_email_settings_to_categories_edit_panel($term){
	
	$t_id = $term->term_id;
 	echo "<script>console.log('Debug Exclusions: " . $t_id . "' );</script>";
  
    $send_category_email = get_term_meta($term_id = $t_id, $key = 'send_category_email', TRUE);
    $category_email = get_term_meta($term_id = $t_id, $key = 'category_email', TRUE);
	
	echo "<script>console.log('Debug Exclusions: " . $send_category_email . "' );</script>";
	echo "<script>console.log('Debug Exclusions: " . $category_email . "' );</script>";

	$output = '
		<table class="form-table">
		<tr><td><h2>Email Settings</h2></td></tr>';
	if($send_category_email){
	$output .= '<tr><td><label for="send_category_email">Send Email on Publish</label></td><td><select name="send_category_email" id="send_category_email">
  		<option value="0">No</option>
  		<option selected value="1">Yes</option>
		</select></td>';
	}
	else {
		$output .= '<tr><td><label for="send_category_email">Send Email on Publish</label></td><td><select name="send_category_email" id="send_category_email">
  		<option selected value="0">No</option>
  		<option value="1">Yes</option>
		</select></td>';
	}
	
	$output .= '<tr><td><label for="category_email">Send to Email</label></td><td><input type="email" id="category_email" name="category_email" value="' . $category_email . '"></td></tr>';
	$output .='</table><br>';
																									 
	echo $output;
}
add_action('category_edit_form_fields', 'add_email_settings_to_categories_edit_panel', 10, 2);
add_action('category_add_form_fields', 'add_email_settings_to_categories_edit_panel', 10, 2);

function save_email_settings_to_categories_panel($term_id){
	$t_id = $term_id;
	if (isset( $_POST['send_category_email'] ) && in_array($_POST['send_category_email'], array(0,1)) ) {
      update_term_meta($term_id = $t_id, $meta_key = 'send_category_email', $meta_value = $_POST['send_category_email']);
    }
	
	if (isset( $_POST['category_email'] ) && !is_null($_POST['category_email']) ) {
        $email = sanitize_text_field($_POST['category_email']);
		update_term_meta($term_id = $t_id, $meta_key = 'category_email', $meta_value = $email);
    }
}
add_action('edited_category', 'save_email_settings_to_categories_panel', 10, 2);
add_action('create_category', 'save_email_settings_to_categories_panel', 10, 2);


function force_category_for_users($post_id, $post) {
	$user_category = array(11); //Retrieve this from wherever you want. The number references wp_term_taxonomy.term_taxonomy_id for the appropriate category
	if (!in_array('administrator',  wp_get_current_user()->roles)) {
		if(!empty($user_category)){
       		wp_set_post_categories($post_id, $user_category);
		} 
	}
} 
//add_action( 'publish_post', 'force_category_for_users', 10, 3 );

function default_category_for_users($default_category, $post) {
	$user_category = 11; //Retrieve this from wherever you want. The number references wp_term_taxonomy.term_taxonomy_id for the appropriate category
	if (!in_array('administrator',  wp_get_current_user()->roles)) {
		//if(!is_null($user_category)){
       		return $user_category;
		//}
	}
		else{
			return $default_category;
		}
} 
//add_filter( 'default_category', 'default_category_for_users', 10, 2 ); //Not currently working as expected


function send_mail_on_post_publish($post_id, $post){
	$categories = wp_get_post_categories($post_id);
	
	
	$emails = '';
	$first_cat = '';
	foreach($categories as $category){
		$cat = get_category($category);
		
		if ($first_cat === ""){
			$first_cat = $cat->name;
		}
		$send_category_email = get_term_meta($term_id = $category, $key = 'send_category_email', TRUE);
    	$category_email = get_term_meta($term_id = $category, $key = 'category_email', TRUE);
		
		if($send_category_email){
			$emails .= $category_email . ',';
		}
	}
	
		
	if(strpos($_SERVER['HTTP_REFERRER'], 'edit-question') !== false) {
		//Action to perform if post edited
		return;
	}
	elseif(wp_is_post_revision( $post)){
		return;
	}
	elseif(wp_is_post_autosave( $post)){
		return;
	}
	else {
		//$headers = 'From: "From Name <from@email.com>' . "\r\n" . 'Reply-To: from@email.com' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
		$headers = 'X-Mailer: PHP/' . phpversion();
		$headers .= "Content-Transfer-Encoding: 8bit\n";
		$headers .= "Content-Type: text/html; charset=UTF-8\n";
		$headers .= 'MIME-Version: 1.0' . "\r\n";
		
		$to = $emails;
		if($first_cat != ""){
			$subject = '[' . $first_cat . '] ' . $post->post_title;
		}
		else{
			$subject = $post->post_title;
		}
		
		$post_content = apply_filters('the_content',$post->post_content);
		$post_content = wp_kses_post($post_content);
	
		$message = '
					<html>
					<head>
					  <style>
						body { font-family: Arial, sans-serif; }
						h1 { color: #333; }
						p { line-height: 1.6; }
					  </style>
					</head>
					<body>
					<h1>' . $post->post_title . '</h1>
					' . get_userdata($post->post_author)->first_name . ' ' . get_userdata($post->post_author)->last_name . '<br>
					' . $post->post_date . '<br>
					<a href="' . get_permalink($post_id) . '">See More</a>
					  ' . $post_content . '
					</body></html>';
		
		$key='_post_counted';
		if(! filter_var(get_post_meta($post_id, $key, true), FILTER_VALIDATE_BOOLEAN)){
			wp_mail($to, $subject, $message, $headers);
			update_post_meta($post_id, $key, true);
		}
	
	}
	
}
add_action( 'publish_post', 'send_mail_on_post_publish', 10, 3 );
