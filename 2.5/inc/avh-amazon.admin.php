<?php
class AVHAmazonAdmin extends AVHAmazonCore {

	/**
	 * Message management
	 *
	 */
	var $message = '';
	var $status = '';

	function __construct () {

		parent::__construct();

		// Admin URL and Pagination
		$this->admin_base_url = $this->info['siteurl'] . '/wp-admin/admin.php?page=';
		if ( isset ( $_GET['pagination'] ) ) {
			$this->actual_page = ( int ) $_GET['pagination'];
		}

		// Admin Capabilities
		add_action('init', array(&$this, 'initRoles'));

		// Admin menu
		add_action( 'admin_menu', array ( &$this, 'adminMenu') );

		// CSS Helper
		add_action( 'admin_head', array ( &$this, 'helperCSS') );

		// Helper JS & jQuery & Prototype
		$avhamazon_pages = array (
			'avhamazon_options',
			'avhamazon_tools');
		/**
		 * TODO  With WordPress 2.5 the Tabs UI is build in :)
		 */
		if ( in_array( $_GET['page'], $avhamazon_pages ) ) {
			wp_enqueue_script( 'jquery-tabs', $this->info['install_url'] . '/inc/js/jquery.tabs.pack.js', array ('jquery'), '3' );
			wp_enqueue_script( 'jquery-forms', $this->info['install_url'] . '/inc/js/jquery.form.js', array ('jquery'), '3' );
		}
		return;
	}

	/**
	 * PHP4 Constructor - Intialize Admin
	 *
	 * @return
	 */
	function AVHAmazonAdmin() {
		$this->__construct();
	}

	/**
	 * Setup Roles
	 *
	 */
	function initRoles() {
		if ( function_exists('get_role') ) {
			$role = get_role('administrator');
			if( $role != null && !$role->has_cap('avh_amazon') ) {
				$role->add_cap('avh_amazon');
			}
			if( $role != null && !$role->has_cap('admin_avh_amazon') ) {
				$role->add_cap('admin_avh_amazon');
			}
			// Clean var
			unset($role);

			$role = get_role('editor');
			if( $role != null && !$role->has_cap('avh_amazon') ) {
				$role->add_cap('avh_amazon');
			}
			// Clean var
			unset($role);
		}
	}

	/**
	 * Add the Tools and Options to the Management and Options page repectively
	 *
	 */
	function adminMenu() {
		add_management_page( __( 'AVH Amazon Tools', 'avhamazon' ), __( 'AVH Amazon Tools', 'avhamazon' ), 'avh_amazon', 'avhamazon_tools', array (	&$this, 'pageAVHAmazonTools') );
		add_options_page( __( 'AVH Amazon: Options', 'avhamazon' ), 'AVH Amazon', 'avh_amazon', 'avhamazon_options', array ( &$this, 'pageOptions') );
	}

	/**
	 * WP Page Manage - AVH Amazon Find Wishlist ID
	 *
	 */

	function pageAVHAmazonTools() {

		// Locale Table
		$locale_table = $this->locale_table;
		?>
<script type="text/javascript"><!--

jQuery(document).ready(function() {
	var searchoptions = {
		target:		'#avhamazonwishlistoutputsearch',	// target element(s) to be updated with server response
	};

	jQuery('#findid').submit(function() {
		jQuery(this).ajaxSubmit(searchoptions);
		return false;
	});
});
// -->
</script>
<?php
		echo '<div class="wrap">';
		echo '<h2>';
		echo _e( 'AVH Amazon: Tools', 'avhamazon' );
		echo '</h2>';
		echo '<h3>Find Wish List ID</h3>';
		echo '<form id="findid" action=' . $this->info['install_url'] . '/inc/avh-amazon.tools.php method="post" autocomplete="on"><input type="hidden" value="hiddenValue" name="Hidden" autocomplete="on"/>';
		echo '<table class="form-table"><tbody><tr><td>';
		echo '<p>Select the Amazon locale.</p>';
		echo '<p><select id="locale" name="locale" />';
		$seldata = '';
		foreach ( $locale_table as $key => $value ) {
			$seldata .= '<option value="' . $key . '" >' . $value . '</option>' . "\n";
		}
		echo $seldata;
		echo '</select>';
		echo '<p>Enter the e-mail address used to sign on to Amazon to find the Wish List ID.</p>';
		echo '<p><input name="action" value="findid" type="hidden" />';
		echo '<input id="email" type="text" size="40" name="email" id="email" autocomplete="on" />';
		echo '<input class="button-secondary" type="submit" value="Search"	name="submitButton" autocomplete="on" /></p>';
		echo '</td></tr>';
		echo '</tbody></table>';
		echo '<div id="avhamazonwishlistoutputsearch"></div>';
		echo '</form>';

		$this->printAdminFooter();
		echo '</div>';
	}

