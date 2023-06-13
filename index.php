<?php
/*
Plugin Name: Was This Article Helpful?
Description: Simple article feedback plugin.
Version: 1.0.2
Author: WaspThemes
Author URI: https://yellowpencil.waspthemes.com
*/



// Installation plugin
function wthf_activate() {

	// Add default options
	add_option('wthf_types', '["post","page"]');
	add_option('wthf_question_text', 'Was this article helpful?');
	add_option('wthf_yes_text', 'Yes');
	add_option('wthf_no_text', 'No');
	add_option('wthf_thank_text', 'Thanks for your feedback!');

}

register_activation_hook( __FILE__, 'wthf_activate' );



// unistallation plugin
function wthf_uninstall(){

	// delete options
	delete_option('wthf_types');
	delete_option('wthf_question_text');
	delete_option('wthf_yes_text');
	delete_option('wthf_no_text');
	delete_option('wthf_thank_text');

	// Delete custom fields
	global $wpdb;
	$table = $wpdb->prefix.'postmeta';
	$wpdb->delete ($table, array('meta_key' => '_wthf_no'));
	$wpdb->delete ($table, array('meta_key' => '_wthf_yes'));

}

register_uninstall_hook( __FILE__, 'wthf_uninstall' );



// Adds "was this helpful" after the content
function wthf_after_post_content($content){

	// Read selected post types
	$selected_post_types = json_decode(get_option("wthf_types"));

	// show on only selected post types
	if(is_singular($selected_post_types)){

		// Get post id
		$post_id = get_the_ID();

		// Dont show if already voted
		if(!isset($_COOKIE["helpful_id_".$post_id])){

	    	$content .= '<div id="was-this-helpful" data-post-id="'.$post_id.'" data-thank-text="'.get_option("wthf_thank_text").'"><div id="wthf-title">'.get_option("wthf_question_text").'</div><div id="wthf-yes-no"><span data-value="1">'.get_option("wthf_yes_text").'</span><span data-value="0">'.get_option("wthf_no_text").'</span></div></div>';

		}

	}

    return $content;

}

add_filter( "the_content", "wthf_after_post_content", 10000);



// Adds script and styles
function wthf_style_scripts(){

	// Read selected post types
	$selected_post_types = json_decode(get_option("wthf_types"));

	// show on only selected post types
	if(is_singular($selected_post_types)){

    	wp_enqueue_style('wthf-style', plugins_url('/css/style.css', __FILE__));
    	wp_enqueue_script('wthf-script', plugins_url('/js/script.js', __FILE__), array('jquery'), '1.0', TRUE);
   		wp_add_inline_script('wthf-script', 'var nonce_wthf = "'.wp_create_nonce("wthf_nonce").'";var ajaxurl = "' . admin_url('admin-ajax.php') . '";', TRUE);

	}

}

add_action( 'wp_enqueue_scripts', 'wthf_style_scripts');



// Ajax callback for yes-no
function wthf_ajax_callback() {

	// Check Nonce
	if(!wp_verify_nonce($_REQUEST['nonce'], "wthf_nonce")) {
		exit("No naughty business please.");
	}

	// Get posts
	$post_id = intval($_REQUEST['id']);
	$value = intval($_REQUEST['val']);

	$value_name = "_wthf_no";
	if($value == "1"){
		$value_name = "_wthf_yes";
	}

	// Cookie check
	if(isset($_COOKIE["helpful_id_".$post_id])){
		exit("No naughty business please.");
	}

	// Get 
	$current_post_value = get_post_meta($post_id, $value_name, true);

	// Make it zero if empty
	if(empty($current_post_value)){
		$current_post_value = 0;
	}

	// Update value
	$new_value = $current_post_value + 1;

	// Update post meta
	update_post_meta($post_id, $value_name, $new_value);

	// Die WP
	wp_die();

}

add_action("wp_ajax_wthf_ajax", "wthf_ajax_callback");
add_action("wp_ajax_nopriv_wthf_ajax", "wthf_ajax_callback");



// Adds custom column to admin
function wthf_admin_columns($columns) {
    return array_merge($columns, array('helpful' => 'Helpful'));
}



// Custom column content
function wthf_realestate_column($column, $post_id) {

	// Variables
	$positive_value = intval(get_post_meta($post_id, "_wthf_yes", true));
	$negative_value = intval(get_post_meta($post_id, "_wthf_no", true));

	// Total
	$total = $positive_value + $negative_value;

	if($total > 0){
		$ratio = intval($positive_value * 100 / $total);
	}

	// helpful ration
	if($column == 'helpful'){

		if($total > 0){
			echo "<strong style='display:block;'>%" . $ratio . "</strong>";
			echo "<em style='display:block;color:rgba(0,0,0,.55);'>".$positive_value . " helpful" . " / ".$negative_value." not helpful</em>";
			echo "<div style='margin-top: 5px;width:100%;max-width:100px;background:rgba(0,0,0,.12);line-height:0px;font-size:0px;border-radius:3px;'><span style='width:".$ratio."%;background:rgba(0,0,0,.55);height:4px;display:inline-block;border-radius:3px;'></span></div>";
		}else{
			echo "—";
		}

	}

}



// Adds post type support
function wthf_post_type_support(){

	// Get selected post types
	$selected_post_types = get_option("wthf_types");

	// Read selected post types
	$selected_type_array = json_decode($selected_post_types);

	// loop selected type
	if(!empty($selected_type_array)){

		foreach ($selected_type_array as $selected_type) {
			
			add_filter('manage_'.$selected_type.'_posts_columns', 'wthf_admin_columns');
			add_action('manage_'.$selected_type.'_posts_custom_column', 'wthf_realestate_column', 10, 2);

		}

	}

}

