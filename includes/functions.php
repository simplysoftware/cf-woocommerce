<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Initialize the processor
 */
add_action( 'caldera_forms_pre_load_processors', function(){

    new CF_Woocommerce_Checkout(woo_caldera_register_checkout_processor(),  woo_caldera_checkout_processor_fields(), 'woo-caldera-checkout' );
    new CF_Woocommerce_Product( woo_caldera_register_product_processor(),   woo_caldera_product_processor_fields(), 'woo-caldera-product' );

});

/**
 * Registers the Product Processor
 *
 * @param $processors
 * @return array
 */
function woo_caldera_register_product_processor(  ) {
    
    $processor = array(
        'name'				=>	__( 'Woocommerce Product', 'woo_cal_addon' ),
        'description'		=>	__( 'Integrates woocommerce product submission option with calder form.', 'woo_cal_addon'),
        'icon'				=>	'',
        'author'			=>	'Real Big Plugins',
        'author_url'		=>	'https://calderaforms.com',
        'template'			=>	WOO_CAL_INCLUDES_DIR . 'product_config.php',
    );

    return $processor;
}

/**
 * Woocommerce processor Config template
 */
function woo_caldera_product_processor_fields(){ 

    $fields = array();
    if( isset( $_REQUEST['edit'] ) ) {
        $form_id = sanitize_text_field( $_REQUEST['edit'] );

        $config = Caldera_Forms_Forms::get_form( $form_id );
        $fields = array();
        foreach( Caldera_Forms_Forms::get_fields( $config, true ) as $field_id => $field ) {
                $fields[ $field['slug'] ] = $field[ 'label' ];
        }
    }
   
    return array(
        array(
            'id' => 'product_title',
            'label' => __( 'Product Title', 'woo_cal_addon' ),
            'type' => 'dropdown',
            'required' => true,
            'options' => $fields,
        ),
        array(
            'id' => 'product_short_description',
            'label' => __( 'Short Description', 'woo_cal_addon' ),
            'type' => 'dropdown',
            'required' => true,
            'options' => $fields,
        ),
        array(
            'id' => 'product_description',
            'label' => __( 'Product Description', 'woo_cal_addon' ),
            'type' => 'dropdown',
            'required' => true,
            'options' => $fields,
        ),
        array(
            'id' => 'product_price',
            'label' => __( 'Product Price', 'woo_cal_addon' ),
            'type' => 'dropdown',
            'required' => true,
            'options' => $fields,
        ),
        array(
            'id' => 'product_quantity',
            'label' => __( 'Product Quantity', 'woo_cal_addon' ),
            'type' => 'dropdown',
            'required' => false,
            'options' => $fields, 
        ),
        
        array(
            'id' => 'product_category',
            'label' => __( 'Product Category', 'woo_cal_addon' ),
            'type' => 'dropdown',
            'required' => true,
            'options' => $fields,
        ),
        array(
            'id' => 'product_tags',
            'label' => __( 'Product Tags', 'woo_cal_addon' ),
            'type' => 'dropdown',
            'required' => true,
            'options' => $fields,
        ),

        array(
            'id' => 'product_image',
            'label' => __( 'Product Image', 'woo_cal_addon' ),
            'type' => 'dropdown',
            'required' => true,
            'options' => $fields,
        ),
        array(
            'id' => 'product_stock', 
            'label' => __( 'Manage Stock', 'mp_cal_addon' ),
            'type' => 'dropdown',
            'required' => false,
            'options' => array(
                'Yes' => __( 'Yes', 'mp_cal_addon' ),
                'No' => __( 'No', 'mp_cal_addon' ),
            )
        )
    );
}

/**
 * Woocommerce processor Config template
 */
function woo_caldera_checkout_processor_fields(){ 

    $fields = array();
    if( isset( $_REQUEST['edit'] ) ) {
        $form_id = sanitize_text_field( $_REQUEST['edit'] );

        $config = Caldera_Forms_Forms::get_form( $form_id );
        $fields = array();
        foreach( Caldera_Forms_Forms::get_fields( $config, true ) as $field_id => $field ){
            if( $field['type']=='text' || $field['type']=='email' )
                $fields[ $field['slug'] ] = $field[ 'label' ];
        }
    }
   
    return array(
        array(
            'id' => 'attached_product_ids',
            'label' => __( 'Product IDs (Comma Separated List)', 'woo_cal_addon' ),
            'type' => 'text',
            'required' => true
        )
    );
}


/**
 * Registers the Woocommerce Checkout
 *
 * @param $processors
 * @return array
 */
function woo_caldera_register_checkout_processor(  ) {
    
    $processor = array(
        'name'				=>	__( 'Woocommerce Checkout', 'woo_cal_addon' ),
        'description'		=>	__( 'Integrates woocommerce product(s) purchase option after submitting a Caldera form.', 'woo_cal_addon'),
        'icon'				=>	'',
        'author'			=>	'Real Big Plugins',
        'author_url'		=>	'https://calderaforms.com',
        'template'			=>	WOO_CAL_INCLUDES_DIR . 'checkout_config.php',
    );

    return $processor;
}