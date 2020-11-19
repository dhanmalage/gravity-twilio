<?php
/*
Plugin Name: Gravity Forms Rent Beetle Add-On
Plugin URI: http://jarthur.co/
Description: A add-on to send sms the use of the Add-On Framework
Version: 2.1
Author: Dhan
Author URI: http://jarthur.co/

------------------------------------------------------------------------
Copyright 2012-2016 Rocketgenius Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

define( 'GF_RENT_BEETLE_ADDON_VERSION', '1.0' );

add_action( 'gform_loaded', array( 'GF_RentBeetle_AddOn_Bootstrap', 'load' ), 5 );
//add_action( 'gform_loaded', array( 'GF_RentBeetle_AddOn_Bootstrap', 'rentbeetle_create_db' ), 5 );

class GF_RentBeetle_AddOn_Bootstrap {

    public static function load() {

        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }

        require_once( 'class-gfrentbeetle.php' );

        GFAddOn::register( 'GFRentBeetleAddOn' );
    }

    /*
    public static function rentbeetle_create_db() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'my_analysis';

        $sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		views smallint(5) NOT NULL,
		clicks smallint(5) NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    */

}

function gf_rent_beetle_addon() {
    return GFRentBeetleAddOn::get_instance();
}