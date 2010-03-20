<?php
class AVHAmazonAdmin extends AVHAmazonCore
{
	/**
	 * AVHAmazonCore
	 * @var unknown_type
	 */
	var $core;

	/**
	 * Message management
	 *
	 */
	var $message = '';
	var $status = '';

	function __construct ()
	{
		$this->core = & AVHAmazonCore::getInstance();

		// Admin URL and Pagination
		$this->core->admin_base_url = $this->core->info['siteurl'] . '/wp-admin/admin.php?page=';
		if ( isset( $_GET['pagination'] ) ) {
			$this->core->actual_page = ( int ) $_GET['pagination'];
		}

		// Admin Capabilities
		add_action( 'init', array (&$this, 'actionInitRoles' ) );

		// Admin menu
		add_action( 'admin_menu', array (&$this, 'actionAdminMenu' ) );

		// CSS Helper
		add_action( 'admin_print_styles', array (&$this, 'actionInjectCSS' ) );
		add_filter( 'admin_enqueue_scripts', array (&$this, 'filterInjectJS' ) );

		// Admin notice A AWS developer key is necessary
		if ( empty( $this->core->options['general']['awssecretkey'] ) ) {
			add_action( 'admin_notices', array (&$this, 'actionNotice' ) );
		}
		return;
	}

	/**
	 * PHP4 Constructor - Intialize Admin
	 *
	 * @return
	 */
	function AVHAmazonAdmin ()
	{
		$this->__construct();
	}

	/**
	 * Setup Roles
	 *
	 * @WordPress Action init
	 */
	function actionInitRoles ()
	{
		if ( function_exists( 'get_role' ) ) {
			$role = get_role( 'administrator' );
			if ( $role != null && ! $role->has_cap( 'avh_amazon' ) ) {
				$role->add_cap( 'avh_amazon' );
			}
			if ( $role != null && ! $role->has_cap( 'admin_avh_amazon' ) ) {
				$role->add_cap( 'admin_avh_amazon' );
			}
			// Clean var
			unset( $role );

			$role = get_role( 'editor' );
			if ( $role != null && ! $role->has_cap( 'avh_amazon' ) ) {
				$role->add_cap( 'avh_amazon' );
			}
			// Clean var
			unset( $role );
		}
	}

	/**
	 * Add the Tools and Options to the Management and Options page repectively
	 *
	 * @WordPress Action admin_menu
	 *
	 */
	function actionAdminMenu ()
	{
		add_management_page( __( 'AVH Amazon Tools', 'avhamazon' ), __( 'AVH Amazon Tools', 'avhamazon' ), 'avh_amazon', 'avhamazon_tools', array (&$this, 'pageAVHAmazonTools' ) );
		add_options_page( __( 'AVH Amazon: Options', 'avhamazon' ), 'AVH Amazon', 'avh_amazon', 'avhamazon_options', array (&$this, 'pageOptions' ) );
		add_filter( 'plugin_action_links_avh-amazon/avh-amazon.php', array (&$this, 'filterPluginActions' ), 10, 2 );
	}

	/**
	 * Enqueue CSS
	 *
	 * @WordPress Action admin_print_styles
	 * @since 3.0
	 *
	 */
	function actionInjectCSS ()
	{
		wp_enqueue_style( 'avhamazonadmin', $this->core->info['plugin_url'] . '/inc/avh-amazon.admin.css', array (), $this->core->version, 'screen' );
	}

	/**
	 * Add the javascript to the admin pages.
	 *
	 * @WordPress Action admin_enqueue_scripts
	 * @since 3.0
	 *
	 */
	function filterInjectJS ( $hook_suffix )
	{

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.dev' : '';
		$admin_pages = array ('settings_page_avhamazon_options' );
		if ( in_array( $hook_suffix, $admin_pages ) ) {
			wp_enqueue_script( 'avhamazonsettings', $this->core->info['plugin_url'] . '/inc/js/admin' . $suffix . '.js', array ('jquery' ), $this->core->version, true );
		}
	}

