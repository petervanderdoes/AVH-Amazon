<?php
class AVHAmazonAdmin {

	var $version;
	var $info;
	var $options;
	var $default_options;
	var $db_options;
	var $locale_table;
	var $associate_table;
	/**
	 * Message management
	 *
	 */
	var $message = '';
	var $status = '';

	/**
	 * PHP4 Constructor - Intialize Admin
	 *
	 * @return
	 */
	function AVHAmazonAdmin($default_options = array(), $version = '', $info = array(), $locale_table = array()) {

		// 1. load version number
		$this->version = $version;
		unset( $version );

		// 2. Set class property for default options
		$this->default_options = $default_options;

		// 3. Get options from WP
		$this->db_options = 'avhamazon';
		$options_from_table = get_option( $this->db_options );

		// 4. Update default options by getting not empty values from options table
		foreach ( ( array ) $default_options as $default_options_name => $default_options_value ) {
			if ( ! is_null( $options_from_table[$default_options_name] ) ) {
				if ( is_int( $default_options_value ) ) {
					$default_options[$default_options_name] = ( int ) $options_from_table[$default_options_name];
				} else {
					$default_options[$default_options_name] = $options_from_table[$default_options_name];
					if ('associated_id' == $default_options_name) {
						if ('blogavirtualh-20' == $options_from_table[$default_options_name] ) $default_options[$default_options_name] = 'avh-amazon-20';
					}
				}
			}
		}

		// 5. Set the class property and unset no used variable
		$this->options = $default_options;
		unset( $default_options );
		unset( $options_from_table );
		unset( $default_options_value );

		// 6. Get info data from constructor
		$this->info = $info;
		unset( $info );

		// Get Locale Table
		$this->locale_table=$locale_table;

		// 8. Admin URL and Pagination
		$this->admin_base_url = $this->info['siteurl'] . '/wp-admin/admin.php?page=';
		if ( isset( $_GET['pagination'] ) ) {
			$this->actual_page = ( int ) $_GET['pagination'];
		}

		// 9. Admin Capabilities
		add_action('init', array(&$this, 'initRoles'));

		// 10. Admin menu
		add_action( 'admin_menu', array ( &$this, 'adminMenu') );

		// 12. CSS Helper
		add_action( 'admin_head', array ( &$this, 'helperCSS') );

		// 17. Helper JS & jQuery & Prototype
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
			'general'=>array (
				array (
					'associated_id',
					'Associated ID:',
					'text',
					16,
					'Use this Associated ID when clicking on the wishlist')),
			'wishlist'=>array (
				array (
					'wishlist_id',
					'Default wishlist ID:',
					'text',
					16,
					'This is the default wishlist ID, if you don\'t fill out a Wishlist ID in the widget this one will be used.'),
				array (
					'locale',
					'Locale Amazon:',
					'dropdown',
					'US/CA/DE/UK', // Locale Value
					'Amazon.com/Amazon.ca/Amazon.de/Amazon.co.uk'), // Locale Text
				array (
					'wishlist_imagesize',
					'Size of thumbnail:',
					'dropdown',
					'Small/Medium/Large', // Value
					'Small/Medium/Large'), // Text
				array (
					'nr_of_items',
					'Number of items:',
					'text',
					3,
					'Amount of items of your Wish List to show in the widget.'),
				array (
					'footer_template',
					'Footer template:',
					'text',
					30,
					'The display of the footer is controlled in the widget options<BR />%nr_of_items% is replaced by the actual number of items in the wishlist.'
					)
			),
			'faq'=>array(
				array (
					'text-helper',
					'text-helper',
					'helper',
					'',
					'<b>Where is the Baby/Wedding Registry widget?</b><br />'.
					'There is no seperate widget for the registries. To show the registry items use the Wishlist widget and use your Baby Registry ID or Wedding Registry ID.<br /><br />'.
					'<b>How do I find my Baby Registry and/or Wedding Registry ID?</b><br />'.
					'When you create either registry Amazon sends you an email with the direct link to access your registry. The ID is the last part of the URL.<br />' .
					'Example:<br />'.
					'http://www.amazon.com/gp/registry/1234567890ABC<br/>'.
					'The ID is 1234567890ABC<br /><br />'.
					'<b>What is an ASIN?</b><br />'.
					'ASIN stands for Amazon Standard Identification Number.'.
					'Every product has its own ASIN--a unique code they use to identify it. For books, the ASIN is the same as the 10-digit ISBN number.<br />'.
					'You will find an item\'s ASIN on the product detail page.<br /><br />')),
			'about'=>array (
				array (
					'text-helper',
					'text-helper',
					'helper',
					'',
					'<p>The AVH Amazon plugin gives you the ability to add multiple widgets which will display one or more random item(s) from your Amazon wishlist, baby registry and/or wedding registry.'.
					'It also has the ability to show itemsmwith their link, in posts and pages by use of shortcode.<br />'.
					'In the plugin reference is made to Wishlist only but you can use your Baby Registry ID or Wedding Registry ID as well.</p>'.
					'<b>General</b>'.
					'<ul>'.
					'<li>Works with amazon.com, and locales amazon.ca, amazon.de and amazon.co.uk.<br /><br />'.
					'</ul>'.
					'<b>Wishlist</b>'.
					'<ul>'.
					'<li>Add Associated ID.'.
					'<li>Choice of thumbnail size, Small/Medium/Large.'.
					'<li>Option to use up to 9 widgets.'.
					'<li>Multiple items from the same Wish List can be displayed in the widget.'.
					'<li>A configurable footer can be displayed on the bottom of the widget linking to the list on Amazon.'.
					'</ul>'.
					'<b>Shortcode</b>'.
					'<ul>'.
					'<li>Create the shortcode with the help of a metabox'.
					'<li>In the metabox you can select an item or select to randomize the items from your wishlist or search for an item by ASIN.'.
					'<li>The shortcode creates text or picture links.'.
					'<li>If a text link is used, the default text is the item description from Amazon but the text of the link can be changed.'.
					'</ul>'.
					'<b>Tools</b>'.
					'<ul>'.
					'<li>Look up your wishlist ID.'.
					'</ul>'.
					'<b>Support</b><br />'.
					'For support visit the AVH support forums at <a href="http://forums.avirtualhome.com/">http://forums.avirtualhome.com/</a><br /><br />'.
					'<b>Developer</b><br />'.
					'Peter van der Does')));

