<?php
class AVHAmazonWidget extends AVHAmazonCore {

	/**
	 * PHP5 Constructor
	 *
	 */
	function __construct () {
		parent::__construct();

		// Initialize!
		add_action ( 'widgets_init', array (&$this, 'initWidget') );
	}

	/**
	 * PHP4 Constructor
	 *
	 * @return AVHAmazonWidget
	 */
	function AVHAmazonWidget () {
		$this->__construct ();

	}

	/**
	 * Initialize the widget
	 *
	 */
	function initWidget () {

		add_action ( 'wp_head', array ( &$this, 'widgetCss'));

		$widget_options = array (
				'classname' => 'widget_avhamazon_wishlist'
		);
		$widget_function = array (	&$this,	'widgetWishlist' );

		$control_options = array (
				'width' => 300,
				'height' => 270
		);
		$control_function = array (	&$this,	'widgetControl' );
		$name = 'AVH Amazon Wishlist';

		if ( ! $options = get_option ( $this->db_options_name_widget_wishlist ) ) {
			$options = array ();
		}

		$registered = false;

		foreach ( array_keys ( $options ) as $key ) {
			if ( ! isset ( $options[$key]['title'] ) ) {
				continue;
			}

			// $id should look like {$id_base}-{$o}
			$id = 'widget-avhamazon-wishlist-' . $key;
			$registered = true;
			wp_register_sidebar_widget ( $id, $name, $widget_function, $widget_options, array (	'number' => $key ) );
			wp_register_widget_control ( $id, $name, $control_function, $control_options, array ( 'number' => $key ) );

		}

		// If there are none, we register the widget's existance with a generic template
		if ( ! $registered ) {
			wp_register_sidebar_widget ( 'widget-avhamazon-wishlist-1', $name, $widget_function, $widget_options, array ( 'number' => - 1 ) );
			wp_register_widget_control ( 'widget-avhamazon-wishlist-1', $name, $control_function, $control_options, array ( 'number' => - 1 ) );

		}
	}

