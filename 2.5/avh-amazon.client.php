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
		$this->amazon_endpoint_table = array (
			'US' => 'http://ecs.amazonaws.com/onca/xml',
			'CA' => 'http://ecs.amazonaws.ca/onca/xml',
			'DE' => 'http://ecs.amazonaws.de/onca/xml',
			'UK' => 'http://ecs.amazonaws.co.uk/onca/xml' );
		$this->amazon_endpoint = $this->amazon_endpoint_table['US'];
		$this->amazon_standard_request = array (
			'Service' => 'AWSECommerceService',
			'Version' => '2009-03-31',
			'AWSAccessKeyId' => '',
			'Timestamp' => '' );

		/**
		 * Amazon general options
		 *
		 */
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

		$this->db_options_name_core = 'avhamazon';
		$this->db_options_name_widget_wishlist = 'widget_avhamazon_wishlist';

		/**
		 * Default options - General Purpose
		 *
		 */
		$this->default_general_options = array (
			'version' => $this->version,
			'associated_id' => 'avh-amazon-20',
			'awskey' => '',
			'awssecretkey' => '',
			'policychange' => '' );

		/**
		 * Default options - Widget Wishlist
		 *
		 */
		$this->default_widget_wishlist_options = array (
			'title' => 'Amazon Wish List',
			'wishlist_id' => '2CC2KKW02870',
			'wishlist_imagesize' => 'Medium',
			'locale' => 'US',
			'nr_of_items' => 1,
			'show_footer' => 0,
			'footer_template' => 'Show all %nr_of_items% items',
			'new_window' => 0 );

		/**
		 * Default options - Shortcode
		 *
		 */
		$this->default_shortcode_options = array (
			'wishlist_id' => '',
			'locale' => 'US' );

		/**
		 * Default Options - All as stored in the DB
		 *
		 */
		$this->default_options = array (
			'general' => $this->default_general_options,
			'widget_wishlist' => $this->default_widget_wishlist_options,
			'shortcode' => $this->default_shortcode_options );

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
				$info['install_uri'] = '/wp-content/plugins/' . $path;
			}
		}

		// Set class property for info
		$this->info = array ('home' => get_option( 'home' ), 'siteurl' => $info['siteurl'], 'install_url' => $info['install_url'], 'install_uri' => $info['install_uri'], 'install_dir' => $info['install_dir'], 'graphics_url' => $info['install_url'] . '/images', 'wordpress_version' => $this->getWordpressVersion() );

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
		$wsdlcachefolder = str_replace( '/2.5', '', $this->info['install_dir'] ) . '/cache/';
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
		if ( $nr_of_items > $total_items ){
			$nr_of_items = $total_items;
		}
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

		$querystring = $this->getAWSQueryString($query_array);

		$url = $this->amazon_endpoint . '?' . $querystring;

		// Starting with WordPress 2.7 we'll use the HTTP class.
		if ( function_exists( 'wp_remote_request' ) ) {
			$response = wp_remote_request( $url );
			if ( ! is_wp_error( $response ) ) {
				$xml_array = $this->ConvertXML2Array( $response['body'] );
			} else {
				$return_array = array ('Error' => $response->errors );
			}
		} else { // Prior to WordPress 2.7 we'll use the Snoopy Class.
			require_once (ABSPATH . 'wp-includes/class-snoopy.php');
			$snoopy = new Snoopy( );
			$snoopy->fetch( $url );
			if ( ! $snoopy->error ) {
				$response = $snoopy->results;
				$xml_array = $this->ConvertXML2Array( $response );
			} else {
				$response = array ($snoopy->error => array (0 => $url ) );
				$return_array = array ('Error' => $response );
			}
		}

		// It will be empty if we had an error.
		if ( ! empty( $xml_array ) ) {
			// Depending on the Operation called we'll return the right array back.
			$key = $query_array['Operation'] . 'Response';
			if ( ! isset( $xml_array[$key] ) ) {
				echo 'Unknown Operation in rest Call';
				die();
			}
			$return_array = $xml_array[$key];
		}
		return ($return_array);
	}

	/**
	 * Build the Query
	 *
	 * @param array $query_array
	 * @return string
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

			$query_array['Signature'] = base64_encode( hash_hmac( 'sha256', $str, $this->options['general']['awssecretkey'], true ) );
			$test =  base64_encode($this->hmac($this->options['general']['awssecretkey'],$str));
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

		$listLookup = array (
			'Operation' => 'ListLookup',
			'ListId' => $ListID,
			'ListType' => $WhatList,
			'ResponseGroup' => 'ListFull',
			'IsOmitPurchasedItems' => '1',
			'ProductPage' => ( string ) $page,
			'Sort' => 'LastUpdated' );

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
		$itemLookUp = array (
			'Operation' => 'ItemLookup',
			'ItemId' => $Itemid,
			'IdType' => 'ASIN',
			'Condition' => 'All',
			'ResponseGroup' => 'Medium',
			'AssociateTag' => $associatedid );

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
		$request = array (
			'Operation' => 'ListSearch',
			'Email' => $email,
			'ListType' => $list,
			'ResponseGroup' => 'ListInfo' );

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
	function getRestStandardRequest() {

		// @TODO Until August
		$this->amazon_standard_request['AWSAccessKeyId'] = (empty($this->options['general']['awskey'])) ? '1MPCC36EZ827YJQ02AG2' : $this->options['general']['awskey'];

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
	function BuildQuery ( $array = NULL, $convention = '%s' )
	{
		if ( count( $array ) == 0 ) {
			return '';
		} else {
			if ( function_exists( 'http_build_query' ) ) {
				$query = http_build_query( $array );
			} else {
				$query = '';
				foreach ( $array as $key => $value ) {
					if ( is_array( $value ) ) {
						$new_convention = sprintf( $convention, $key ) . '[%s]';
						$query .= BuildQuery( $value, $new_convention );
					} else {
						$key = urlencode( $key );
						$value = urlencode( $value );
						$query .= sprintf( $convention, $key ) . "=$value&";
					}
				}
			}
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
		switch ( strtolower( $imagesize ) ) {
			case 'medium' :
				$img['url'] = $item_result['Items']['Item']['MediumImage']['URL'];
				$img['h'] = $item_result['Items']['Item']['MediumImage']['Height'];
				$img['w'] = $item_result['Items']['Item']['MediumImage']['Width'];
				if ( empty( $img['url'] ) ) {
					$img['url'] = $imageurl . '/no-image-160.gif';
					$img['h'] = 75;
					$img['w'] = 75;
				}
				break;
			case 'small' :
				$img['url'] = $item_result['Items']['Item']['SmallImage']['URL'];
				$img['h'] = $item_result['Items']['Item']['SmallImage']['Height'];
				$img['w'] = $item_result['Items']['Item']['SmallImage']['Width'];
				if ( empty( $img['url'] ) ) {
					$img['url'] = $imageurl . '/no-image-75.gif';
					$img['h'] = 75;
					$img['w'] = 75;
				}
				break;
			case 'large' :
				$img['url'] = $item_result['Items']['Item']['LargeImage']['URL'];
				$img['h'] = $item_result['Items']['Item']['LargeImage']['Height'];
				$img['w'] = $item_result['Items']['Item']['LargeImage']['Width'];
				if ( empty( $img['url'] ) ) {
					$img['url'] = $imageurl . '/no-image-500.gif';
					$img['h'] = 75;
					$img['w'] = 75;
				}
				break;
			default :
				$img['url'] = $item_result['Items']['Item']['MediumImage']['URL'];
				$img['h'] = $item_result['Items']['Item']['SmallImage']['Height'];
				$img['w'] = $item_result['Items']['Item']['SmallImage']['Width'];
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
		if ( $checked == $current )
			return (' checked="checked"');
	}

	/**
	 * Insert the CSS file
	 *
	 * @param string $handle CSS Handle
	 * @param string $cssfile
	 *
	 * @since 2.3
	 */
	function handleCssFile ( $handle, $cssfile )
	{
		wp_register_style( $handle, $this->info['install_uri'] . $cssfile, array (), $this->version, 'all' );
		if ( did_action( 'wp_print_styles' ) ) { // we already printed the style queue.  Print this one immediately
			wp_print_styles( $handle );
		} else {
			wp_enqueue_style( $handle );
		}
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

   function hmac($key, $data, $hashfunc='sha256')
    {
     $blocksize=64;

     if (strlen($key) > $blocksize) $key=pack('H*', $hashfunc($key));
     $key=str_pad($key, $blocksize, chr(0x00));
     $ipad=str_repeat(chr(0x36), $blocksize);
     $opad=str_repeat(chr(0x5c), $blocksize);
     $hmac = pack('H*', $hashfunc(($key^$opad) . pack('H*', $hashfunc(($key^$ipad) . $data))));
     return $hmac;
    }
} //End Class avh_amazon

