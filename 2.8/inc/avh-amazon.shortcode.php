<?php
class AVHAmazonShortcode
{
	/**
	 *
	 * @var AVHAmazoncore
	 */
	var $core;

	/**
	 * PHP5 Constructor
	 *
	 */
	function __construct ()
	{
		$this->core = & AVHAmazonCore::getInstance();

		// Set the actions, filters and shortcode.
		add_action( 'admin_menu', array (&$this, 'handleAdminMenu' ) );
		add_action( 'wp_ajax_avhamazon_metabox', array (&$this, 'on_wp_ajax_avhamazon_metabox' ) ); // New function for AJAX calls from the submit button.
		add_filter( 'admin_enqueue_scripts', array (&$this, 'handleAdminScripts' ) ); // Runs in the HTML header so a plugin can add JavaScript scripts to all admin pages.
		add_shortcode( 'avhamazon', array (&$this, 'handleShortcode' ) );
	}

	/**
	 * PHP4 Constructor
	 *
	 */
	function AVHAmazonShortcode ()
	{
		$this->__construct();
	}

	/**
	 * Add the metabox to the administration pages Post and Page
	 *
	 */
	function handleAdminMenu ()
	{
		add_meta_box( 'avhamazonmetabox01', 'AVH Amazon Short Code', array (&$this, 'createMetabox' ), 'post', 'normal' );
		add_meta_box( 'avhamazonmetabox01', 'AVH Amazon Short Code', array (&$this, 'createMetabox' ), 'page', 'normal' );
	}

	/**
	 * Add the javascript to the admin pages.
	 *
	 * @WordPress Action admin_enqueue_scripts
	 * @since 3.0
	 *
	 */
	function handleAdminScripts ( $hook_suffix )
	{

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.dev' : '';
		$admin_pages = array ('post.php', 'page.php' );
		if ( in_array( $hook_suffix, $admin_pages ) ) {
			wp_enqueue_script( 'avhamazonmetabox', $this->core->info['plugin_url'] . '/inc/js/metabox' . $suffix . '.js', array ('jquery' ), $this->core->version, true );
		}
	}

