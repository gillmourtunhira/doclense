<?php
/**
 * Document-Lense
 *
 * @package     Document-Lense
 * @author      Gillmour Tunhira
 * @copyright   2021 CodeCraft-ZW
 * @license     GPL-2.0-or-later
 *
*/
/*
Plugin Name: Document-Lense
Plugin URI:  https://github.com/gillmourtunhira/doclense
Description: This is a file download plugin, when activated allows files to be uploaded in the backend, and downloaded in the frontend. Files can be pdf, doc,docx
Author: Gillmour Tunhira
Version: 1.0.0
Author URI: https://github.com/gillmourtunhira
Text Domain: doclense
*/
/*
Document-Lense is free software: you can redistribute it and/or modify it under the terms of GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version.

Document-Lense Form is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with Contact-Lense Form.
*/

/* Exit if directly accessed */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define variable for path to this plugin file.
define( 'DL_LOCATION', dirname( __FILE__ ) );
define( 'DL_LOCATION_URL' , plugins_url( '', __FILE__ ) );
define( 'DL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 *
 */
class DocumentLense
{

  public function __construct()
  {
    // Create Meta Boxes in Custom Post Type centric_documents
    add_action( 'add_meta_boxes', array( $this, 'dl_download_meta_boxes' ) );

    // Add shortcode
    add_shortcode( 'doc-lense', array( $this, 'dl_load_shortcode' ) );

    // Hook for adding admin menus
    add_action( 'admin_menu', 'dl_register_ref_page' );

    add_action( 'init', 'dl_create_documents_taxonomies', 0 );

    add_filter( 'post_type_link', 'dl_filter_post_type_link', 10, 2 );

    // Define default term in the custom taxonomy
    add_action( 'save_post', 'default_taxonomy_term', 100, 2 );

    add_filter( 'rest_route_for_post', 'dl_rest_route_cpt', 10, 2 );

    // Load scripts
    add_action( 'wp_footer', array( $this, 'dl_load_scripts' ) );

    // Add Assets (js, css)
    add_action( 'wp_enqueue_scripts', array( $this, 'dl_load_assets' ) );

  }

  // Enqueue Scripts
  public function dl_load_assets()
  {
    wp_enqueue_style( 'doclense-css', DL_PLUGIN_URL . 'css/doclense.css', [], time(), 'all' );
    wp_enqueue_script( 'doclense-js', DL_PLUGIN_URL . 'js/doclense.js', ['jquery'], time(), 1 );
  }


  /**
   * Custom type meta boxes.
   *
   * return @void
   */
   public function dl_download_meta_boxes(){
     add_meta_box( 'upload_file', 'Attach File', 'dl_render_meta_box_content', 'centric_documents', 'advanced', 'high' );
   }

   /**
   * Render Meta Box content.
   *
   * @param WP_Post $post The post object.
   */
   public function dl_load_shortcode(){
     ?>
     <div class="quicklinks__layout">
       <div class="popular__forms--links">
         <h3>Download popular forms</h3>
         <div class="popular__forms--download">
           <div id="select__form--input">
          		<select name="zlcforms" id="forms-container"></select>
          	</div>
            <button id="load-forms" class="docdownload" type="submit" value="Download">Download Form</button>
         </div>
       </div>
     </div>
     <?php
   }

  // Run Script
  public function dl_load_scripts(){
    ?>
      <script>
        const formsBtnLoad = document.querySelector('#load-forms');
        const formsContainerData = document.querySelector('#forms-container');

          window.addEventListener( 'load', (e) => {
            var ourReq = new XMLHttpRequest();
            ourReq.open( 'GET', 'http://zlc.local/wp-json/wp/v2/documents' );
            ourReq.onload = function() {
              if ( ourReq.status >= 200 && ourReq.status < 400 ){
                var data = JSON.parse( ourReq.responseText );
                // console.log(data);
                selectFormHTML(data);
              } else {
                console.log( 'Cannot load content' );
              }
            };

            ourReq.onerror = function(){
              console.log( 'Connection error' );
            };

            ourReq.send();

          } );

        function selectFormHTML( postsData ){
          var ourHTMLString = '';

          for( i = 0; i < postsData.length; i++ ) {
            ourHTMLString += '<option>' + postsData[i].title.rendered + '</option>';
          }
          formsContainerData.innerHTML = ourHTMLString;
        }
      </script>
    <?php
  }

}

new DocumentLense;

/**
* Register a rest route for custom post type
*/
function dl_rest_route_cpt( $route, $post ){
  if ( $post->post_type === 'centric_documents' ) {
    $route = '/wp/v2/documents/' . $post->ID;
  }
  return $route;
}

/**
 * Register a custom post type.
 *
 * return @void
 */
function dl_download_form_post_type() {
   $labels = array(
     'name'           => _x( 'Documents', 'Post type general name', 'doclense' ),
     'singular'       => _x( 'Document', 'Post type singular name', 'doclense' ),
     'menu_name'      => _x( 'Documents', 'Admin Menu Text', 'lands' ),
     'name_admin_bar' => _x( 'Document', 'Add New on Toolbar', 'doclense' ),
     'add_new'        => __( 'Add New', 'doclense' ),
     'add_new_item'   => __( 'Add New Document', 'doclense' ),
     'new_item'       => __( 'New Document' ),
     'edit_item'      => __( 'Edit Document', 'doclense' ),
     'view_item'      => __( 'View Document', 'doclense' ),
     'all_items'      => __( 'All Documents', 'doclense' ),
   );
   $args   = array(
     'labels'          => $labels,
     'public'          => true,
     'has_archive'     => 'centric_documents',
     'rewrite'         => array(
       'slug'          => 'centric_documents/%documentcat%',
       'with_front'    => FALSE
     ),
     'hierarchical'    => false,
     'show_in_rest'    => true,
     'rest_base'       => 'documents',
     'rest_controller_class'  =>  'WP_REST_Posts_Controller',
     'supports'        => array( 'title', 'editor' ),
     'capability_type' => 'post',
     'menu_icon'       => 'dashicons-text-page',
   );
   register_post_type( 'centric_documents', $args );
 }
 add_action( 'init', 'dl_download_form_post_type' );

 /**
 * Custom Documents Columns.
 *
 * @param WP_Post $post The post object.
 */
 function dl_doclense_columns( $columns ){
   $newColumns = array();
   $newColumns['title'] = 'File Title';
   $newColumns['details'] = 'Excerpt Details';
   $newColumns['document'] = 'Document Url';
   $newColumns['date'] = 'Date';

   return $newColumns;
 }
 add_filter( 'manage_centric_documents_posts_columns', 'dl_doclense_columns' );

 // Manage custom column data
 add_action( 'manage_centric_documents_posts_custom_column', 'dl_doclense_custom_column_data', 10, 2 );
 function dl_doclense_custom_column_data( $column, $post_id ){
   switch ( $column ) {
     case 'details':
       echo get_the_excerpt();
       break;
    case 'document':
      $url = '';
      // Get attached file.
      $file = get_post_meta(get_the_ID(), 'dl_render_meta_box_content', true);
      echo $file['url'];
      break;
     default:
       // code...
       break;
   }
 }

 /**
 * Render Meta Box content.
 *
 * @param WP_Post $post The post object.
 */
 function dl_render_meta_box_content( $post ){
    // Add an once field so we can check for it later.
    wp_nonce_field( DL_LOCATION, 'dl_render_attachment_nonce' );

    $value = get_post_meta(get_the_ID(), 'dl_render_meta_box_content', true);

    $html = '<p class="description">';
    $html .= 'Upload your PDF here.';
    $html .= '</p>';
    $html .= '<input type="file" id="dl_render_meta_box_content" name="dl_render_meta_box_content" value="'. esc_attr($value) .'" size="25">';
    echo $html;
  }

 /**
 * Save the meta data when the post is saved.
 *
 * @param int $post_id The ID of the post being saved.
 */
 function dl_save_custom_meta_data( $id ){
   // Security Verification
   if ( !wp_verify_nonce( $_POST['dl_render_attachment_nonce'], DL_LOCATION ) ) {
     return $id;
   }

   if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
     return $id;
   }

   if ( 'page' == $__POST['post_type'] ) {
     if( !current_user_can('edit_page', $id) ) {
       return $id;
     } else {
       if( !current_user_can('edit_page', $id) ){
         return $id;
       }
     }
   }

   // Make sure the file array isn't empty
   if ( !empty( $_FILES['dl_render_meta_box_content']['name'] ) ) {
     // Setup the array of supported file types. In this case, it's just PDF.
     $supported_types = array( 'application/pdf' );

     // Get the file type of the upload
     $arr_file_type = wp_check_filetype( basename( $_FILES['dl_render_meta_box_content']['name'] ) );
     $uploaded_type = $arr_file_type['type'];

     // Check if the type is supported. If not, throw an error.
     if( in_array( $uploaded_type, $supported_types ) ) {
       // Use the WordPress API to upload the file
       $upload = wp_upload_bits( $_FILES['dl_render_meta_box_content']['name'], null, file_get_contents( $_FILES['dl_render_meta_box_content']['tmp_name'] ) );

       if( isset( $upload['error'] ) && $upload['error'] != 0 ) {
         wp_die( 'There was an error uploading your file. This error is: ' . $upload['error'] );
       } else {
         add_post_meta( $id, 'dl_render_meta_box_content', $upload );
         update_post_meta( $id, 'dl_render_meta_box_content', $upload );
       }
     } else {
       wp_die( "The file type that you've uploaded is not a PDF." );
     }
   }

 }