	/**
	 * WP Page Options- AVH Amazon options
	 *
	 */
	function pageOptions() {
		$option_data = array (
				'general' => array (
						array (
								'avhamazon[general][associated_id]',
								'Associated ID:',
								'text',
								16,
								'Use this Associated ID when clicking on the wishlist'
						)
				),
				'widget_wishlist' => array (
						array (
								'avhamazon[widget_wishlist][wishlist_id]',
								'Default wishlist ID:',
								'text',
								16,
								'This is the default wishlist ID, if you don\'t fill out a Wishlist ID in the widget this one will be used.'
						),
						array (
								'avhamazon[widget_wishlist][locale]',
								'Locale Amazon:',
								'dropdown',
								'US/CA/DE/UK',  // Locale Value
								'Amazon.com/Amazon.ca/Amazon.de/Amazon.co.uk'
						),  // Locale Text
						array (
								'avhamazon[widget_wishlist][wishlist_imagesize]',
								'Size of thumbnail:',
								'dropdown',
								'Small/Medium/Large',  // Value
								'Small/Medium/Large'
						),  // Text
						array (
								'avhamazon[widget_wishlist][nr_of_items]',
								'Number of items:',
								'text',
								3,
								'Amount of items of your Wish List to show in the widget.'
						),
						array (
								'avhamazon[widget_wishlist][footer_template]',
								'Footer template:',
								'text',
								30,
								'The display of the footer is controlled in the widget options<BR />%nr_of_items% is replaced by the actual number of items in the wishlist.'
						)
				),
				'shortcode' => array (
						array (
								'avhamazon[shortcode][wishlist_id]',
								'Default wishlist ID:',
								'text',
								16,
								'This value will be automatically be entered in the AVH Amazon Short Code - wishlist metabox.'
						),
						array (
								'avhamazon[shortcode][locale]',
								'Locale Amazon:',
								'dropdown',
								'US/CA/DE/UK',  // Locale Value
								'Amazon.com/Amazon.ca/Amazon.de/Amazon.co.uk'
						)
				),  // Locale Text
				'faq' => array (
						array (
								'text-helper',
								'text-helper',
								'helper',
								'',
								'<b>Can I use this plugin if I don\'t have a widget enabled theme?</b><br />' .
									'Yes you can, you can use the following code to display the wishlist:<br />' .
									'<?php $avhwidget=& new AVHAmazonWidget();$avhwidget->widgetWishlist(array(),1 , FALSE); ?> <br /><br />' .
								'<b>Where is the Baby/Wedding Registry widget?</b><br />' .
									'There is no seperate widget for the registries. To show the registry items use the Wishlist widget and use your Baby Registry ID or Wedding Registry ID.<br /><br />' .
								'<b>How do I find my Baby Registry and/or Wedding Registry ID?</b><br />' .
									'When you create either registry Amazon sends you an email with the direct link to access your registry. The ID is the last part of the URL.<br />' .
									'Example:<br />' .
									'http://www.amazon.com/gp/registry/1234567890ABC<br/>' .
									'The ID is 1234567890ABC<br /><br />' .
								'<b>What is an ASIN?</b><br />' .
									'ASIN stands for Amazon Standard Identification Number.<br />' .
									'Every product has its own ASIN--a unique code they use to identify it. For books, the ASIN is the same as the 10-digit ISBN number.<br />' .
									'You will find an item\'s ASIN on the product detail page.<br /><br />'
						)
				),
				'about' => array (
						array (
								'text-helper',
								'text-helper',
								'helper',
								'',
								'<p>The AVH Amazon plugin gives you the ability to add multiple widgets which will display one or more random item(s) from your Amazon wishlist, baby registry and/or wedding registry. It also has the ability to show items with their link, in posts and pages by use of shortcode.<br />' .
								'In the plugin reference is made to Wishlist only but you can use your Baby Registry ID or Wedding Registry ID as well.</p>' .
								'<b>General</b>' .
									'<ul>' .
										'<li>Works with amazon.com, and locales amazon.ca, amazon.de and amazon.co.uk.<br /><br />' .
									'</ul>' .
								'<b>Wishlist</b>' .
									'<ul>' .
										'<li>Add Associated ID.' .
										'<li>Choice of thumbnail size, Small/Medium/Large.' .
										'<li>Option to use up to unlimited widgets.' .
										'<li>Multiple items from the same Wish List can be displayed in the widget.' .
										'<li>A configurable footer can be displayed on the bottom of the widget linking to the list on Amazon.' .
									'</ul>' .
								'<b>Shortcode</b>' .
									'<ul>' .
										'<li>Create the shortcode with the help of a metabox' .
										'<li>In the metabox you can select an item or select to randomize the items from your wishlist or search for an item by ASIN.' .
										'<li>The shortcode creates text, picture or text & picture links.' .
										'<li>If a text link or text & picture links is used, the default text is the item description from Amazon but the text of the link can be changed.' .
										'<li>The value all for the ASIN option will show all items from your wishlist. In combination with a text & picture link type you can create a wishlist page.' .
									'</ul>' .
								'<b>Tools</b>' .
									'<ul>' .
										'<li>Look up your wishlist ID.' .
									'</ul>' .
								'<b>Support</b><br />' .
									'For support visit the AVH support forums at <a href="http://forums.avirtualhome.com/">http://forums.avirtualhome.com/</a><br /><br />' .
								'<b>Developer</b><br />' .
									'Peter van der Does'
						)
				)
		);

		// Update or reset options
		if ( isset ( $_POST['updateoptions'] ) ) {
			// Set all checkboxes unset
			if ( isset ( $_POST['avh_checkboxes'] ) ) {
				$checkboxes = explode ( '|', $_POST['avh_checkboxes']);
				foreach ( $checkboxes as $value ) {
					$value = ltrim ( $value, 'option[' );
					$value = rtrim ( $value, ']' );
					$keys = explode ( '][', $value );
					$this->setOption ( $keys, 0 );
				}
			}
			$formoptions=$_POST['avhamazon'];
			foreach ( $this->options as $key => $value ) {
				foreach ( $value as $key2 => $value2 ) {
					$newval = (isset ( $formoptions[$key][$key2] )) ? stripslashes ( $formoptions[$key][$key2] ) : '0';
					if ( 'nr_of_items' == $key2 ) {
						if ( ! is_numeric ( $formoptions[$key][$key2] ) ) {
							$newval = 1;
						}
					}
					if ( $newval != $value2 ) {
						$this->setOption ( array ( $key, $key2 ), $newval );
					}
				}
			}
			$this->saveOptions();
			$this->message = 'Options saved';
			$this->status = 'updated';
		} elseif ( isset( $_POST['reset_options'] ) ) {
			$this->resetToDefaultOptions();
			$this->message = 'AVH Amazon options resetted to default options!';
		}

		$this->displayMessage();
		?>
<script type="text/javascript">
		jQuery(document).ready( function() {
			jQuery('#printOptions').tabs({fxSlide: true});
		});

  </script>
<div id="wpbody">
<div class="wrap avh_wrap">
<h2><?php _e( 'AVH Amazon: Options', 'avhamazon' );	?></h2>
<form
	action="<?php	echo $this->admin_base_url . 'avhamazon_options'; ?>"
	method="post">
<p><input type="submit" name="updateoptions"
	value="<?php _e( 'Update Options &raquo;', 'avhamazon' ); ?>" /> <input
	type="submit" name="reset_options"
	onclick="return confirm('<?php _e( 'Do you really want to restore the default options?', 'avhamazon' ); ?>');"
	value="<?php _e( 'Reset Options', 'avhamazon' ); ?>" /></p>
<div id="printOptions">
<ul class="avhamazon_submenu">
			<?php
		foreach ( $option_data as $key => $value ) {
			echo '<li><a href="#' . sanitize_title( $key ) . '">' . $this->getNiceTitleOptions( $key ) . '</a></li>';
		}
		?>
			</ul>

		<?php
		echo $this->printOptions( $option_data );
		?>
				</div>

<p class="submit"><input type="submit" name="updateoptions"
	value="<?php
		_e( 'Update Options &raquo;', 'avhamazon' );
		?>" /> <input type="submit" name="reset_options"
	onclick="return confirm('<?php
		_e( 'Do you really want to restore the default options?', 'avhamazon' );
		?>');"
	value="<?php
		_e( 'Reset Options', 'avhamazon' );
		?>" /></p>
</form>
		<?php
		$this->printAdminFooter();
		?>
		</div>
</div>

<?php
	}

