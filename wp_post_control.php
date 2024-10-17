<?php

/*

Plugin Name: WP Post Control

Plugin URI: 

Description: Plugin for user management. Allows for restricting non-administrative users posts to specific categories.

Version: 1.0

Author: Joe Shaffer

Author URI: 

License: BSD 3.0

Text Domain: wp_post_control

*/



function restrict_user_categories(){
	$exclusions = '';
	$categories = get_categories();
	$user_string = get_user_meta(get_current_user_id(), 'allowed_categories', TRUE);
	$user_categories = explode(',', $user_string);
	
	//If this allowed_categories field is empty or does not exist, then no exclusions
	if($user_string === ""){
		return $exclusions;
	}
	//Otherwise compare available categories with set of allowed categories. Exclude categories not in user_categories.
	$cat_exclude = array_diff($categories, $user_categories); 
	 if (!in_array('administrator',  wp_get_current_user()->roles)) {
		 if(!empty($cat_exclude)){
			$exclusions .= ' AND t.term_id NOT IN (';
			foreach($cat_exclude as $exclude) {
				$exclusions .= $exclude.',';
			}
			$exclusions = substr($exclusions, 0, -1); // Removing the last comma
        	$exclusions .= ')';
		 }
    }
	return $exclusions;	
}
add_filter( 'list_terms_exclusions', 'restrict_user_categories', 20 );


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

add_action('edit_user_profile', 'add_category_restrictions_to_user_panel');

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
add_action('profile_update', 'save_category_restrictions_to_user_panel');
	

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
	if(strpos($_SERVER['HTTP_REFERRER'], 'edit-question') !== false) {
		//Action to perform if post edited
	}
	else {
			$headers = 'From: "From Name <from@email.com>' . "\r\n" . 'Reply-To: from@email.com' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
			$headers .= "Content-Transfer-Encoding: 8bit\n";
			$headers .= "Content-Type: text/html; charset=UTF-8\n";
			$headers .= 'MIME-Version: 1.0' . "\r\n";
			
			$to = 'to@email.com';
			$subject = '[TAG]' . $post->post_title;
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
			
			wp_mail($to, $subject, $message, $headers);
	
	}
}
//add_action( 'publish_post', 'send_mail_on_post_publish', 10, 3 );