	/**
	 * Notice to get an AWS account
	 *
	 * @WordPress Action admin_notices
	 * @since 3.0
	 *
	 */
	function actionNotice ()
	{
		$options = get_option( $this->core->db_options_name_core );

		if ( ! wp_verify_nonce( $options['general']['policychange'], 'AmazonPolicyChange' ) ) { //Use nonce for daily check. If older as 24 hours, display the message again.
			$options['general']['policychange'] = wp_create_nonce( 'AmazonPolicyChange' );
			update_option( $this->core->db_options_name_core, $options );
			$show = true;
		}
		$avhamazon_pages = array ('avhamazon_options', 'avhamazon_tools' );
		if ( in_array( $_GET['page'], $avhamazon_pages ) ) {
			$show = true;
		}

		if ( $show ) {
			$this->message = 'AVH Amazon Plugin Notice<br />';
			$this->message .= 'Amazon has changed their policy and per August 15, 2009 all calls to Amazon are going to have to be signed.<br />';
			$this->message .= 'You will need your own personal AWS account (See the FAQ of the plugin for details on how to get one).<br/>The account gives you access to your personal secret key which is needed for calls to Amazon. When you have your account please enter both keys in the settings page of the AVH Amazon plugin.<br/>';
			$this->message .= 'The AVH Amazon plugin will not work correctly if you don\'t enter your personal secret key';
			$this->displayMessage();
		}
	}

	/**
	 * Adds Settings next to the plugin actions
	 *
	 * @WordPress Filter plugin_action_links_avh-amazon/avh-amazon.php
	 *
	 */
	function filterPluginActions ( $links, $file )
	{
		static $this_plugin;

		if ( ! $this_plugin )
			$this_plugin = $this->core->getBaseDirectory( plugin_basename( $this->core->info['plugin_dir'] ) );
		if ( $file )
			$file = $this->core->getBaseDirectory( $file );
		if ( $file == $this_plugin ) {
			$settings_link = '<a href="options-general.php?page=avhamazon_options">' . __( 'Settings', 'avhamazon' ) . '</a>';
			array_unshift( $links, $settings_link ); // before other links
		//$links = array_merge ( array (	$settings_link ), $links ); // before other links
		}
		return $links;

	}

	/**
	 * WP Page Manage - AVH Amazon Find Wishlist ID
	 *
	 */

