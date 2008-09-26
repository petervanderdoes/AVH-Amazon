<?php
class AVHAmazonShortcode {

	var $version;

	var $info;

	var $options;

	var $default_options;

	var $db_options;

	var $locale_table;

	/**
	 * PHP4 Constructor
	 *
	 * @param array $default_options
	 * @param string $version
	 * @param array $info
	 * @param array $locale_table
	 *
	 * @return AVHAmazonShortcode
	 */
	function AVHAmazonShortcode ( $default_options = array(), $version = '', $info = array(), $locale_table = array() ) {

		// Load version number
		$this->version = $version;
		unset ( $version );

		// Set class property for default options
		$this->default_options = $default_options;

		// Get options from WP
		$this->db_options = 'avhamazon';
		$options_from_table = get_option ( $this->db_options );

		// Update default options by getting not empty values from options table
		foreach ( ( array ) $default_options as $default_options_name => $default_options_value ) {
			if ( ! is_null ( $options_from_table[$default_options_name] ) ) {
				if ( is_int ( $default_options_value ) ) {
					$default_options[$default_options_name] = ( int ) $options_from_table[$default_options_name];
				} else {
					$default_options[$default_options_name] = $options_from_table[$default_options_name];
					if ( 'associated_id' == $default_options_name ) {
						if ( 'blogavirtualh-20' == $options_from_table[$default_options_name] ) $default_options[$default_options_name] = 'avh-amazon-20'; // Changed the Amazon ID for better tracking
					}
				}
			}
		}

		// Set the class property and unset no used variable
		$this->options = $default_options;
		unset ( $default_options );
		unset ( $options_from_table );
		unset ( $default_options_value );

		// Get info data from constructor
		$this->info = $info;
		unset ( $info );

		// Set locale Tables
		$this->locale_table = $locale_table;

		// Set the actions, filters and shortcode.
		add_action ( 'admin_menu', array ( &$this, 'handleAdminMenu' ) );
		add_action ( 'wp_ajax_avhamazon_metabox', array ( &$this, 'on_wp_ajax_avhamazon_metabox' ) ); // New function for AJAX calls from the submit button.
		add_filter ( 'admin_print_scripts', array (	&$this,	'adminHead' ) ); // Runs in the HTML header so a plugin can add JavaScript scripts to all admin pages.
		add_shortcode ( 'avhamazon', array ( &$this, 'handleShortcode' ) );
	}

	/**
	 * Add the metabox to the administration pages Post and Page
	 *
	 */
	function handleAdminMenu () {

		add_meta_box ( 'avhamazonmetabox01', 'AVH Amazon Short Code', array ( &$this, 'createMetabox' ), 'post', 'normal' );
		add_meta_box ( 'avhamazonmetabox01', 'AVH Amazon Short Code', array ( &$this, 'createMetabox' ), 'page', 'normal' );
	}

	/**
	 * Add the javascript to all admin pages.
	 *
	 */
	function adminHead () {

		if ( $GLOBALS['editing'] ) { //@todo Check if there's a better solution for this.
			wp_enqueue_script ( 'avhamazonmetabox', $this->info['install_url'] . '/inc/js/metabox.js', array ( 'jquery' ), $this->version );
			wp_enqueue_script('jquery-ui-tabs');
		}
	}

