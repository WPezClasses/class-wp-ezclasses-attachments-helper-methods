<?php
/*
*
*/
 
// No WP? Die! Now!!
if (!defined('ABSPATH')) {
	header( 'HTTP/1.0 403 Forbidden' );
    die();
}

include_once( ABSPATH . 'wp-admin/includes/image.php' ); 

if ( !class_exists('Class_WP_ezClasses_Attachments_Helper_Methods')) {
	class Class_WP_ezClasses_Attachments_Helper_Methods extends Class_WP_ezClasses_Master_Singleton {		

		protected function __construct(){
			parent::__construct();
		}
		
		public function ezc_init(){

		}
		
		/*
	     * Pass in an attachment_id (via $arr_args['attachment_id']) and wp_get_attachment_path() will return the path to the attachment
		 */
		public function wp_get_attachment_path($arr_args=array()) {
		
			if ( WP_ezMethods::array_pass($arr_args) && isset($arr_args['attachment_id']) && filter_var($arr_args['attachment_id'], FILTER_VALIDATE_INT)){
			
				$str_attachment_url = wp_get_attachment_url($arr_args['attachment_id']);
			
				if ( $str_attachment_url === false ) {
					return array('status' => false, 'msg' => 'ERROR: wp_get_attachment_url() for $arr_args[attachment_id] returned false. ', 'arr_args' => 'error');
				}
				
				// ref: http://codex.wordpress.org/Function_Reference/wp_upload_dir
				$arr_upload_dir = wp_upload_dir();
				$str_dir_content_and_upload = str_replace(ABSPATH , '', $arr_upload_dir['path'] );
				$str_dir_content_and_upload = str_replace($arr_upload_dir['subdir'] , '', $str_dir_content_and_upload );
				
				$str_yyyy_mm_file = str_replace($arr_upload_dir['baseurl'] , '', $str_attachment_url );
								
				return array('status' => true, 'msg' => 'success', 'arr_args' => $arr_args, 'attachment_path' => ABSPATH . $str_dir_content_and_upload . $str_yyyy_mm_file);
			
			}
			return array('status' => false, 'msg' => 'ERROR: array_pass($arr_args) failed || arr_args[attachment_id ] ! isset(). ', 'arr_args' => 'error');
		}
		
		/*
		 * Pass in an attachment_id (via $arr_args['attachment_id']) and return numerous attachment properties
		 *
		 * Inspired by: http://wordpress.org/ideas/topic/functions-to-get-an-attachments-caption-title-alt-description
		 */ 
		function wp_get_attachment( $arr_args=array() ) {
		
			if ( WP_ezMethods::array_pass($arr_args) && isset($arr_args['attachment_id']) && filter_var($arr_args['attachment_id'], FILTER_VALIDATE_INT)){

				$obj_attachment = get_post( $arr_args['attachment_id'] );
				
				if ( $obj_attachment === NULL ){
					return array('status' => false, 'msg' => 'ERROR: post not found for this arr_args[attachment_id] = ' . $arr_args['attachment_id'] . '. ', 'arr_args' => 'error');
				}
				
				$arr_path = $this->wp_get_attachment_path($arr_args);
				
				$int_attachment_post_author = $obj_attachment->post_author;
				$obj_attachment_userdata = get_userdata($int_attachment_post_author);
				
				$arr_attachment = array(
										'alt' 						=> get_post_meta( $obj_attachment->ID, '_wp_attachment_image_alt', true ),
										'caption' 					=> $obj_attachment->post_excerpt,
										'description' 				=> $obj_attachment->post_content,
										'href' 						=> get_permalink( $obj_attachment->ID ),
										'mime_type' 				=> $obj_attachment->post_mime_type,
										'path'						=> $arr_path['attachment_path'],
										'post_author' 				=> $obj_attachment->post_author,
										'post_author_display_name'	=> $obj_attachment_userdata->display_name,
										'post_date'					=> $obj_attachment->post_date, 
										'post_name'					=> $obj_attachment->post_name,
										'post_parent' 				=> $obj_attachment->post_parent,
										'post_parent_title' 		=> get_the_title($obj_attachment->post_parent),
										'src' 						=> $obj_attachment->guid,
										'title' 					=> $obj_attachment->post_title,
										'url'						=> wp_get_attachment_url($arr_args['attachment_id']),
									);
				return array('status' => true, 'msg' => 'success', 'arr_args' => $arr_args, 'attachment_properties' => $arr_attachment);
			}
			return array('status' => false, 'msg' => 'ERROR: array_pass($arr_args) failed || arr_args[attachment_id] ! isset(). ', 'arr_args' => 'error');
		}
		

		/*
		* pass an attachment_id and if it's an attachment, the method will return: path/file.ext
		*
		* Similar to WP's get_attached_file() but with some cool extras. Mainly you can specifiy which file size you want back (not just 'full')
		*
		* http://codex.wordpress.org/Function_Reference/get_attached_file
		*/
		public function get_attached_file_ez($arr_args = NULL) {
		
			if ( ! isset($arr_args['validate']) || ( isset($arr_args['validate']) && ! is_bool($arr_args['validate'])) ) {
				$arr_args['validate'] = true;
			} 
			
			if ( ! isset($arr_args['validate_unset']) || (isset($arr_args['validate_unset']) && ! is_bool($arr_args['validate_unset'])) ) {
				$arr_args['validate_unset'] = true;
			} 
			
			if ( $arr_args['validate']  ){
			
				$arr_validate_return = $this->get_attached_file_ez_validate($arr_args);
				if ( $arr_validate_return['status'] === true ) {
					$arr_args = $arr_validate_return['arr_args'];
					
				} else {
					return $arr_validate_return;		
				}
			}
					
			$arr_args = array_merge($this->get_attached_file_ez_defaults(),$arr_args);
			
			/*
			* if we're here then that means we have an attachment
			*/
		
			$obj_attachment_post = get_post($arr_args['attachment_id'], OBJECT);	
			$str_attachment_id = $obj_attachment_post->ID;
			
			
			if ( $arr_args['size'] == 'full' ) {

				return array('status' => true, 'msg' => 'success', 'arr_args' => $arr_args, 'get_attached_file' => get_attached_file($str_attachment_id, false ));

			} else {
				/*
				 * if the size != full then use a hybrid of the full attachment's [dirname] and the [basename] of the file size requested
				 */
				$arr_parse_attach_src = pathinfo(get_attached_file( $str_attachment_id, false ));
				$str_dirname = $arr_parse_attach_src['dirname']; 
				
				$arr_featured_img = wp_get_attachment_image_src($str_attachment_id, $arr_args['size']);
				$str_attach_src = $arr_featured_img[0];
				$arr_parse_attach_src = pathinfo($str_attach_src);
				if ( isset($arr_parse_attach_src['basename']) ){
					return array('status' => true, 'msg' => 'success', 'arr_args' => $arr_args, 'get_attached_file' => $str_dirname . '/' . $arr_parse_attach_src['basename']);
				} 
				return array('status' => false, 'msg' => 'ERROR: [basename] from pathinfo() !isset(). ', 'arr_args' => 'error');
			}
		} // close method: get_attached_file_ez
		
		
		/*
		* validation for method: path_with_attachment
		*/
		public function get_attached_file_ez_validate($arr_args = NULL) {
		
			if ( !is_array($arr_args) ){
				return array('status' => false, 'msg' => 'ERROR: arr_args !is_array(). ', 'arr_args' => 'error');
			}
			
			if ( ! isset($arr_args['attachment_id']) || ! filter_var($arr_args['attachment_id'], FILTER_VALIDATE_INT) ) {
				return array('status' => false, 'msg' => 'ERROR: arr_args[attachment_id] is required. ', 'arr_args' => 'error');
			}
			
			$obj_attachment_post = get_post($arr_args['attachment_id'], OBJECT);
			
			if ( !isset($obj_attachment_post) || !is_object($obj_attachment_post) ) {
				return array('status' => false, 'msg' => 'ERROR: get_post(arr_args[attachment_id]) did not return an object. ', 'arr_args' => 'error');
			}
		
			if ( isset($obj_attachment_post) && $obj_attachment_post->post_type != 'attachment' ){
				return array('status' => false, 'msg' => 'ERROR: arr_args[attachment_id] -> post_type != attachment. ', 'arr_args' => 'error');
			}
			
			if ( isset($arr_args['size']) && !in_array(strtolower($arr_args['size']), get_intermediate_image_sizes()) ){
				if ( $arr_args['validate_unset'] ) {
					unset($arr_args['size']);
					return array('status' => true, 'msg' => 'ERROR: arr_args[size] !in_array(get_intermediate_image_sizes()). ', 'arr_args' => $arr_args);
				} else {
					return array('status' => false, 'msg' => 'ERROR: arr_args[size] !in_array(get_intermediate_image_sizes()). ', 'arr_args' => 'error');
				}
			}
			
			//TODO if size is not set?

			return array('status' => true, 'msg' => 'success', 'arr_args' => $arr_args);
			
		}
		
		public function get_attached_file_ez_defaults($arr_args = NULL){
		
			$arr_defaults = array (
								'size'	=> 'full',
								);
			
			/*
			 * Does the MasterParent allow for the use of filters?
			 */
			if ( $this->_bool_ez_filters ){
				$arr_defaults_filter_override = apply_filters('filter_ezc_attachments_get_attached_file_ez_defaults', $arr_defaults);
				$arr_defaults = array_merge($arr_defaults, $arr_defaults_filter_override);
			}
			return $arr_defaults;				
		}
		

	} // END: class
} // END: if class exists
?>