<?php
class BackWPup_JobType_WPWidget extends BackWPup_JobTypes {
    public function __construct() {
        $this->info[ 'ID' ]          = 'WPWIDGET';
        $this->info[ 'name' ]        = __( 'Widget', 'backwpup-widget' );
        $this->info[ 'description' ] = __( 'Widget list', 'backwpup-widget' );
        $this->info[ 'URI' ]         = translate( BackWPup::get_plugin_data( 'WidgetURI' ), 'backwpup-widget' );
        $this->info[ 'author' ]      = BackWPup::get_plugin_data( 'Author' );
        $this->info[ 'authorURI' ]   = translate( BackWPup::get_plugin_data( 'AuthorURI' ), 'backwpup-widget' );
        $this->info[ 'version' ]     = BackWPup::get_plugin_data( 'Version' );
    }

    public function creates_file() {
        return TRUE;
    }

    public function option_defaults() {
        return array( 'pluginlistfilecompression' => '', 'pluginlistfile' => sanitize_file_name( get_bloginfo( 'name' ) ) . '.pluginlist.%Y-%m-%d' );
    }

    public function edit_tab( $jobid ) {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="idwidgetlistfile"><?php _e( 'Widget list file name', 'backwpup' ) ?></label></th>
                <td>
                    <input name="widgetlistfile" type="text" id="idwidgetlistfile"
                           value="<?php echo BackWPup_Option::get( $jobid, 'widgetlistfile' );?>"
                           class="medium-text code"/>.sql
                </td>
            </tr>
        </table>
        <?php
    }

    public function edit_form_post_save( $id ) {
        BackWPup_Option::update( $id, 'widgetlistfile', BackWPup_Job::sanitize_file_name( $_POST[ 'widgetlistfile' ] ) );
    }

    public function job_run( BackWPup_Job $job_object ) {
        global $wpdb;
        $job_object->substeps_todo = 1;

        $job_object->log( sprintf( __( '%d. Trying to generate a file with installed widget names&#160;&hellip;', 'backwpup' ), $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] ) );
        //build filename
        if ( empty( $job_object->temp[ 'widgetlistfile' ] ) )
            $job_object->temp[ 'widgetlistfile' ] = $job_object->generate_filename( $job_object->job[ 'widgetlistfile' ], 'sql' ) . $job_object->job[ 'widgetlistfilecompression' ];
        $handle = fopen($job_object->temp[ 'widgetlistfile' ], 'w' );

        if ( $handle ) {
            $query = "SELECT * FROM $wpdb->options WHERE option_name LIKE 'widget_%'";
            $rows = $wpdb->get_results($query);
            $header = '';
            foreach($rows as $row){
                $header .= "INSERT INTO $wpdb->options (option_name, option_value, autoload) VALUES".
                    "('".esc_sql($row->option_name)."', '".esc_sql($row->option_value)."', '".esc_sql($row->autoload)."')".
                    "ON DUPLICATE KEY UPDATE option_value = '".esc_sql($row->option_value)."';\n";
            }
            $query = "SELECT * FROM $wpdb->options WHERE option_name = 'sidebars_widgets'";
            $rows = $wpdb->get_results($query);
            foreach($rows as $row){
                $header .= "INSERT INTO $wpdb->options (option_name, option_value, autoload) VALUES".
                    "('".esc_sql($row->option_name)."', '".esc_sql($row->option_value)."', '".esc_sql($row->autoload)."')".
                    "ON DUPLICATE KEY UPDATE option_value = '".esc_sql($row->option_value)."';\n";
            }
            fwrite( $handle, $header );
            fclose( $handle );
        } else {
            $job_object->log( __( 'Can not open target file for writing.', 'backwpup' ), E_USER_ERROR );
            return FALSE;
        }

        if ($job_object->temp[ 'widgetlistfile' ] )  {
            $job_object->additional_files_to_backup[ ] = $job_object->temp[ 'widgetlistfile' ];
            $job_object->log(
                sprintf( __( 'Added widget list file "%1$s" with %2$s to backup file list.', 'backwpup' ),
                    $job_object->temp[ 'widgetlistfile' ],
                    size_format($job_object->temp[ 'widgetlistfile' ] ),
                    2 )
            );
        }
        $job_object->substeps_done = 1;
        return TRUE;
    }
}