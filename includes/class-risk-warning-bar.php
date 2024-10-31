<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Risk_Warning_Bar {

	/**
	 * The single instance of Risk_Warning_Bar.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token = 'risk_warning_bar';

		// Load plugin environment variables
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		add_action('wp_footer', array( $this, 'insert_warning_bar' ));
		add_action( 'add_meta_boxes', array( $this, 'risk_warning_broker_meta_box' ));
		add_action('save_post', array( $this, 'risk_warning_broker_save_metabox' ));

		// Load API for generic admin functions
		if ( is_admin() ) {
			$this->admin = new Risk_Warning_Bar_Admin_API();
		}

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
	} // End __construct ()

	/**
	 * Wrapper function to register a new post type
	 * @param  string $post_type   Post type name
	 * @param  string $plural      Post type item plural name
	 * @param  string $single      Post type item single name
	 * @param  string $description Description of post type
	 * @return object              Post type class object
	 */
	public function register_post_type ( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		$post_type = new Risk_Warning_Bar_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 * @param  string $taxonomy   Taxonomy name
	 * @param  string $plural     Taxonomy single name
	 * @param  string $single     Taxonomy plural name
	 * @param  array  $post_types Post types to which this taxonomy applies
	 * @return object             Taxonomy class object
	 */
	public function register_taxonomy ( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) return;

		$taxonomy = new Risk_Warning_Bar_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
		// wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		// wp_enqueue_script( $this->_token . '-frontend' );
	} // End enqueue_scripts ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
		wp_register_style( $this->_token . '-multi-select', esc_url( $this->assets_url ) . 'css/multi-select.min.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-multi-select' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );
		wp_register_script( $this->_token . '-multi-select', esc_url( $this->assets_url ) . 'js/multi-select' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-multi-select' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'risk-warning-bar', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'risk-warning-bar';

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	public function insert_warning_bar() {
		$country_code = $_SERVER["HTTP_CF_IPCOUNTRY"];
		$show_in_countries = get_option('warning_bar_show_in_countries');

		if ($show_in_countries != null && $show_in_countries != '' && $country_code != null && $country_code != '' && $country_code != 'T1' && $country_code != 'XX' && !in_array($country_code, $show_in_countries)) {
			return;
		}

		$text = $this->getPageWarningMessage();

		$opacity = get_option('warning_bar_background_opacity');
		$background = get_option('warning_bar_background_color');
		$color = get_option('warning_bar_text_color');
		$text_size = get_option('warning_bar_text_size');

		$style = '';
		if($opacity) $style .= 'opacity: ' . $opacity . ';';
		if($background) $style .= 'background-color: ' . $background . ';';
		if($color) $style .= 'color: ' . $color . ';';
		if($text_size) $style .= 'font-size: ' . $text_size . 'px;';

		$close_warning = '';
		if(get_option('warning_bar_allow_minimise_bar') === 'on')
			$close_warning = '<div id="risk_warning_close" class="risk_warning_close" onclick="document.getElementById(\'risk_warning_bar\').style.display = \'none\';">&#10008;</div>';

		$custom_css = null;
		$custom_css = get_option('warning_bar_custom_css');
		if($custom_css) echo '<style>'.$custom_css.'</style>';

		echo '
			<div id="risk_warning_bar" class="risk_warning_bar" style="'.$style.'">
				' . $close_warning . '
				<div id="risk_warning_message" class="risk_warning_message">' . $text . '</div>
			</div>
		';
	}

	public function getPageWarningMessage () {
		global $post;
		$selected_broker = get_post_meta($post->ID, 'risk_warning_broker_meta_box', true);
		$this->incrementPageViews($selected_broker);
		$this->fetchWarningsFromAPI();

		$text = "CFDs are complex instruments and come with a high risk of losing money rapidly due to leverage. You should consider whether you understand how CFDs work and whether you can afford to take the high risk of losing your money.";
		
		$new_text = get_option('warning_bar_default_risk_warning');
		if($new_text) $text = $new_text;

		$brokers = json_decode(get_option('warning_bar_warning_messages', []));

		if ($selected_broker != 0) {
			foreach ($brokers as $broker) {
				if ($broker->id == $selected_broker) {
					return $broker->message;
				}
			}
		}

		return $text;
	}

	public function incrementPageViews ($broker) {
		$new_view = [
			"broker" => $broker,
			"path" => $_SERVER['REQUEST_URI'],
			"time" => time()
		];
		$views = get_option('warning_bar_page_views', []);
		array_push($views, $new_view);
		update_option('warning_bar_page_views', $views);
	}

	public function fetchWarningsFromAPI () {
		$last_fetched = get_option('warning_bar_warning_messages_last_fetched');
		$views = get_option('warning_bar_page_views', []);
		
		// Only fetch new data after 10 minutes
		if ($last_fetched == null || time() > $last_fetched + (10 * 60)) {
			$response = wp_remote_post('https://riskwarningbar.com/api' , array(
				'body'        => array(
					'domain' => $_SERVER['HTTP_HOST'],
					'views' => $views
				)
			));

			if ( is_array( $response ) && $response['response']['code'] == '200') {
				update_option('warning_bar_page_views', []);
				update_option('warning_bar_warning_messages', $response['body']);
				update_option('warning_bar_warning_messages_last_fetched', time());
			}
		}
	}

	function risk_warning_broker_meta_box($post){
		add_meta_box('risk_warning_broker_meta_box', 'Risk Warning Bar', 'risk_warning_broker_meta_box', $post->post_type, 'side' , 'high');
	}

	function risk_warning_broker_save_metabox(){ 
		global $post;
		if(isset($_POST["risk_warning_broker"])){
			$meta_element_class = $_POST['risk_warning_broker'];
			update_post_meta($post->ID, 'risk_warning_broker_meta_box', $meta_element_class);
		}
	}

	/**
	 * Main Risk_Warning_Bar Instance
	 *
	 * Ensures only one instance of Risk_Warning_Bar is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Risk_Warning_Bar()
	 * @return Main Risk_Warning_Bar instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
		$this->fetchWarningsFromAPI();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

}