	/**
	 * Handle the shortcode
	 *
	 * [avhamazon asin= locale= linktype= wishlist]content[/avhamazon]
	 *
	 */
	function handleShortcode ( $atts, $content = null ) {

		global $avhamazon;

		$result = '';
		$error = '';
		$attrs = shortcode_atts ( array (
				'asin' => '',
				'locale' => 'US',
				'linktype' => 'text',
				'wishlist' => '' ), $atts );

		$locale = $attrs['locale'];

		// Get the associate ID
		$associatedid = avh_getAssociateId ( $locale );

		/**
		 * Set up WSDL Cache
		 */
		$avhamazon->wsdlurl = $avhamazon->wsdlurl_table[$locale];
		$cache = new wsdlcache ( $avhamazon->wsdlcachefolder, 0 ); // Cache it indefinitely
		$avhamazon->wsdl = $cache->get ( $avhamazon->wsdlurl );
		if ( is_null ( $avhamazon->wsdl ) ) {
			$avhamazon->wsdl = new wsdl ( $avhamazon->wsdlurl );
			$cache->put ( $avhamazon->wsdl );
		} else {
			$avhamazon->wsdl->debug_str = '';
			$avhamazon->wsdl->debug ( 'Retrieved from cache' );
		}

		/**
		 * Create SOAP Client
		 */
		$client = new nusoap_client ( $avhamazon->wsdl, true );
		$client->soap_defencoding = 'UTF-8';
		$proxy = $client->getProxy ();

		if ( $attrs['wishlist'] ) {
			$list_result = avh_getListResults ( $attrs['wishlist'], $proxy );
			if ( $list_result['Lists']['Request']['Errors'] ) {
				$error = 'WishList ' . $attrs['wishlist'] . ' doesn\'t exists';
				$attrs['asin'] = null;
			}
		}

		// If a random item is wanted, fill $attrs['asin'] with an ASIN from the wishlist
		if ( 'random' == strtolower ( $attrs['asin'] ) ) {
			$Item_keys = avh_getItemKeys ( $list_result['Lists']['List']['ListItem'] );
			foreach ( $Item_keys as $value ) {
				$Item = $list_result['Lists']['List']['ListItem'][$value];
			}
			$attrs['asin'] = $Item['Item']['ASIN'];
		}

		if ( 'all' == strtolower($attrs['asin'])) {
			foreach ($list_result['Lists']['List']['ListItem'] as $key => $value) {
				$attrs['asin'] = $value['Item']['ASIN'];
				list ( $oneresult, $error ) = $this->shortcodeAsin ( &$proxy, $attrs, $content, $associatedid );
				$result .= $oneresult.'<br />';
			}
			$attrs['asin'] = null;
		}
		if ( $attrs['asin'] ) {
			list ( $result, $error ) = $this->shortcodeAsin ( &$proxy, $attrs, $content, $associatedid );
		}

		if ( $error ) {
			$return = '<strong>avhamazon error:' . $error . '</strong>';
		} else {
			$return = $result;
		}
		return ($return);
	}

	/**
	 * Create the output for the shortcode
	 *
	 * @param array $proxy
	 * @param array $attrs
	 * @param string $content
	 * @param string $associatedid
	 * @return array $return and $error, if and error occurs the error variable is used and return will be empty.
	 */
	function shortcodeAsin ( $proxy, $attrs, $content, $associatedid ) {

		$error = '';
		$item_result = $proxy->ItemLookup ( avh_getSoapItemLookupParams ( $attrs['asin'], $associatedid ) );
		if ( $item_result['Items']['Request']['Errors'] ) {
			$return = '';
			$error = 'Item with ASIN ' . $attrs['asin'] . ' doesn\'t exist';
		} else {
			$pos = strpos ( $item_result['Items']['Item']['DetailPageURL'], $attrs['asin'] );

			$myurl = substr ( $item_result['Items']['Item']['DetailPageURL'], 0, $pos + strlen ( $attrs['asin'] ) );
			// If a wishlist is given, make sure when somebody clicks on the link, Amazon knows the List owner.
			if ( $attrs['wishlist'] ) {
				$myurl .= '/ref=wl_it_dp?ie=UTF8&colid=' . $attrs['wishlist'];
			}
			$myurl .= '&tag=' . $associatedid;

			// If no content is given we use the Title from Amazon.
			$content = ($content) ? $content : $item_result['Items']['Item']['ItemAttributes']['Title'];

			switch ( $attrs['linktype'] ) {
				case 'text' :
					$return = '<a title="' . $content . '" href="' . $myurl . '">' . $content . '</a>';
					break;
				case 'pic' :
					$imgsrc = $item_result['Items']['Item']['SmallImage']['URL'];
					$return = '<div class="wp-caption alignleft"><a title="' . $content . '" href="' . $myurl . '"><img src="' . $imgsrc . '" alt="' . $content . '"/></a><p class="wp-caption-text">'.$content .'</p></div>';
					break;
				case 'pic-text' :
					$imgsrc = $item_result['Items']['Item']['SmallImage']['URL'];
					$return = '<table style=" border: none; cellpadding: 2px; align: left"><tr><td><a title="' . $content . '" href="' . $myurl . '"><img class="alignleft" src="' . $imgsrc . '" alt="' . $content . '"/></a></td><td><a title="' . $content . '" href="' . $myurl . '">' . $content . '</a></td></tr></table>';
					break;
				default :
					$return = '<a title="' . $content . '" href="' . $myurl . '">' . $content . '</a>';
					break;
			}
		}
		return array (
				$return,
				$error );
	}