class shaHelper
{
    function shaHelper()
    {
        // nothing to construct here...
    }

    // Do the SHA-256 Padding routine (make input a multiple of 512 bits)
    function char_pad($str)
    {
        $tmpStr = $str;

        $l = strlen($tmpStr)*8;     // # of bits from input string

        $tmpStr .= "\x80";          // append the "1" bit followed by 7 0's

        $k = (512 - (($l + 8 + 64) % 512)) / 8;   // # of 0 bytes to append
        $k += 4;    // PHP String's will never exceed (2^31)-1, so 1st 32bits of
                    // the 64-bit value representing $l can be all 0's

        for ($x = 0; $x < $k; $x++)
            $tmpStr .= "\0";

        // append the last 32-bits representing the # of bits from input string ($l)
        $tmpStr .= chr((($l>>24) & 0xFF));
        $tmpStr .= chr((($l>>16) & 0xFF));
        $tmpStr .= chr((($l>>8) & 0xFF));
        $tmpStr .= chr(($l & 0xFF));

        return $tmpStr;
    }

    // Here are the bitwise and custom functions as defined in FIPS180-2 Standard
    function addmod2n($x, $y, $n = 4294967296)      // Z = (X + Y) mod 2^32
    {
        $mask = 0x80000000;

        if ($x < 0)
        {
            $x &= 0x7FFFFFFF;
            $x = (float)$x + $mask;
        }

        if ($y < 0)
        {
            $y &= 0x7FFFFFFF;
            $y = (float)$y + $mask;
        }

        $r = $x + $y;

        if ($r >= $n)
        {
            while ($r >= $n)
                $r -= $n;
        }

        return (int)$r;
    }

