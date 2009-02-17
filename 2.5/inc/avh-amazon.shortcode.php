<?php
class AVHAmazonShortcode extends AVHAmazonCore {

	/**
	 * PHP5 Constructor
	 *
	 */
	function __construct ( ) {

		parent::__construct();

		// Set the actions, filters and shortcode.
		add_action ( 'admin_menu', array ( &$this, 'handleAdminMenu' ) );
		add_action ( 'wp_ajax_avhamazon_metabox', array ( &$this, 'on_wp_ajax_avhamazon_metabox' ) ); // New function for AJAX calls from the submit button.
		add_filter ( 'admin_print_scripts', array (	&$this,	'adminHead' ) ); // Runs in the HTML header so a plugin can add JavaScript scripts to all admin pages.
		add_shortcode ( 'avhamazon', array ( &$this, 'handleShortcode' ) );
	}

	/**
	 * PHP4 Constructor
	 *
	 */
	function AVHAmazonShortcode () {
		$this->__construct ();
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

		$result = '';
		$error = '';
		$locale = $this->getOption('locale','shortcode');
		$attrs = shortcode_atts ( array (
				'asin' => '',
				'locale' => $locale,
				'linktype' => 'text',
				'wishlist' => '' ), $atts );

		$locale = $attrs['locale'];

		// Get the associate ID
		$associatedid = $this->getAssociateId ( $locale );

		/**
		 * Set up Endpoint
		 */
		$this->amazon_endpoint = $this->amazon_endpoint_table[$locale];

		if ( $attrs['wishlist'] ) {
			$list_result = $this->getListResults ( $attrs['wishlist'] );
			if ( $list_result['Lists']['Request']['Errors'] ) {
				$error = 'WishList ' . $attrs['wishlist'] . ' doesn\'t exists';
				$attrs['asin'] = null;
			}
		}

		// If a random item is wanted, fill $attrs['asin'] with an ASIN from the wishlist
		if ( 'random' == strtolower ( $attrs['asin'] ) ) {
			$Item_keys = $this->getItemKeys ( $list_result['Lists']['List']['ListItem'] );
			foreach ( $Item_keys as $value ) {
				$Item = $list_result['Lists']['List']['ListItem'][$value];
			}
			$attrs['asin'] = $Item['Item']['ASIN'];
		}

		if ( 'all' == strtolower($attrs['asin'])) {
			foreach ($list_result['Lists']['List']['ListItem'] as $key => $value) {
				$attrs['asin'] = $value['Item']['ASIN'];
				list ( $oneresult, $error ) = $this->shortcodeAsin ( $attrs, $content, $associatedid );
				$result .= $oneresult.'<br />';
			}
			$attrs['asin'] = null;
		}
		if ( $attrs['asin'] ) {
			list ( $result, $error ) = $this->shortcodeAsin ( $attrs, $content, $associatedid );
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
	 * @param array $attrs
	 * @param string $content
	 * @param string $associatedid
	 * @return array $return and $error, if and error occurs the error variable is used and return will be empty.
	 */
	function shortcodeAsin ( $attrs, $content, $associatedid ) {

		$error = '';
		$item_result = $this->handleRESTcall ( $this->getRestItemLookupParams ( $attrs['asin'], $associatedid ) );
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
					$imgsrc = $this->getImageUrl('small', $item_result);
					$return = '<div class="wp-caption alignleft"><a title="' . $content . '" href="' . $myurl . '"><img src="' . $imgsrc . '" alt="' . $content . '"/></a><p class="wp-caption-text">'.$content .'</p></div>';
					break;
				case 'pic-text' :
					$imgsrc = $this->getImageUrl('small', $item_result);
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
		$locale = $this->getOption('locale','shortcode');

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

		$wishlist_id=$this->getOption('wishlist_id','shortcode');
		echo '<div id="avhamazon_tab_wishlist" class="ui-tabs-panel">';
		echo '	<div id="avhamazon-wishlist-show" style="display:block">';
		echo '		<p>';
		echo '			<label style="display:block">' . __ ( 'Wish List ID:', 'avhamazon' );
		echo '			<input style="width: 13em" type="text" value="'.$wishlist_id.'" id="avhamazon_scwishlist_wishlist" name="avhamazon_scwishlist_wishlist" autocomplete="on"/>';
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
		echo '	<p>Searching <img src="'.$this->info['graphics_url'].'/ajax-loader.gif"></p>';
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
		echo '	<p>Searching <img src="'.$this->info['graphics_url'].'/ajax-loader.gif"></p>';
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

		$wishlist = $values[0];
		$locale = $values[1];

		/**
		 * Set up endpoint
		 */
		$this->amazon_endpoint = $this->amazon_endpoint_table[$locale];

		$list_result = $this->getListResults ( $wishlist );
		$total_items = count ( $list_result['Lists']['List']['ListItem'] );
		if ( $total_items > 0 ) {
			$this->metaboxTabOutputHeader ();
			$listitem = $list_result['Lists']['List']['ListItem'];
			foreach ( $listitem as $key => $value ) {
				$Item = $value;
				$item_result = $this->handleRESTcall ( $this->getRestItemLookupParams ( $Item['Item']['ASIN'], '' ) );
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

		$asin = $values[0];
		$locale = $values[1];

		/**
		 * Set up endpoint
		 */
		$this->amazon_endpoint = $this->amazon_endpoint_table[$locale];


		$item_result = $this->handleRESTcall ( $this->getRestItemLookupParams ( $asin, '' ) );
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