	/**
	 * Create the metabox
	 *
	 */
	function createMetabox () {

		//@todo Use of the nonce field for security
		//wp_nonce_field( 'avhamazon-metabox', '_ajax_nonce', false );
		$locale = "US";

		echo '<ul id="avhamazon_tabs" class="ui-tabs-nav">';
		echo '<input name="avhamazon_mb_url" id="avhamazon_mb_url" value="' . $this->info['siteurl'] . '" type="hidden" />';

		// The tabs
		echo '<li class="ui-tabs-selected"><a href="#avhamazon_tab_wishlist">' . __ ( 'Wishlist', 'avhamazon' ) . '</a></li>';
		echo '<li class=""><a href="#avhamazon_tab_asin">' . __ ( 'ASIN', 'avhamazon' ) . '</a></li></ul>';

		$this->metaboxTabWishlist ($locale);
		$this->metaboxTabAsin ($locale);
	}

	/**
	 * HTML for the Wishlist Tab
	 *
	 * @param string $locale
	 *
	 */
	function metaboxTabWishlist ($locale) {

		echo '<div id="avhamazon_tab_wishlist" class="ui-tabs-panel">';
		echo '	<div id="avhamazon-wishlist-show" style="display:block">';
		echo '		<p>';
		echo '			<label style="display:block">' . __ ( 'Wish List ID:', 'avhamazon' );
		echo '			<input style="width: 13em" type="text" value="" id="avhamazon_scwishlist_wishlist" name="avhamazon_scwishlist_wishlist" autocomplete="on"/>';
		echo '			</label>';
		echo '			<label style="display:block">' . __ ( 'Locale Amazon:', 'avhamazon' );
		echo '			<select id="avhamazon_scwishlist_locale" name="avhamazon_scwishlist_locale" />';
		$seldata = '';
		foreach ( $this->locale_table as $key => $value ) {
			$seldata .= '<option value="' . $key . '" ' . (($locale == $key) ? 'selected="selected"' : '') . ' >' . $value . '</option>' . "\n";
		}
		echo $seldata;
		echo '			</select></label>';
		echo '			<br />';
		echo '			<input class="button-secondary" type="submit" value="Show Items" id="avhamazon_submit_wishlist" name="avhamazon_submit_wishlist" />';
		echo '		</p>';
		echo '	</div>';
		echo '<div id="avhamazon_wishlist_loading" style="display:hide">';
		echo '	<p>Searching <img src="'.$this->info['install_url'].'/images/ajax-loader.gif"></p>';
		echo '</div>';
		echo '	<div id="avhamazon_wishlist_output"></div>';
		echo '</div>';
	}

	/**
	 * HTML for the ASIN tab
	 *
	 * @param string $locale
	 *
	 */
	function metaboxTabAsin ($locale) {

		echo '<div id="avhamazon_tab_asin" class="ui-tabs-panel">';
		echo '	<div id="avhamazon_asin_show">';
		echo '		<p>';
		echo '			<label style="display:block">' . __ ( 'ASIN', 'avhamazon' );
		echo '			<input type="text" value="" style="style="width: 13em";" id ="avhamazon_asin_nr" name="avhamazon_asin_nr" autocomplete="on"/>';
		echo '			</label>';
		echo '			<label style="display:block">' . __ ( 'Locale Amazon:', 'avhamazon' );
		echo '			<select id="avhamazon_scasin_locale" name="avhamazon_scasin_locale" />';
		$seldata = '';
		foreach ( $this->locale_table as $key => $value ) {
			$seldata .= '<option value="' . $key . '" ' . (($locale == $key) ? 'selected="selected"' : '') . ' >' . $value . '</option>' . "\n";
		}
		echo $seldata;
		echo '			</select></label>';
		echo '			<br />';
		echo '			<input class="button-secondary" type="button" value="Show Item" id="avhamazon_submit_asin" name="avhamazon_submit_asin" />';
		echo '		</p>';
		echo '	</div>';
		echo '<div id="avhamazon_asin_loading" style="display:hide">';
		echo '	<p>Searching <img src="'.$this->info['install_url'].'/images/ajax-loader.gif"></p>';
		echo '</div>';
		echo '	<div id="avhamazon_asin_output"></div>';
		echo '</div>';
	}