	/**
	 * Add initial avh-amazon options in DB
	 *
	 */
	function installPlugin() {

		$options_from_table = get_option( $this->db_options );
		if ( ! $options_from_table ) {
			$this->resetToDefaultOptions();
		}

		$this->wsdlcachefolder = $this->info['install_dir'] . '/cache/';
		if ( ! is_dir( $this->wsdlcachefolder ) ) {
			$this->message = "Can't find the cache folder. This plugin will not work unless " . $this->wsdlcachefolder . "is present and writeable";
			$this->displayMessage();
			$this->removePlugin( trim( $_GET['plugin'] ) );

		} else {
			$cache = new nusoap_wsdlcache( $this->wsdlcachefolder, 0 ); // Cache it indefinitely
			$this->wsdl = $cache->get( $this->wsdlurl );
			if ( is_null( $this->wsdl ) ) {
				$this->wsdl = new wsdl( $this->wsdlurl );
				$cache->put( $this->wsdl );
			} else {
				$this->wsdl->debug_str = '';
				$this->wsdl->debug( 'Retrieved from cache' );
			}
		}
	}

	############## WP Options ##############
	/**
	 * Removes the plugin, old style of doing it.
	 *
	 * @param string $plugin
	 */
	function removePlugin($plugin) {
		$current = get_option( 'active_plugins' );
		array_splice( $current, array_search( $plugin, $current ), 1 ); // Array-fu!
		update_option( 'active_plugins', $current );
	}

