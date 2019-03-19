<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'Caldera_Forms_Processor_UI' ) ) {
    echo Caldera_Forms_Processor_UI::config_fields( woo_caldera_checkout_processor_fields() ); 
}