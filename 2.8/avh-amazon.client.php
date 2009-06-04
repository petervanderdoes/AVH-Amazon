<?php

class AVHAmazonCore
{

	/**
	 * Amazon endpoint URL Array
	 *
	 * @var array
	 * @since 2.4
	 */
	var $amazon_endpoint_table;

	/**
	 * Amazon endpoint URL
	 *
	 * @var string
	 * @since 2.4
	 */
	var $amazon_endpoint;

	/**
	 * Amazon Standard Request
	 *
	 * @var array
	 * @since 2.4
	 */
	var $amazon_standard_request;

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
	 * Comments used in HTML do identify the plugin
	 *
	 * @var string
	 */
	var $comment_begin;
	var $comment_end;

	/**
	 * Paths and URI's of the WordPress information, 'home', 'siteurl', 'plugin_url', 'plugin_dir'
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
	var $default_general_options;
	var $default_widget_wishlist_options;
	var $default_shortcode_options;
	var $default_options;

	/**
	 * Name of the options field in the WordPress database options table.
	 *
	 * @var string
	 */
	var $db_options_name_core;
	var $db_options_name_widget_wishlist;

	/**
	 * Singleton method
	 *
	 * @return object
	 */
	function getInstance ()
	{
		static $_instance;
		if ( $_instance === null ) {
			$_instance = & new self( );
		}
		return $_instance;
	}

	/**
	 * PHP5 constructor
	 *
	 */
	function __construct ()
	{
		$this->version = "3.0-rc1";
		$this->comment_begin = '<!-- AVH Amazon version ' . $this->version . ' Begin -->';
		$this->comment_end = '<!-- AVH Amazon version ' . $this->version . ' End -->';

		/**
		 * Amazon RESTful properties
		 *
		 */
		$this->amazon_endpoint_table = array ('US' => 'http://ecs.amazonaws.com/onca/xml', 'CA' => 'http://ecs.amazonaws.ca/onca/xml', 'DE' => 'http://ecs.amazonaws.de/onca/xml', 'UK' => 'http://ecs.amazonaws.co.uk/onca/xml' );
		$this->amazon_endpoint = $this->amazon_endpoint_table['US'];
		$this->amazon_standard_request = array ('Service' => 'AWSECommerceService', 'Version' => '2009-03-31', 'AWSAccessKeyId' => '1MPCC36EZ827YJQ02AG2', 'Timestamp' => '' );

		/**
		 * Amazon general options
		 *
		 */
		$this->locale_table = array ('US' => 'Amazon.com', 'CA' => 'Amazon.ca', 'DE' => 'Amazon.de', 'UK' => 'Amazon.co.uk' );
		$this->associate_table = array ('US' => 'avh-amazon-20', 'CA' => 'avh-amazon-ca-20', 'DE' => 'avh-amazon-de-21', 'UK' => 'avh-amazon-uk-21' );

		$this->db_options_name_core = 'avhamazon';
		$this->db_options_name_widget_wishlist = 'widget_avhamazon_wishlist';

		/**
		 * Default options - General Purpose
		 *
		 */
		$this->default_general_options = array ('version' => $this->version, 'associated_id' => 'avh-amazon-20', 'awskey' => '', 'awssecretkey' => '', 'policychange' => '' );

		/**
		 * Default options - Widget Wishlist
		 *
		 */
		$this->default_widget_wishlist_options = array ('title' => 'Amazon Wish List', 'wishlist_id' => '2CC2KKW02870', 'wishlist_imagesize' => 'Medium', 'locale' => 'US', 'nr_of_items' => 1, 'show_footer' => 0, 'footer_template' => 'Show all %nr_of_items% items', 'new_window' => 0 );

		/**
		 * Default options - Shortcode
		 *
		 */
		$this->default_shortcode_options = array ('wishlist_id' => '', 'locale' => 'US' );

		/**
		 * Default Options - All as stored in the DB
		 *
		 */
		$this->default_options = array ('general' => $this->default_general_options, 'widget_wishlist' => $this->default_widget_wishlist_options, 'shortcode' => $this->default_shortcode_options );

		/**
		 * Set the options for the program
		 *
		 */
		$this->handleOptions();

		/**
		 * Set the Access Key ID for the requests
		 */
		$this->amazon_standard_request['AWSAccessKeyId'] = $this->options['general']['awskey'];

		// Determine installation path & url
		$path = str_replace( '\\', '/', dirname( __FILE__ ) );
		$path = substr( $path, strpos( $path, 'plugins' ) + 8, strlen( $path ) );

			$info['siteurl'] = get_option( 'siteurl' );
		if ( $this->isMuPlugin() ) {
			$info['plugin_url'] = WPMU_PLUGIN_URL;
			$info['plugin_dir'] = WPMU_PLUGIN_DIR;

			if ( $path != 'mu-plugins' ) {
				$info['plugin_url'] .= '/' . $path;
				$info['plugin_dir'] .= '/' . $path;
			}
		} else {
			$info['plugin_url'] = WP_PLUGIN_URL;
			$info['plugin_dir'] = WP_PLUGIN_DIR;

			if ( $path != 'plugins' ) {
				$info['plugin_url'] .= '/' . $path;
				$info['plugin_dir'] .= '/' . $path;
			}
		}

		// Set class property for info
		$this->info = array ('home' => get_option( 'home' ), 'siteurl' => $info['siteurl'], 'plugin_url' => $info['plugin_url'], 'plugin_dir' => $info['plugin_dir'], 'graphics_url' => $info['plugin_url'] . '/images', 'wordpress_version' => $this->getWordpressVersion() );

		// Add filter for sanatizing text
		add_filter('avhamazon_text', 'wptexturize');
		add_filter('avhamazon_text', 'convert_chars');
		add_filter('avhamazon_text', 'esc_html');

		return;
	}

