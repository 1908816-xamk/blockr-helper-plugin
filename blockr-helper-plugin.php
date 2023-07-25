<?php
/*
 * Plugin Name: Blockr Helper Plugin
 * Plugin URI:
 * description: This tiny helper plugin is meant to use with "Blockr" Vue application by Henri Tikkanen to extend native REST API functionalities on the WordPress side.
 * Version: 1.0.2
 * Author: Henri Tikkanen
 * Author URI: http://www.henritikkanen.info
 * License: License: GPLv2
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

// Add custom category image
function blockr_include_script() {

	if ( ! did_action( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
	}
	wp_enqueue_script( 'blockr-uploader', plugins_url('/js/uploader.js', __FILE__));
}
add_action( 'admin_enqueue_scripts', 'blockr_include_script' );

function taxonomy_add_custom_field() {
	?>
	<div class="form-field term-image-wrap">
	<label for="cat-image"><?php _e( 'Image' ); ?></label>
	<p><a href="#" class="aw_upload_image_button button button-secondary"><?php _e('Upload Image'); ?></a></p>
	<input type="text" name="category_image" id="cat-image" value="" size="40" />
	</div>
	<?php
}
add_action( 'attachment_category_add_form_fields', 'taxonomy_add_custom_field');

function taxonomy_edit_custom_field($taxonomy) {

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
add_action( 'attachment_category_edit_form_fields', 'taxonomy_edit_custom_field');

function save_taxonomy_custom_meta_field( $term_id ) {
    if ( isset( $_POST['category_image'] ) ) {
        update_term_meta($term_id, 'category_image', $_POST['category_image']);
    }
}
add_action( 'edited_attachment_category', 'save_taxonomy_custom_meta_field', 10, 2 );
add_action( 'create_attachment_category', 'save_taxonomy_custom_meta_field', 10, 2 );

// Add category image REST API route
function register_rest_field_for_category_image() {
    register_rest_field( 'attachment_category',
        'category_image',
        array(
            'get_callback'    => 'image_get_term_meta_field',
            'update_callback' => 'image_update_term_meta_field',
            'schema' => null,
        )
    );
}
add_action( 'rest_api_init', 'register_rest_field_for_category_image' );

function image_update_term_meta_field( $value, $object, $field_name ) {
    if ( ! $value || ! is_string( $value ) ) {
        return;
    }
    return update_term_meta( $object->ID, $field_name, $value );
}

function image_get_term_meta_field( $object, $field_name, $request ) {
    return get_term_meta( $object[ 'id' ], $field_name, true );
}

// Show attachment category in REST API
function show_taxonomy_in_rest() {
    $attachment_category_args = get_taxonomy( 'attachment_category' );
    $attachment_category_args->show_in_rest = true;
    register_taxonomy( 'attachment', 'attachment_category', (array) $attachment_category_args );
}
add_action( 'init', 'show_taxonomy_in_rest', 11 );

// Search media REST API route
function register_media_search() {
	register_rest_route( 'wp/v2', 'media/search/(?P<term>.+)', array(
        	'methods' => WP_REST_SERVER::READABLE,
			'callback' => 'media_search_results'
    ));
}
add_action( 'rest_api_init', 'register_media_search' );

function media_search_results($data) {

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
            $image['id']          = $id;
            $image['source_url']  = $meta->guid;
            $image['title']['rendered'] = $meta->post_title;
            $image['caption']     = $meta->post_excerpt;
            $image['description'] = $meta->post_content;

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
function media_likes() {
	register_rest_route( 'wp/v2', 'media/likes/(?P<id>[\d]+)',
		array(
			array(
        	'methods' => 'GET',
			'callback' => 'media_get_likes',
    		),
			array(
			'methods' => 'POST',
			'callback' => 'media_add_likes'
    		)
		)
	);
}
add_action( 'rest_api_init', 'media_likes' );

function media_get_likes($data) {

   	$media_id = $data['id'];
	$value = get_post_meta( $media_id, 'likes', true );

	return $value;
}

function media_add_likes($data) {

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
function block_status() {
	register_rest_route( 'wp/v2', 'media/blockchain/(?P<id>[\d]+)',
		array(
        	'methods' => 'GET',
			'callback' => 'get_block_status',
		));
}
add_action( 'rest_api_init', 'block_status' );

function get_block_status($data) {
	$media_id = $data['id'];
	$value = get_post_meta( $media_id, 'blockchain', true );
	return $value;
}
