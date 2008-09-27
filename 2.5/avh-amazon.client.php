<?php
// SOAP Class
require (dirname ( __FILE__ ) . '/../inc/nusoap/nusoap.php');
require (dirname ( __FILE__ ) . '/../inc/nusoap/class.wsdlcache.php');

class AVHAmazonCore {

	/**
	 * Amazon WSDl URL Array
	 *
	 * @var array
	 */
	var $wsdlurl_table;

	/**
	 * Amazon WSDl URL
	 *
	 * @var string
	 */
	var $wsdlurl;

	/**
	 * Amazon Cached WSDL File
	 *
	 * @var string
	 */
	var $wsdl;

	/**
	 * Complete patch to the cached wsdl file
	 *
	 * @var string
	 */
	var $wsdlcachefolder;

	/**
	 * Amazon Webservices Accesskey ID
	 *
	 * @var string
	 */
	var $accesskeyid;

	/**
	 * The Locale Table
	 *
	 */
	var $locale_table;
	/**
	 * The Associated ID table
	 */
	var $associate_table;

	/**
	 * Version of AVH Amazon
	 *
	 * @var string
	 */
	var $version;

	/**
	 * Paths and URI's of the WordPress information, 'home', 'siteurl', 'install_url', 'install_dir'
	 *
	 * @var array
	 */
	var $info;

	/**
	 * Options set for the plugin
	 *
	 * @var array
	 */
	var $options;

	/**
	 * Default options for the plugin
	 *
	 * @var array
	 */
	var $default_options;

	/**
	 * Name of the options field in the WordPress database options table.
	 *
	 * @var string
	 */
	var $db_options;

	/**
	 * Dateformat used in the plugin
	 *
	 * @var unknown_type
	 */
	var $dateformat;

	/**
	 * WP Object cache
	 *
	 * @var boolean
	 */
	var $use_cache;

	/**
	 * PHP4 constructor - Initialize the Core
	 *
	 * @return
	 */
	function AVHAmazonCore () {

		$this->version = "2.1";

		$this->wsdlurl_table = array (
				'US' => 'http://ecs.amazonaws.com/AWSECommerceService/2008-08-19/AWSECommerceService.wsdl',
				'CA' => 'http://ecs.amazonaws.com/AWSECommerceService/2008-08-19/CA/AWSECommerceService.wsdl',
				'DE' => 'http://ecs.amazonaws.com/AWSECommerceService/2008-08-19/DE/AWSECommerceService.wsdl',
				'UK' => 'http://ecs.amazonaws.com/AWSECommerceService/2008-08-19/UK/AWSECommerceService.wsdl' );
		$this->wsdlurl = $this->wsdlurl_table['US'];

		$this->accesskeyid = '1MPCC36EZ827YJQ02AG2';

		$this->locale_table = array (
				'US' => 'Amazon.com',
				'CA' => 'Amazon.ca',
				'DE' => 'Amazon.de',
				'UK' => 'Amazon.co.uk' );
		$this->associate_table = array (
				'US' => 'avh-amazon-20',
				'CA' => 'avh-amazon-ca-20',
				'DE' => 'avh-amazon-de-21',
				'UK' => 'avh-amazon-uk-21' );

		$this->db_options = 'avhamazon';
		$this->use_cache = false;

		// Default Options
		$this->default_options = array (
				'version' => $this->version,
				'associated_id' => 'avh-amazon-20',
				'title' => 'Amazon Wish List',
				'wishlist_id' => '2CC2KKW02870',
				'wishlist_imagesize' => 'Medium',
				'locale' => 'US',
				'nr_of_items' => 1,
				'show_footer' => 0,
				'footer_template' => 'Show all %nr_of_items% items' );

		$this->handleOptions ();

		// Determine installation path & url
		$path = str_replace ( '\\', '/', dirname ( __FILE__ ) );
		$path = substr ( $path, strpos ( $path, 'plugins' ) + 8, strlen ( $path ) );

		$info['siteurl'] = get_option ( 'siteurl' );
		if ( $this->isMuPlugin () ) {
			$info['install_url'] = $info['siteurl'] . '/wp-content/mu-plugins';
			$info['install_dir'] = ABSPATH . 'wp-content/mu-plugins';

			if ( $path != 'mu-plugins' ) {
				$info['install_url'] .= '/' . $path;
				$info['install_dir'] .= '/' . $path;
			}
		} else {
			$info['install_url'] = $info['siteurl'] . '/wp-content/plugins';
			$info['install_dir'] = ABSPATH . 'wp-content/plugins';

			if ( $path != 'plugins' ) {
				$info['install_url'] .= '/' . $path;
				$info['install_dir'] .= '/' . $path;
			}
		}

		// Set class property for info
		$this->info = array (
				'home' => get_option ( 'home' ),
				'siteurl' => $info['siteurl'],
				'install_url' => $info['install_url'],
				'install_dir' => $info['install_dir'] );

		// Set class property for the WSDl cache folder
		$this->wsdlcachefolder = str_replace ( '/2.5', '', $this->info['install_dir'] ) . '/cache/';

		// Set class property to use WP Object Cache? Or not ?
		global $wp_object_cache;
		$this->use_cache = ($wp_object_cache->cache_enabled === true) ? true : false;

		// Set class property for dateformat
		$this->dateformat = get_option ( 'date_format' );

		/**
		 * TODO Localization
		 */
		// Localization.
		//$locale = get_locale();
		//if ( !empty( $locale ) ) {
		//	$mofile = $this->info['install_dir'].'/languages/avhamazon-'.$locale.'.mo';
		//	load_textdomain('avhamazon', $mofile);
		//}


		return;
	}

