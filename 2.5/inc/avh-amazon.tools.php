<?php
require (dirname( __FILE__ ) . '/../../inc/nusoap/nusoap.php');
/** Accesskey ID */
$accesskeyid = '1MPCC36EZ827YJQ02AG2';

$action = $_POST['action'];
switch ( $action ) {
	case 'findid' :
		avh_tools_findID( $_POST['email'], $_POST['locale'] );
		break;

	default :
		;
		break;
}
return;

function avh_tools_findID($email,$locale) {

	/**
	* Locale WSDL Url
	*
	*/
	switch ( $locale ) {
		case 'US':
			$wsdlurl = "http://ecs.amazonaws.com/AWSECommerceService/2008-08-19/AWSECommerceService.wsdl";
		break;

		case 'CA':
			$wsdlurl = 'http://ecs.amazonaws.com/AWSECommerceService/2008-08-19/CA/AWSECommerceService.wsdl';
		break;

		case 'DE':
			$wsdlurl = 'http://ecs.amazonaws.com/AWSECommerceService/2008-08-19/DE/AWSECommerceService.wsdl';
		break;

		case 'UK':
			$wsdlurl = 'http://ecs.amazonaws.com/AWSECommerceService/2008-08-19/UK/AWSECommerceService.wsdl';
		break;

		default:
			$wsdlurl = "http://ecs.amazonaws.com/AWSECommerceService/2008-08-19/AWSECommerceService.wsdl";
		break;
	}



	/**
	 * Create SOAP Client
	 */
	$client = new nusoap_client( $wsdlurl, true );
	$client->decode_utf8 = FALSE;
	$result = array ();
	$result = $client->call( 'ListSearch', array (avh_tools_getSoapListSearchParams( $email )) );
	$Total = $result['Lists']['TotalResults'];

	if ( 0 == $Total ) {
		echo '<h3>No wishlists found<h3>';
	} elseif ( 1 == $Total ) {
		if ( empty( $result['Lists']['List']['DateCreated'] ) ) { // Wishlist is deleted recently, the list entry still excists but the URL is invalid
			echo '<h3>No wishlist found</h3>';
		} else {
			echo '<h3>Wishlist found:<br/></h3>';
			avh_tools_tableHead();
			avh_tools_showList( $result['Lists']['List'], '' );
			avh_tools_tableFooter();
		}
	} else {
		echo '<h3>Wishlist(s) found:<br /></h3>';
		avh_tools_tableHead();
		$class = '';

		foreach ( $result['Lists']['List'] as $List ) {
			if ( ! empty( $List['DateCreated'] ) ) { // Wishlist isn't deleted.
				avh_tools_showList( $List, $class );
				$class = ('alternate' == $class) ? '' : 'alternate';
			}
		}
		avh_tools_tableFooter();
	}
}

function avh_tools_tableHead() {
	echo '<table class="widefat"><thead><tr><th style="text-align: center;" scope="col">ID</th><th scope="col">Name</th><th scope="col">URL</th></th></thead><tbody>';
}

function avh_tools_tableFooter() {
	echo '</tbody></table>';
}

function avh_tools_showList($List, $class) {
	echo '<tr class="' . $class . '"><th style="text-align: center;" scope="row">' . $List['ListId'] . '</th><td>' . $List['ListName'] . '</td><td><a href="' . $List['ListURL'] . '"  target="_blank">' . $List['ListURL'] . '</td></tr>';
}

function avh_tools_getSoapListSearchParams($email, $list='WishList') {
	global $accesskeyid;

	$itemListsearchRequest[] = array (
		'Email'=>$email,
		'ListType'=>$list,
		'ResponseGroup'=>'ListInfo');

	$itemListsearch = array (
		'AWSAccessKeyId'=>$accesskeyid,
		'Request'=>$itemListsearchRequest);
	return $itemListsearch;
}
?>