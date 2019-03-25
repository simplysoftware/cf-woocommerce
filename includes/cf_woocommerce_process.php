<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class CF_Woocommerce_Process
 */
class CF_Woocommerce_Process {
    
    /**
     * Constructor function
     */
	public function __construct(  ){
        
        add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'show_price_table' ], 0 );

        add_action( 'caldera_forms_entry_saved', [ $this, 'save_entryid_after_forms_entry_saved' ], 10, 3 );

        add_action( 'caldera_forms_submit_complete', [ $this, 'caldera_forms_submit_complete_callback' ], 10, 4 );

        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_enteryid_to_cart_item'], 10, 3 );

        add_filter( 'woocommerce_get_item_data', [ $this, 'show_entry_after_review_order_item_name'], 10, 2 );

        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'update_order_item_meta'], 20, 4 );

        remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
        add_action( 'woocommerce_after_shop_loop_item', [ $this, 'loop_add_to_cart'], 10 );
    }
    
    /**
     * Override the loop add to cart template
     *
     * @return none
     */
    function loop_add_to_cart() {
        global $product;

		if ( $product ) {

            $prod_id = $product->get_ID();
            $form_id = get_post_meta( $prod_id, '_woo_caldera_pricing_table_id', true );
            if( !empty( $form_id ) ) {
                echo '<a href="'.get_permalink( $prod_id ).'" class="button">'.__( 'View More', 'woo_cal_addon' ).'</a>';
            } else {
                $defaults = array(
                    'quantity'   => 1,
                    'class'      => implode( ' ', array_filter( array(
                        'button',
                        'product_type_' . $product->get_type(),
                        $product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
                        $product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $product->is_in_stock() ? 'ajax_add_to_cart' : '',
                    ) ) ),
                    'attributes' => array(
                        'data-product_id'  => $product->get_id(),
                        'data-product_sku' => $product->get_sku(),
                        'aria-label'       => $product->add_to_cart_description(),
                        'rel'              => 'nofollow',
                    ),
                );
                $args = [];
                $args = apply_filters( 'woocommerce_loop_add_to_cart_args', wp_parse_args( $args, $defaults ), $product );
    
                if ( isset( $args['attributes']['aria-label'] ) ) {
                    $args['attributes']['aria-label'] = strip_tags( $args['attributes']['aria-label'] );
                }
    
                wc_get_template( 'loop/add-to-cart.php', $args );
            }
			
		}
    }

    /**
     * Override the caldera submit callback function.
     *
     * @return none
     */
    function caldera_forms_submit_complete_callback( $form, $referrer, $process_id, $entryid ) {
        
        global $post;

        if( is_admin() ) {
            return;
        }

        if( $post && intval( $post->ID ) > 0 ) {
            $post_type = get_post_type( $post->ID );
            if( trim( $post_type ) == 'product' ) {
                $qty = sanitize_text_field( $_POST['quantity'] );

                if( !isset( $qty ) || intval( $qty ) < 1 )
                    $qty = 1;

                WC()->cart->add_to_cart( $post->ID, $qty);
                
                $products = array( $products => $post->ID );

                wc_add_to_cart_message( $products );
            }
        }
    }
    /**
     * Add entry_id to cart item for use on other process.
     *
     * @param array $cart_item_data
     * @param int   $product_id
     * @param int   $variation_id
     *
     * @return array
     */
   function add_enteryid_to_cart_item( $cart_item_data, $product_id, $variation_id ) {
        
        if( isset( $_SESSION[ 'woo_cal_entry_id' ] ) && !empty( $_SESSION[ 'woo_cal_entry_id' ] ) ) {
            $woo_cal_product_id = intval( $_SESSION[ 'woo_cal_product_id' ] );
            $woo_cal_entry_id   = intval( $_SESSION[ 'woo_cal_entry_id' ] );
    
            if( $product_id == $woo_cal_product_id ) {
                $cart_item_data['woo_cal_entry_id']     =   $woo_cal_entry_id;
                $cart_item_data['woo_cal_product_id']   =   $woo_cal_product_id;
            } 
        }
        
        return $cart_item_data;
   }
    
    /**
     * Save entry info on session after entry is saved in db.
     *
     * @param $entryid
     * @param $new_entry
     * @param $form
     *
     * @return none
     */
    function save_entryid_after_forms_entry_saved( $entryid, $new_entry, $form ) {
        
        global $post;

        if( is_admin() ) {
            return;
        }

        if( $post && intval( $post->ID ) > 0 ) {
            $post_type = get_post_type( $post->ID );
            if( trim( $post_type ) == 'product' ) {
                $_SESSION[ 'woo_cal_entry_id' ] = $entryid;
                $_SESSION[ 'woo_cal_product_id' ] = $post->ID;
            }
        }
    }

    /**
     * Show entry values on cart and checkout.
     *
     * @param $item_data
     * @param $cart_item
     *
     * @return $item_data
     */
    public function show_entry_after_review_order_item_name( $item_data, $cart_item ) {
        
        if( is_array( $cart_item ) ) {
            if( isset( $cart_item['woo_cal_entry_id'] ) && !empty( $cart_item['woo_cal_entry_id'] ) ) {
                
                $woo_cal_entry_id       = $cart_item['woo_cal_entry_id'];
                $product_id             = $cart_item['product_id'];
                
                $form_id = get_post_meta( $product_id, '_woo_caldera_pricing_table_id', true );
                $form = Caldera_Forms_Forms::get_form( strip_tags( $form_id ) );
                $body = '';
                if( 0 < $woo_cal_entry_id && is_array( $form ) ){
                
                    $entry = Caldera_Forms::get_entry( $woo_cal_entry_id, $form );
                    if( isset( $entry['data'] ) && is_array( $entry['data'] ) ) {
                        foreach( $entry['data'] as $field ) {
                            $body .= '<tr><th>'.$field['label'].'</th><td>'.$field['view'].'</td></tr>';
                        }
                    }
                }

                if( !empty( $body ) ) {
                    $body = '<table class="cf_woo_table cf_woo_table_'.$product_id.'">'.$body.'</table>';
                }

                echo $body;
            }
        }
        
        return $item_data;
    } 

    /**
     * Update caldera entry values on order item meta.
     *
     * @param $item
     * @param $cart_item_key
     * @param $values
     * @param $order
     *
     * @return array
     */
    function update_order_item_meta( $item, $cart_item_key, $values, $order ) {
        
        if( is_array( $values ) ) {
            if( isset( $values['woo_cal_entry_id'] ) && !empty( $values['woo_cal_entry_id'] ) ) {
                $woo_cal_product_id = $values['woo_cal_product_id'];
                $woo_cal_entry_id   = $values['woo_cal_entry_id'];
    
                $form_id = get_post_meta( $woo_cal_product_id, '_woo_caldera_pricing_table_id', true );
                $form = Caldera_Forms_Forms::get_form( strip_tags( $form_id ) );
                
                if( 0 < $woo_cal_entry_id && is_array( $form ) ){
                
                    $entry = Caldera_Forms::get_entry( $woo_cal_entry_id, $form );
                    if( isset( $entry['data'] ) && is_array( $entry['data'] ) ) {
                        foreach( $entry['data'] as $field ) {
                            $item->update_meta_data($field['label'], $field['value'] );
                        }
                    }
                }
            }
        }
    }

	/**
	 *  Show caldera form before add to cart item with shortcode.
	 *
	 * @return none
	 */
	public function show_price_table( ){
        global $post;
        $post_type = get_post_type($post->ID);
        if( $post_type == 'product' ) {
            $pricing_table_id = get_post_meta( $post->ID, '_woo_caldera_pricing_table_id', true );
            if( ! empty( $pricing_table_id ) && $pricing_table_id != '0' ) {
                echo '<div class="caldera-product-form caldera-product-'.$post->ID.'">';
                echo do_shortcode('[caldera_form id="'.$pricing_table_id.'"]', true);
                echo '</div>';
                echo '<style>';
                echo '.woocommerce button[name=add-to-cart].button {display:none;}';
                echo '</style>';
            }
        }
	}
}
new CF_Woocommerce_Process();