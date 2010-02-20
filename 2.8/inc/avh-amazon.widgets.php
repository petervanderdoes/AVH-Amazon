<?php
class WP_Widget_AVHAmazon_Wishlist extends WP_Widget
{
	/**
	 *
	 * @var AVHAmazonCore
	 */
	var $core;

	/**
	 * PHP5 Constructor
	 *
	 */
	function __construct ()
	{
		$this->core = & AVHAmazonCore::getInstance();

		$widget_options = array ('description' => 'Gives you the ability to show items from your Amazon wishlist by using widgets or shortcode in posts and pages. The shortcode can also be used to display an item from Amazon', 'classname' => 'widget_avhamazon_wishlist' );
		WP_Widget::__construct( false, __( 'AVH Amazon' ), $widget_options );
		$this->core->handleCssFile( 'avhamazonwidget', '/inc/avh-amazon.widget.css' );

	}

	/**
	 * PHP4 Constructor
	 *
	 * @return AVHAmazonWidget
	 */
	function WP_Widget_AVHAmazon_Wishlist ()
	{
		$this->__construct();

	}

	/** Echo the settings update form
	 *
	 * @param array $instance Current settings
	 * @since 3.0
	 *
	 */
	function form ( $instance )
	{

		$instance = wp_parse_args( ( array ) $instance, array ('title' => '', 'associated_id' => '', 'wishlist_id' => '', 'nr_of_items' => '', 'show_footer' => 0, 'footer_template' => '', 'new_window' => 0 ) );
		$locale_table = $this->core->locale_table;

		// Prepare data for display
		$title = esc_attr($instance['title'] );
		$associated_id = format_to_edit( $instance['associated_id'] );
		$wishlist_id = format_to_edit( $instance['wishlist_id'] );
		$locale = $instance['locale'];
		$nr_of_items = format_to_edit( $instance['nr_of_items'] );
		$show_footer = $instance['show_footer'];
		$footer_template = format_to_edit( $instance['footer_template'] );
		$new_window = $instance['new_window'];

		echo '<div>';
		echo '<p>';
		echo '<label for="' . $this->get_field_id( 'title' ) . '" >';
		_e( 'Title:', 'avhamazon' );
		echo '</label>';
		echo '<input class="widefat" type="text" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" value="' . $title . '" />';
		echo '</p>';

		echo '<p>';
		echo '<label for="' . $this->get_field_id( 'associated_id' ) . '">';
		_e( 'Associated ID:', 'avhamazon' );
		echo '</label>';
		echo '<input class="widefat" type="text" id="' . $this->get_field_id( 'associated_id' ) . '" name="' . $this->get_field_name( 'associated_id' ) . '" value="' . $associated_id . '" />';
		echo '</p>';

		echo '<p>';
		echo '<label for="' . $this->get_field_id( 'wishlist_id' ) . '">';
		_e( 'Wishlist ID:', 'avhamazon' );
		echo '</label>';
		echo '<input class="widefat" type="text" id="' . $this->get_field_id( 'wishlist_id' ) . '" name="' . $this->get_field_name( 'wishlist_id' ) . '" value="' . $wishlist_id . '" />';
		echo '</p>';

		echo '<p>';
		echo '<label for="' . $this->get_field_id( 'locale' ) . '">';
		_e( 'Locale Amazon:', 'avhamazon' );
		echo '</label>';
		echo '<select class="widefat" id="' . $this->get_field_id( 'locale' ) . '" name="' . $this->get_field_name( 'locale' ) . '">';
		$seldata = '';
		foreach ( $locale_table as $key => $sel ) {
			echo '<option value="' . $key . '"' . (($locale == $key) ? ' selected="selected"' : '') . '>' . $sel . '</option>';
		}
		#echo $seldata;
		echo '</select>';
		echo '</p>';

		echo '<p>';
		echo '<label for="' . $this->get_field_id( 'nr_of_items' ) . '">';
		_e( 'Number of items:', 'avhamazon' );
		echo '</label>';
		echo '<input class="widefat" type="text" id="' . $this->get_field_id( 'nr_of_items' ) . '" name="' . $this->get_field_name( 'nr_of_items' ) . '" value="' . $nr_of_items . '" />';
		echo '</p>';

		echo '<p>';
		echo '<label for="' . $this->get_field_id( 'footer_template' ) . '">';
		_e( 'Footer template:', 'avhamazon' );
		echo '</label>';
		echo '<input class="widefat" type="text" id="' . $this->get_field_id( 'footer_template' ) . '" name="' . $this->get_field_name( 'footer_template' ) . '" value="' . $footer_template . '" />';

		echo '<label for="' . $this->get_field_id( 'show_footer' ) . '">';
		echo '<input type="checkbox" id="' . $this->get_field_id( 'show_footer' ) . '" name="' . $this->get_field_name( 'show_footer' ) . '" value="1"' . $this->core->isChecked( '1', $show_footer ) . ' /> ';
		_e( 'Show footer', 'avhamazon' );
		echo '</label>';
		echo '</p>';

		echo '<p>';
		echo '<label for="' . $this->get_field_id( 'new_window' ) . '">';
		echo '<input type="checkbox" id="' . $this->get_field_id( 'new_window' ) . '" name="' . $this->get_field_name( 'new_window' ) . '" value="1"' . $this->core->isChecked( '1', $new_window ) . ' />';
		_e( 'Open links in new window', 'avhamazon' );
		echo '</label>';
		echo '</p>';

		echo '<input type="hidden" id="' . $this->get_field_id( 'submit' ) . '" name="' . $this->get_field_name( 'submit' ) . '" value="1" />';
		_e( 'Empty field will use default value.', 'avhamazon' );
		echo '</div>';
	}