	/**
	 * The AJAX function called when the submit button is clicked. The call to admin-ajax.php is done through javascript in metabox.js
	 *
	 */
	function on_wp_ajax_avhamazon_metabox () {

		//@todo Use of the nonce for security
		//check_ajax_referer ( 'avhamazon-metabox', '_ajax_nonce' );
		echo '<script type="text/javascript">';
		echo 'var avhamazon = new avhamazonmetabox();';
		echo '</script>';
		$action = $_POST['avhamazon_mb_action'];
		$values = $_POST['avhamazon_mb_values'];

		switch ( $action ) {
			case 'wishlist' :
				$this->metaboxTabWishlistOutput ( $values );
				break;
			case 'asin' :
				$this->metaboxTabAsinOutput ( $values );
				break;
		}
	}

	/**
	 * Get and show the results for the Wishlist tab
	 *
	 * @param array $values We get them from the javascript call.
	 */
	function metaboxTabWishlistOutput ( $values ) {

		global $avhamazon;

		$wishlist = $values[0];
		$locale = $values[1];

		/**
		 * Set up WSDL Cache
		 */
		$avhamazon->wsdlurl = $avhamazon->wsdlurl_table[$locale];
		$cache = new wsdlcache ( $avhamazon->wsdlcachefolder, 0 ); // Cache it indefinitely
		$avhamazon->wsdl = $cache->get ( $avhamazon->wsdlurl );
		if ( is_null ( $avhamazon->wsdl ) ) {
			$avhamazon->wsdl = new wsdl ( $avhamazon->wsdlurl );
			$cache->put ( $avhamazon->wsdl );
		} else {
			$avhamazon->wsdl->debug_str = '';
			$avhamazon->wsdl->debug ( 'Retrieved from cache' );
		}

		/**
		 * Create SOAP Client
		 */
		$client = new nusoap_client ( $avhamazon->wsdl, true );
		$client->soap_defencoding = 'UTF-8';
		$proxy = $client->getProxy ();

		$list_result = avh_getListResults ( $wishlist, $proxy );
		$total_items = count ( $list_result['Lists']['List']['ListItem'] );
		if ( $total_items > 0 ) {
			$this->metaboxTabOutputHeader ();
			$listitem = $list_result['Lists']['List']['ListItem'];
			foreach ( $listitem as $key => $value ) {
				$Item = $value;
				$item_result = $proxy->ItemLookup ( avh_getSoapItemLookupParams ( $Item['Item']['ASIN'], '' ) );
				$this->metaboxTabOutputItem ( $item_result['Items']['Item']['ItemAttributes']['Title'], $Item['Item']['ASIN'], 'avhamazon_scwishlist_asin-' . $key, 'avhamazon_scwishlist_asin', '', ('0' == $key) ? TRUE : FALSE );
			}
			// Display the last row as a randomizing option
			$this->metaboxTabOutputItem ( __('Randomize the items','avhamazon'), 'random', 'avhamazon_scwishlist_asin-random', 'avhamazon_scwishlist_asin', '', FALSE );
			$this->metaboxTabOutputOptions ( 'wishlist' );
			$this->metaboxTabOutputSendtoeditor ( 'wishlist' );
		} else {
			echo '<strong>' . __ ( 'Can\'t find the given wish list', 'avhamazon' ) . '</strong>';
		}
	}