	/**
	 * Update an option value  -- note that this will NOT save the options.
	 *
	 * @param array $optkeys
	 * @param string $optval
	 */
	function setOption($optkeys, $optval) {
		$key1=$optkeys[0];
		$key2=$optkeys[1];
		$this->options[$key1][$key2] = $optval;
	}

	/**
	 * Save all current options
	 *
	 */
	function saveOptions() {
		update_option( $this->db_options, $this->options );
		wp_cache_flush(); // Delete cache
	}

	/**
	 * Reset to default options
	 *
	 */
	function resetToDefaultOptions() {
		update_option( $this->db_options, $this->default_options );
		$this->options = $this->default_options;
		wp_cache_flush(); // Delete cache
	}

	/**
	 * Delete all options from DB.
	 *
	 */
	function deleteAllOptions() {
		delete_option( $this->db_options, $this->default_options );
		wp_cache_flush(); // Delete cache
	}

	############## Admin WP Helper ##############
	/**
	 * Display plugin Copyright
	 *
	 */
	function printAdminFooter() {
		?>
<p class="footer_avhamazon"><?php
		printf( __( '&copy; Copyright 2008 <a href="http://blog.avirtualhome.com/" title="My Thoughts">Peter van der Does</a> | AVH Amazon Version %s', 'avhamazon' ), $this->version );
		?></p>
<?php
	}

	/**
	 * Display WP alert
	 *
	 */
	function displayMessage() {
		if ( $this->message != '' ) {
			$message = $this->message;
			$status = $this->status;
			$this->message = $this->status = ''; // Reset
		}

		if ( $message ) {
			?>
<div id="message"
	class="<?php
			echo ($status != '') ? $status : 'updated';
			?> fade">
<p><strong><?php
			echo $message;
			?></strong></p>
</div>
<?php
		}
	}

	/**
	 * Print link to CSS
	 *
	 */
	function helperCSS() {
		echo '<link rel="stylesheet" href="' . $this->info['install_url'] . '/inc/avh-amazon.admin.css?ver=' . $this->version . '" type="text/css" />' . "\n";
	}