    // Logical bitwise right shift (PHP default is arithmetic shift)
    function SHR($x, $n)        // x >> n
    {
        if ($n >= 32)       // impose some limits to keep it 32-bit
            return (int)0;

        if ($n <= 0)
            return (int)$x;

        $mask = 0x40000000;

        if ($x < 0)
        {
            $x &= 0x7FFFFFFF;
            $mask = $mask >> ($n-1);
            return ($x >> $n) | $mask;
        }

        return (int)$x >> (int)$n;
    }

    function ROTR($x, $n) { return (int)($this->SHR($x, $n) | ($x << (32-$n))); }
    function Ch($x, $y, $z) { return ($x & $y) ^ ((~$x) & $z); }
    function Maj($x, $y, $z) { return ($x & $y) ^ ($x & $z) ^ ($y & $z); }
    function Sigma0($x) { return (int) ($this->ROTR($x, 2)^$this->ROTR($x, 13)^$this->ROTR($x, 22)); }
    function Sigma1($x) { return (int) ($this->ROTR($x, 6)^$this->ROTR($x, 11)^$this->ROTR($x, 25)); }
    function sigma_0($x) { return (int) ($this->ROTR($x, 7)^$this->ROTR($x, 18)^$this->SHR($x, 3)); }
    function sigma_1($x) { return (int) ($this->ROTR($x, 17)^$this->ROTR($x, 19)^$this->SHR($x, 10)); }

    /*
     * Custom functions to provide PHP support
     */
    // split a byte-string into integer array values
    function int_split($input)
    {
        $l = strlen($input);

        if ($l <= 0)        // right...
            return (int)0;

        if (($l % 4) != 0)  // invalid input
            return false;

        for ($i = 0; $i < $l; $i += 4)
        {
            $int_build  = (ord($input[$i]) << 24);
            $int_build += (ord($input[$i+1]) << 16);
            $int_build += (ord($input[$i+2]) << 8);
            $int_build += (ord($input[$i+3]));

            $result[] = $int_build;
        }

        return $result;
    }
}

