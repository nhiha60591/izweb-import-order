<div class="wrap">
    <h2><?php _e( "Import Order Settings", __TEXTDOMAIN__ ); ?></h2>
    <?php
        if( isset( $_POST['izw-save-import-setting'])){
            update_option('izw_import_export_settings', $_POST );
        }
        if( isset( $_POST['izw-export-product'])){
            IZWEB_Import_Export::izw_process_export_product();
        }
        if( isset( $_POST['izw-export-order'])){
            IZWEB_Import_Export::izw_process_export_order();
        }
        $import_folder = __IZWIEPATH__."imports";
        $export_folder = __IZWIEPATH__."exports";
        $izw_import_data = get_option( 'izw_import_export_settings');
        $data = array(
            'import_folder' => !(empty( $izw_import_data['import_folder'] ) ) ? $izw_import_data['import_folder'] : $import_folder,
            'export_folder' => !(empty( $izw_import_data['export_folder'] ) ) ? $izw_import_data['export_folder'] : $export_folder,
            'import_time' => !(empty( $izw_import_data['import_time'] ) ) ? $izw_import_data['import_time'] : '',
            'export_time' => !(empty( $izw_import_data['export_time'] ) ) ? $izw_import_data['export_time'] : '',
            'product_number' => !(empty( $izw_import_data['product_number'] ) ) ? $izw_import_data['product_number'] : '12345',
            'order_number' => !(empty( $izw_import_data['order_number'] ) ) ? $izw_import_data['order_number'] : '12345',
        );
    ?>
    <div class="izw-import-order-settings">
        <form name="" action="" method="post">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="import_folder">Import Form Folder</label></th>
                        <td>
                            <input class="regular-text" id="import_folder" name="import_folder" value="<?php echo $data['import_folder']; ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="export_folder">Export To Folder</label></th>
                        <td>
                            <input class="regular-text" id="export_folder" name="export_folder" value="<?php echo $data['export_folder']; ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="import_time">Import Time</label></th>
                        <td>
                            <select name="import_time">
                                <?php echo get_schedule_time($data['import_time']); ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="export_time">Export Time</label></th>
                        <td>
                            <select name="export_time">
                                <?php echo get_schedule_time($data['export_time']); ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="product_number">Product Number</label></th>
                        <td>
                            <input type="text" name="product_number" value="<?php echo $data['product_number'] ; ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="order_number">Order Number</label></th>
                        <td>
                            <input type="text" name="order_number" value="<?php echo $data['order_number'] ; ?>" />
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button( 'Save Change', 'primary','izw-save-import-setting'); ?>
            <?php submit_button( 'Export Product', 'primary','izw-export-product'); ?>
            <?php submit_button( 'Export Order', 'primary','izw-export-order'); ?>
        </form>
    </div>
</div><!-- END .wrap -->