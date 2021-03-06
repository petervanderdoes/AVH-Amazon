<?php
/*
Plugin Name: AVH Amazon
Plugin URI: http://blog.avirtualhome.com/wordpress-plugins
Description: This plugin gives you the abillity to add multiple widgets which will display one or more random item(s) from your Amazon wishlist, baby registry and/or wedding registry. It also has the ability to show items, with their link, in posts and pages by use of shortcode.
Version: 3.3.2
Author: Peter van der Does
Author URI: http://blog.avirtualhome.com/

Copyright 2009  Peter van der Does  (email : peter@avirtualhome.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


// Include WordPress version
require (ABSPATH . WPINC . '/version.php');

switch ( (floatval( $wp_version )) ) {

	case 2.9 :
	case 3.0 :
		require (dirname( __FILE__ ) . '/2.9/avh-amazon.client.php');
		break;
	default :
		$message = '<div class="updated fade"><p><strong>' . __( 'AVH Amazon can\'t work with this WordPress version !', 'avhamazon' ) . '</strong></p></div>';
		add_action( 'admin_notices', create_function( '$message', 'echo "$message";' ) );
		break;
}