// Compatability with older versions of PHP < 5
if ( ! function_exists( 'str_split' ) ) {

	function str_split ( $string, $split_length = 1 )
	{
		$sign = (($split_length < 0) ? - 1 : 1);
		$strlen = strlen( $string );
		$split_length = abs( $split_length );

		if ( ($split_length == 0) || ($strlen == 0) ) {
			$result = false;
		} elseif ( $split_length >= $strlen ) {
			$result[] = $string;
		} else {
			$length = $split_length;

			for ( $i = 0; $i < $strlen; $i ++ ) {
				$i = (($sign < 0) ? $i + $length : $i);
				$result[] = substr( $string, $sign * $i, $length );
				$i --;
				$i = (($sign < 0) ? $i : $i + $length);

				if ( ($i + $split_length) > ($strlen) ) {
					$length = $strlen - ($i + 1);
				} else {
					$length = $split_length;
				}
			}
		}

		return $result;
	}
}

function sha256 ( $str, $ig_func = false )
{
	unset( $binStr ); // binary representation of input string
	unset( $hexStr ); // 256-bit message digest in readable hex format


	// check for php 5.1.2's internal sha256 function, ignore if ig_func is true
	if ( $ig_func == false )
		if ( function_exists( "hash" ) )
			return hash( "sha256", $str, false );

	$sh = new shaHelper( );

	// SHA-256 Constants
	// sequence of sixty-four constant 32-bit words representing the first thirty-two bits
	// of the fractional parts of the cube roots of the first sixtyfour prime numbers.
	$K = array (( int ) 0x428a2f98, ( int ) 0x71374491, ( int ) 0xb5c0fbcf, ( int ) 0xe9b5dba5, ( int ) 0x3956c25b, ( int ) 0x59f111f1, ( int ) 0x923f82a4, ( int ) 0xab1c5ed5, ( int ) 0xd807aa98, ( int ) 0x12835b01, ( int ) 0x243185be, ( int ) 0x550c7dc3, ( int ) 0x72be5d74, ( int ) 0x80deb1fe, ( int ) 0x9bdc06a7, ( int ) 0xc19bf174, ( int ) 0xe49b69c1, ( int ) 0xefbe4786, ( int ) 0x0fc19dc6, ( int ) 0x240ca1cc, ( int ) 0x2de92c6f, ( int ) 0x4a7484aa, ( int ) 0x5cb0a9dc, ( int ) 0x76f988da, ( int ) 0x983e5152, ( int ) 0xa831c66d, ( int ) 0xb00327c8, ( int ) 0xbf597fc7, ( int ) 0xc6e00bf3, ( int ) 0xd5a79147, ( int ) 0x06ca6351, ( int ) 0x14292967, ( int ) 0x27b70a85, ( int ) 0x2e1b2138, ( int ) 0x4d2c6dfc, ( int ) 0x53380d13, ( int ) 0x650a7354, ( int ) 0x766a0abb, ( int ) 0x81c2c92e, ( int ) 0x92722c85, ( int ) 0xa2bfe8a1, ( int ) 0xa81a664b, ( int ) 0xc24b8b70, ( int ) 0xc76c51a3, ( int ) 0xd192e819, ( int ) 0xd6990624, ( int ) 0xf40e3585, ( int ) 0x106aa070, ( int ) 0x19a4c116, ( int ) 0x1e376c08, ( int ) 0x2748774c, ( int ) 0x34b0bcb5, ( int ) 0x391c0cb3, ( int ) 0x4ed8aa4a, ( int ) 0x5b9cca4f, ( int ) 0x682e6ff3, ( int ) 0x748f82ee, ( int ) 0x78a5636f, ( int ) 0x84c87814, ( int ) 0x8cc70208, ( int ) 0x90befffa, ( int ) 0xa4506ceb, ( int ) 0xbef9a3f7, ( int ) 0xc67178f2 );

	// Pre-processing: Padding the string
	$binStr = $sh->char_pad( $str );

	// Parsing the Padded Message (Break into N 512-bit blocks)
	$M = str_split( $binStr, 64 );

	// Set the initial hash values
	$h[0] = ( int ) 0x6a09e667;
	$h[1] = ( int ) 0xbb67ae85;
	$h[2] = ( int ) 0x3c6ef372;
	$h[3] = ( int ) 0xa54ff53a;
	$h[4] = ( int ) 0x510e527f;
	$h[5] = ( int ) 0x9b05688c;
	$h[6] = ( int ) 0x1f83d9ab;
	$h[7] = ( int ) 0x5be0cd19;

	// loop through message blocks and compute hash. ( For i=1 to N : )
	for ( $i = 0; $i < count( $M ); $i ++ ) {
		// Break input block into 16 32-bit words (message schedule prep)
		$MI = $sh->int_split( $M[$i] );

		// Initialize working variables
		$_a = ( int ) $h[0];
		$_b = ( int ) $h[1];
		$_c = ( int ) $h[2];
		$_d = ( int ) $h[3];
		$_e = ( int ) $h[4];
		$_f = ( int ) $h[5];
		$_g = ( int ) $h[6];
		$_h = ( int ) $h[7];
		unset( $_s0 );
		unset( $_s1 );
		unset( $_T1 );
		unset( $_T2 );
		$W = array ();

		// Compute the hash and update
		for ( $t = 0; $t < 16; $t ++ ) {
			// Prepare the first 16 message schedule values as we loop
			$W[$t] = $MI[$t];

			// Compute hash
			$_T1 = $sh->addmod2n( $sh->addmod2n( $sh->addmod2n( $sh->addmod2n( $_h, $sh->Sigma1( $_e ) ), $sh->Ch( $_e, $_f, $_g ) ), $K[$t] ), $W[$t] );
			$_T2 = $sh->addmod2n( $sh->Sigma0( $_a ), $sh->Maj( $_a, $_b, $_c ) );

			// Update working variables
			$_h = $_g;
			$_g = $_f;
			$_f = $_e;
			$_e = $sh->addmod2n( $_d, $_T1 );
			$_d = $_c;
			$_c = $_b;
			$_b = $_a;
			$_a = $sh->addmod2n( $_T1, $_T2 );
		}

		for (; $t < 64; $t ++ ) {
			// Continue building the message schedule as we loop
			$_s0 = $W[($t + 1) & 0x0F];
			$_s0 = $sh->sigma_0( $_s0 );
			$_s1 = $W[($t + 14) & 0x0F];
			$_s1 = $sh->sigma_1( $_s1 );

			$W[$t & 0xF] = $sh->addmod2n( $sh->addmod2n( $sh->addmod2n( $W[$t & 0xF], $_s0 ), $_s1 ), $W[($t + 9) & 0x0F] );

			// Compute hash
			$_T1 = $sh->addmod2n( $sh->addmod2n( $sh->addmod2n( $sh->addmod2n( $_h, $sh->Sigma1( $_e ) ), $sh->Ch( $_e, $_f, $_g ) ), $K[$t] ), $W[$t & 0xF] );
			$_T2 = $sh->addmod2n( $sh->Sigma0( $_a ), $sh->Maj( $_a, $_b, $_c ) );

			// Update working variables
			$_h = $_g;
			$_g = $_f;
			$_f = $_e;
			$_e = $sh->addmod2n( $_d, $_T1 );
			$_d = $_c;
			$_c = $_b;
			$_b = $_a;
			$_a = $sh->addmod2n( $_T1, $_T2 );
		}

		$h[0] = $sh->addmod2n( $h[0], $_a );
		$h[1] = $sh->addmod2n( $h[1], $_b );
		$h[2] = $sh->addmod2n( $h[2], $_c );
		$h[3] = $sh->addmod2n( $h[3], $_d );
		$h[4] = $sh->addmod2n( $h[4], $_e );
		$h[5] = $sh->addmod2n( $h[5], $_f );
		$h[6] = $sh->addmod2n( $h[6], $_g );
		$h[7] = $sh->addmod2n( $h[7], $_h );
	}

	// Convert the 32-bit words into human readable hexadecimal format.
	$hexStr = sprintf( "%08x%08x%08x%08x%08x%08x%08x%08x", $h[0], $h[1], $h[2], $h[3], $h[4], $h[5], $h[6], $h[7] );

	return $hexStr;
}


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
	$avhamazon_widget = & new AVHAmazonWidget( );

} // End avhamazon_init()


add_action( 'plugins_loaded', 'avhamazon_init' );
?>