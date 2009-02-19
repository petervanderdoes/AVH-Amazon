<?php

/** Accesskey ID */
$accesskeyid = '1MPCC36EZ827YJQ02AG2';

$action = $_POST['action'];
switch ( $action ) {
	case 'findid' :
		avh_tools_findID ( $_POST['email'], $_POST['locale'], $_POST['abs'] );
		break;

	default :
		;
		break;
}
return;

function avh_tools_findID ( $email, $locale, $abs ) {

	/**
	 * Locale WSDL Url
	 *
	 */
	switch ( $locale ) {
		case 'US' :
			$amazon_endpoint = "http://ecs.amazonaws.com/onca/xml";
			break;

		case 'CA' :
			$amazon_endpoint = 'http://ecs.amazonaws.ca/onca/xml';
			break;

		case 'DE' :
			$amazon_endpoint = 'http://ecs.amazonaws.de/onca/xml';
			break;

		case 'UK' :
			$amazon_endpoint = 'http://ecs.amazonaws.co.uk/onca/xml';
			break;

		default :
			$amazon_endpoint = "http://ecs.amazonaws.com/onca/xml";
			break;
	}

	$result = array ();
	$result = handleRESTcall ( avh_tools_getRestListSearchParams ( $email ), $amazon_endpoint, $abs );
	$Total = $result['Lists']['TotalResults'];

	if ( 0 == $Total ) {
		echo '<h3>No wishlists found<h3>';
	} elseif ( 1 == $Total ) {
		if ( empty ( $result['Lists']['List']['DateCreated'] ) ) { // Wishlist is deleted recently, the list entry still excists but the URL is invalid
			echo '<h3>No wishlist found</h3>';
		} else {
			echo '<h3>Wishlist found:<br/></h3>';
			avh_tools_tableHead ();
			avh_tools_showList ( $result['Lists']['List'], '' );
			avh_tools_tableFooter ();
		}
	} else {
		echo '<h3>Wishlist(s) found:<br /></h3>';
		avh_tools_tableHead ();
		$class = '';

		foreach ( $result['Lists']['List'] as $List ) {
			if ( ! empty ( $List['DateCreated'] ) ) { // Wishlist isn't deleted.
				avh_tools_showList ( $List, $class );
				$class = ('alternate' == $class) ? '' : 'alternate';
			}
		}
		avh_tools_tableFooter ();
	}
}

function avh_tools_tableHead () {

	echo '<table class="widefat"><thead><tr><th style="text-align: center;" scope="col">ID</th><th scope="col">Name</th><th scope="col">URL</th></th></thead><tbody>';
}

function avh_tools_tableFooter () {

	echo '</tbody></table>';
}

function avh_tools_showList ( $List, $class ) {

	echo '<tr class="' . $class . '"><th style="text-align: center;" scope="row">' . $List['ListId'] . '</th><td>' . $List['ListName'] . '</td><td><a href="' . $List['ListURL'] . '"  target="_blank">' . $List['ListURL'] . '</td></tr>';
}

/**
 * Actual Rest Call
 *
 * @param array $query_array
 * @return array
 * @since 2.4
 */
function handleRESTcall ( $query_array, $amazon_endpoint, $abs ) {

	$xml_array = array ();

	$querystring = BuildQuery ( $query_array );
	$url = $amazon_endpoint . '?' . $querystring;

	// Starting with WordPress 2.7 we'll use the HTPP class.
	if ( function_exists ( 'wp_remote_request' ) ) {
		$response = wp_remote_request ( $url );
		$xml_array = xml2array ( $response['body'] );
	} else { // Prior to WordPress 2.7 we'll use the Snoopy Class.
		require_once ($abs . 'wp-includes/class-snoopy.php');
		$snoopy = new Snoopy ( );
		$snoopy->fetch ( $url );
		$response = $snoopy->results;
		$xml_array = xml2array ( $response );
	}

	$return_array = $xml_array['ListSearchResponse'];

	return ($return_array);
}

/**
 * Convert an array into a query string
 *
 * @param array $array
 * @param string $convention
 * @return string
 * @since 2.4
 */