	function pageAVHAmazonTools ()
	{
		$email = '';
		$locale = '';

		if ( isset( $_POST['action'] ) ) {
			check_admin_referer( 'avhamazon-tools' );
			$action = esc_attr( $_POST['action'] );
			$email = esc_attr( $_POST['email'] );
			$locale = esc_attr( $_POST['locale'] );
		}

		// Locale Table
		$locale_table = $this->core->locale_table;

		echo '<div class="wrap">';
		echo '<h2>';
		_e( 'AVH Amazon: Tools', 'avhamazon' );
		echo '</h2>';
		echo '<h3>Find Wish List ID</h3>';
		echo '<form id="findid" action=' . $this->core->getBackLink() . ' method="post">';
		wp_nonce_field( 'avhamazon-tools' );
		echo '<table class="form-table"><tbody><tr><td>';
		echo '<p>Select the Amazon locale.</p>';
		echo '<p><select id="locale" name="locale" />';
		$seldata = '';
		foreach ( $locale_table as $key => $value ) {
			$seldata .= '<option value="' . $key . '"';
			if ( $key == $locale ) {
				$seldata .= ' selected ';
			}
			$seldata .= '>' . $value . '</option>' . "\n";
		}
		echo $seldata;
		echo '</select>';
		echo '<p>Enter the e-mail address used to sign on to Amazon to find the Wish List ID.</p>';
		echo '<p><input name="action" value="findid" type="hidden" />';
		echo '<input id="email" type="text" size="40" name="email" id="email" autocomplete="on" value="' . $email . '"/>';
		echo '<input class="button-secondary" type="submit" value="Search"	name="submitButton" autocomplete="on" /></p>';
		echo '</td></tr>';
		echo '</tbody></table>';
		echo '<div id="avhamazonwishlistoutputsearch">';
		if ( isset( $action ) && 'findid' == $action ) {
			$this->core->amazon_endpoint = $this->core->amazon_endpoint_table[$locale];
			$result = $this->core->handleRESTcall( $this->core->getRestListSearchParams( $email ) );
			$total = $result['Lists']['TotalResults'];

			if ( 0 == $total ) {
				echo '<h3>No wishlists found for ' . $email . '<h3>';
			} elseif ( 1 == $total ) {
				if ( empty( $result['Lists']['List']['DateCreated'] ) ) { // Wishlist is deleted recently, the list entry still excists but the URL is invalid
					echo '<h3>No wishlist found for ' . $email . '</h3>';
				} else {
					echo '<h3>Wishlist found:<br/></h3>';
					$this->toolsTableHead();
					$this->toolsTableRow( $result['Lists']['List'], '' );
					$this->toolsTableFooter();
				}
			} else {
				echo '<h3>Wishlist(s) found:<br /></h3>';
				$this->toolsTableHead();
				$class = '';

				foreach ( $result['Lists']['List'] as $list ) {
					if ( ! empty( $list['DateCreated'] ) ) { // Wishlist isn't deleted.
						$this->toolsTableRow( $list, $class );
						$class = ('alternate' == $class) ? '' : 'alternate';
					}
				}
			}
		}
		echo '</div>';

		echo '</form>';

		echo '<h3>Clear Cache</h3>';
		if ( isset( $action ) && 'clearcache' == $action ) {
			update_option( $this->core->db_options_name_cached_wishlist, array() );
			update_option($this->core->db_options_name_cached_item, array());
		}
		echo '<form id="clearcache" action=' . $this->core->getBackLink() . ' method="post">';
		wp_nonce_field( 'avhamazon-tools' );
		echo '<table class="form-table"><tbody><tr><td>';
		$wishlists_in_cache = count( get_option( $this->core->db_options_name_cached_wishlist ) );
		$items_in_cache = count( get_option( $this->core->db_options_name_cached_item ) );
		echo '<p>Number of Wishlists in cache: ' . $wishlists_in_cache . '</p>';
		echo '<p>Number of Items in cache:     ' . $items_in_cache . '</p>';
		echo '<p><input name="action" value="clearcache" type="hidden" />';
		echo '<input class="button-secondary" type="submit" value="Clear"	name="submitButton" autocomplete="on" /></p>';
		echo '</td></tr>';
		echo '</tbody></table>';
		echo '<div id="avhamazonwishlistoutputclear">';

		echo '</div>';

		echo '</form>';

		$this->printAdminFooter();
		echo '</div>';
	}

	/**
	 * Displays the table head for the tools page
	 *
	 * @since 2.4
	 */
	function toolsTableHead ()
	{
		echo '<table class="widefat"><thead><tr><th style="text-align: center;" scope="col">ID</th><th scope="col">Name</th><th scope="col">URL</th></th></thead><tbody>';
	}

	/**
	 * Display the table footer for the tools page
	 *
	 * @since 2.4
	 */
	function toolsTableFooter ()
	{

		echo '</tbody></table>';
	}

	/**
	 * Display the table row for the tools page
	 *
	 * @param array $list
	 * @param string $class
	 * @since 2.4
	 */
	function toolsTableRow ( $list, $class )
	{
		echo '<tr class="' . $class . '"><th style="text-align: center;" scope="row">' . $list['ListId'] . '</th><td>' . $list['ListName'] . '</td><td><a href="' . $list['ListURL'] . '"  target="_blank">' . $list['ListURL'] . '</td></tr>';
	}