	/**
	 * Widget Options in Admin Panel
	 *
	 */
	function widgetControl ( $widget_args = 1 ) {
		global $wp_registered_widgets;
		static $updated = false; // Whether or not we have already updated the data after a POST submit


		$locale_table = $this->locale_table;

		if ( is_numeric ( $widget_args ) ) {
			$widget_args = array (
					'number' => $widget_args
			);
		}
		$widget_args = wp_parse_args ( $widget_args, array ( 'number' => - 1 ) );
		extract ( $widget_args, EXTR_SKIP );

		// Data should be stored as array:  array( number => data for that instance of the widget, ... )
		// Get actual options
		$all_options = get_option ( $this->db_options_name_widget_wishlist );
		if ( ! is_array ( $all_options ) ) {
			$all_options = array ();
		}

		// We need to update the data
		if ( ! $updated && ! empty ( $_POST['sidebar'] ) ) {
			// Tells us what sidebar to put the data in
			$sidebar = ( string ) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets ();
			if ( isset ( $sidebars_widgets[$sidebar] ) ) {
				$this_sidebar = & $sidebars_widgets[$sidebar];
			} else {
				$this_sidebar = array ();
			}

			foreach ( ( array ) $this_sidebar as $_widget_id ) {
				// Remove all widgets of this type from the sidebar.  We'll add the new data in a second.  This makes sure we don't get any duplicate data
				// since widget ids aren't necessarily persistent across multiple updates
				if ( 'widget_avhamazon_wishlist' == $wp_registered_widgets[$_widget_id]['callback'] && isset ( $wp_registered_widgets[$_widget_id]['params'][0]['number'] ) ) {
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( ! in_array ( "widget-avhamazon-wishlist-$widget_number", $_POST['widget-id'] ) ) { // the widget has been removed.
						unset ( $all_options[$widget_number] );
					}
				}
			}

			foreach ( ( array ) $_POST['widget_avhamazon_wishlist'] as $widget_number => $widget_instance ) {
				// compile data from $widget_instance
				if ( ! isset ( $widget_instance['title'] ) && isset ( $all_options[$widget_number] ) ) { // User clicked cancel
					continue;
				}

				$options = array ();
				$options['title'] = strip_tags ( stripslashes ( $widget_instance['title'] ) );
				$options['associated_id'] = strip_tags ( stripslashes ( $widget_instance['associatedid'] ) );
				$options['wishlist_id'] = strip_tags ( stripslashes ( $widget_instance['wishlistid'] ) );
				$options['locale'] = strip_tags ( stripslashes ( $widget_instance['locale'] ) );
				$options['nr_of_items'] = strip_tags ( stripslashes ( $widget_instance['nr-of-items'] ) );
				$options['show_footer'] = ($widget_instance['widget-avhamazon-show-footer'] ? 1 : 0);
				$options['footer_template'] = strip_tags ( stripslashes ( $widget_instance['footer-template'] ) );

				$all_options[$widget_number] = $options;
			}

			update_option ( $this->db_options_name_widget_wishlist, $all_options );
			$updated = true;
		}

		// Here we echo out the form
		if ( - 1 == $number ) { // We echo out a template for a form which can be converted to a specific form later via JS
			$title = '';
			$associated_id = '';
			$wishlist_id = '';
			$locale = '';
			$nr_of_items = '';
			$show_footer = 0;
			$footer_template = '';
			$number = '%i%';
		} else {
			// Prepare data for display
			$title = attribute_escape ( $all_options[$number]['title'] );
			$associated_id = format_to_edit ( $all_options[$number]['associated_id'] );
			$wishlist_id = format_to_edit ( $all_options[$number]['wishlist_id'] );
			$locale = $all_options[$number]['locale'];
			$nr_of_items = format_to_edit ( $all_options[$number]['nr_of_items'] );
			$show_footer = $all_options[$number]['show_footer'];
			$footer_template = format_to_edit ( $all_options[$number]['footer_template'] );
		}

		// The form has inputs with names like widget_avhamazon_wishlist[$number][something] so that all data for that instance of
		// the widget are stored in one $_POST variable: $_POST['widget_avhamazon_wishlist'][$number]
		echo '<div>';
		echo '<p>';
		_e ( 'Empty field will use default value.', 'avhamazon' );

		echo '<label for="widget-avhamazon-title-"' . $number . '" style="line-height: 35px; display: block;">';
		_e ( 'Title:', 'avhamazon' );
		echo '<br />';
		echo '<input style="width: 100% !important;" type="text" id="widget-avhamazon-title-' . $number . '" name="widget_avhamazon_wishlist[' . $number . '][title]" value="' . $title . '" />';
		echo '</label>';

		echo '<label for="widget-avhamazon-associatedid-' . $number . '" style="line-height: 35px; display: block;">';
		_e ( 'Associated ID:', 'avhamazon' );
		echo '<br />';
		echo '<input style="width: 100% !important;" type="text" id="widget-avhamazon-associatedid-' . $number . '" name="widget_avhamazon_wishlist[' . $number . '][associatedid]" value="' . $associated_id . '" /> </label>';

		echo '<label for="widget-avhamazon-wishlistid-' . $number . '" style="line-height: 35px; display: block;">';
		_e ( 'Wishlist ID:', 'avhamazon' );
		echo '<br />';
		echo '<input style="width: 100% !important;" type="text" id="widget-avhamazon-wishlistid-' . $number . '" name="widget_avhamazon_wishlist[' . $number . '][wishlistid]" value="' . $wishlist_id . '" />';
		echo '</label>';

		echo '<label for="widget-avhamazon-locale-' . $number . '" style="line-height: 35px; display: block;">';
		_e ( 'Locale Amazon:', 'avhamazon' );
		echo '<br />';
		echo '<select id="widget-avhamazon-locale-' . $number . '" name="widget_avhamazon_wishlist[' . $number . '][locale]" />';
		$seldata = '';
		foreach ( $locale_table as $key => $sel ) {
			$seldata .= '<option value="' . $key . '" ' . (($locale == $key) ? 'selected="selected"' : '') . ' >' . $sel . '</option>' . "\n";
		}
		echo $seldata;
		echo '</select>';
		echo '</label>';

		echo '<label for="widget-avhamazon-nr-of-items-' . $number . '" style="line-height: 35px; display: block;">';
		_e ( 'Number of items:', 'avhamazon' );
		echo '<br />';
		echo '<input style="width: 100% !important;" type="text" id="widget-avhamazon-nr-of-items-' . $number . '" name="widget_avhamazon_wishlist[' . $number . '][nr-of-items]" value="' . $nr_of_items . '" />';
		echo '</label>';

		echo '<label for="widget-avhamazon-show-footer-' . $number . '" style="line-height: 35px; display: block;">';
		_e ( 'Show footer:', 'avhamazon' );
		echo '<br />';
		echo '<input style="width: 100% !important;" type="checkbox" id="widget-avhamazon-show-footer-' . $number . '" name="widget_avhamazon_wishlist[' . $number . '][show-footer]" value="1"' . checked ( '1', $show_footer ) . ' />';
		echo '</label>';

		echo '<label for="widget-avhamazon-footer-template-' . $number . '" style="line-height: 35px; display: block;">';
		_e ( 'Footer template:', 'avhamazon' );
		echo '<br />';
		echo '<input style="width: 100% !important;" type="text" id="widget-avhamazon-footer-template-' . $number . '" name="widget_avhamazon_wishlist[' . $number . '][footer-template]" value="' . $footer_template . '" />';
		echo '</label>';

		echo '<input type="hidden" name="widget_avhamazon_wishlist[' . $number . '][submit]" id="widget-avhamazon-submit-' . $number . '" value="1" />';
		echo '</div>';
	}

