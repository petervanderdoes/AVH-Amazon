<?php
// This is an include file, all normal WordPress functions will still work.
// Because the plugin is already deactivated it won't regonize any class declarations.

if ( ! defined ( 'ABSPATH' ) && ! defined ( 'WP_UNINSTALL_PLUGIN' ) ) exit ();
global $file;
if ( 'avh-amazon' == dirname ( $file ) ) {
	delete_option ( 'avhamazon' );
	delete_option ( 'widget_avhamazon_wishlist' );
}

?>