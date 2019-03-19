<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class CF_Woocommerce_Product
 */
class CF_Woocommerce_Product extends Caldera_Forms_Processor_Processor implements Caldera_Forms_Processor_Interface_Process {

	public function pre_processor( array $config, array $form, $proccesid ){

        /**
         * Setup $this->data_object 
         */
		$this->set_data_object_initial( $config, $form );
        
        /**
         * At this point errors would be beacuse of missing requirments
         */
		$errors = $this->data_object->get_errors();
		if ( ! empty( $errors ) ) {
			return $errors;
		}

		/**
         * Get all processor field values as an array
         */
        $values = $this->data_object->get_values();
        
        if( isset( $config['processor_id'] ) && ! empty( $config['processor_id'] ) ) {
            $processor_id = $config['processor_id'];
            if( is_array( $form['processors'] ) && count( $form['processors'] ) > 0 ) {
                $processor = $form['processors'][ $processor_id];
                if( !empty( $processor['type'] ) && $processor['type'] == 'woo-caldera-product' ) {

                    $fields = $processor['config'];
                    if( is_array( $fields ) && count( $fields ) > 0 ) {
                        
                        $product_title              = Caldera_Forms::get_field_data( $fields['product_title'], $form );
                        $product_description        = Caldera_Forms::get_field_data( $fields['product_description'], $form );
                        $product_price              = Caldera_Forms::get_field_data( $fields['product_price'], $form );
                        $product_image              = Caldera_Forms::get_field_data( $fields['product_image'], $form );

                        $product_short_description  = Caldera_Forms::get_field_data( $fields['product_short_description'], $form );
                        $product_categories         = Caldera_Forms::get_field_data( $fields['product_category'], $form );
                        $product_tags               = Caldera_Forms::get_field_data( $fields['product_tags'], $form );
                        if( ! is_array( $product_tags ) ) {
                            $product_tags = [ $product_tags ];
                        }
                        if( ! is_array( $product_categories ) ) {
                            $product_categories = [ $product_categories ];
                        }

                        if( isset( $fields['product_quantity'] ) && !empty( $fields['product_quantity'] ) ) {
                            $product_quantity           = Caldera_Forms::get_field_data( $fields['product_quantity'], $form );
                        } else {
                            $product_quantity           = 1;
                        }
                        
                        $product_stock  = $fields['product_stock'];
                        
                        try {
                            $sku = str_replace( ' ','-', $product_title.'-'.time() );
                            $objProduct = new WC_Product();
                            $objProduct->set_name( $product_title );
                            $objProduct->set_status( 'pending' );
                            $objProduct->set_featured( FALSE );
                            $objProduct->set_catalog_visibility( 'visible' );
                            $objProduct->set_description( $product_description );
                            $objProduct->set_short_description( $product_short_description );
                            $objProduct->set_sku( $sku );
                            $objProduct->set_price( floatval( $product_price ) );
                            $objProduct->set_regular_price( floatval( $product_price ) );
                            $objProduct->set_sale_price($product_price);
                            
                            if( isset( $product_stock ) && strtolower( $product_stock ) == 'yes' ) {
                                $objProduct->set_manage_stock(true);
                                $objProduct->set_stock_status('instock'); 
                                $objProduct->set_stock_quantity( intval( $product_quantity ) );
                            } else {
                                $objProduct->set_manage_stock(false);
                            }
                            
                            $objProduct->set_category_ids($product_categories);
                            $objProduct->set_tag_ids($product_tags);
                            $new_product_id = $objProduct->save();

                            if( $new_product_id ) {
                                $image_url        = $product_image;
                                $image_name       = wp_basename( $image_url );
                                $upload_dir       = wp_upload_dir();
                                $image_data       = file_get_contents($image_url);
                                $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name );
                                $filename         = basename( $unique_file_name );

                                /**
                                 * Check folder permission and define file location
                                 */
                                if( wp_mkdir_p( $upload_dir['path'] ) ) {
                                    $file = $upload_dir['path'] . '/' . $filename;
                                } else {
                                    $file = $upload_dir['basedir'] . '/' . $filename;
                                }

                                /**
                                 * Create the image  file on the server
                                 */
                                file_put_contents( $file, $image_data );

                                /**
                                 * Check image file type
                                 */
                                $wp_filetype = wp_check_filetype( $filename, null );

                                /**
                                 * Set attachment data
                                 */
                                $attachment = array(
                                    'post_mime_type' => $wp_filetype['type'],
                                    'post_title'     => sanitize_file_name( $filename ),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit'
                                );

                                /**
                                 * Create the attachment
                                 */
                                $attach_id = wp_insert_attachment( $attachment, $file, $new_product_id );

                                /**
                                 * Include image.php
                                 */
                                require_once(ABSPATH . 'wp-admin/includes/image.php');

                                /**
                                 * Define attachment metadata
                                 */
                                $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

                                /**
                                 * Assign metadata to attachment
                                 */
                                wp_update_attachment_metadata( $attach_id, $attach_data );

                                /**
                                 * And finally assign featured image to post
                                 */
                                set_post_thumbnail( $new_product_id, $attach_id );
                            }

                            
                        }
                        catch( Exception $exception ) {
                            $this->data_object->add_error( __( $exception->getMessage(), 'woo_cal_addon' ) );
                        }
                        
                    } else {
                        $this->data_object->add_error( __( 'Invalid Form', 'woo_cal_addon' ) );
                    }
                }
            }
        }
        
        $errors = $this->data_object->get_errors();
        if ( ! empty( $errors ) ) {
			return $errors;
		}
        
        /**
         * Before ending this method, store the processor data in the transient
         */
		$this->setup_transata( $proccesid );
	}



	/**
	 *  Process callback
	 *
	 * @param array $config Processor config
	 * @param array $form Form config
	 * @param string $proccesid Process ID
	 *
	 * @return array
	 */
	public function processor( array $config, array $form, $proccesid ){

	}
	
}
