<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class CF_Woocommerce_Checkout
 */
class CF_Woocommerce_Checkout extends Caldera_Forms_Processor_Processor implements Caldera_Forms_Processor_Interface_Process {

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
        if( isset( $config['processor_id'] ) && !empty( $config['processor_id'] ) ) {
            $processor_id = $config['processor_id'];
            if( is_array( $form['processors'] ) && count( $form['processors'] ) > 0 ) {
                $processor = $form['processors'][ $processor_id];
                if( !empty( $processor['type'] ) && $processor['type'] == 'woo-caldera-checkout' ) {
                    $fields = $processor[ 'config' ];
                    if( is_array( $fields ) && count( $fields ) > 0 ) { 
                        $attached_product_ids = $values[ 'attached_product_ids' ] ; //Caldera_Forms::get_field_data( $fields[ 'attached_product_ids' ], $form );
                        $products = explode( ',', $attached_product_ids );
                        
                        try {

                            $order = wc_create_order();

                            foreach( $products as $product_id ) {
                                $order->add_product( get_product( $product_id ), 2 );
                            }

                            $user_id = get_current_user_id();

                            $billing_first_name = get_user_meta( $user_id, 'billing_first_name', true );
                            $billing_last_name  = get_user_meta( $user_id, 'billing_last_name', true );
                            $billing_address_1  = get_user_meta( $user_id, 'billing_address_1', true ); 
                            $billing_address_2  = get_user_meta( $user_id, 'billing_address_2', true );
                            $billing_city       = get_user_meta( $user_id, 'billing_city', true );
                            $billing_postcode   = get_user_meta( $user_id, 'billing_postcode', true );
                            $billing_country    = get_user_meta( $user_id, 'billing_country', true );
                            $billing_state      = get_user_meta( $user_id, 'billing_state', true );
                            $billing_company    = get_user_meta( $user_id, 'billing_company', true );
                            $billing_email      = get_user_meta( $user_id, 'billing_email', true );
                            $billing_phone      = get_user_meta( $user_id, 'billing_phone', true );

                            $billing_address = array(
                                'first_name' => $billing_first_name,
                                'last_name'  => $billing_last_name,
                                'company'    => $billing_company,
                                'email'      => $billing_email,
                                'phone'      => $billing_phone,
                                'address_1'  => $billing_address_1,
                                'address_2'  => $billing_address_2, 
                                'city'       => $billing_city,
                                'state'      => $billing_state,
                                'postcode'   => $billing_postcode,
                                'country'    => $billing_country
                            );

                            $order->set_address( $billing_address, 'billing' );

                            $shipping_first_name = get_user_meta( $user_id, 'shipping_first_name', true );
                            $shipping_last_name  = get_user_meta( $user_id, 'shipping_last_name', true );
                            $shipping_address_1  = get_user_meta( $user_id, 'shipping_address_1', true ); 
                            $shipping_address_2  = get_user_meta( $user_id, 'shipping_address_2', true );
                            $shipping_city       = get_user_meta( $user_id, 'shipping_city', true );
                            $shipping_postcode   = get_user_meta( $user_id, 'shipping_postcode', true );
                            $shipping_country    = get_user_meta( $user_id, 'shipping_country', true );
                            $shipping_state      = get_user_meta( $user_id, 'shipping_state', true );
                            $shipping_company    = get_user_meta( $user_id, 'shipping_company', true );
                            $shipping_email      = get_user_meta( $user_id, 'shipping_email', true );
                            $shipping_phone      = get_user_meta( $user_id, 'shipping_phone', true );
                            
                            $shipping_address = array(
                                'first_name' => $shipping_first_name,
                                'last_name'  => $shipping_last_name,
                                'company'    => $shipping_company,
                                'email'      => $shipping_email,
                                'phone'      => $shipping_phone,
                                'address_1'  => $shipping_address_1,
                                'address_2'  => $shipping_address_2, 
                                'city'       => $shipping_city,
                                'state'      => $shipping_state,
                                'postcode'   => $shipping_postcode,
                                'country'    => $shipping_country
                            );
                            $order->set_address( $shipping_address, 'shipping' );

                            $order->calculate_totals();
                            $order->update_status("wc-pending", __( 'Imported order', 'woo_cal_addon' ), TRUE);
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