	/**
	 * PHP4 constructor - Initialize the Core
	 *
	 * @return
	 */
	function AVHAmazonCore ()
	{
		$this->__construct();
	}

	/**
	 * Test if local installation is mu-plugin or a classic plugin
	 *
	 * @return boolean
	 */
	function isMuPlugin ()
	{
		if ( strpos( dirname( __FILE__ ), 'mu-plugins' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Sets the class property "options" to the options stored in the DB and if they do not exists set them to the default options
	 * Checks if upgrades are necessary based on the version number
	 *
	 * @since 2.1
	 *
	 */
	function handleOptions ()
	{
		$default_options = $this->default_options;

		// Get options from WP options
		$options_from_table = get_option( $this->db_options_name_core );

		if ( empty( $options_from_table ) ) {
			$options_from_table = $this->default_options; // New installation
		} else {

			// As of version 2.2 I changed the way I store the default options.
			// I need to upgrade the options before setting the options but we don't update the version yet.
			if ( ! $options_from_table['general'] ) {
				$this->upgradeDefaultOptions_2_2();
				$options_from_table = get_option( $this->db_options_name_core ); // Get the new options
			}

			// Update default options by getting not empty values from options table
			foreach ( $default_options as $section_key => $section_array ) {
				foreach ( $section_array as $name => $value ) {
					if ( ! is_null( $options_from_table[$section_key][$name] ) ) {
						if ( is_int( $value ) ) {
							$default_options[$section_key][$name] = ( int ) $options_from_table[$section_key][$name];
						} else {
							$default_options[$section_key][$name] = $options_from_table[$section_key][$name];
							if ( 'associated_id' == $name ) {
								if ( 'blogavirtualh-20' == $options_from_table[$section_key][$name] )
									$default_options[$section_key][$name] = 'avh-amazon-20';
							}
						}
					}
				}
			}

			// If a newer version is running do upgrades if neccesary and update the database.
			if ( $this->version > $options_from_table['general']['version'] ) {
				// Starting with version 2.1 I switched to a new way of storing the widget options in the database. We need to convert these.
				if ( $options_from_table['general']['version'] < '2.1' ) {
					$this->upgradeWidgetOptions_2_1();
				}

				if ( $options_from_table['general']['version'] < '2.4' ) {
					$this->doRemoveCacheFolder();
				}
				if ( $options_from_table['general']['version'] < '3.0' ) {
					$this->upgradeWidgetSettings_3_0();
				}
				// Write the new default options and the proper version to the database
				$default_options['general']['version'] = $this->version;
				update_option( $this->db_options_name_core, $default_options );
			}
		}
		// Set the class property for options
		$this->options = $default_options;
	} // End handleOptions()


	/**
	 * Remove Cache folder as it's no longer needed
	 *
	 * @since 2.4
	 *
	 */
	function doRemoveCacheFolder ()
	{
		$wsdlcachefolder = str_replace( '/2.5', '', $this->info['plugin_dir'] ) . '/cache/';
		if ( ($dirhandle = @opendir( $wsdlcachefolder )) ) {
			while ( false !== ($filename = readdir( $dirhandle )) ) {
				$filename = $wsdlcachefolder . $filename;
				@unlink( $filename );
			}
			closedir( $dirhandle );
			rmdir( $wsdlcachefolder );
		}
	} // end removeCacheFolder


	/**
	 * Upgrade the way the widgets data is stored
	 *
	 * Because of the the way I handle multiple widgets since version 2.1, the way the widget data is stored
	 * in the WordPress database has changed. This function will handle the conversion.
	 *
	 * @since 2.1
	 *
	 */
	function upgradeWidgetOptions_2_1 ()
	{
		//  Keep hardcoded name, in case we change the name at a later stage
		$oldvalues = get_option( 'widget_avhamazon_wishlist' );
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

		delete_option( 'widget_avhamazon_wishlist' );
		add_option( $this->db_options_name_widget_wishlist, $all_options );

	} // End upgradeWidgetOptions_2_1


	/**
	 * Since version 2.2 the default options are stored in a multidimensional array.
	 * This function will convert the pre 2.2 settings to the new standard.
	 *
	 * @since 2.2
	 *
	 */
	function upgradeDefaultOptions_2_2 ()
	{
		// Keep hardcoded name, in case we change the name at a later stage
		$oldvalues = get_option( 'avhamazon' );
		$newvalues = array ('general' => array (), 'widget_wishlist' => array () );

		foreach ( $oldvalues as $name => $value ) {
			if ( array_key_exists( $name, $this->default_options['general'] ) ) {
				$newvalues['general'][$name] = $value;
			}
			if ( array_key_exists( $name, $this->default_options['widget_wishlist'] ) ) {
				$newvalues['widget_wishlist'][$name] = $value;
			}
		}
		delete_option( 'avhamazon' );
		add_option( $this->db_options_name_core, $newvalues );
	} // end upgradeDefaultOptions


	function upgradeWidgetSettings_3_0 ()
	{
		$sidebars_widgets = get_option( 'sidebars_widgets' );
		foreach ( $sidebars_widgets as $key_sidebar => $sidebar ) {
			if ( (! empty( $sidebar )) && (is_array( $sidebar )) ) {
				foreach ( $sidebar as $key_widget => $widget ) {
					if ( 'widget-avhamazon-wishlist' == substr( $widget, 0, 25 ) ) {
						$new_widget = str_replace( 'widget-avhamazon-wishlist', 'avhamazon_wishlist', $widget );
						$sidebars_widgets[$key_sidebar][$key_widget] = $new_widget;
					}
				}
			}
		}
		update_option( 'sidebars_widgets', $sidebars_widgets );
	}

	/**
	 * Get all the items from the list
	 *
	 * @param string $ListID The Wish List ID of the list to get
	 * @return array Items
	 */
	function getListResults ( $ListID )
	{
		$list = $this->handleRESTcall( $this->getRestListLookupParams( $ListID ) );

		if ( 1 == $list['Lists']['List']['TotalItems'] ) {
			$list['Lists']['List']['ListItem'] = array ('0' => $list['Lists']['List']['ListItem'] ); // If one item in the list we need to make it a multi array
		} else {
			if ( $list['Lists']['List']['TotalPages'] > 1 ) { // If the list contains over 10 items we need to process the other pages.
				$page = 2;
				while ( $page <= $list['Lists']['List']['TotalPages'] ) {
					$result = $this->handleRESTcall( $this->getRestListLookupParams( $ListID, null, $page ) );
					foreach ( $result['Lists']['List']['ListItem'] as $key => $value ) {
						$newkey = 10 * ($page - 1) + $key;
						$list['Lists']['List']['ListItem'][$newkey] = $value; //Add the items from the remaining pages to the lists.
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
	function getItemKeys ( $list, $nr_of_items = 1 )
	{
		$total_items = count( $list );
		if ( $nr_of_items > $total_items )
			$nr_of_items = $total_items;
		return (( array ) array_rand( $list, $nr_of_items ));
	}

	/**
	 * Actual Rest Call
	 *
	 * @param array $query_array
	 * @return array
	 * @since 2.4
	 */
	function handleRESTcall ( $query_array )
	{
		$xml_array = array ();

		$querystring = $this->getAWSQueryString( $query_array );

		$url = $this->amazon_endpoint . '?' . $querystring;

		$response = wp_remote_request( $url );
		if ( ! is_wp_error( $response ) ) {
			$xml_array = $this->ConvertXML2Array( $response['body'] );
		} else {
			$return_array = array ('Error' => $response->errors );
		}
		// It will be empty if we had an error.
		if ( ! empty( $xml_array ) ) {
			// Depending on the Operation called we'll return the right array back.
			$key = $query_array['Operation'] . 'Response';
			if ( ! isset( $xml_array[$key] ) ) {
				echo 'Unknown Operation in rest Call';
				die();
			}

			if ( isset( $xml_array[$key]['OperationRequest']['Errors'] ) ) {
				$return_array = $xml_array[$key]['OperationRequest']['Errors']['Error'][1];
			} else {
				$return_array = $xml_array[$key];
			}
		}
		return ($return_array);
	}

	/**
	 * Build the Query
	 *
	 * @param array $query_array
	 * @return string
	 * @since 3.0
	 *
	 */
	function getAWSQueryString ( $query_array )
	{
		$query_array['Timestamp'] = gmdate( 'Y-m-d\TH:i:s\Z' );
		//@TODO Per August 15, 2009 all request to Amazon need to be signed, until then they accept unsigned requests as well.


		if ( ! empty( $this->options['general']['awssecretkey'] ) ) {
			$endpoint = parse_url( $this->amazon_endpoint );
			ksort( $query_array );

			$query_string = $this->BuildQuery( $query_array );
			$str = "GET\n" . $endpoint['host'] . "\n/onca/xml\n" . $query_string;

			if ( $this->running_php5 ) {
				// PHP5 Native function is much quicker.
				$query_array['Signature'] = base64_encode( hash_hmac( 'sha256', $str, $this->options['general']['awssecretkey'], true ) );
			} else {
				// PHP4 function to get the hash_hmac sha256
				$query_array['Signature'] = base64_encode( hmac( $this->options['general']['awssecretkey'], $str ) );
			}
		}

		$querystring = $this->BuildQuery( $query_array );
		return ($querystring);
	}

	/**
	 * Format an error message from the WP_Error response by wp_remote_request
	 *
	 * @param array $error
	 * @return string
	 * @since 2.4
	 *
	 */
	function getHttpError ( $error )
	{
		foreach ( $error as $key => $value ) {
			$error_short = $key;
			$error_long = $value[0];
		}
		return '<strong>avhamazon error:' . $error_short . ' - ' . $error_long . '</strong>';
	}

	/**
	 * Rest Request - ListLookup
	 *
	 * @param string $ListID
	 * @param string $WhatList
	 * @param integer $page
	 * @return array
	 * @since 2.4
	 */
	function getRestListLookupParams ( $ListID, $WhatList = null, $page = null )
	{
		$WhatList = (is_null( $WhatList ) ? 'WishList' : $WhatList);
		$page = (is_null( $page ) ? 1 : $page);

		$listLookup = array ('Operation' => 'ListLookup', 'ListId' => $ListID, 'ListType' => $WhatList, 'ResponseGroup' => 'ListFull', 'IsOmitPurchasedItems' => '1', 'ProductPage' => ( string ) $page, 'Sort' => 'LastUpdated' );

		$request = array_merge( $this->getRestStandardRequest(), $listLookup );

		return $request;
	}

	/**
	 * Rest Request - ItemLookup
	 *
	 * @param string $Itemid
	 * @param string $associatedid
	 * @return array
	 * @since 2.4
	 */
	function getRestItemLookupParams ( $Itemid, $associatedid )
	{
		$itemLookUp = array ('Operation' => 'ItemLookup', 'ItemId' => $Itemid, 'IdType' => 'ASIN', 'Condition' => 'All', 'ResponseGroup' => 'Medium', 'AssociateTag' => $associatedid );

		$request = array_merge( $this->getRestStandardRequest(), $itemLookUp );

		return $request;
	}

	/**
	 * Rest request - ListSearch
	 *
	 * @param string $email
	 * @param string $list
	 * @return array
	 * @since ??
	 */
	function getRestListSearchParams ( $email, $list = 'WishList' )
	{
		$request = array ('Operation' => 'ListSearch', 'Email' => $email, 'ListType' => $list, 'ResponseGroup' => 'ListInfo' );

		$return = array_merge( $this->getRestStandardRequest(), $request );

		return $return;
	}

	/**
	 * Get the standard request array.
	 *
	 * @return array
	 * @since 3.0
	 * @TODO Per August 15, 2009 all request to Amazon need to be signed, until then they accept unsigned requests as well.
	 */
	function getRestStandardRequest ()
	{

		// @TODO Until August
		$this->amazon_standard_request['AWSAccessKeyId'] = empty( $this->amazon_standard_request['AWSAccessKeyId'] ) ? '1MPCC36EZ827YJQ02AG2' : $this->options['general']['awskey'];
		// After August
		// $this->amazon_standard_request['AWSAccessKeyId'] = $this->options['general']['awskey'];
		return $this->amazon_standard_request;
	}

	/**
	 * Convert an array into a query string
	 *
	 * @param array $array
	 * @param string $convention
	 * @return string
	 * @since 2.4
	 */
	function BuildQuery ( $array = NULL )
	{
		if ( count( $array ) == 0 ) {
			return '';
		} else {
			$query = http_build_query( $array );
			return $query;
		}
	}

	/**
	 * Convert XML into an array
	 *
	 * @param string $contents
	 * @param integer $get_attributes
	 * @param string $priority
	 * @return array
	 * @since 2.4
	 * @see http://www.bin-co.com/php/scripts/xml2array/
	 */
	function ConvertXML2Array ( $contents = '', $get_attributes = 1, $priority = 'tag' )
	{
		$xml_values = '';
		$return_array = array ();
		$tag = '';
		$type = '';
		$level = 0;
		$attributes = array ();
		if ( function_exists( 'xml_parser_create' ) ) {
			$parser = xml_parser_create( 'UTF-8' );

			xml_parser_set_option( $parser, XML_OPTION_TARGET_ENCODING, "UTF-8" );
			xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
			xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 );
			xml_parse_into_struct( $parser, trim( $contents ), $xml_values );
			xml_parser_free( $parser );

			//Initializations
			$xml_array = array ();
			$parent = array ();

			$current = & $xml_array; // Reference


			// Go through the tags.
			$repeated_tag_index = array ();

			// Multiple tags with same name will be turned into an array
			foreach ( $xml_values as $data ) {
				unset( $attributes, $value ); //Remove existing values, or there will be trouble


				// This command will extract these variables into the foreach scope
				// tag(string), type(string), level(int), attributes(array).
				extract( $data ); //We could use the array by itself, but this cooler.


				$result = array ();
				$attributes_data = array ();

				if ( isset( $value ) ) {
					if ( $priority == 'tag' ) {
						$result = $value;
					} else {
						$result['value'] = $value; //Put the value in an associate array if we are in the 'Attribute' mode
					}
				}

				// Set the attributes too
				if ( isset( $attributes ) and $get_attributes ) {
					foreach ( $attributes as $attr => $val ) {
						if ( $priority == 'tag' )
							$attributes_data[$attr] = $val;
						else
							$result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
					}
				}

				// See tag status and do what's needed
				if ( $type == "open" ) { // The starting of the tag '<tag>'
					$parent[$level - 1] = & $current;

					if ( ! is_array( $current ) or (! in_array( $tag, array_keys( $current ) )) ) { //Insert New tag
						$current[$tag] = $result;
						if ( $attributes_data )
							$current[$tag . '_attr'] = $attributes_data;
						$repeated_tag_index[$tag . '_' . $level] = 1;

						$current = & $current[$tag];

					} else { // There was another element with the same tag name


						if ( isset( $current[$tag][0] ) ) { //If there is a 0th element it is already an array
							$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
							$repeated_tag_index[$tag . '_' . $level] ++;
						} else { //This section will make the value an array if multiple tags with the same name appear together
							$current[$tag] = array ($current[$tag], $result );
							//This will combine the existing item and the new item together to make an array
							$repeated_tag_index[$tag . '_' . $level] = 2;

							if ( isset( $current[$tag . '_attr'] ) ) { // The attribute of the last(0th) tag must be moved as well
								$current[$tag]['0_attr'] = $current[$tag . '_attr'];
								unset( $current[$tag . '_attr'] );
							}
						}
						$last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
						$current = & $current[$tag][$last_item_index];
					}
				} elseif ( $type == "complete" ) { //Tags that ends in 1 line '<tag />'
					//See if the key is already taken.
					if ( ! isset( $current[$tag] ) ) { // New key
						$current[$tag] = $result;
						$repeated_tag_index[$tag . '_' . $level] = 1;
						if ( $priority == 'tag' and $attributes_data )
							$current[$tag . '_attr'] = $attributes_data;
					} else { //If taken, put all things inside a list(array)
						if ( isset( $current[$tag][0] ) and is_array( $current[$tag] ) ) {
							//This will combine the existing item and the new item together to make an array
							$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
							if ( $priority == 'tag' and $get_attributes and $attributes_data ) {
								$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
							}
							$repeated_tag_index[$tag . '_' . $level] ++;
						} else { //If it is not an array...
							$current[$tag] = array ($current[$tag], $result ); //...Make it an array using using the existing value and the new value
							$repeated_tag_index[$tag . '_' . $level] = 1;
							if ( $priority == 'tag' and $get_attributes ) {
								if ( isset( $current[$tag . '_attr'] ) ) { //The attribute of the last(0th) tag must be moved as well
									$current[$tag]['0_attr'] = $current[$tag . '_attr'];
									unset( $current[$tag . '_attr'] );
								}
								if ( $attributes_data ) {
									$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
								}
							}
							$repeated_tag_index[$tag . '_' . $level] ++; //0 and 1 index is already taken
						}
					}
				} elseif ( $type == 'close' ) { //End of tag '</tag>'
					$current = & $parent[$level - 1];
				}
			}
			$return_array = $xml_array;
		}
		return ($return_array);
	}

	/**
	 * Get the image URL for an item
	 *
	 * @param string $imagesize (small,medium,large)
	 * @param array Result of the Item Lookup call
	 * @return array the image's URL, Height, Width
	 */
	function getImageInfo ( $imagesize, $item_result )
	{
		$imageurl = $this->info['graphics_url'];
		if (is_array($item_result['Items']['Item']['ImageSets']['ImageSet'])) {
			if (is_array($item_result['Items']['Item']['ImageSets']['ImageSet'][0])) {
				$imageset = $item_result['Items']['Item']['ImageSets']['ImageSet'][0];
			} else {
				$imageset = $item_result['Items']['Item']['ImageSets']['ImageSet'];
			}
		} else {
			 $imageset = $item_result['Items']['Item'];
		}
		switch ( strtolower( $imagesize ) ) {
			case 'medium' :
				$img['url'] = $imageset['MediumImage']['URL'];
				$img['h'] = $imageset['MediumImage']['Height'];
				$img['w'] = $imageset['MediumImage']['Width'];
				if ( empty( $img['url'] ) ) {
					$img['url'] = $imageurl . '/no-image-160.gif';
					$img['h'] = 75;
					$img['w'] = 75;
				}
				break;
			case 'small' :
				$img['url'] = $imageset['SmallImage']['URL'];
				$img['h'] = $imageset['SmallImage']['Height'];
				$img['w'] = $imageset['SmallImage']['Width'];
				if ( empty( $img['url'] ) ) {
					$img['url'] = $imageurl . '/no-image-75.gif';
					$img['h'] = 75;
					$img['w'] = 75;
				}
				break;
			case 'large' :
				$img['url'] = $imageset['LargeImage']['URL'];
				$img['h'] = $imageset['LargeImage']['Height'];
				$img['w'] = $imageset['LargeImage']['Width'];
				if ( empty( $img['url'] ) ) {
					$img['url'] = $imageurl . '/no-image-500.gif';
					$img['h'] = 75;
					$img['w'] = 75;
				}
				break;
			case 'swatch' :
				$img['url'] = $imageset['SwatchImage']['URL'];
				$img['h'] = $imageset['SwatchImage']['Height'];
				$img['w'] = $imageset['SwatchImage']['Width'];
				if ( empty( $img['url'] ) ) {
					$img['url'] = '';
					$img['h'] = 0;
					$img['w'] = 0;
				}
				break;
			default :
				$img['url'] = $imageset['MediumImage']['URL'];
				$img['h'] = $imageset['MediumImage']['Height'];
				$img['w'] = $imageset['MediumImage']['Width'];
				if ( empty( $img['url'] ) ) {
					$img['url'] = $imageurl . '/no-image-160.gif';
					$img['h'] = 75;
					$img['w'] = 75;
				}
				break;
		}
		return ($img);
	}

	/**
	 * Get the options for the widget
	 *
	 * @param array $a
	 * @param mixed $key
	 * @param string $widget Which widget to get the values from. Defined in the options variable.
	 * @return mixed
	 */
	function getWidgetOptions ( $a, $key, $widget = 'widget_wishlist' )
	{
		$return = '';

		if ( isset( $a[$key] ) && (! empty( $a[$key] )) ) {
			$return = $a[$key]; // From widget
		} else {
			$return = $this->getOption( $key, $widget ); // From Admin Page or Default value
		}
		return ($return);
	}

	/**
	 * Get the value for an option. If there's no option is set on the Admin page, return the default value.
	 *
	 * @param string $key
	 * @param string $option
	 * @return mixed
	 */
	function getOption ( $key, $option )
	{
		if ( $this->options[$option][$key] ) {
			$return = $this->options[$option][$key]; // From Admin Page
		} else {
			$return = $this->default_options[$option][$key]; // Default
		}
		return ($return);
	}

	/**
	 * Get the associate id based on the locale and locale_table
	 *
	 * @param string $locale
	 *
	 */
	function getAssociateId ( $locale )
	{
		if ( array_key_exists( $locale, $this->associate_table ) ) {
			$associatedid = $this->associate_table[$locale];
		} else {
			$associatedid = 'avh-amazon-20';
		}
		return ($associatedid);
	}

	/**
	 * Get the base directory of a directory structure
	 *
	 * @param string $directory
	 * @return string
	 *
	 * @since 2.3
	 *
	 */
	function getBaseDirectory ( $directory )
	{
		//get public directory structure eg "/top/second/third"
		$public_directory = dirname( $directory );
		//place each directory into array
		$directory_array = explode( '/', $public_directory );
		//get highest or top level in array of directory strings
		$public_base = max( $directory_array );

		return $public_base;
	}

	/**
	 * Returns the wordpress version
	 * Note: 2.7.x will return 2.7
	 *
	 * @return float
	 *
	 * @since 2.3
	 */
	function getWordpressVersion ()
	{
		// Include WordPress version
		require (ABSPATH . WPINC . '/version.php');
		$version = ( float ) $wp_version;
		return $version;
	}

	/**
	 * Used in forms to set the checked option.
	 *
	 * @param mixed $checked
	 * @param mixed_type $current
	 * @return string
	 *
	 * @since 2.3.4
	 */
	function isChecked ( $checked, $current )
	{
		if ( $checked == $current ) {
			return (' checked="checked"');
		}
		return '';
	}

	/**
	 * Get the backlink for forms
	 *
	 * @return strings
	 * @since 2.4
	 */
	function getBackLink ()
	{
		$page = basename( __FILE__ );
		if ( isset( $_GET['page'] ) && ! empty( $_GET['page'] ) ) {
			$page = preg_replace( '[^a-zA-Z0-9\.\_\-]', '', $_GET['page'] );
		}

		if ( function_exists( "admin_url" ) )
			return admin_url( basename( $_SERVER["PHP_SELF"] ) ) . "?page=" . $page;
		else
			return $_SERVER['PHP_SELF'] . "?page=" . $page;
	}

} //End Class avh_amazon


/**
 * SHA256 Class.
 */
require_once 'inc/avh-amazon.sha256.inc.php';

/**
 * Initialize the plugin
 *
 */
function avhamazon_init ()
{
	// Admin
	if ( is_admin() ) {
		require (dirname( __FILE__ ) . '/inc/avh-amazon.admin.php');
		$avhamazon_admin = & new AVHAmazonAdmin( );
		// Installation
		register_activation_hook( __FILE__, array (&$avhamazon_admin, 'installPlugin' ) );
	}

	// Include shortcode class
	require (dirname( __FILE__ ) . '/inc/avh-amazon.shortcode.php');
	$avhamazon_shortcode = & new AVHAmazonShortcode( );

	// Include the widgets code
	require (dirname( __FILE__ ) . '/inc/avh-amazon.widgets.php');
	add_action( 'widgets_init', 'avhamazon_widgets_init' );

} // End avhamazon_init()


/**
 * Register the widget
 *
 * @WordPress Action widgets_init
 * @since 3.0
 *
 */
function avhamazon_widgets_init ()
{
	register_widget( 'WP_Widget_AVHAmazon_Wishlist' );
}

add_action( 'plugins_loaded', 'avhamazon_init' );
?>
