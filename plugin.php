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
    public $ftp_connect, $login_result;

    function __construct(){
        $this->izw_import_settings = get_option( 'izw_import_export_settings' );

        register_activation_hook( __FILE__, array( $this, 'izw_activation' ) );
        register_deactivation_hook( __FILE__, array( $this, 'izw_deactivation' ) );

        add_action( 'izw_export_order', array( $this, 'izw_process_export_order' ) );
        add_action( 'izw_export_product', array( $this, 'izw_process_export_product' ) );

        add_action( 'izw_import_order', array( $this, 'izw_process_import_order' ) );
        add_action( 'izw_import_product', array( $this, 'izw_process_import_product' ) );

        add_action( 'izw_exip_install', array( $this, 'install_schedule_event' ) );
        add_action( 'izw_exip_uninstall', array( $this, 'uninstall_schedule_event' ) );
        add_action( 'init', array( $this, 'init') );
        $this->defines();
        $this->includes();
    }

    /**
     * Defines
     */
    public function defines(){
        define( '__TEXTDOMAIN__', 'izweb-import-order' );
        define( '__IZWIEPATH__', plugin_dir_path( __FILE__ ) );
        define( '__IZWIEURL__', plugin_dir_url( __FILE__ ) );
    }

    /**
     * Include files
     */
    public function includes(){
        require_once ( "functions.php" );
    }

    /**
     * Set init function
     */
    public function init(){
        add_action( 'admin_menu',  array( $this, 'admin_menu') );
    }

    /**
     * Add Admin menu
     */
    public function admin_menu(){
        add_menu_page( 'WC Import/Export', 'Import/Export', 'manage_options', 'wc-import-export', array( $this, 'izw_import_order_settings' ) );
    }

    /**
     * Back-end Scripts
     */
    public function backend_script(){

    }

    /**
     * Front-End Scripts
     */
    public function frontend_script(){

    }

    /**
     * Connect to FTP information
     */
    function connect_to_ftp_server(){
        $this->ftp_connect = ftp_connect( $this->izw_import_settings['ftp_server'], $this->izw_import_settings['ftp_port'] );
        $this->login_result = ftp_login($this->ftp_connect, $this->izw_import_settings['ftp_username'], $this->izw_import_settings['ftp_password']);
        ftp_pasv( $this->ftp_connect, true );
    }

    /**
     * Get file contents from FTP
     *
     * @param string $filename
     * @return array
     */
    function izw_get_file_content( $filename = 'Orders.csv' ){
        $this->connect_to_ftp_server();
        //Create temp handler:
        $tempHandle = fopen('php://temp', 'r+');

        //Get file from FTP assuming that it exists:
        $string = array();
        if( ftp_fget($this->ftp_connect, $tempHandle, $this->izw_import_settings['import_folder']."/".$filename, FTP_ASCII, 0 ) ) {
            rewind($tempHandle);
            $string = array();
            while (!feof($tempHandle)) {
                $string[] = fgetcsv($tempHandle, null, ";");
            }
            ftp_close($this->ftp_connect);
        }
        return $string;
    }

    /**
     * Process Export Order
     */
    public function izw_process_export_order(){
        $args = array(
            'post_type' => 'shop_order',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );
        // The Query
        $the_query = new WP_Query( $args );

        // The Loop
        $csv_string = '';
        $number = $this->izw_import_settings['order_number'] ? $this->izw_import_settings['order_number'] : '12345';
        if ( $the_query->have_posts() ) {
            $total = 0;
            while ( $the_query->have_posts() ) {
                $the_query->the_post();
                global $post;
                $exported = get_post_meta( get_the_ID(), 'izw_exported', true );
                if( $exported ){continue;}else{update_post_meta( get_the_ID(), 'izw_exported', 'true');}
                $user = new WP_User( $post->post_author );
                $order = new WC_Order( get_the_ID() );
                $first_name = get_post_meta( get_the_ID(), '_billing_first_name', true );
                $last_name = get_post_meta( get_the_ID(), '_billing_last_name', true );
                // Customer
                $csv_string .= '"CUSTOMER"';
                $csv_string .= ',"'.$number.'"';
                $csv_string .= ',"'.$post->post_author.'"';
                $csv_string .= ",\"{$first_name}\"";
                $csv_string .= ",\"".$last_name."\"";
                $csv_string .= ",\"".get_post_meta( get_the_ID(), '_billing_address_1', true )."\"";
                $csv_string .= ",\"".WC()->countries->countries[ get_post_meta( get_the_ID(), 'billing_country', true ) ]."\"";
                $csv_string .= ",\"".$user->user_email."\"\n";

                // Pick list
                $csv_string .= 'PICKLIST';
                $csv_string .= ',"'.$number.'"';
                $csv_string .= ',"'.$user->ID.'"';
                $csv_string .= ',"'.get_the_ID().'"';
                $csv_string .= ',""';
                $csv_string .= ',""';
                $csv_string .= ',"'.$order->get_status().'"';
                $csv_string .= "\n";

                // ADDR
                $csv_string .= 'ADDR';
                $csv_string .= ',"'.$number.'"';
                $csv_string .= ',"'.$user->ID.'"';
                $csv_string .= ',"'.$user->display_name.'"';
                $csv_string .= ",\"".get_user_meta( $post->post_author, 'billing_address_1', true )."\"";
                $csv_string .= ",\"".WC()->countries->countries[ get_user_meta( $post->post_author, 'billing_country', true ) ]."\"";
                $csv_string .= "\n";

                //ORDER LINES
                $i=0;
                foreach( $order->get_items() as $item){
                    $_product = $order->get_product_from_item($item);
                    $csv_string .= 'ORDER_LINE';
                    $csv_string .= ','.get_the_ID();
                    $csv_string .= ','.$i;
                    $csv_string .= ','.$_product->id;
                    $csv_string .= ','.$item['qty'];
                    $csv_string .= ',1234567890123';
                    $csv_string .= "\n";
                    $i++;
                    $total = $total + 1;
                }
            }
        } else {
            // no posts found
        }
        if( $csv_string != ''){
            $csv_string .= '"END_OF_FILE";'.$total;
        }
        $filename = 'Orders'.date("Y-m-d_H-i-s").'.csv';
        $tempHandle = fopen( __IZWIEPATH__.'exported/'.$filename, 'w+');
        fwrite($tempHandle, $csv_string);
        rewind($tempHandle);
        $this->connect_to_ftp_server();

        ftp_fput($this->ftp_connect, $this->izw_import_settings['export_folder']."/Orders.csv", $tempHandle, FTP_ASCII );
        ftp_close($this->ftp_connect);
        fclose( $tempHandle );
        /* Restore original Post Data */
        wp_reset_postdata();
    }

    /**
     * Process Export Products
     */
    public function izw_process_export_product(){
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );
        // The Query
        $the_query = new WP_Query( $args );

        // The Loop
        $csv_string = '';
        $total = 0;
        $number = $this->izw_import_settings['product_number'] ? $this->izw_import_settings['product_number'] : '12345';
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post();
                $product = new WC_Product( get_the_ID() );
                $csv_string .= '"ARTICLE"';
                $csv_string .= ';"'.$number.'"';
                $csv_string .= ';"EAN1234567890123"';
                $csv_string .= ";\"{$product->get_sku()}\"";
                $csv_string .= ";\"".get_the_ID()."\"";
                $csv_string .= ";\"".$product->get_total_stock()."\"";
                $csv_string .= ";\"".get_the_title()."\"\n";
                $total++;
            }
        } else {
            // no posts found
        }
        if( $csv_string != ''){
            $csv_string .= '"END_OF_FILE";'.$total;
            $filename = 'Products-'.date("Y-m-d_H-i-s").'.csv';
            $tempHandle = fopen( __IZWIEPATH__.'exported/'.$filename, 'w+');
            fwrite($tempHandle, $csv_string);
            rewind($tempHandle);
            $this->connect_to_ftp_server();

            ftp_fput( $this->ftp_connect, $this->izw_import_settings['export_folder']."/Products.csv", $tempHandle, FTP_ASCII );
            ftp_close( $this->ftp_connect);
            fclose( $tempHandle );
        }
        /* Restore original Post Data */
        wp_reset_postdata();
    }

    /**
     * Load Import Order Settings
     */
    public function izw_import_order_settings(){
        include "templates/import-order-settings.php";
    }

    /**
     * Process Import Orders
     */
    public function izw_process_import_order(){
        $filename = "import-orders.csv";
        $newfilename = 'import-orders-'.date('Y-m-d_H-i-s').'.csv';
        $array = $this->izw_get_file_content($filename);
        if( is_array( $array ) && sizeof( $array ) > 0){
            foreach( $array as $item){
                if( sizeof( $item )<3 ) continue;
                $order = new WC_Order( preg_replace("/[^0-9]/","", $item[3] ) );
                $order->add_order_note( 'Tracking Number:'. $item[5] );
                $order->update_status( strtolower( $item[4] ) );
            }
        }
        $this->connect_to_ftp_server();
        ftp_get( $this->ftp_connect, __IZWIEPATH__.'imported/'.$newfilename, $this->izw_import_settings['import_folder'].$filename, FTP_BINARY );
        ftp_delete( $this->ftp_connect, $this->izw_import_settings['import_folder'].$filename );
        ftp_close( $this->ftp_connect );

    }

    /**
     * Process Import Products
     */
    public function izw_process_import_product(){
        $filename = "stock-update.csv";
        $newfilename = 'stock-update-'.date('Y-m-d_H-i-s').'.csv';
        $array = $this->izw_get_file_content($filename);
        if( is_array( $array ) && sizeof( $array ) > 0){
            foreach( $array as $item){
                update_post_meta( preg_replace("/[^0-9]/","", $item[2] ), '_stock', preg_replace("/[^0-9]/","", $item[3] ) );
            }
        }
        $this->connect_to_ftp_server();
        ftp_get( $this->ftp_connect, __IZWIEPATH__.'imported/'.$newfilename, $this->izw_import_settings['import_folder'].$filename, FTP_BINARY );
        ftp_delete( $this->ftp_connect, $this->izw_import_settings['import_folder'].$filename );
        ftp_close( $this->ftp_connect );
    }

    /**
     * Set Schedule event
     */
    public function install_schedule_event(){
        $time_schedule = get_option( 'izw_import_export_settings' );
        if( !empty( $time_schedule['export_time'] ) ) {
            $timeEP = explode( "|", $time_schedule['export_time'] );
            wp_schedule_event(time(), $timeEP[0], 'izw_export_order');
            wp_schedule_event(time(), $timeEP[0], 'izw_export_product');
        }

        if( !empty( $time_schedule['import_time'] ) ) {
            $timeIP = explode( "|", $time_schedule['import_time'] );
            wp_schedule_event(time(), $timeIP[0], 'izw_import_order');
            wp_schedule_event(time(), $timeIP[0], 'izw_import_product');
        }
    }

    /**
     * Clear All Schedule Event
     */
    public function uninstall_schedule_event(){
        wp_clear_scheduled_hook( 'izw_export_order' );
        wp_clear_scheduled_hook( 'izw_export_product' );
        wp_clear_scheduled_hook( 'izw_import_order' );
        wp_clear_scheduled_hook( 'izw_import_product' );
    }
    /**
     * On activation, set a time, frequency and name of an action hook to be scheduled.
     */
    function izw_activation() {
        do_action( 'izw_exip_install' );
    }
    /**
     * On deactivation, remove all functions from the scheduled action hook.
     */
    function izw_deactivation() {
        do_action( 'izw_exip_uninstall' );
    }
}
endif;
new IZWEB_Import_Export();