	/**
	 * Display the widget
	 *
	 * @param array $args
	 * @param array $widget_args
	 * @param boolean $usingwidget
	 */
	function widgetWishlist ( $args, $widget_args = 1, $usingwidget = TRUE ) {

		extract ( $args, EXTR_SKIP );
		if ( is_numeric ( $widget_args ) ) {
			$widget_args = array (
					'number' => $widget_args
			);
		}

		$widget_args = wp_parse_args ( $widget_args, array ( 'number' => - 1 ) );
		extract ( $widget_args, EXTR_SKIP );

		if ( $usingwidget ) {
			// Data should be stored as array:  array( number => data for that instance of the widget, ... )
			$options = get_option ( $this->db_options_name_widget_wishlist );
			if ( ! isset ( $options[$number] ) ) {
				return;
			}
		}
		// Title of the widget
		$title = isset($title) ? $title : $this->getWidgetOptions ( $options[$number], 'title', 'widget_wishlist' );

		// Wishlist ID
		$ListID = isset($ListID) ? $ListID : $this->getWidgetOptions ( $options[$number], 'wishlist_id', 'widget_wishlist' );

		// Assiociated ID
		$associatedid = isset($associatedid) ? $associatedid : $this->getWidgetOptions ( $options[$number], 'associated_id', 'general' );

		// Image size
		$imagesize = isset($imagesize) ? $imagesize : $this->getWidgetOptions ( $options[$number], 'wishlist_imagesize', 'widget_wishlist' );

		// Amazon locale
		$locale = isset($locale) ? $locale : $this->getWidgetOptions ( $options[$number], 'locale', 'widget_wishlist' );

		// Number of Items
		$nr_of_items = isset($nr_of_items) ? $nr_of_items : $this->getWidgetOptions ( $options[$number], 'nr_of_items', 'widget_wishlist' );

		// Show Footer
		$show_footer = isset($show_footer) ? $show_footer : $this->getWidgetOptions ( $options[$number], 'show_footer', 'widget_wishlist' );

		// Footer Template
		$footer_template = isset($footer_template) ? $footer_template : $this->getWidgetOptions ( $options[$number], 'footer_template', 'widget_wishlist' );

		// Check default assiociate ID and change it for the Locale
		if ( 'avh-amazon-20' == $associatedid ) {
			$associatedid = $this->getAssociateId ( $locale );
		}
		/**
		 * Set up WSDL Cache
		 */
		$this->wsdlurl = $this->wsdlurl_table[$locale];
		$cache = new wsdlcache ( $this->wsdlcachefolder, 0 ); // Cache it indefinitely
		$this->wsdl = $cache->get ( $this->wsdlurl );
		if ( is_null ( $this->wsdl ) ) {
			$this->wsdl = new wsdl ( $this->wsdlurl );
			$cache->put ( $this->wsdl );
		} else {
			$this->wsdl->debug_str = '';
			$this->wsdl->debug ( 'Retrieved from cache' );
		}

		/**
		 * Create SOAP Client
		 */
		$client = new nusoap_client ( $this->wsdl, true );
		$client->soap_defencoding = 'UTF-8';
		$proxy = $client->getProxy ();

		$list_result = $this->getListResults ( $ListID, $proxy );
		$total_items = count ( $list_result['Lists']['List']['ListItem'] );

		echo $before_widget;
		echo '<!-- AVH Amazon version ' . $this->version . ' | http://blog.avirtualhome.com/wordpress-plugins/ -->';
		echo '<div id="avhamazon-widget">';
		echo $before_title . $title . $after_title;

		// Check for a fault
		if ( $proxy->fault ) {
			echo 'Fault<br/><pre>';
			print_r ( $list_result );
			echo '</pre>';
		} else {
			// Check for errors
			$err = $proxy->getError ();
			if ( $err ) {
				// Display the error
				echo 'Error<br/><pre>' . $err . '</pre>';
			} else {
				// Display the result
				$Item_keys = $this->getItemKeys ( $list_result['Lists']['List']['ListItem'], $nr_of_items );

				foreach ( $Item_keys as $value ) {
					$Item = $list_result['Lists']['List']['ListItem'][$value];
					$item_result = $proxy->ItemLookup ( $this->getSoapItemLookupParams ( $Item['Item']['ASIN'], $associatedid ) );

					$imgsrc = $this->getImageUrl ( $imagesize, $item_result );

					$pos = strpos ( $item_result['Items']['Item']['DetailPageURL'], $Item['Item']['ASIN'] );
					$myurl = substr ( $item_result['Items']['Item']['DetailPageURL'], 0, $pos + strlen ( $Item['Item']['ASIN'] ) );
					$myurl .= '/ref=wl_it_dp?ie=UTF8&colid=' . $ListID;
					$myurl .= '&tag=' . $associatedid;

					echo '<a title="' . $Item['Item']['ItemAttributes']['Title'] . '" href="' . $myurl . '"><img class="wishlistimage" src="' . $imgsrc . '" alt="' . $Item['Item']['ItemAttributes']['Title'] . '"/></a><br/>';
					echo '<div class="wishlistcaption">' . $Item['Item']['ItemAttributes']['Title'] . '</div>';
					echo '<BR />';
				}
				if ( $show_footer ) {
					$footer = str_replace ( '%nr_of_items%', $total_items, $footer_template );
					$myurl = $list_result['Lists']['List']['ListURL'];
					$myurl .= '?tag=' . $associatedid;
					echo '<div class="footer"><a title="Show all on Wishlist" href="' . $myurl . '">' . $footer . '</a></div><br />';
				}
			}
		}
		echo "</div>";
		echo $after_widget;
	}

	/**
	 * Output the CSS file
	 *
	 */
	function widgetCss () {
		if ( $this->info['wordpress_version'] >= 2.6 ) {
			$this->handleCssFile ( 'avhamazonwidget', '/inc/avh-amazon.widget.css' );
		} else {
			// for older versions
			echo '<link media="all" type="text/css" href="' . $this->info['install_url'] . '/inc/avh-amazon.widget.css?ver=' . $this->version . '" rel="stylesheet"> </link>';
		}
	}
}

