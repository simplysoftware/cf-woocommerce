<?php

/**
 * Abort if this file is accessed directly.
 */

if ( ! defined( 'ABSPATH' ) ) { 
    exit;
}

class Woo_Caldera_Meta_Boxes {
    
    /**
	 * Constructor.
	 */
	public  function __construct( ) {
        
        add_action( 'add_meta_boxes', array( $this, 'register_meta_box') );
        add_action( 'save_post', [$this,'ld_retake_save_meta_box'] );
    }
    
    /**
	 * Save meta box content.
	 *
	 * @param int $post_id Post ID
	 */
	function ld_retake_save_meta_box( $post_id ) {

		if( !is_admin( ) )
			return;

		$post_tvp = get_post_type( $post_id );
		if( trim( $post_tvp ) == 'product' ) {
			$woo_caldera_pricing_table_id = $_POST['woo_caldera_pricing_table_id'];
			update_post_meta( $post_id, '_woo_caldera_pricing_table_id', $woo_caldera_pricing_table_id );
		}
    }
    
    /**
	 * Register Meta Box
	 */
	function register_meta_box() {
		add_meta_box( 'woo-caldera-meta-box', esc_html__( 'Caldera Pricing Table', 'woo_cal_addon' ), [$this,'caldera_form_selection'], 'product', 'side', 'high' );
    }
    
    /**
	 * show form on product post
	 *
	 * @param $meta_id
	 */
	function caldera_form_selection( $meta_id ) {
		
		$post_id = isset( $_GET['post'] ) ? $_GET['post'] : 0;
		
		$woo_caldera_pricing_table_id = get_post_meta( $post_id, '_woo_caldera_pricing_table_id', true );

		$forms = Caldera_Forms_Forms::get_forms( true );

		echo '<table width="100%">';
		echo '<tr><td>Caldera Form:</td></tr>';
		echo '<tr><td>';
		echo '<select id="woo_caldera_pricing_table_id" name="woo_caldera_pricing_table_id">';
		echo '<option value="0">'.__( 'Select Caldera Form', 'woo_cal_addon' ).'</option>';
		foreach( $forms as $form ) {
			echo '<option value="'.$form['ID'].'" '.($woo_caldera_pricing_table_id==$form['ID']?'selected':'').'>'.$form['name'].'</option>';
		}
		echo '<select></td></tr>';
		echo '</table>';
	}
}

new Woo_Caldera_Meta_Boxes();