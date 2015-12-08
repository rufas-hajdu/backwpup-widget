<?php
/*
Plugin Name: backwpup widget
Plugin URI: https://github.com/rufas-hajdu/backwpup-widget
Description: add backup only widget data
Version: 1.0
Author: miki_sin
Author URI: http://mikitheworld.com
License: A "Slug" license name e.g. GPL2
*/

if ( ! class_exists( 'BackWPup_Widget' ) ) {

    // Don't activate on anything less than PHP 5.2.7 or WordPress 3.1
    if ( version_compare( PHP_VERSION, '5.2.7', '<' ) || version_compare( get_bloginfo( 'version' ), '3.4', '<' ) || ! function_exists( 'spl_autoload_register' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins( basename( __FILE__ ) );
        if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'activate' || $_GET['action'] == 'error_scrape' ) ) {
            die( __( 'BackWPup requires PHP version 5.2.7 with spl extension or greater and WordPress 3.4 or greater.', 'backwpup' ) );
        }
    }

    //Start Plugin
    if ( function_exists( 'add_filter' ) ) {
        add_action( 'plugins_loaded', array( 'BackWPup_Widget', 'get_instance' ), 10 );
    }

    /**
     * Main BackWPup Plugin Class
     */
    final class BackWPup_Widget {

        private static $instance = NULL;
        private static $plugin_data = array();
        private static $autoload = array();
        private static $destinations = array();
        private static $registered_destinations = array();
        private static $job_types = array();
        private static $wizards = array();

        /**
         * Set needed filters and actions and load
         */
        private function __construct() {

            $this->registerTypes();
            spl_autoload_register( array( $this, 'autoloader' ) );
        }

        /**
         * @static
         *
         * @return self
         */
        public static function get_instance() {

            if (NULL === self::$instance) {
                self::$instance = new self;
            }
            return self::$instance;
        }
        private function __clone() {}

        private function autoloader( $class ) {

            //BackWPup classes auto load
            if ( strstr( strtolower( $class ), 'backwpup_' ) ) {
                $dir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR;
                $class_file_name = 'class-' . str_replace( array( 'backwpup_', '_' ), array( '', '-' ), strtolower( $class ) ) . '.php';
                if ( strstr( strtolower( $class ), 'backwpup_pro' ) ) {
                    $dir .=  'pro' . DIRECTORY_SEPARATOR;
                    $class_file_name = str_replace( 'pro-','', $class_file_name );
                }
                if ( file_exists( $dir . $class_file_name ) )
                    require $dir . $class_file_name;
            }

            // namespaced PSR-0
            if ( ! empty( self::$autoload ) ) {
                $pos = strrpos( $class, '\\' );
                if ( $pos !== FALSE ) {
                    $class_path = str_replace( '\\', DIRECTORY_SEPARATOR, substr( $class, 0, $pos ) ) . DIRECTORY_SEPARATOR . str_replace( '_', DIRECTORY_SEPARATOR, substr( $class, $pos + 1 ) ) . '.php';
                    foreach ( self::$autoload as $prefix => $dir ) {
                        if ( $class === strstr( $class, $prefix ) ) {
                            if ( file_exists( $dir . DIRECTORY_SEPARATOR . $class_path ) )
                                require $dir . DIRECTORY_SEPARATOR . $class_path;
                        }
                    }
                } // Single class file
                elseif ( ! empty( self::$autoload[ $class ] ) && is_file( self::$autoload[ $class ] ) ) {
                    require self::$autoload[ $class ];
                }
            }

            //Google SDK Auto loading
            $classPath = explode( '_', $class );
            if ( $classPath[0] == 'Google' ) {
                if ( count( $classPath ) > 3 ) {
                    $classPath = array_slice( $classPath, 0, 3 );
                }
                $filePath = self::get_plugin_data( 'plugindir' ) . '/vendor/' . implode( '/', $classPath ) . '.php';
                if ( file_exists( $filePath ) ) {
                    require $filePath;
                }
            }

        }

        public static function registerTypes() {
            add_filter('backwpup_job_types', function($types){
                $types[ 'WPWIDGET' ] = new BackWPup_JobType_WPWidget;
                return $types;
            });
            return self::$job_types;
        }
        public static function load_text_domain() {
            if ( is_textdomain_loaded( 'backwpup-widget' ) ) {
                return TRUE;
            }
            return load_plugin_textdomain( 'backwpup-widget', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        }
    }

}
