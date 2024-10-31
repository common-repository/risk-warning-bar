<?php
/*
 * Plugin Name: Risk Warning Bar
 * Version: 1.0
 * Plugin URI: http://www.aliazlan.com
 * Description: Risk Warning Bar for Broker Affiliates
 * Author: Ali Azlan
 * Author URI: http://www.aliazlan.com
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: risk-warning-bar
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Ali Azlan
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-risk-warning-bar.php' );
require_once( 'includes/class-risk-warning-bar-settings.php' );

// Load plugin libraries
require_once( 'includes/lib/class-risk-warning-bar-admin-api.php' );
require_once( 'includes/lib/class-risk-warning-bar-post-type.php' );
require_once( 'includes/lib/class-risk-warning-bar-taxonomy.php' );

function risk_warning_broker_meta_box($post){
	$selected_broker = get_post_meta($post->ID, 'risk_warning_broker_meta_box', true);
	$brokers = json_decode(get_option('warning_bar_warning_messages', []));
	?>   
	<label>Risk Warning: </label>

	<select name="risk_warning_broker" id="risk_warning_broker">
		<option value="0" <?php selected( $selected_broker, '0' ); ?>>Default</option>
		
		<?php foreach ($brokers as $broker) { ?>
			<option value="<?php echo $broker->id; ?>" <?php selected( $selected_broker, $broker->id ); ?>><?php echo $broker->name; ?></option>
		<?php } ?>
	</select>

	<?php
}

/**
 * Returns the main instance of Risk_Warning_Bar to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Risk_Warning_Bar
 */
function Risk_Warning_Bar () {
	$instance = Risk_Warning_Bar::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Risk_Warning_Bar_Settings::instance( $instance );
	}

	return $instance;
}

Risk_Warning_Bar();