		// Update or reset options
		if ( isset( $_POST['updateoptions'] ) ) {
		// Set all checkboxes unset
			if (isset ($_POST['avh_checkboxes'])) {
				$checkboxes = explode('|', $_POST['avh_checkboxes']);
				foreach ( $checkboxes as $value) {
					$this->setOption ( $value, 0);
				}
			}
			foreach ( ( array ) $this->options as $key => $value ) {
				$newval = (isset( $_POST[$key] )) ? stripslashes( $_POST[$key] ) : '0';
				if ('nr_of_items' == $key) {
					if (!is_numeric($_POST[$key])) {
						$newval = 1;
					}
				}
				$skipped_options = array (
					'use_auto_tags',
					'auto_list');
				if ( $newval != $value && ! in_array( $key, $skipped_options ) ) {
					$this->setOption( $key, $newval );
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
<div id="wpbody"><div class="wrap avh_wrap">
<h2><?php _e( 'AVH Amazon: Options', 'avhamazon' );	?></h2>
<form action="<?php	echo $this->admin_base_url . 'avhamazon_options'; ?>" method="post">
<p>
<input type="submit" name="updateoptions" value="<?php _e( 'Update Options &raquo;', 'avhamazon' ); ?>" />
<input type="submit" name="reset_options" onclick="return confirm('<?php _e( 'Do you really want to restore the default options?', 'avhamazon' ); ?>');" value="<?php _e( 'Reset Options', 'avhamazon' ); ?>" />
</p>
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
		</div></div>

<?php
	}

	/**
	 * Add initial avh-amazon options in DB
	 *
	 */
	function installPlugin() {
		global $avhamazon;
		$options_from_table = get_option( $this->db_options );
		if ( ! $options_from_table ) {
			$this->resetToDefaultOptions();
		}

		$avhamazon->wsdlcachefolder = $this->info['install_dir'] . '/cache/';
		if ( ! is_dir( $avhamazon->wsdlcachefolder ) ) {
			$this->message = "Can't find the cache folder. This plugin will not work unless " . $avhamazon->wsdlcachefolder . "is present and writeable";
			$this->displayMessage();
			$this->removePlugin( trim( $_GET['plugin'] ) );

		} else {
			$cache = new wsdlcache( $avhamazon->wsdlcachefolder, 0 ); // Cache it indefinitely
			$avhamazon->wsdl = $cache->get( $avhamazon->wsdlurl );
			if ( is_null( $avhamazon->wsdl ) ) {
				$avhamazon->wsdl = new wsdl( $avhamazon->wsdlurl );
				$cache->put( $avhamazon->wsdl );
			} else {
				$avhamazon->wsdl->debug_str = '';
				$avhamazon->wsdl->debug( 'Retrieved from cache' );
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
	 * @param string $optname
	 * @param string $optval
	 */
	function setOption($optname, $optval) {
		$this->options[$optname] = $optval;
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
				// Helper
				if ( $option[2] == 'helper' ) {
					$output .= '<tr style="vertical-align: top;"><td class="helper" colspan="2">' . $option[4] . '</td></tr>' . "\n";
					continue;
				}

				switch ( $option[2] ) {
					case 'checkbox' :
						$input_type = '<input type="checkbox" id="' . $option[0] . '" name="' . $option[0] . '" value="' . attribute_escape( $option[3] ) . '" ' . checked('1',$option_actual[$option[0]]) . ' />' . "\n";
						$checkbox .= $option[0] . '|' ;
						$explanation = $option[4];
						break;

					case 'dropdown' :
						$selvalue = explode( '/', $option[3] );
						$seltext = explode( '/', $option[4] );
						$seldata = '';
						foreach ( ( array ) $selvalue as $key => $sel ) {
							$seldata .= '<option value="' . $sel . '" ' . (($option_actual[$option[0]] == $sel) ? 'selected="selected"' : '') . ' >' . ucfirst( $seltext[$key] ) . '</option>' . "\n";
						}
						$input_type = '<select id="' . $option[0] . '" name="' . $option[0] . '">' . $seldata . '</select>' . "\n";
						$explanation = $option[5];
						break;

					case 'text-color' :
						$input_type = '<input type="text" ' . (($option[3] > 50) ? ' style="width: 95%" ' : '') . 'id="' . $option[0] . '" name="' . $option[0] . '" value="' . attribute_escape( $option_actual[$option[0]] ) . '" size="' . $option[3] . '" /><div class="box_color ' . $option[0] . '"></div>' . "\n";
						$explanation = $option[4];
						break;

					case 'text' :
					default :
						$input_type = '<input type="text" ' . (($option[3] > 50) ? ' style="width: 95%" ' : '') . 'id="' . $option[0] . '" name="' . $option[0] . '" value="' . attribute_escape( $option_actual[$option[0]] ) . '" size="' . $option[3] . '" />' . "\n";
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
			case 'wishlist' :
				return __( 'Wishlist', 'avhamazon' );
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