	/**
	 * Echo the widget content.
	 *
	 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget
	 * @since 3.0
	 *
	 */
	function widget ( $args, $instance )
	{

		extract( $args );

		// Set up variables
		$title = apply_filters('widget_title',$this->core->getWidgetOptions( $instance, 'title', 'widget_wishlist' ) );
		$wishlist_id = $this->core->getWidgetOptions( $instance, 'wishlist_id', 'widget_wishlist' );
		$associated_id = $this->core->getWidgetOptions( $instance, 'associated_id', 'general' );
		$imagesize = $this->core->getWidgetOptions( $instance, 'wishlist_imagesize', 'widget_wishlist' );
		$locale = $this->core->getWidgetOptions( $instance, 'locale', 'widget_wishlist' );
		$nr_of_items = $this->core->getWidgetOptions( $instance, 'nr_of_items', 'widget_wishlist' );
		$show_footer = $this->core->getWidgetOptions( $instance, 'show_footer', 'widget_wishlist' );
		$footer_template = $this->core->getWidgetOptions( $instance, 'footer_template', 'widget_wishlist' );
		$new_window = $this->core->getWidgetOptions( $instance, 'new_window', 'widget_wishlist' );

		// Check default associate ID and change it for the Locale
		if ( $this->core->default_options['general']['associated_id'] == $associated_id ) {
			$associated_id = $this->core->getAssociateId( $locale );
		}
		$this->core->amazon_endpoint = $this->core->amazon_endpoint_table[$locale];

		$list_result = $this->core->getListResults( $wishlist_id );

		echo $before_widget;
		echo $this->core->comment_begin;
		echo '<div id="avhamazon-widget">';
		echo $before_title . $title . $after_title;

		if ( isset( $list_result['Error'] ) ) {
			echo $this->core->getHttpError( $list_result['Error'] );
		} else {
			// Display the result
			$total_items = count( $list_result['Lists']['List']['ListItem'] );
			$Item_keys = $this->core->getItemKeys( $list_result['Lists']['List']['ListItem'], $nr_of_items );


			foreach ( $Item_keys as $value ) {
				$Item = $list_result['Lists']['List']['ListItem'][$value];
				$item_result = $this->core->getItemLookup( $Item['Item']['ASIN'], $associated_id );
				if ( isset( $item_result['Error'] ) ) {
					echo $this->core->getHttpError( $item_result['Error'] );
				} else {
					if ( isset( $item_result['Items']['Request']['Errors'] ) ) {
						echo 'Item with ASIN ' . $Item['Item']['ASIN'] . ' doesn\'t exist';
					} else {
						$imginfo = $this->core->getImageInfo( $imagesize, $item_result );

						$pos = strpos( $item_result['Items']['Item']['DetailPageURL'], $Item['Item']['ASIN'] );
						$myurl = substr( $item_result['Items']['Item']['DetailPageURL'], 0, $pos + strlen( $Item['Item']['ASIN'] ) );
						$myurl .= '/ref=wl_it_dp';

						$query['ie'] = 'UTF8';
						$query['colid'] = $wishlist_id;
						$query['tag'] = $associated_id;
						$myurl .= '?' . $this->core->BuildQuery( $query );

						$target = $new_window == 1 ? 'target="_blank"' : '';
						echo '<a ' . $target . ' title="' . $Item['Item']['ItemAttributes']['Title'] . '" href="' . $myurl . '"><img class="wishlistimage" width="' . $imginfo['w'] . '" height="' . $imginfo['h'] . '" src="' . $imginfo['url'] . '" alt="' . $Item['Item']['ItemAttributes']['Title'] . '"/></a>';
						echo '<div class="wishlistcaption">' . $Item['Item']['ItemAttributes']['Title'] . '</div>';
					}
				}
			}

			if ( $show_footer ) {
				$footer = apply_filters('avhamazon_text',str_replace( '%nr_of_items%', $total_items, $footer_template ));
				$myurl = $list_result['Lists']['List']['ListURL'];
				$myurl .= '?tag=' . $associated_id;
				echo '<div class="footer"><a title="Total items on the list" href="' . $myurl . '">' . $footer . '</a></div><br />';
			}
		}
		echo "</div>";
		echo $this->core->comment_end;
		echo $after_widget;
	}