	/**
	 * Test if local installation is mu-plugin or a classic plugin
	 *
	 * @return boolean
	 */
	function isMuPlugin () {
		if ( strpos ( dirname ( __FILE__ ), 'mu-plugins' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Sets the class options to the default options and checks if upgrades are necessary.
	 *
	 * @since 2.1
	 *
	 */
	function handleOptions () {

		$default_options = $this->default_options;

		// Get options from WP options
		$options_from_table = get_option ( 'avhamazon' );

		if ( empty ( $options_from_table ) ) {
			$options_from_table = $this->default_options; // New installation
		} else {

			// Update default options by getting not empty values from options table
			foreach ( ( array ) $default_options as $default_options_name => $default_options_value ) {
				if ( ! is_null ( $options_from_table[$default_options_name] ) ) {
					if ( is_int ( $default_options_value ) ) {
						$default_options[$default_options_name] = ( int ) $options_from_table[$default_options_name];
					} else {
						$default_options[$default_options_name] = $options_from_table[$default_options_name];
						if ( 'associated_id' == $default_options_name ) {
							if ( 'blogavirtualh-20' == $options_from_table[$default_options_name] ) $default_options[$default_options_name] = 'avh-amazon-20';
						}
					}
				}
			}
			// If a newer version is running do upgrades if neccesary and update the database.
			if ( $this->version > $options_from_table['version'] ) {
				// Starting with version 2.1 I switched to a new way of storing the widget options in the database. We need to convert these.
				if ( $options_from_table['version'] < "2.1" ) {
					$this->upgradeWidgetOptions_2_1 ();
				}

				// Write the new default options and the proper version to the database
				$default_options['version'] = $this->version;
				update_option ( $this->db_options, $default_options );
			}
		}
		// Set the class property for options
		$this->options = $default_options;
	} // End handleOptions()


	/**
	 * Upgrade the the way the widgets data is stored
	 *
	 * Because of the the way I handle multiple widgets since version 2.1, the way the widget data is stored
	 * in the WordPress database has changed. This function will handle the conversion.
	 *
	 * @since 2.1
	 *
	 */
	function upgradeWidgetOptions_2_1 () {
		$oldvalues = get_option ( 'widget_avhamazon_wishlist' );
		$all_options = array ();

		foreach ( $oldvalues as $name => $value ) {
			if ( $name != 'number' ) {
				$options = array ();
				$options['title'] = $value['title'];
				$options['associated_id'] = $value['associated_id'];
				$options['wishlist_id'] = $value['wishlist_id'];
				$options['locale'] = $value['locale'];
				$options['nr_of_items'] = $value['nr_of_items'];
				$options['show_footer'] = $value['show_footer'];
				$options['footer_template'] = $value['footer_template'];
				$all_options[$name] = $options;
			}
		}

		delete_option ( 'widget_avhamazon_wishlist' );
		add_option ( 'widget_avhamazon_wishlist', $all_options );

	} // End upgradeWidgetOptions_2_1

	/**
	 * Get all the items from the list
	 *
	 * @param string $ListID The Wish List ID of the list to get
	 * @param class $proxy
	 * @return array Items
	 */
	function avh_getListResults ( $ListID, &$proxy ) {

		$list = $proxy->ListLookup ( $this->avh_getSoapListLookupParams ( $ListID ) );

		if ( 1 == $list['Lists']['List']['TotalItems'] ) {
			$list['Lists']['List']['ListItem'] = array (
					'0' => $list['Lists']['List']['ListItem'] ); // If one item in the list we need to make it a multi array
		} else {
			if ( $list['Lists']['List']['TotalPages'] > 1 ) { // If the list contains over 10 items we need to process the other pages.
				$page = 2;
				while ( $page <= $list['Lists']['List']['TotalPages'] ) {
					$result = $proxy->ListLookup ( $this->avh_getSoapListLookupParams ( $ListID, null, $page ) );
					foreach ( $result['Lists']['List']['ListItem'] as $key => $value ) {
						$newkey = 10 * ($page - 1) + $key;
						$list['Lists']['List']['ListItem'][$newkey] = $result['Lists']['List']['ListItem'][$key]; //Add the items from the remaining pages to the lists.
					}
					$page ++;
				}
			}
		}
		return ($list);
	}

	/**
	 * Get a list of keys from the Item List to display
	 *
	 * @param array $list The wishlist
	 * @param int $nr_of_items Amount of keys to return, default is 1
	 * @return array Associative array where the value is the Keys
	 */
	function avh_getItemKeys ( $list, $nr_of_items = 1 ) {
		$total_items = count ( $list );
		if ( $nr_of_items > $total_items ) $nr_of_items = $total_items;
		return (( array ) array_rand ( $list, $nr_of_items ));
	}

	/**
	 * SOAP Find the List parameters
	 *
	 * @param string $ListID
	 * @param string $WhatList
	 * @return array
	 */
	function avh_getSoapListLookupParams ( $ListID, $WhatList = null, $page = null ) {

		$WhatList = (is_null ( $WhatList ) ? 'WishList' : $WhatList);
		$page = (is_null ( $page ) ? 1 : $page);

		$listLookupRequest[] = array (
				'ListId' => $ListID,
				'ListType' => $WhatList,
				'ResponseGroup' => 'ListFull',
				'IsOmitPurchasedItems' => '1',
				'ProductPage' => ( string ) $page,
				'Sort' => 'LastUpdated' );

		$listLookup = array (
				'AWSAccessKeyId' => $this->accesskeyid,
				'Request' => $listLookupRequest );
		return $listLookup;
	}

	/**
	 * SOAP Get Item Details
	 *
	 * @param string $Itemid
	 * @param string $associatedid
	 * @return array
	 */
	function avh_getSoapItemLookupParams ( $Itemid, $associatedid ) {

		$itemLookupRequest[] = array (
				'ItemId' => $Itemid,
				'IdType' => 'ASIN',
				'Condition' => 'All',
				'ResponseGroup' => 'Medium' );

		$itemLookUp = array (
				'AWSAccessKeyId' => $this->accesskeyid,
				'Request' => $itemLookupRequest,
				'AssociateTag' => $associatedid );
		return $itemLookUp;
	}

	/**
	 * Get the options for the widget
	 *
	 * @param array $a
	 * @param mixed $key
	 * @return mixed
	 */
	function avh_getWidgetOptions ( $a, $key ) {
		$return = '';

		if ( $a[$key] ) {
			$return = $a[$key]; // From widget
		} elseif ( $this->options[$key] ) {
			$return = $this->options[$key]; // From Admin Page
		} else {
			$return = $this->default_options[$key]; // Default
		}
		return ($return);
	}

	/**
	 * Find the associate id based on the locale and locale_table
	 *
	 * @param string $locale
	 *
	 */
	function avh_getAssociateId ( $locale ) {

		if ( array_key_exists ( $locale, $this->associate_table ) ) {
			$associatedid = $this->associate_table[$locale];
		} else {
			$associatedid = 'avh-amazon-20';
		}
		return ($associatedid);
	}
} //End Class avh_amazon


/**
 * Initialize the plugin
 *
 */
function avhamazon_init () {

	// Admin and XML-RPC
	if ( is_admin () ) {
		require (dirname ( __FILE__ ) . '/inc/avh-amazon.admin.php');
		$avhamazon_admin = & new AVHAmazonAdmin ( );
		// Installation
		register_activation_hook ( __FILE__, array ( & $avhamazon_admin, 'installPlugin' ) );
	}

	// Include shortcode class
	require (dirname ( __FILE__ ) . '/inc/avh-amazon.shortcode.php');
	$avhamazon_shortcode = & new AVHAmazonShortcode ( );

	// Include the widgets code
	require (dirname ( __FILE__ ) . '/inc/avh-amazon.widgets.php');
	$avhamazon_widget = & new AVHAmazonWidget ( );


} // End avhamazon_init()


add_action ( 'plugins_loaded', 'avhamazon_init' );
?>