<?php
/*
Plugin Name: Izweb Import Oder
Plugin URI: https://github.com/nhiha60591/izweb-import-order/
Description: Import/Export Woocommerce Order
Version: 1.0.1
Author: Izweb Team
Author URI: https://github.com/nhiha60591
Text Domain: izweb-import-order
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'IZWEB_Import_Export' ) ):
class IZWEB_Import_Export{

    public $izw_import_settings = array();

    function __construct(){
        $this->izw_import_settings = get_option( 'izw_import_export_settings' );

        //register_activation_hook( __FILE__, array( $this, 'izw_activation' ) );
        //register_deactivation_hook( __FILE__, array( $this, 'izw_deactivation' ) );
        //add_action( 'izw_import_order', array( $this, 'izw_process_import_order' ) );
        add_action( 'init', array( $this, 'init') );
        /*add_filter( 'wp_import_post_data_processed', function($postdata){
            $postdata['post_parent'] = 7030;
            return $postdata;
        });*/
        $this->defines();
        $this->includes();
    }
    public function defines(){
        define( '__TEXTDOMAIN__', 'izweb-import-order' );
        define( '__IZWIEPATH__', plugin_dir_path( __FILE__ ) );
        define( '__IZWIEURL__', plugin_dir_url( __FILE__ ) );
    }
    public function includes(){
        require_once ( "functions.php" );
    }
    public function init(){
        add_action( 'admin_menu',  array( $this, 'admin_menu') );
    }
    public function admin_menu(){
        add_menu_page( 'WC Import/Export', 'Import/Export', 'manage_options', 'wc-import-export', array( $this, 'izw_import_order_settings' ) );
    }
    public function backend_script(){

    }
    public function frontend_script(){

    }
    public function izw_process_import_order(){
        $folder =  __IZWIEPATH__."/exports";
        $args = array(
            'post_type' => 'shop_order',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );
        // The Query
        $the_query = new WP_Query( $args );

        // The Loop
        $csv_string = '';
        $number = '12345';
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $product = new WC_Product( get_the_ID() );
                $the_query->the_post();
                $csv_string .= '"ARTICLE"';
                $csv_string .= ';"'.$number.'"';
                $csv_string .= ';"EAN1234567890123"';
                $csv_string .= ";\"{$product->get_sku()}\"";
                $csv_string .= ";\"".get_the_ID()."\"";
                $csv_string .= ";\"".$product->get_total_stock()."\"";
                $csv_string .= ";\"".get_the_title()."\"\n";
            }
        } else {
            // no posts found
        }
        if( $csv_string != ''){
            $csv_string .= '"END_OF_FILE";'.$the_query->found_posts;
            $of = fopen($folder."/Products.csv", "w+");
            fwrite( $of, $csv_string );
            fclose( $of );
        }
        /* Restore original Post Data */
        wp_reset_postdata();
    }
    public function izw_process_export_product(){
        $folder =  __IZWIEPATH__."/exports";
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );
        // The Query
        $the_query = new WP_Query( $args );

        // The Loop
        $csv_string = '';
        $number = '12345';
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $product = new WC_Product( get_the_ID() );
                $the_query->the_post();
                $csv_string .= '"ARTICLE"';
                $csv_string .= ';"'.$number.'"';
                $csv_string .= ';"EAN1234567890123"';
                $csv_string .= ";\"{$product->get_sku()}\"";
                $csv_string .= ";\"".get_the_ID()."\"";
                $csv_string .= ";\"".$product->get_total_stock()."\"";
                $csv_string .= ";\"".get_the_title()."\"\n";
            }
        } else {
            // no posts found
        }
        if( $csv_string != ''){
            $csv_string .= '"END_OF_FILE";'.$the_query->found_posts;
            $of = fopen($folder."/Products.csv", "w+");
            fwrite( $of, $csv_string );
            fclose( $of );
        }
        /* Restore original Post Data */
        wp_reset_postdata();
    }
    public function izw_import_order_settings(){
        include "templates/import-order-settings.php";
    }
    /**
     * On activation, set a time, frequency and name of an action hook to be scheduled.
     */
    function izw_activation() {
        wp_schedule_event( time(), 'hourly', 'izw_import_order' );
    }
    /**
     * On deactivation, remove all functions from the scheduled action hook.
     */
    function izw_deactivation() {
        wp_clear_scheduled_hook( 'izw_import_order' );
    }
}
endif;
new IZWEB_Import_Export();