	/**
	 * Update a particular instance.
	 *
	 * This function should check that $new_instance is set correctly.
	 * The newly calculated value of $instance should be returned.
	 * If "false" is returned, the instance won't be saved/updated.
	 *
	 * @param array $new_instance New settings for this instance as input by the user via form()
	 * @param array $old_instance Old settings for this instance
	 * @return array Settings to save or bool false to cancel saving
	 * @since 3.0
	 */
	function update ( $new_instance, $old_instance )
	{
		// update the instance's settings
		if ( ! isset( $new_instance['submit'] ) ) {
			return false;
		}

		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['associated_id'] = strip_tags( $new_instance['associated_id'] );
		$instance['wishlist_id'] = strip_tags( $new_instance['wishlist_id'] );
		$instance['locale'] = strip_tags( $new_instance['locale'] );
		$instance['nr_of_items'] = strip_tags( $new_instance['nr_of_items'] );
		$instance['show_footer'] = ($new_instance['show_footer'] ? 1 : 0);
		$instance['footer_template'] = strip_tags( $new_instance['footer_template'] );
		$instance['new_window'] = ($new_instance['new_window'] ? 1 : 0);

		return $instance;
	}

	/**
	 * Display the widget
	 *
	 * @param array $args
	 * @param array $widget_args
	 * @param boolean $usingwidget
	 */
	function widgetWishlist ( $args, $widget_args = 1, $usingwidget = TRUE )
	{
		extract( $args, EXTR_SKIP );
		if ( is_numeric( $widget_args ) ) {
			$widget_args = array ('number' => $widget_args );
		}

		$widget_args = wp_parse_args( $widget_args, array ('number' => - 1 ) );
		extract( $widget_args, EXTR_SKIP );

		if ( $usingwidget ) {
			// Data should be stored as array:  array( number => data for that instance of the widget, ... )
			$options = get_option( $this->core->db_options_name_widget_wishlist );
			if ( ! isset( $options[$number] ) ) {
				return;
			}
		}
		// Title of the widget
		$title = isset( $title ) ? $title : $this->core->getWidgetOptions( $options[$number], 'title', 'widget_wishlist' );

		// Wishlist ID
		$wishlist_id = isset( $wishlist_id ) ? $wishlist_id : $this->core->getWidgetOptions( $options[$number], 'wishlist_id', 'widget_wishlist' );

		// Assiociated ID
		$associated_id = isset( $associated_id ) ? $associated_id : $this->core->getWidgetOptions( $options[$number], 'associated_id', 'general' );

		// Image size
		$imagesize = isset( $imagesize ) ? $imagesize : $this->core->getWidgetOptions( $options[$number], 'wishlist_imagesize', 'widget_wishlist' );

		// Amazon locale
		$locale = isset( $locale ) ? $locale : $this->core->getWidgetOptions( $options[$number], 'locale', 'widget_wishlist' );

		// Number of Items
		$nr_of_items = isset( $nr_of_items ) ? $nr_of_items : $this->core->getWidgetOptions( $options[$number], 'nr_of_items', 'widget_wishlist' );

		// Show Footer
		$show_footer = isset( $show_footer ) ? $show_footer : $this->core->getWidgetOptions( $options[$number], 'show_footer', 'widget_wishlist' );

		// Footer Template
		$footer_template = isset( $footer_template ) ? $footer_template : $this->core->getWidgetOptions( $options[$number], 'footer_template', 'widget_wishlist' );

		// Open in new windows
		$new_window = isset( $new_window ) ? $new_window : $this->core->getWidgetOptions( $options[$number], 'new_window', 'widget_wishlist' );

		// Check default associate ID and change it for the Locale
		if ( $this->core->associate_table['US'] == $associated_id ) {
			$associated_id = $this->core->getAssociateId( $locale );
		}
		$this->core->amazon_endpoint = $this->core->amazon_endpoint_table[$locale];

		$list_result = $this->core->getListResults( $wishlist_id );

		echo $before_widget;
		echo $this->core->comment_begin;
		echo '<div id="avhamazon-widget">';
		echo $before_title . $title . $after_title;

		if ( isset( $list_result['Error'] ) ) {
			echo $this->core->getHttpError( $list_result['Error'] );
		} else {
			// Display the result
			$total_items = count( $list_result['Lists']['List']['ListItem'] );
			$Item_keys = $this->core->getItemKeys( $list_result['Lists']['List']['ListItem'], $nr_of_items );

			foreach ( $Item_keys as $value ) {
				$Item = $list_result['Lists']['List']['ListItem'][$value];
				$item_result = $this->core->getItemLookup( $Item['Item']['ASIN'], $associated_id );
				if ( isset( $item_result['Error'] ) ) {
					echo $this->core->getHttpError( $item_result['Error'] );
				} else {
					if ( isset( $item_result['Items']['Request']['Errors'] ) ) {
						echo 'Item with ASIN ' . $Item['Item']['ASIN'] . ' doesn\'t exist';
					} else {
						$imginfo = $this->core->getImageInfo( $imagesize, $item_result );

						$pos = strpos( $item_result['Items']['Item']['DetailPageURL'], $Item['Item']['ASIN'] );
						$myurl = substr( $item_result['Items']['Item']['DetailPageURL'], 0, $pos + strlen( $Item['Item']['ASIN'] ) );
						$myurl .= '/ref=wl_it_dp';

						$query['ie'] = 'UTF8';
						$query['colid'] = $wishlist_id;
						$query['tag'] = $associated_id;
						$myurl .= '?' . $this->core->BuildQuery( $query );

						$target = $new_window == 1 ? 'target="_blank"' : '';
						echo '<a ' . $target . ' title="' . $Item['Item']['ItemAttributes']['Title'] . '" href="' . $myurl . '"><img class="wishlistimage" width="' . $imginfo['w'] . '" height="' . $imginfo['h'] . '" src="' . $imginfo['url'] . '" alt="' . $Item['Item']['ItemAttributes']['Title'] . '"/></a><br/>';
						echo '<div class="wishlistcaption">' . $Item['Item']['ItemAttributes']['Title'] . '</div>';
						echo '<BR />';
					}
				}
			}
			if ( $show_footer ) {
				$footer = str_replace( '%nr_of_items%', $total_items, $footer_template );
				$myurl = $list_result['Lists']['List']['ListURL'];
				$myurl .= '?tag=' . $associated_id;
				echo '<div class="footer"><a title="Show all on Wishlist" href="' . $myurl . '">' . $footer . '</a></div><br />';
			}
		}
		echo "</div>";
		echo $this->core->comment_end;
		echo $after_widget;
	}

	/**
	 * Output the CSS file
	 *
	 */
	function handleWidgetCss ()
	{
		wp_enqueue_style( 'avhamazonwidget', $this->core->info['plugin_url'] . '/inc/avh-amazon.widget.css', array (), $this->core->version, 'screen' );
	}
}