add_action("init", "wthf_post_type_support");



// Register option page
function wthf_register_options_page(){
  add_options_page('Helpful Plugin Options', 'Helpful', 'manage_options', 'wthf', 'wthf_options_page');
}

add_action('admin_menu', 'wthf_register_options_page');



// Option page settings
function wthf_options_page() {

	// If isset
	if(isset($_POST['wthf_options_nonce'])){

		// Check Nonce
		if(wp_verify_nonce($_POST['wthf_options_nonce'], "wthf_options_nonce")) {

			// Update options
			update_option('wthf_types', json_encode(array_values($_POST['wthf_types'])));
			update_option('wthf_question_text', sanitize_text_field($_POST["wthf_question_text"]));
			update_option('wthf_yes_text', sanitize_text_field($_POST["wthf_yes_text"]));
			update_option('wthf_no_text', sanitize_text_field($_POST["wthf_no_text"]));
			update_option('wthf_thank_text', sanitize_text_field($_POST["wthf_thank_text"]));

			// Settings saved
			echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong>Settings saved.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';

		}
	}
	
	?>
	<div class="wrap">

	<h2>Helpful Options</h2>

	<p>"Was this article helpful" widget will automatically appear at the end of the articles. Please select the post types that you want to show this widget.</p>
	<p>Use this shortcode <code>[was_this_article_helpful]</code> to show the widget at anywhere.</p>

	<form method="post" action="options-general.php?page=wthf">

		<input type="hidden" value="<?php echo wp_create_nonce("wthf_options_nonce"); ?>" name="wthf_options_nonce" />

		<table class="form-table">

			<tr>
			<th scope="row"><label for="wthf_post_types">Post Types</label></th>
			<td>
				<?php

				// Post Types
				$post_types = get_post_types(array('public' => true), 'names');
				$selected_post_types = get_option("wthf_types");

				// Read selected post types
				$selected_type_array = json_decode($selected_post_types);

				// Foreach
				foreach ($post_types as $post_type) {

					// Skip Attachment
					if($post_type == 'attachment'){
						continue;
					}

					// Get value
					$checkbox = '';
					if(!empty($selected_type_array)){
						if(in_array($post_type, $selected_type_array)){
							$checkbox = ' checked';
						}
					}

					// print inputs
					echo '<label for="'.$post_type.'" style="margin-right:18px;"><input'.$checkbox.' name="wthf_types[]" type="checkbox" id="'.$post_type.'" value="'.$post_type.'">'.$post_type.'</label>';

				}

				?>
			</td>
			</tr>

			<tr>
			<th scope="row"><label for="wthf_question_text">Question</label></th>
			<td><input type="text" placeholder="Was this article helpful?" class="regular-text" id="wthf_question_text" name="wthf_question_text" value="<?php echo get_option('wthf_question_text'); ?>" /></td>
			</tr>

			<tr>
			<tr>
			<th scope="row"><label for="wthf_yes_text">Positive Answer</label></th>
			<td><input type="text" placeholder="Yes" class="regular-text" id="wthf_yes_text" name="wthf_yes_text" value="<?php echo get_option('wthf_yes_text'); ?>" /></td>
			</tr>

			<tr>
			<th scope="row"><label for="wthf_no_text">Negative Answer</label></th>
			<td><input type="text" placeholder="No" class="regular-text" id="wthf_no_text" name="wthf_no_text" value="<?php echo get_option('wthf_no_text'); ?>" /></td>
			</tr>

			<tr>
			<th scope="row"><label for="wthf_thank_text">Thank You Message</label></th>
			<td><input type="text" placeholder="Thanks for your feedback!" class="regular-text" id="wthf_thank_text" name="wthf_thank_text" value="<?php echo get_option('wthf_thank_text'); ?>" /></td>
			</tr>

		</table>

		<p><strong>You can customize the helpful widget by using <a target="_blank" href="https://yellowpencil.waspthemes.com/?utm_source=helpful-plugin&utm_medium=link&utm_campaign=option-page">YellowPencil: Visual CSS Style Editor plugin</a>.</strong></p>

		<?php submit_button(); ?>

	</form>
	
	</div>
<?php

}





function wthf_shortcode() {
	
	// Get post id
	$post_id = get_the_ID();

	$content = "";

	// Dont show if already voted
	if(!isset($_COOKIE["helpful_id_".$post_id])){

		// Enqueue style and scripts
		wp_enqueue_style('wthf-style', plugins_url('/css/style.css', __FILE__));
		wp_enqueue_script('wthf-script', plugins_url('/js/script.js', __FILE__), array('jquery'), '1.0', TRUE);
		wp_add_inline_script('wthf-script', 'var nonce_wthf = "'.wp_create_nonce("wthf_nonce").'";var ajaxurl = "' . admin_url('admin-ajax.php') . '";', TRUE);

		// The widget markup
	    $content = '<div id="was-this-helpful" data-post-id="'.$post_id.'" data-thank-text="'.get_option("wthf_thank_text").'"><div id="wthf-title">'.get_option("wthf_question_text").'</div><div id="wthf-yes-no"><span data-value="1">'.get_option("wthf_yes_text").'</span><span data-value="0">'.get_option("wthf_no_text").'</span></div></div>';

	}

	return $content;

}

add_shortcode( 'was_this_article_helpful', 'wthf_shortcode' );