	/**
	 * Handle the shortcode
	 *
	 * [avhamazon asin= locale= linktype= wishlist]content[/avhamazon]
	 *
	 */
	function handleShortcode ( $atts, $content = null )
	{
		$return = $this->core->comment_begin;
		$result = '';
		$error = '';
		$locale = $this->core->getOption( 'locale', 'shortcode' );
		$attrs = shortcode_atts( array ('asin' => '', 'locale' => $locale, 'linktype' => 'text', 'wishlist' => '', 'picsize' => 'small', 'col' => 1 ), $atts );

		$locale = $attrs['locale'];

		// Get the associate ID
		$associatedid = $this->core->getOption( 'associated_id', 'general' );
		if ( $this->core->associate_table['US'] == $associatedid ) {
			$associatedid = $this->core->getAssociateId( $locale );
		}

		/**
		 * Set up Endpoint
		 */
		$this->core->amazon_endpoint = $this->core->amazon_endpoint_table[$locale];

		if ( $attrs['wishlist'] ) {
			$list_result = $this->core->getListResults( $attrs['wishlist'] );
			if ( $list_result['Lists']['Request']['Errors'] ) {
				$error = 'WishList ' . $attrs['wishlist'] . ' doesn\'t exists';
				$attrs['asin'] = null;
			}
		}

		if ( isset( $list_result['Error'] ) ) {
			$error = $this->core->getHttpError( $list_result['Error'] );
			$attrs['asin'] = null;
		}

		// If a random item is wanted, fill $attrs['asin'] with an ASIN from the wishlist
		if ( 'random' == strtolower( $attrs['asin'] ) ) {
			$Item_keys = $this->core->getItemKeys( $list_result['Lists']['List']['ListItem'] );
			foreach ( $Item_keys as $value ) {
				$Item = $list_result['Lists']['List']['ListItem'][$value];
			}
			$attrs['asin'] = $Item['Item']['ASIN'];
		}

		if ( 'all' == strtolower( $attrs['asin'] ) ) {
			$return .= '<table style=" border: none; align: left">';

			for ($x=0; $x<=count ( $list_result['Lists']['List']['ListItem'])-1; $x+=$attrs['col'] ) {
				$return .= '<tr>';
				for ($i=1; $i<=$attrs['col']; $i++) {
					$value=$list_result['Lists']['List']['ListItem'][$x+$i-1];
					$attrs['asin'] = $value['Item']['ASIN'];
					list ( $oneresult, $error ) = $this->shortcodeAsin( $attrs, $content, $associatedid, false );
					$return .= '<td>'.$oneresult .'</td>';
				}
				$return .= '</tr>';
			}
			$return .= '</table>';
			$attrs['asin'] = null;
		}

		if ( $attrs['asin'] ) {
			list ( $result, $error ) = $this->shortcodeAsin( $attrs, $content, $associatedid );
		}

		if ( $error ) {
			$return .= $error;
		} else {
			$return .= $result;
		}
		$return .= $this->core->comment_end;
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
	function shortcodeAsin ( $attrs, $content, $associatedid, $single=true )
	{
		$error = '';
		$item_result = $this->core->getItemLookup( $attrs['asin'], $associatedid ) ;
		if ( isset( $item_result['Error'] ) ) {
			$return = '';
			$error = $this->core->getHttpError( $item_result['Error'] );
		} else {
			if ( isset( $item_result['Items']['Request']['Errors'] ) ) {
				$return = '';
				$error = 'Item with ASIN ' . $attrs['asin'] . ' doesn\'t exist';
			} else {
				$pos = strpos( $item_result['Items']['Item']['DetailPageURL'], $attrs['asin'] );

				$myurl = substr( $item_result['Items']['Item']['DetailPageURL'], 0, $pos + strlen( $attrs['asin'] ) );
				// If a wishlist is given, make sure when somebody clicks on the link, Amazon knows the List owner.
				if ( $attrs['wishlist'] ) {
					$myurl .= '/ref=wl_it_dp';
					$query['ie'] = 'UTF8';
					$query['colid'] = $attrs['wishlist'];
				}
				$query['tag'] = $associatedid;
				$myurl .= '?' . $this->core->BuildQuery( $query );

				// If no content is given we use the Title from Amazon.
				$content = ($content) ? $content : $item_result['Items']['Item']['ItemAttributes']['Title'];

				switch ( $attrs['linktype'] ) {
					case 'text' :
						$return = '<a title="' . $content . '" href="' . $myurl . '">' . $content . '</a>';
						break;
					case 'pic' :
						$imginfo = $this->core->getImageInfo( $attrs['picsize'], $item_result );
						$return = '<div class="wp-caption alignleft"><a title="' . $content . '" href="' . $myurl . '"><img width="' . $imginfo['w'] . '" height="' . $imginfo['h'] . '" src="' . $imginfo['url'] . '" alt="' . $content . '"/></a></div>';
						break;
					case 'pic-text' :
						$imginfo = $this->core->getImageInfo( $attrs['picsize'], $item_result );
						if ($single){
							$return = '<table style=" border: none; cellpadding: 2px; align: left"><tr><td><a title="' . $content . '" href="' . $myurl . '"><img class="alignleft" width="' . $imginfo['w'] . '" height="' . $imginfo['h'] . '" src="' . $imginfo['url'] . '" alt="' . $content . '"/></a></td><td><a title="' . $content . '" href="' . $myurl . '">' . $content . '</a></td></tr></table>';
						} else {
							$return = '<a title="' . $content . '" href="' . $myurl . '"><img class="alignleft" width="' . $imginfo['w'] . '" height="' . $imginfo['h'] . '" src="' . $imginfo['url'] . '" alt="' . $content . '"/></a><a title="' . $content . '" href="' . $myurl . '">' . $content . '</a>';
						}
						break;
					default :
						$return = '<a title="' . $content . '" href="' . $myurl . '">' . $content . '</a>';
						break;
				}
			}
		}
		return array ($return, $error );
	}

	/**
	 * Create the metabox
	 *
	 */
	function createMetabox ()
	{
		$locale = $this->core->getOption( 'locale', 'shortcode' );

		echo '<ul id="avhamazon_tabs" class="avhamazon-tabs-nav">';

		// The tabs
		echo '<li class="avhamazon-tabs-selected"><a href="#avhamazon_tab_wishlist">' . __( 'Wishlist', 'avhamazon' ) . '</a></li>';
		echo '<li class=""><a href="#avhamazon_tab_asin">' . __( 'ASIN', 'avhamazon' ) . '</a></li></ul>';

		$this->metaboxTabWishlist( $locale );
		$this->metaboxTabAsin( $locale );
	}

	/**
	 * HTML for the Wishlist Tab
	 *
	 * @param string $locale
	 *
	 */
	function metaboxTabWishlist ( $locale )
	{
		wp_nonce_field( 'avhamazon-metabox', 'avhamazon_ajax_nonce', false );
		$wishlist_id = $this->core->getOption( 'wishlist_id', 'shortcode' );
		echo '<div id="avhamazon_tab_wishlist" class="avhamazon-tabs-panel">';
		echo '	<div id="avhamazon-wishlist-show" style="display:block">';
		echo '		<p>';
		echo '			<label style="display:block">' . __( 'Wish List ID:', 'avhamazon' );
		echo '			<input style="width: 13em" type="text" value="' . $wishlist_id . '" id="avhamazon_scwishlist_wishlist" name="avhamazon_scwishlist_wishlist" autocomplete="on"/>';
		echo '			</label>';
		echo '			<label style="display:block">' . __( 'Locale Amazon:', 'avhamazon' );
		echo '			<select id="avhamazon_scwishlist_locale" name="avhamazon_scwishlist_locale" />';
		$seldata = '';
		foreach ( $this->core->locale_table as $key => $value ) {
			$seldata .= '<option value="' . $key . '" ' . (($locale == $key) ? 'selected="selected"' : '') . ' >' . $value . '</option>' . "\n";
		}
		echo $seldata;
		echo '			</select></label>';
		echo '			<br />';

		echo '			<input class="button-secondary" type="submit" value="Show Items" id="avhamazon_submit_wishlist" name="avhamazon_submit_wishlist" />';
		echo '		</p>';
		echo '	</div>';
		echo '<div id="avhamazon_wishlist_loading" style="display:hide">';
		echo '	<p>Searching <img src="' . $this->core->info['graphics_url'] . '/ajax-loader.gif"></p>';
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
	function metaboxTabAsin ( $locale )
	{
		echo '<div id="avhamazon_tab_asin" class="avhamazon-tabs-panel" style="display:none;">';
		echo '	<div id="avhamazon_asin_show">';
		echo '		<p>';
		echo '			<label style="display:block">' . __( 'ASIN', 'avhamazon' );
		echo '			<input type="text" value="" style="style="width: 13em";" id ="avhamazon_asin_nr" name="avhamazon_asin_nr" autocomplete="on"/>';
		echo '			</label>';
		echo '			<label style="display:block">' . __( 'Locale Amazon:', 'avhamazon' );
		echo '			<select id="avhamazon_scasin_locale" name="avhamazon_scasin_locale" />';
		$seldata = '';
		foreach ( $this->core->locale_table as $key => $value ) {
			$seldata .= '<option value="' . $key . '" ' . (($locale == $key) ? 'selected="selected"' : '') . ' >' . $value . '</option>' . "\n";
		}
		echo $seldata;
		echo '			</select></label>';
		echo '			<br />';
		echo '			<input class="button-secondary" type="button" value="Show Item" id="avhamazon_submit_asin" name="avhamazon_submit_asin" />';
		echo '		</p>';
		echo '	</div>';
		echo '<div id="avhamazon_asin_loading" style="display:hide">';
		echo '	<p>Searching <img src="' . $this->core->info['graphics_url'] . '/ajax-loader.gif"></p>';
		echo '</div>';
		echo '	<div id="avhamazon_asin_output"></div>';
		echo '</div>';
	}

	/**
	 * The AJAX function called when the submit button is clicked. The call to admin-ajax.php is done through javascript in metabox.js
	 *
	 */
	function on_wp_ajax_avhamazon_metabox ()
	{
		check_ajax_referer( 'avhamazon-metabox', 'avhamazon_ajax_nonce' );
		echo '<script type="text/javascript">';
		echo 'var avhamazon = new avhamazonmetabox();';
		echo '</script>';
		$action = esc_attr( $_POST['avhamazon_mb_action'] );
		$values = $_POST['avhamazon_mb_values'];

		switch ( $action ) {
			case 'wishlist' :
				$this->metaboxTabWishlistOutput( $values );
				break;
			case 'asin' :
				$this->metaboxTabAsinOutput( $values );
				break;
		}
	}

	/**
	 * Get and show the results for the Wishlist tab
	 *
	 * @param array $values We get them from the javascript call.
	 */
	function metaboxTabWishlistOutput ( $values )
	{
		$wishlist = esc_attr( $values[0] );
		$locale = esc_attr( $values[1] );

		/**
		 * Set up endpoint
		 */
		$this->core->amazon_endpoint = $this->core->amazon_endpoint_table[$locale];

		$list_result = $this->core->getListResults( $wishlist );
		$total_items = count( $list_result['Lists']['List']['ListItem'] );
		if ( $total_items > 0 ) {
			$this->metaboxTabOutputHeader();
			$listitem = $list_result['Lists']['List']['ListItem'];
			foreach ( $listitem as $key => $value ) {
				$Item = $value;
				$item_result = $this->core->getItemLookup( $Item['Item']['ASIN'], '' );
				$this->metaboxTabOutputItem( $item_result['Items']['Item']['ItemAttributes']['Title'], $Item['Item']['ASIN'], 'avhamazon_scwishlist_asin-' . $key, 'avhamazon_scwishlist_asin', '', ('0' == $key) ? TRUE : FALSE );
			}
			// Display the last row as a randomizing option
			$this->metaboxTabOutputItem( __( 'Randomize the items', 'avhamazon' ), 'random', 'avhamazon_scwishlist_asin-random', 'avhamazon_scwishlist_asin', '', FALSE );
			$this->metaboxTabOutputOptions( 'wishlist' );
			$this->metaboxTabOutputSendtoeditor( 'wishlist' );
		} else {
			echo '<strong>' . __( 'Can\'t find the given wish list', 'avhamazon' ) . '</strong>';
		}
	}

	/**
	 * Get and show the results for the ASIN tab
	 *
	 * @param array $values We get them from the javascript call.
	 */
	function metaboxTabAsinOutput ( $values )
	{
		$asin = esc_attr( $values[0] );
		$locale = esc_attr( $values[1] );

		/**
		 * Set up endpoint
		 */
		$this->core->amazon_endpoint = $this->core->amazon_endpoint_table[$locale];

		$item_result = $this->core->getItemLookup( $asin, '' );
		if ( $item_result['Items']['Request']['Errors'] ) {
			echo '<strong>' . __( 'Can\'t find the given item', 'avhamazon' ) . '</strong>';
		} else {
			$this->metaboxTabOutputHeader();
			$imageinfo = $this->core->getImageInfo( 'swatch', $item_result );
			$this->metaboxTabOutputItem( $item_result['Items']['Item']['ItemAttributes']['Title'], $asin, 'avhamazon_scasin_asin', 'avhamazon_scasin_asin', '', TRUE, $imageinfo );
			$this->metaboxTabOutputOptions( 'asin' );
			$this->metaboxTabOutputSendtoeditor( 'asin' );
		}
	}

	/**
	 * Print the header for the result
	 *
	 */
	function metaboxTabOutputHeader ()
	{
		echo '<strong>' . __( 'Select item', 'avhamazon' ) . '</strong><br />';
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
	function metaboxTabOutputItem ( $title, $asin, $id, $name, $class = '', $checked = FALSE, $pic = '' )
	{
		$class = ($class) ? 'class="' . $class . '"' : '';
		$image = '';
		if ( is_array( $pic ) && (! empty( $pic['url'] )) ) {
			$image = '<img width="' . $pic['w'] . '" height="' . $pic['h'] . '" src="' . $pic['url'] . '" />';
		}
		echo '<p><label ' . $class . '><input type="radio" value="' . $asin . '" id="' . $id . '" name="' . $name . '"' . ($checked ? ' checked="checked" ' : "") . ' /> ' . $image . ' ' . $title . '</label></p>';
	}

	/**
	 * Display the general options after the row(s) of item(s)
	 *
	 * @param string $tabid (wishlist/asin)
	 */
	function metaboxTabOutputOptions ( $tabid )
	{
		echo '<p><strong>' . __( 'Link type:', 'avhamazon' ) . '</strong><br/>';
		echo '<label><input type="radio" value="text" id="avhamazon_sc' . $tabid . '_linktypet" checked="checked" name="avhamazon_sc' . $tabid . '_linktype"/> ' . __( 'Text', 'avhamazon' ) . '</label><br />';
		echo '<label><input type="radio" value="pic" id="avhamazon_sc' . $tabid . '_linktypep" name="avhamazon_sc' . $tabid . '_linktype"/> ' . __( 'Picture', 'avhamazon' ) . '</label><br />';
		echo '<label><input type="radio" value="pic-text" id="avhamazon_sc' . $tabid . '_linktypept" name="avhamazon_sc' . $tabid . '_linktype"/> ' . __( 'Picture and Text', 'avhamazon' ) . '</label></p>';
		echo '<p><strong>' . __( 'Picture Size:', 'avhamazon' ) . '</strong><br/>';
		echo '<label>';
		echo '<select id="avhamazon_sc' . $tabid . '_picsize" name="avhamazon_sc' . $tabid . '_picsize" autocomplete="on">';
		echo '<option selected="selected" value="small">Small</option>';
		echo '<option value="medium">Medium</option>';
		echo '<option value="large">Large</option>';
		echo '</select></p>';
		echo '<p><label><strong>' . __( 'Content:', 'avhamazon' ) . '</strong>';
		echo '<input type="text" style="width: 98%" id="avhamazon_sc' . $tabid . '_content" name="avhamazon_sc' . $tabid . '_content"/></p>';
	}

	/**
	 * Show the send to editor button
	 *
	 * @param string $tabid (wishlist/asin)
	 */
	function metaboxTabOutputSendtoeditor ( $tabid )
	{
		echo '<p class="submit">';
		echo '<input type="button" id="avhamazon_sendtoeditor" name="' . $tabid . '" value="' . __( 'Send to Editor', 'avhamazon' ) . '" />';
		echo '</p>';
	}
}
?>