add_action( 'save_post', 'dl_save_custom_meta_data' );

 /**
 * Append the enctype attribute to the post editor form.
 *
 */
 function update_edit_form() {
     echo ' enctype="multipart/form-data"';
 }
add_action( 'post_edit_form_tag', 'update_edit_form' );

 /**
 * Adds a submenu page under a custom post type
 *
 */
 function dl_register_ref_page(){
   add_submenu_page(
     'edit.php?post_type=centric_documents',
     __( 'Documents Details', 'doclense' ),
     __( 'Details', 'doclense' ),
     'manage_options',
     'documents',
     'dl_ref_page_callback'
   );
 }

 function dl_ref_page_callback(){
  require_once( DL_LOCATION . '/inc/templates/doclense-admin.php' );
 }

 /**
  * Create taxonomies for the post type "centric_documents".
  *
  */
 function dl_create_documents_taxonomies(){
   // Add new taxonomy, make it hierarchical
   $labels = array(
     'name'         =>  _x( 'Documents Categories', 'taxonomy general name', 'doclense' ),
     'singular_name'  =>  _x( 'Document Category', 'taxonomy singular name', 'doclense' ),
     'search_items'   =>  __( 'Search Documents Categories', 'doclense' ),
     'all_items'      =>  __( 'All Documents Categories', 'doclense' ),
     'edit_item'      =>  __( 'Edit Documents Categories', 'doclense' ),
     'update_item'    =>  __( 'Update Document Category', 'doclense' ),
     'add_new_item'   =>  __( 'Add New Document Category', 'doclense' ),
     'new_item_name'  =>  __( 'New Document Category Name', 'doclense' ),
     'menu_name'      =>  __( 'Document Categories', 'doclense' ),
   );

   $args = array(
     'hierarchical'   =>  true,
     'labels'         =>  $labels,
     'show_ui'        =>  true,
     'show_admin_column'  => true,
     'query_var'          =>  true,
     'rewrite'            =>  array(
       'slug'       => 'documentcat',
       'with_front' =>  false,
     ),
   );

   register_taxonomy( 'documentcat', array( 'centric_documents' ), $args );
 }

 /**
  * Changing the permalink
  *
  */
  function dl_filter_post_type_link( $link, $post ){
    if( $post->post_type !== 'centric_documents' ){
      return $link;
    }

    if( $cats = get_the_terms( $post->ID, 'documentcat' ) ){
      $link = str_replace( '%documentcat%', array_pop( $cats )->slug, $link );

      return $link;
    }
  }

  /**
   * Default term in the custom Taxonomy
   *
   */
   function default_taxonomy_term( $post_id, $post ){
     if( 'publish' === $post->post_status ){
       $defaults = array(
         'documentcat'  =>  array( 'other' ),
       );
       $taxonomies = get_object_taxonomies( $post->post_type );
       foreach ( (array) $taxonomies as $taxonomy ) {
         $terms = wp_get_post_terms( $post_id, $taxonomy );
         if( empty($terms) && array_key_exists( $taxonomy, $defaults ) ){
           wp_set_object_terms( $post_id, $defaults[$taxonomy], $taxonomy );
         }
       }
     }
   }