function BuildQuery ( $array = NULL, $convention = '%s' ) {

	if ( count ( $array ) == 0 ) {
		$query = '';
	} else {
		if ( function_exists ( 'http_build_query' ) ) {
			$query = http_build_query ( $array );
		} else {
			$query = '';
			foreach ( $array as $key => $value ) {
				if ( is_array ( $value ) ) {
					$new_convention = sprintf ( $convention, $key ) . '[%s]';
					$query .= BuildQuery ( $value, $new_convention );
				} else {
					$key = urlencode ( $key );
					$value = urlencode ( $value );
					$query .= sprintf ( $convention, $key ) . "=$value&";
				}
			}
		}
	}
	return $query;
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
function xml2array ( $contents = '', $get_attributes = 1, $priority = 'tag' ) {

	$xml_values = '';
	$return_array = array ();
	$tag = '';
	$type = '';
	$level = 0;
	$attributes = array ();
	if ( function_exists ( 'xml_parser_create' ) ) {
		$parser = xml_parser_create ( 'UTF-8' );

		xml_parser_set_option ( $parser, XML_OPTION_TARGET_ENCODING, "UTF-8" );
		xml_parser_set_option ( $parser, XML_OPTION_CASE_FOLDING, 0 );
		xml_parser_set_option ( $parser, XML_OPTION_SKIP_WHITE, 1 );
		xml_parse_into_struct ( $parser, trim ( $contents ), $xml_values );
		xml_parser_free ( $parser );

		//Initializations
		$xml_array = array ();
		$parent = array ();

		$current = & $xml_array; // Reference


		// Go through the tags.
		$repeated_tag_index = array ();

		// Multiple tags with same name will be turned into an array
		foreach ( $xml_values as $data ) {
			unset ( $attributes, $value ); //Remove existing values, or there will be trouble


			// This command will extract these variables into the foreach scope
			// tag(string), type(string), level(int), attributes(array).
			extract ( $data ); //We could use the array by itself, but this cooler.


			$result = array ();
			$attributes_data = array ();

			if ( isset ( $value ) ) {
				if ( $priority == 'tag' ) {
					$result = $value;
				} else {
					$result['value'] = $value; //Put the value in an associate array if we are in the 'Attribute' mode
				}
			}

			// Set the attributes too
			if ( isset ( $attributes ) and $get_attributes ) {
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

				if ( ! is_array ( $current ) or (! in_array ( $tag, array_keys ( $current ) )) ) { //Insert New tag
					$current[$tag] = $result;
					if ( $attributes_data )
						$current[$tag . '_attr'] = $attributes_data;
					$repeated_tag_index[$tag . '_' . $level] = 1;

					$current = & $current[$tag];

				} else { // There was another element with the same tag name


					if ( isset ( $current[$tag][0] ) ) { //If there is a 0th element it is already an array
						$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
						$repeated_tag_index[$tag . '_' . $level] ++;
					} else { //This section will make the value an array if multiple tags with the same name appear together
						$current[$tag] = array (
								$current[$tag],
								$result );
						//This will combine the existing item and the new item together to make an array
						$repeated_tag_index[$tag . '_' . $level] = 2;

						if ( isset ( $current[$tag . '_attr'] ) ) { // The attribute of the last(0th) tag must be moved as well
							$current[$tag]['0_attr'] = $current[$tag . '_attr'];
							unset ( $current[$tag . '_attr'] );
						}
					}
					$last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
					$current = & $current[$tag][$last_item_index];
				}
			} elseif ( $type == "complete" ) { //Tags that ends in 1 line '<tag />'
				//See if the key is already taken.
				if ( ! isset ( $current[$tag] ) ) { // New key
					$current[$tag] = $result;
					$repeated_tag_index[$tag . '_' . $level] = 1;
					if ( $priority == 'tag' and $attributes_data )
						$current[$tag . '_attr'] = $attributes_data;
				} else { //If taken, put all things inside a list(array)
					if ( isset ( $current[$tag][0] ) and is_array ( $current[$tag] ) ) {
						//This will combine the existing item and the new item together to make an array
						$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
						if ( $priority == 'tag' and $get_attributes and $attributes_data ) {
							$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
						}
						$repeated_tag_index[$tag . '_' . $level] ++;
					} else { //If it is not an array...
						$current[$tag] = array (
								$current[$tag],
								$result ); //...Make it an array using using the existing value and the new value
						$repeated_tag_index[$tag . '_' . $level] = 1;
						if ( $priority == 'tag' and $get_attributes ) {
							if ( isset ( $current[$tag . '_attr'] ) ) { //The attribute of the last(0th) tag must be moved as well
								$current[$tag]['0_attr'] = $current[$tag . '_attr'];
								unset ( $current[$tag . '_attr'] );
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

function avh_tools_getRestListSearchParams ( $email, $list = 'WishList' ) {

	global $accesskeyid;

	$itemListsearch = array (
			'Service' => 'AWSECommerceService',
			'Operation' => 'ListSearch',
			'AWSAccessKeyId' => $accesskeyid,
			'Email' => $email,
			'ListType' => $list,
			'ResponseGroup' => 'ListInfo' );

	return $itemListsearch;
}
?>