	/**
	 * WP Page Options- AVH Amazon options
	 *
	 */
	function pageOptions ()
	{

		$option_data = array (
			'general' => array (
				array (
					'avhamazon[general][associated_id]',
					'Associated ID:',
					'text',
					16,
					'Use this Associated ID when clicking on the wishlist.' ),
				array (
					'avhamazon[general][awskey]',
					'AWS Key:',
					'text',
					20,
					'Your Amazon Web Services Access Key ID.' ),
				array (
					'avhamazon[general][awssecretkey]',
					'AWS Secret Key:',
					'text',
					40,
					'Your Amazon Web Services Secret Access Key.' ) ),
			'widget_wishlist' => array (
				array (
					'avhamazon[widget_wishlist][wishlist_id]',
					'Default wishlist ID:',
					'text',
					16,
					'This is the default wishlist ID, if you don\'t fill out a Wishlist ID in the widget this one will be used.' ),
				array (
					'avhamazon[widget_wishlist][locale]',
					'Locale Amazon:',
					'dropdown',
					'US/CA/DE/UK/FR',  // Locale Value
					'Amazon.com/Amazon.ca/Amazon.de/Amazon.co.uk'/'Amazon.fr' ),  // Locale Text
				array (
					'avhamazon[widget_wishlist][wishlist_imagesize]',
					'Size of thumbnail:',
					'dropdown',
					'Small/Medium/Large',  // Value
					'Small/Medium/Large' ),  // Text
				array (
					'avhamazon[widget_wishlist][nr_of_items]',
					'Number of items:',
					'text',
					3,
					'Amount of items of your Wish List to show in the widget.' ),
				array (
					'avhamazon[widget_wishlist][footer_template]',
					'Footer template:',
					'text',
					30,
					'The display of the footer is controlled in the widget options<BR />%nr_of_items% is replaced by the actual number of items in the wishlist.' ),
				array (
					'avhamazon[widget_wishlist][new_window]',
					'Open in new windows:',
					'checkbox',
					1,
					'When a user clicks on the link open it in a new windows' ) ),
			'shortcode' => array (
				array (
					'avhamazon[shortcode][wishlist_id]',
					'Default wishlist ID:',
					'text',
					16,
					'This value will be automatically be entered in the AVH Amazon Short Code - wishlist metabox.' ),
				array (
					'avhamazon[shortcode][locale]',
					'Locale Amazon:',
					'dropdown',
					'US/CA/DE/UK',  // Locale Value
					'Amazon.com/Amazon.ca/Amazon.de/Amazon.co.uk' ) ),  // Locale Text
			'faq' => array (
				array (
					'text-helper',
					'text-helper',
					'helper',
					'',
					'<strong>Can I use this plugin if I don\'t have a widget enabled theme?</strong><br />'.
					'Yes you can, you can use the following code to display the wishlist:<br />'.
					'<&#63;php $avhwidget=& new WP_Widget_AVHAmazon_Wishlist();$avhwidget->widget(array,1); ?><br />'.
					'array is in the following format array( [ option => value [, option => value ] ])<br />'.
					'Overview of options and values<br />'.
					'<ul><br />'.
					'<li>\'title\' => string</li><br />'.
					'<li>\'ListID\' => string</li><br />'.
					'<li>\'associatedid\' => string</li><br />'.
					'<li>\'imagesize\' => Small/Medium/Large</li><br />'.
					'<li>\'locale\' => US/CA/DE/UK</li><br />'.
					'<li>\'nr_of_items\' => number</li><br />'.
					'<li>\'show_footer\' => 0/1</li><br />'.
					'<li>\'footer_template\' => string (%nr_of_items% is replaced by the actual number of items in the wishlist.)</li><br />'.
					'<li>\'sort_order\' => DateAdded/LastUpdated/Price/Priority</li><br />'.
					'<li>\'randomize\' => 0/1</li><br />'.
					' </ul><br />'.
					'<i>Important</i>:There is no validity check for the values, entering wrong values can lead to unexpected results.<br />'.
					'<br />'.
					'<strong>What are the shortcode options?</strong><br />'.
					'The shortcode is made up as follows:<br />'.
					'[avhamazon <options>]<br />'.
					'The options are:<br />'.
					'<ul><br />'.
					'<li>asin<br />'.
					'	ASIN can have the following values:<br />'.
					'	The ASIN of the item you want to display.<br />'.
					'	<i>all</i> to display all the items from the given wishlist.<br />'.
					'	<i>random</i> to display a random item from thw wishlist.</li><br />'.
					'<li>locale<br />'.
					'	US/CA/DE/UK/FR. If not given it defaults to the locale set up in the administration page.</li><br />'.
					'<li>linktype<br />'.
					'	text		: Only show the text of the item that is retrieved<br />'.
					'	pic		: Only show the picture of the item that is retrieved<br />'.
					'	pic-text	: Show a picture with a caption of the item that is retrieved</li><br />'.
					'<li>wishlist<br />'.
					'	Wishlist ID. Use this in combination with the ASIN values all or random.</li><br />'.
					'<li>picsize<br />'.
					'	The size of the picture. small/medium/large</li><br />'.
					'<li>col<br />'.
					'	Amount of columns to use. Default is 1.</li><br />'.
					'<li>sort_order<br />'.
					'	DateAdded/LastUpdated/Price/Priority</li><br />'.
					'</ul><br />'.
					'All values are case-senitive.<br />'.
					'<br />'.
					'<strong>Where is the Baby/Wedding Registry widget?</strong><br />'.
					'There is no seperate widget for the registries. To show the registry items use the Wishlist widget and use your Baby Registry ID or Wedding Registry ID.<br />'.
					'<br />'.
					'<strong>How do I find my Baby Registry and/or Wedding Registry ID?</strong><br />'.
					'When you create either registry Amazon sends you an email with the direct link to access your registry. The ID is the last part of the URL.<br />'.
					'Example:<br />'.
					'http://www.amazon.com/gp/registry/1234567890ABC<br />'.
					'The ID is 1234567890ABC<br />'.
					'<br />'.
					'<strong>What is an ASIN?</strong><br />'.
					'ASIN stands for Amazon Standard Identification Number.<br />'.
					'Every product has its own ASIN--a unique code they use to identify it. For books, the ASIN is the same as the 10-digit ISBN number.<br />'.
					'You will find an item\'s ASIN on the product detail page.<br />'.
					'<br />'.
					'<strong>Amazon Policy Change per May 11, 2009</strong><br />'.
					'Amazon has decided that calls to Amazon have to signed using a secret key you receive as a developer. Because this key can be used for other purposes as this plugin it is necessary for everybody who uses this plugin to sign up as a developer and receive their secret key.<br />'.
					'You can sign up at the following pages and signing up is free:<br />'.
					'<br />'.
					'Canada https://associates.amazon.ca/gp/flex/advertising/api/sign-in.html<br />'.
					'France https://partenaires.amazon.fr/gp/flex/advertising/api/sign-in.html<br />'.
					'Germany https://partnernet.amazon.de/gp/flex/advertising/api/sign-in.html<br />'.
					'United Kingdom https://affiliate-program.amazon.co.uk/gp/flex/advertising/api/sign-in.html<br />'.
					'United States https://affiliate-program.amazon.com/gp/flex/advertising/api/sign-in.html<br />'.
					'<br />'.
					'After the registration is complete go to this page:<br />'.
					'https://aws-portal.amazon.com/gp/aws/developer/account/index.html?ie=UTF8&action=access-key<br />'.
					'<br />'.
					'And select Access Identifiers You will the ability to see your secret key, if you don\'t see one generate one. Copy your key into the options page of the plugin and you are all set.<br />'.
					'<br />'.
					'If you don\'t get a secret key all calls from this plugin to Amazon will fail per August 15, 2009.<br />'.
					'Until you enter your secret key, you will see a reminder to do this once day in the Admin section WordPress, and all the time when you go to the settings page of this plugin.<br />' ) ),
			'about' => array (
				array (
					'text-helper',
					'text-helper',
					'helper',
					'',
					'<p>The AVH Amazon plugin gives you the ability to add multiple widgets which will display one or more random item(s) from your Amazon wishlist, baby registry and/or wedding registry. It also has the ability to show items with their link, in posts and pages by use of shortcode.<br />' . 'In the plugin reference is made to Wishlist only but you can use your Baby Registry ID or Wedding Registry ID as well.</p>' . '<b>General</b>' . '<ul>' . '<li>Works with amazon.com, and locales amazon.ca, amazon.de and amazon.co.uk.<br /><br />' . '</ul>' . '<b>Wishlist</b>' . '<ul>' . '<li>Add Associated ID.' . '<li>Choice of thumbnail size, Small/Medium/Large.' . '<li>Option to use up to unlimited widgets.' . '<li>Multiple items from the same Wish List can be displayed in the widget.' . '<li>A configurable footer can be displayed on the bottom of the widget linking to the list on Amazon.' . '</ul>' . '<b>Shortcode</b>' . '<ul>' . '<li>Create the shortcode with the help of a metabox' . '<li>In the metabox you can select an item or select to randomize the items from your wishlist or search for an item by ASIN.' . '<li>The shortcode creates text, picture or text & picture links.' . '<li>If a text link or text & picture links is used, the default text is the item description from Amazon but the text of the link can be changed.' . '<li>The value all for the ASIN option will show all items from your wishlist. In combination with a text & picture link type you can create a wishlist page.' . '</ul>' . '<b>Tools</b>' . '<ul>' . '<li>Look up your wishlist ID.' . '</ul>' . '<b>Support</b><br />' . 'For support visit the AVH support forums at <a href="http://forums.avirtualhome.com/">http://forums.avirtualhome.com/</a><br /><br />' . '<b>Developer</b><br />' . 'Peter van der Does' ) ) );

		// Update or reset options
		if ( isset( $_POST['updateoptions'] ) ) {
			$formoptions = $_POST['avhamazon'];
			foreach ( $this->core->options as $key => $value ) {
				foreach ( $value as $key2 => $value2 ) {
					if ( ! ('general' == $key && ('version' == $key2 || 'policychange' == $key2)) ) {
						$newval = (isset( $formoptions[$key][$key2] )) ? esc_attr( $formoptions[$key][$key2] ) : '0';
						if ( 'nr_of_items' == $key2 ) {
							if ( ! is_numeric( $formoptions[$key][$key2] ) ) {
								$newval = 1;
							}
						}
						if ( $newval != $value2 ) {
							$this->setOption( array ($key, $key2 ), $newval );
						}
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

		echo '<script type="text/javascript">';
		echo 'jQuery(document).ready( function() {';
		echo 'jQuery(\'#printOptions\').tabs();';
		echo '});';
		echo '</script>';

		echo '<div class="wrap avh_wrap">';
		echo '<h2>';
		_e( 'AVH Amazon: Options', 'avhamazon' );
		echo '</h2>';
		echo '<form	action="' . $this->core->admin_base_url . 'avhamazon_options' . '"method="post">';
		echo '<div id="printOptions">';
		echo '<ul class="avhamazon_submenu">';
		foreach ( $option_data as $key => $value ) {
			echo '<li><a href="#' . sanitize_title( $key ) . '">' . $this->getNiceTitleOptions( $key ) . '</a></li>';
		}
		echo '</ul>';
		echo $this->printOptions( $option_data );
		echo '</div>';

		echo '<p class="submit"><input	class="button-primary"	type="submit" name="updateoptions" value="' . __( 'Save Changes', 'avhamazon' ) . '" />';
		echo '<input class="button-secondary" type="submit" name="reset_options" onclick="return confirm(\'' . __( 'Do you really want to restore the default options?', 'avhamazon' ) . '\')" value="' . __( 'Reset Options', 'avhamazon' ) . '" /></p>';
		echo '</form>';

		echo '<div id="avhdonations">';
		echo '<p>If you enjoy this plug-in please consider a donation. There are several ways you can show your appreciation</p>';
		echo '<p>';
		echo '<span class="b">Amazon Wish List</span><br />';
		echo 'You can send me something from my <a href="http://www.amazon.com/gp/registry/wishlist/1U3DTWZ72PI7W?tag=avh-donation-20">Amazon Wish List</a>';
		echo '</p>';
		echo '<p>';
		echo '<span class="b">Through Paypal.</span><br />';
		echo 'Click on the Donate button and you will be directed to Paypal where you can make your donation and you don\'t need to have a Paypal account to make a donation.';
		echo '<form action="https://www.paypal.com/cgi-bin/webscr" method="post"> <input name="cmd" type="hidden" value="_donations" /> <input name="business" type="hidden" value="paypal@avirtualhome.com" /> <input name="item_name" type="hidden" value="AVH Plugins" /> <input name="no_shipping" type="hidden" value="1" /> <input name="no_note" type="hidden" value="1" /> <input name="currency_code" type="hidden" value="USD" /> <input name="tax" type="hidden" value="0" /> <input name="lc" type="hidden" value="US" /> <input name="bn" type="hidden" value="PP-DonationsBF" /> <input alt="PayPal - The safer, easier way to pay online!" name="submit" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" type="image" /> </form>';
		echo '</p></div>';

		$this->printAdminFooter();
		echo '</div>';
	}

	/**
	 * Add initial avh-amazon options in DB
	 *
	 */
	function installPlugin ()
	{
		$options_from_table = get_option( $this->core->db_options_name_core );
		if ( ! $options_from_table ) {
			$this->resetToDefaultOptions();
		}

	}

	############## WP Options ##############
	/**
	 * Update an option value  -- note that this will NOT save the options.
	 *
	 * @param array $optkeys
	 * @param string $optval
	 */
	function setOption ( $optkeys, $optval )
	{
		$key1 = $optkeys[0];
		$key2 = $optkeys[1];
		$this->core->options[$key1][$key2] = $optval;
	}

	/**
	 * Save all current options
	 *
	 */
	function saveOptions ()
	{
		update_option( $this->core->db_options_name_core, $this->core->options );
		wp_cache_flush(); // Delete cache
	}

	/**
	 * Reset to default options
	 *
	 */
	function resetToDefaultOptions ()
	{
		update_option( $this->core->db_options_name_core, $this->core->default_options );
		$this->core->options = $this->core->default_options;
		wp_cache_flush(); // Delete cache
	}

	/**
	 * Delete all options from DB.
	 *
	 */
	function deleteAllOptions ()
	{
		delete_option( $this->core->db_options_name_core, $this->core->default_options );
		wp_cache_flush(); // Delete cache
	}

	############## Admin WP Helper ##############
	/**
	 * Display plugin Copyright
	 *
	 */
	function printAdminFooter ()
	{
		echo '<p class="footer_avhamazon">';
		printf( __( '&copy; Copyright 2009 <a href="http://blog.avirtualhome.com/" title="My Thoughts">Peter van der Does</a> | AVH Amazon Version %s', 'avhamazon' ), $this->core->version );
		echo '</p>';
	}

	/**
	 * Display WP alert
	 *
	 */
	function displayMessage ()
	{
		if ( $this->message != '' ) {
			$message = $this->message;
			$status = $this->status;
			$this->message = $this->status = ''; // Reset
		}

		if ( $message ) {
			$status = ($status != '') ? $status : 'updated';
			echo '<div id="message"	class="' . $status . ' fade">';
			echo '<p><strong>' . $message . '</strong></p></div>';
		}
	}

	/**
	 * Ouput formatted options
	 *
	 * @param array $option_data
	 * @return string
	 */
	function printOptions ( $option_data )
	{
		// Get actual options
		$option_actual = ( array ) $this->core->options;

		// Generate output
		$output = '';
		$checkbox = '|';
		foreach ( $option_data as $section => $options ) {
			$output .= "\n" . '<div id="' . sanitize_title( $section ) . '"><fieldset class="options"><legend>' . $this->getNiceTitleOptions( $section ) . '</legend><table class="form-table">' . "\n";
			foreach ( ( array ) $options as $option ) {
				$option_key = rtrim( $option[0], ']' );
				$option_key = substr( $option_key, strpos( $option_key, '][' ) + 2 );

				// Helper
				if ( $option[2] == 'helper' ) {
					$output .= '<tr style="vertical-align: top;"><td class="helper" colspan="2">' . $option[4] . '</td></tr>' . "\n";
					continue;
				}

				switch ( $option[2] )
				{
					case 'checkbox' :
						$input_type = '<input type="checkbox" id="' . $option[0] . '" name="' . $option[0] . '" value="' . esc_attr( $option[3] ) . '" ' . $this->core->isChecked( '1', $option_actual[$section][$option_key] ) . ' />' . "\n";
						$checkbox .= $option[0] . '|';
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
						$input_type = '<input type="text" ' . (($option[3] > 50) ? ' style="width: 95%" ' : '') . 'id="' . $option[0] . '" name="' . $option[0] . '" value="' . esc_attr( $option_actual[$section][$option_key] ) . '" size="' . $option[3] . '" /><div class="box_color ' . $option[0] . '"></div>' . "\n";
						$explanation = $option[4];
						break;

					case 'text' :
					default :
						$input_type = '<input type="text" ' . (($option[3] > 50) ? ' style="width: 95%" ' : '') . 'id="' . $option[0] . '" name="' . $option[0] . '" value="' . esc_attr( $option_actual[$section][$option_key] ) . '" size="' . $option[3] . '" />' . "\n";
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
			if ( '|' !== $checkbox ) {
				$checkbox = ltrim( $checkbox, '|' );
				$output .= '<input	type="hidden" name="avh_checkboxes" value="' . rtrim( $checkbox, '|' ) . '" />';
			}
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
	function getNiceTitleOptions ( $id = '' )
	{
		switch ( $id )
		{
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