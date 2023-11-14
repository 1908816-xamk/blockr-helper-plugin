<?php
/*
 * Plugin Name: Blockr Helper Plugin
 * Plugin URI:
 * Description: This helper plugin is meant to use with Blockr Photo App to extend some the native REST API functions in WordPress.
 * Version: 1.0.4
 * Requires PHP: 7.4
 * Author: Henri Tikkanen
 * Author URI: https://github.com/henritik/
 * License: License: GPLv2
 * Tested up to: WordPress 6.4.1
 */

defined( 'ABSPATH' ) or die();

// Add custom category image
function blockr_include_script() {

	if ( ! did_action( 'wp_enqueue_media' ) ) {
		wp_enqueue_media();
	}
	wp_enqueue_script( 'blockr-uploader', plugins_url('/js/uploader.js', __FILE__));
}
add_action( 'admin_enqueue_scripts', 'blockr_include_script' );

function blockr_taxonomy_add_custom_field() {
	?>
	<div class="form-field term-image-wrap">
	<label for="cat-image"><?php _e( 'Image' ); ?></label>
	<p><a href="#" class="aw_upload_image_button button button-secondary"><?php _e('Upload Image'); ?></a></p>
	<input type="text" name="category_image" id="cat-image" value="" size="40" />
	</div>
	<?php
}
add_action( 'attachment_category_add_form_fields', 'blockr_taxonomy_add_custom_field');

function blockr_taxonomy_edit_custom_field($taxonomy) {

	$image = get_term_meta($taxonomy->term_id, 'category_image', true);
	?>
	<tr class="form-field term-image-wrap">
		<th scope="row"><label for="category_image"><?php _e( 'Image' ); ?></label></th>
		<td>
			<p><a href="#" class="aw_upload_image_button button button-secondary"><?php _e('Upload Image'); ?></a></p><br/>
			<input type="text" name="category_image" id="cat-image" value="<?php echo $image; ?>" size="40" />
		</td>
	</tr>
	<?php
}
add_action( 'attachment_category_edit_form_fields', 'blockr_taxonomy_edit_custom_field');

function blockr_save_taxonomy_custom_meta_field( $term_id ) {
	if ( isset( $_POST['category_image'] ) ) {
		update_term_meta($term_id, 'category_image', $_POST['category_image']);
	}
}
add_action( 'edited_attachment_category', 'blockr_save_taxonomy_custom_meta_field', 10, 2 );
add_action( 'create_attachment_category', 'blockr_save_taxonomy_custom_meta_field', 10, 2 );

// Add category image REST API route
function blockr_register_rest_field_for_category_image() {
	register_rest_field( 'attachment_category',
		'category_image',
		array(
			'get_callback'    => 'blockr_image_get_term_meta_field',
			'update_callback' => 'blockr_image_update_term_meta_field',
			'permission_callback' => '__return_true'
		)
	);
}
add_action( 'rest_api_init', 'blockr_register_rest_field_for_category_image' );

function blockr_image_update_term_meta_field( $value, $object, $field_name ) {
	if ( ! $value || ! is_string( $value ) ) {
		return;
	}
	return update_term_meta( $object->ID, $field_name, $value );
}

function blockr_image_get_term_meta_field( $object, $field_name, $request ) {
	return get_term_meta( $object[ 'id' ], $field_name, true );
}

// Show attachment category in REST API
function blockr_show_taxonomy_in_rest() {
	$attachment_category_args = get_taxonomy( 'attachment_category' );
	$attachment_category_args->show_in_rest = true;
	register_taxonomy( 'attachment', 'attachment_category', (array) $attachment_category_args );
}
add_action( 'init', 'blockr_show_taxonomy_in_rest', 11 );

// Search media REST API route
function blockr_register_media_search() {
	register_rest_route( 'wp/v2', 'media/search/(?P<term>.+)', array(
			'methods' => WP_REST_SERVER::READABLE,
			'callback' => 'blockr_media_search_results',
			'permission_callback' => '__return_true'
	));
}
add_action( 'rest_api_init', 'blockr_register_media_search' );

function blockr_media_search_results($data) {
	
	$query = new WP_Query(array(
		'post_type' => 'attachment',
		'posts_per_page' => $data['per_page'],
		'post_status'    => 'inherit',
		'post_mime_type' => 'image',
		'paged' => $data['page'],
		's' => sanitize_text_field($data['term'])
	));
	
	$image_results = array();
	
	while( $query->have_posts() ) {
		$query->the_post();
		
		$image = array();
		$id = $query->post->ID;

		if ( ! empty( $id ) && $meta = get_post( $id ) ) {
			$image['id']          		= $id;
			$image['source_url']  		= $meta->guid;
			$image['title']['rendered'] 	= $meta->post_title;
			$image['caption']     		= $meta->post_excerpt;
			$image['description'] 		= $meta->post_content;
			$image['attachment_category'] 	= get_the_terms( $id, 'attachment_category' );

			if ( $sizes = get_intermediate_image_sizes() ) {   
				array_unshift( $sizes, 'full' );

				foreach ( $sizes as $size ) {
					$src = wp_get_attachment_image_src( $id, $size );
					$image['media_details']['sizes'][$size]['source_url']= $src[0];
					$image['media_details']['sizes'][$size]['width']  = $src[1];
					$image['media_details']['sizes'][$size]['height'] = $src[2];
				}
			} else {
				$image['sizes'] = null;
			}
		}
		array_push($image_results,
			$image
		);
	}
	return $image_results;
}

// Get and post likes REST API route
function blockr_media_likes() {
	register_rest_route( 'wp/v2', 'media/likes/(?P<id>[\d]+)',
		array(
			array(
			'methods' => WP_REST_SERVER::READABLE,
			'callback' => 'blockr_media_get_likes',
			'permission_callback' => '__return_true'
			),
			array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => 'blockr_media_add_likes',
			'permission_callback' => '__return_true'
			)
		)
	);
}
add_action( 'rest_api_init', 'blockr_media_likes' );

function blockr_media_get_likes($data) {

   	$media_id = $data['id'];
	$value = get_post_meta( $media_id, 'likes', true );

	return $value;
}

function blockr_media_add_likes($data) {

   	$media_id = $data['id'];
	$post = get_post($media_id);
	$old_value = get_post_meta( $media_id, 'likes', true );
		if ( $old_value ) {
			$value = $old_value + 1;
		} else {
			$value = 1;
		}
	update_post_meta( $media_id, 'likes' , $value, $old_value);
	return $value;
}

// Get blockchain status REST API route
function blockr_block_status() {
	register_rest_route( 'wp/v2', 'media/blockchain/(?P<id>[\d]+)',
		array(
			'methods' => 'GET',
			'callback' => 'blockr_get_block_status',
			'permission_callback' => '__return_true'
		));
}
add_action( 'rest_api_init', 'blockr_block_status' );

function blockr_get_block_status($data) {
	$media_id = $data['id'];
	$value = get_post_meta( $media_id, 'blockchain', true );
	return $value;
}