	/**
	 * Get and show the results for the ASIN tab
	 *
	 * @param array $values We get them from the javascript call.
	 */
	function metaboxTabAsinOutput ( $values ) {

		global $avhamazon;

		$asin = $values[0];
		$locale = $values[1];
		$wsdlurl = $avhamazon->wsdlurl_table[$locale];

		/**
		 * Set up WSDL Cache
		 */
		$avhamazon->wsdlurl = $avhamazon->wsdlurl_table[$locale];
		$cache = new wsdlcache ( $avhamazon->wsdlcachefolder, 0 ); // Cache it indefinitely
		$avhamazon->wsdl = $cache->get ( $avhamazon->wsdlurl );
		if ( is_null ( $avhamazon->wsdl ) ) {
			$avhamazon->wsdl = new wsdl ( $avhamazon->wsdlurl );
			$cache->put ( $avhamazon->wsdl );
		} else {
			$avhamazon->wsdl->debug_str = '';
			$avhamazon->wsdl->debug ( 'Retrieved from cache' );
		}

		/**
		 * Create SOAP Client
		 */
		$client = new nusoap_client ( $wsdlurl, true );
		$client->soap_defencoding = 'UTF-8';
		$proxy = $client->getProxy ();
		$item_result = $proxy->ItemLookup ( avh_getSoapItemLookupParams ( $asin, '' ) );
		if ( $item_result['Items']['Request']['Errors'] ) {
			echo '<strong>' . __ ( 'Can\'t find the given item', 'avhamazon' ) . '</strong>';
		} else {
			$this->metaboxTabOutputHeader ();
			$this->metaboxTabOutputItem ( $item_result['Items']['Item']['ItemAttributes']['Title'], $asin, 'avhamazon_scasin_asin', 'avhamazon_scasin_asin', '', TRUE );
			$this->metaboxTabOutputOptions ( 'asin' );
			$this->metaboxTabOutputSendtoeditor ( 'asin' );
		}
	}

	/**
	 * Print the header for the result
	 *
	 */
	function metaboxTabOutputHeader () {

		echo '<strong>' . __ ( 'Select item', 'avhamazon' ) . '</strong><br />';
	}

	/**
	 * Display a row
	 *
	 * @param string $title
	 * @param string $asin
	 * @param string $id
	 * @param string $name
	 * @param string $class
	 * @param boolean $checked
	 */
	function metaboxTabOutputItem ( $title, $asin, $id, $name, $class = '', $checked = FALSE ) {

		$class = ($class) ? 'class="' . $class . '"' : '';
		echo '<label ' . $class . '><input type="radio" value="' . $asin . '" id="' . $id . '" name="' . $name . '"' . ($checked ? ' checked="checked" ' : "") . ' /> ' . $title . '</label><br />';
	}

	/**
	 * Display the general options after the row(s) of item(s)
	 *
	 * @param string $tabid (wishlist/asin)
	 */
	function metaboxTabOutputOptions ( $tabid ) {

		echo '<p><strong>' . __ ( 'Link type:', 'avhamazon' ) . '</strong><br/>';
		echo '<label><input type="radio" value="text" id="avhamazon_sc' . $tabid . '_linktypet" checked="checked" name="avhamazon_sc' . $tabid . '_linktype"/> ' . __ ( 'Text', 'avhamazon' ) . '</label><br />';
		echo '<label><input type="radio" value="pic" id="avhamazon_sc' . $tabid . '_linktypep" name="avhamazon_sc' . $tabid . '_linktype"/> ' . __ ( 'Picture', 'avhamazon' ) . '</label><br />';
		echo '<label><input type="radio" value="pic-text" id="avhamazon_sc' . $tabid . '_linktypept" name="avhamazon_sc' . $tabid . '_linktype"/> ' . __ ( 'Picture and Text', 'avhamazon' ) . '</label></p>';
		echo '<p><label><strong>' . __ ( 'Content:', 'avhamazon' ) . '</strong>';
		echo '<input type="text" style="width: 98%" id="avhamazon_sc' . $tabid . '_content" name="avhamazon_sc' . $tabid . '_content"/></p>';
	}

	/**
	 * Show the send to editor button
	 *
	 * @param string $tabid (wishlist/asin)
	 */
	function metaboxTabOutputSendtoeditor ( $tabid ) {

		echo '<p class="submit">';
		echo '<input type="button" id="avhamazon_sendtoeditor" name="' . $tabid . '" value="' . __ ( 'Send to Editor', 'avhamazon' ) . '" />';
		echo '</p>';
	}
}
?>