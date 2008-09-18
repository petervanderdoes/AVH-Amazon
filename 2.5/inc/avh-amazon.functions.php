<?php
/**
 * Get all the items from the list
 *
 * @param string $ListID The Wish List ID of the list to get
 * @param class $proxy
 * @return array Items
 */
function avh_getListResults($ListID, &$proxy) {

	$list = $proxy->ListLookup( avh_getSoapListLookupParams( $ListID ) );

	if (1 == $list['Lists']['List']['TotalItems']) {
		$list['Lists']['List']['ListItem'] = array( '0' => $list['Lists']['List']['ListItem']); // If one item in the list we need to make it a multi array
	} else {
		if ($list['Lists']['List']['TotalPages'] > 1) { // If the list contains over 10 items we need to process the other pages.
			$page = 2;
			while ($page <= $list['Lists']['List']['TotalPages']) {
				$result = $proxy->ListLookup( avh_getSoapListLookupParams( $ListID, null, $page ) );
				foreach ($result['Lists']['List']['ListItem'] as $key => $value) {
					$newkey = 10*($page-1)+$key;
					$list['Lists']['List']['ListItem'][$newkey]=$result['Lists']['List']['ListItem'][$key]; //Add the items from the remaining pages to the lists.
				}
				$page++;
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
function avh_getItemKeys ($list, $nr_of_items = 1) {
	$total_items = count($list);
	if ($nr_of_items > $total_items ) $nr_of_items = $total_items;
	return ((array) array_rand($list,$nr_of_items));
}

/**
 * SOAP Find the List parameters
 *
 * @param string $ListID
 * @param string $WhatList
 * @return array
 */
function avh_getSoapListLookupParams($ListID, $WhatList=null, $page=null) {
	global $avhamazon;
	$WhatList = (is_null($WhatList)?'WishList':$WhatList);
	$page = (is_null($page)?1:$page);

	$listLookupRequest[] = array (
		'ListId' => $ListID,
		'ListType' => $WhatList,
		'ResponseGroup' => 'ListFull',
		'IsOmitPurchasedItems' => '1',
		'ProductPage' => (string) $page,
		'Sort' => 'LastUpdated'
	);

	$listLookup = array(
		'AWSAccessKeyId' => $avhamazon->accesskeyid,
		'Request' => $listLookupRequest,
	);
	return $listLookup;
}

/**
 * SOAP Get Item Details
 *
 * @param string $Itemid
 * @param string $associatedid
 * @return array
 */
function avh_getSoapItemLookupParams($Itemid, $associatedid) {
	global $avhamazon;

	$itemLookupRequest[] = array (
		'ItemId' => $Itemid,
		'IdType' => 'ASIN',
		'Condition' => 'All',
		'ResponseGroup' => 'Medium',
	);

	$itemLookUp = array(
		'AWSAccessKeyId' => $avhamazon->accesskeyid,
		'Request' => $itemLookupRequest,
		'AssociateTag' => $associatedid
	);
	return $itemLookUp;
}

/**
 * Get the options for the widget
 *
 * @param array $a
 * @param mixed $key
 * @return mixed
 */
function avh_getWidgetOptions ($a, $key) {
	global $avhamazon;
	$return ='';

	if ( $a[$key] ) {
		$return = $a[$key]; // From widget
	} elseif ( $avhamazon->options[$key] ) {
		$return = $avhamazon->options[$key]; // From Admin Page
	} else {
		$return = $avhamazon->default_options[$key]; // Default
	}
	return ($return);
}

/**
 * Find the associate id based on the locale and locale_table
 *
 * @param string $locale
 *
 */
function avh_getAssociateId ($locale) {
	global $avhamazon;

	if (array_key_exists($locale,$avhamazon->associate_table)) {
		$associatedid = $avhamazon->associate_table[$locale];
	} else {
		$associatedid = 'avh-amazon-20';
	}
	return ($associatedid);
}
?>