	/**
	 * Ouput formatted options
	 *
	 * @param array $option_data
	 * @return string
	 */
	function printOptions($option_data) {
		// Get actual options
		$option_actual = ( array ) $this->options;

		// Generate output
		$output = '';
		$checkbox='|';
		foreach ( $option_data as $section => $options ) {
			$output .= "\n" . '<div id="' . sanitize_title( $section ) . '"><fieldset class="options"><legend>' . $this->getNiceTitleOptions( $section ) . '</legend><table class="form-table">' . "\n";
			foreach ( ( array ) $options as $option ) {
				$option_key=rtrim($option[0],']');
				$option_key=substr($option_key,strpos($option_key,'][')+2);

				// Helper
				if ( $option[2] == 'helper' ) {
					$output .= '<tr style="vertical-align: top;"><td class="helper" colspan="2">' . $option[4] . '</td></tr>' . "\n";
					continue;
				}

				switch ( $option[2] ) {
					case 'checkbox' :
						$input_type = '<input type="checkbox" id="' . $option[0] . '" name="' . $option[0] . '" value="' . attribute_escape( $option[3] ) . '" ' . checked('1',$option_actual[$section][$option_key]) . ' />' . "\n";
						$checkbox .= $option[0] . '|' ;
						$explanation = $option[4];
						break;

					case 'dropdown' :
						$selvalue = explode( '/', $option[3] );
						$seltext = explode( '/', $option[4] );
						$seldata = '';
						foreach ( ( array ) $selvalue as $key => $sel ) {
							$seldata .= '<option value="' . $sel . '" ' . (($option_actual[$section][$option_key] == $sel) ? 'selected="selected"' : '') . ' >' . ucfirst( $seltext[$key] ) . '</option>' . "\n";
						}
						$input_type = '<select id="' . $option[0] . '" name="' . $option[0] . '">' . $seldata . '</select>' . "\n";
						$explanation = $option[5];
						break;

					case 'text-color' :
						$input_type = '<input type="text" ' . (($option[3] > 50) ? ' style="width: 95%" ' : '') . 'id="' . $option[0] . '" name="' . $option[0] . '" value="' . attribute_escape( $option_actual[$section][$option_key] ) . '" size="' . $option[3] . '" /><div class="box_color ' . $option[0] . '"></div>' . "\n";
						$explanation = $option[4];
						break;

					case 'text' :
					default :
						$input_type = '<input type="text" ' . (($option[3] > 50) ? ' style="width: 95%" ' : '') . 'id="' . $option[0] . '" name="' . $option[0] . '" value="' . attribute_escape( $option_actual[$section][$option_key] ) . '" size="' . $option[3] . '" />' . "\n";
						$explanation = $option[4];
						break;
				}

				// Additional Information
				$extra = '';
				if ( $explanation ) {
					$extra = '<div class="avhamazon_explain">' . __( $explanation ) . '</div>' . "\n";
				}

				// Output
				$output .= '<tr style="vertical-align: top;"><th scope="row"><label for="' . $option[0] . '">' . __( $option[1] ) . '</label></th><td>' . $input_type . '	' . $extra . '</td></tr>' . "\n";
			}
			$output .= '</table>' . "\n";
			if ('|' !== $checkbox) $checkbox = ltrim($checkbox,'|');
			$output .= '<input	type="hidden" name="avh_checkboxes" value="' . rtrim($checkbox,'|') . '" />';
			$output .= '</fieldset></div>' . "\n";
		}
		return $output;
	}

	/**
	 * Get nice title for tabs title option
	 *
	 * @param string $id
	 * @return string
	 */
	function getNiceTitleOptions($id = '') {
		switch ( $id ) {
			case 'general' :
				return __( 'General', 'avhamazon' );
				break;
			case 'widget_wishlist' :
				return __( 'Wishlist', 'avhamazon' );
				break;
			case 'shortcode' :
				return __( 'Shortcode', 'avhamazon' );
				break;
			case 'faq' :
				return __( 'FAQ', 'avhamazon' );
				break;
			case 'about' :
				return __( 'About', 'avhamazon' );
				break;
		}
		return 'Unknown';
	}
}
?>