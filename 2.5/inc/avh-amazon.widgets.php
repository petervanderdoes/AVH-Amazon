<?php
/**
 * Initialize the Wish List widget
 *
 */
function widget_avhamazon_wishlist_init() {
	// Widgets exists ?
	if ( ! function_exists( 'wp_register_sidebar_widget' ) || ! function_exists( 'wp_register_widget_control' ) ) {
		return;
	}

	// AVH Amazon exists ?
	if ( ! class_exists( 'avh_amazon' ) ) {
		return;
	}

	/**
	 * Display the Wish List widget
	 */
	function widget_avhamazon_wishlist($widget_args, $number = 1) {
		global $avhamazon;

		extract( $widget_args );
		/**
		 * Check for options set in Widget itself
		 */
		$options = get_option( 'widget_avhamazon_wishlist' );

		// Title of the widget
		$title = avh_getWidgetOptions($options[$number],'title');

		// Wishlist ID
		$ListID = avh_getWidgetOptions($options[$number],'wishlist_id');

		// Assiociated ID
		$associatedid = avh_getWidgetOptions($options[$number],'associated_id');

		// Image size
		$imagesize = avh_getWidgetOptions($options[$number],'wishlist_imagesize');

		// Amazon locale
		$locale = avh_getWidgetOptions($options[$number],'locale');

		// Number of Items
		$nr_of_items = avh_getWidgetOptions($options[$number],'nr_of_items');

		// Show Footer
		$show_footer = avh_getWidgetOptions($options[$number],'show_footer');

		// Footer Template
		$footer_template = avh_getWidgetOptions($options[$number],'footer_template');

		// Check default assiociate ID and change it for the Locale
		if ( 'avh-amazon-20' == $associatedid ) {
			$associatedid = avh_getAssociateId($locale);
		}
		/**
		 * Set up WSDL Cache
		 */
		$avhamazon->wsdlurl = $avhamazon->wsdlurl_table[$locale];
		$cache = new wsdlcache( $avhamazon->wsdlcachefolder, 0 ); // Cache it indefinitely
		$avhamazon->wsdl = $cache->get( $avhamazon->wsdlurl );
		if ( is_null( $avhamazon->wsdl ) ) {
			$avhamazon->wsdl = new wsdl( $avhamazon->wsdlurl );
			$cache->put( $avhamazon->wsdl );
		} else {
			$avhamazon->wsdl->debug_str = '';
			$avhamazon->wsdl->debug( 'Retrieved from cache' );
		}

		/**
		 * Create SOAP Client
		 */
		$client = new nusoap_client( $avhamazon->wsdl, true );
		$client->soap_defencoding = 'UTF-8';
		$proxy = $client->getProxy();

		$list_result = avh_getListResults($ListID, $proxy);
		$total_items = count ($list_result['Lists']['List']['ListItem']);
		echo '<link media="screen" type="text/css" href=' . $avhamazon->info['install_url'] . '/inc/avh-amazon.widget.css?ver=' . $avhamazon->version . ' rel="stylesheet">' . "\n";

		echo $before_widget;
		echo '<!-- AVH Amazon version ' . $avhamazon->version . ' | http://blog.avirtualhome.com/wordpress-plugins/ -->';
		echo '<div id="avhamazon-widget">';
		echo $before_title . $title . $after_title;

		// Check for a fault
		if ( $proxy->fault ) {
			echo 'Fault<br/><pre>';
			print_r( $list_result );
			echo '</pre>';
		} else {
			// Check for errors
			$err = $proxy->getError();
			if ( $err ) {
				// Display the error
				echo 'Error<br/><pre>' . $err . '</pre>';
			} else {
				// Display the result
				$Item_keys = avh_getItemKeys( $list_result['Lists']['List']['ListItem'], $nr_of_items );

				foreach ($Item_keys as $value) {
					$Item = $list_result['Lists']['List']['ListItem'][$value];
					$item_result = $proxy->ItemLookup( avh_getSoapItemLookupParams( $Item['Item']['ASIN'], $associatedid ) );
					switch ( $imagesize ) {
						case Small :
							$imgsrc = $item_result['Items']['Item']['SmallImage']['URL'];
							break;
						case Medium :
							$imgsrc = $item_result['Items']['Item']['MediumImage']['URL'];
							break;
						case Large :
							$imgsrc = $item_result['Items']['Item']['LargeImage']['URL'];
							break;
						default:
							$imgsrc = $item_result['Items']['Item']['MediumImage']['URL'];
							break;
					}
					$pos=strpos($item_result['Items']['Item']['DetailPageURL'],$Item['Item']['ASIN']);
					$myurl=substr($item_result['Items']['Item']['DetailPageURL'],0,$pos+strlen($Item['Item']['ASIN']));
					$myurl .= '/ref=wl_it_dp?ie=UTF8&colid=' . $ListID;
					$myurl .= '&tag=' .$associatedid;
					echo '<a title="' . $Item['Item']['ItemAttributes']['Title'] . '" href="' . $myurl . '"><img class="wishlistimage" src="' . $imgsrc . '" alt="' . $Item['Item']['ItemAttributes']['Title'] . '"/></a><br/>';
					echo '<div class="wishlistcaption">' . $Item['Item']['ItemAttributes']['Title'] . '</div>';
					echo '<BR />';
				}
				if ($show_footer) {
					$footer=str_replace('%nr_of_items%', $total_items, $footer_template);
					$myurl = $list_result['Lists']['List']['ListURL'];
					$myurl .= '&tag=' .$associatedid;
					echo '<div class="footer"><a title="Show all on Wishlist" href="' . $myurl . '">' . $footer .'</a></div><br />';
				}
			}
		}
		echo "</div>";
		echo $after_widget;
	}

	/**
	 * Wish List widget options
	 */
	function widget_avhamazon_wishlist_control($number) {
		global $avhamazon;

		// Locale Table
		$locale_table = $avhamazon->locale_table;

		// Get actual options
		$options = $newoptions = get_option( 'widget_avhamazon_wishlist' );
		if ( ! is_array( $options ) ) {
			$options = $newoptions = array ()

			;
		}
		// Post to new options array
		if ( isset( $_POST['widget-avhamazon-submit-' . $number] ) ) {
			$newoptions[$number]['title'] = strip_tags( stripslashes( $_POST['widget-avhamazon-title-' . $number] ) );
			$newoptions[$number]['associated_id'] = strip_tags( stripslashes( $_POST['widget-avhamazon-associatedid-' . $number] ) );
			$newoptions[$number]['wishlist_id'] = strip_tags( stripslashes( $_POST['widget-avhamazon-wishlistid-' . $number] ) );
			$newoptions[$number]['locale'] = strip_tags( stripslashes( $_POST['widget-avhamazon-locale-' . $number] ) );
			$newoptions[$number]['nr_of_items'] = strip_tags( stripslashes( $_POST['widget-avhamazon-nr-of-items-' . $number] ) );
			$newoptions[$number]['show_footer'] = ($_POST['widget-avhamazon-show-footer-' . $number] ? 1 : 0);
			$newoptions[$number]['footer_template'] = strip_tags( stripslashes( $_POST['widget-avhamazon-footer-template-' . $number] ) );
		}

		// Update if new options
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option( 'widget_avhamazon_wishlist', $options );
		}

		// Prepare data for display
		$title = htmlspecialchars( $options[$number]['title'], ENT_QUOTES );
		$associated_id = htmlspecialchars( $options[$number]['associated_id'], ENT_QUOTES );
		$wishlist_id = htmlspecialchars( $options[$number]['wishlist_id'], ENT_QUOTES );
		$locale = $options[$number]['locale'];
		$nr_of_items = htmlspecialchars( $options[$number]['nr_of_items'], ENT_QUOTES );
		$show_footer = $options[$number]['show_footer'];
		$footer_template = htmlspecialchars( $options[$number]['footer_template'], ENT_QUOTES );
		?>

<div>
<p><?php _e( 'Empty field will use default value.', 'avhamazon' ); ?></p>

<label for="widget-avhamazon-title-<?php echo $number; ?>" style="line-height: 35px; display: block;">
	<?php _e( 'Title:', 'avhamazon' ); ?>
	<br />
	<input style="width: 100% !important;" type="text" id="widget-avhamazon-title-<?php echo $number; ?>" name="widget-avhamazon-title-<?php echo $number; ?>" value="<?php	echo $title; ?>" />
</label>
<label for="widget-avhamazon-associatedid-<?php	echo $number; ?>" style="line-height: 35px; display: block;">
	<?php _e( 'Associated ID:', 'avhamazon' ); ?>
	<br />
	<input style="width: 100% !important;" type="text" id="widget-avhamazon-associatedid-<?php echo $number; ?>" name="widget-avhamazon-associatedid-<?php echo $number; ?>" value="<?php echo $associated_id; ?>" />
</label>
<label for="widget-avhamazon-wishlistid-<?php echo $number;	?>"	style="line-height: 35px; display: block;">
	<?php _e( 'Wishlist ID:', 'avhamazon' ); ?>
	<br />
	<input style="width: 100% !important;" type="text" id="widget-avhamazon-wishlistid-<?php echo $number; ?>" name="widget-avhamazon-wishlistid-<?php echo $number; ?>" value="<?php echo $wishlist_id; ?>" />
</label>
<label for="widget-avhamazon-locale-<?php echo $number;	?>"	style="line-height: 35px; display: block;">
	<?php _e( 'Locale Amazon:', 'avhamazon' ); ?>
	<br />
	<select id="widget-avhamazon-locale-<?php echo $number; ?>" name="widget-avhamazon-locale-<?php echo $number; ?>" />
		<?php
		$seldata = '';
		foreach ( $locale_table as $key => $sel ) {
			$seldata .= '<option value="' . $key . '" ' . (($locale == $key) ? 'selected="selected"' : '') . ' >' . $sel . '</option>' . "\n";
		}
		echo $seldata; ?>
	</select>
</label>
<label for="widget-avhamazon-nr-of-items-<?php echo $number;	?>"	style="line-height: 35px; display: block;">
	<?php _e( 'Number of items:', 'avhamazon' ); ?>
	<br />
	<input style="width: 100% !important;" type="text" id="widget-avhamazon-nr-of-items-<?php echo $number; ?>" name="widget-avhamazon-nr-of-items-<?php echo $number; ?>" value="<?php echo $nr_of_items; ?>" />
</label>
<label for="widget-avhamazon-show-footer-<?php echo $number;	?>"	style="line-height: 35px; display: block;">
	<?php _e( 'Show footer:', 'avhamazon' ); ?>
	<br />
	<input style="width: 100% !important;" type="checkbox" id="widget-avhamazon-show-footer-<?php echo $number; ?>" name="widget-avhamazon-show-footer-<?php echo $number; ?>" value="1" <?php checked('1', $show_footer) ?> />
</label>
<label for="widget-avhamazon-footer-template-<?php echo $number;	?>"	style="line-height: 35px; display: block;">
	<?php _e( 'Footer template:', 'avhamazon' ); ?>
	<br />
	<input style="width: 100% !important;" type="text" id="widget-avhamazon-footer-template-<?php echo $number; ?>" name="widget-avhamazon-footer-template-<?php echo $number; ?>" value="<?php echo $footer_template; ?>" />
</label>

<input type="hidden" name="widget-avhamazon-submit-<?php echo $number; ?>" id="widget-avhamazon-submit-<?php echo $number; ?>" value="1" />
</div>
<?php
	}

	/**
	 * Called after the widget_avhamazon_wishlist_page form has been submitted.
	 * Set the amount of widgets wanted and register the widgets
	 *
	 */
	function widget_avhamazon_wishlist_setup() {
		$options = $newoptions = get_option('widget_avhamazon_wishlist');
		if ( isset($_POST['avhamazon_wishlist-number-submit']) ) {
			$number = (int) $_POST['avhamazon_wishlist-number'];
			if ( $number > 9 ) $number = 9;
			if ( $number < 1 ) $number = 1;
			$newoptions['number'] = $number;
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_avhamazon_wishlist', $options);
			widget_avhamazon_wishlist_register($options['number']);
		}
	}
	/**
	 * How many Wish List widgets are wanted.
	 *
	 */
	function widget_avhamazon_wishlist_page() {
		$options = get_option('widget_avhamazon_wishlist');
		echo '	<div class="wrap">';
		echo '		<form method="post">';
		echo '			<h2>'. __('AVH Amazon Wishlist Widgets', 'avhamazon') .'</h2>';
		echo '			<p style="line-height: 30px;">'. __('How many wishlist widgets would you like?', 'avhamazon');
		echo '				<select id="avhamazon_wishlist-number" name="avhamazon_wishlist-number" value="'. $options["number"] .'">';
		for ( $i = 1; $i < 10; ++$i ) echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>";
		echo '				</select>';
		echo '				<span class="submit"><input type="submit" name="avhamazon_wishlist-number-submit" id="avhamazon_wishlist-number-submit" value="'. attribute_escape(__('Save', 'avhamazon')) .'" /></span></p>';
		echo '		</form>';
		echo '	</div>';
	}

	/**
	 * Register/Unregister the Wish List widgets with WordPress
	 */
	function widget_avhamazon_wishlist_register() {
		$options = get_option( 'widget_avhamazon_wishlist' );

		$number = ( int ) $options['number'];
		if ( $number < 1 ) $number = 1;
		if ( $number > 9 ) $number = 9;
		for ( $i = 1; $i <= 9; $i ++ ) {
			$id = "widget-avhamazon-wishlist-$i";
			$name = sprintf( __('AVH Amazon Wishlist %d'), $i );
			wp_register_sidebar_widget( $id, $name, $i <= $number ? 'widget_avhamazon_wishlist' : /* unregister */ '', array ( 'classname'=>'widget_avhamazon_wishlist'), $i );
			wp_register_widget_control( $id, $name, $i <= $number ? 'widget_avhamazon_wishlist_control' : /* unregister */ '', array ( 'width'=>300,	'height'=>270), $i );
		}

		add_action('sidebar_admin_setup', 'widget_avhamazon_wishlist_setup');
		add_action('sidebar_admin_page', 'widget_avhamazon_wishlist_page');
	}
	//Launch Widgets
	widget_avhamazon_wishlist_register();
}

// Initialize!
add_action('widgets_init','widget_avhamazon_wishlist_init');