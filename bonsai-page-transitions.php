<?php
/**
 * Plugin Name: Bonsai Page Transitions
 * Plugin URI:  https://bonsaidigitalcollective.co.uk/
 * Description: Plays a full-screen wipe animation (fade / slide up / curtain) whenever a visitor clicks a link to another page on the site. Real page loads underneath — no AJAX content-swap.
 * Version:     1.0.2
 * Author:      The Bonsai Digital Collective
 * Author URI:  https://bonsaidigitalcollective.co.uk/
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Text Domain: bonsai-page-transitions
 */

defined( 'ABSPATH' ) || exit;

/*
|--------------------------------------------------------------------------
| Plugin Update Checker (via Composer)
|--------------------------------------------------------------------------
*/
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$bpt_update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/gakdesign/bonsai-page-transitions',
	__FILE__,
	'bonsai-page-transitions',
	6
);

$bpt_update_checker->setBranch( 'main' );
$bpt_update_checker->getVcsApi()->enableReleaseAssets();

define( 'BPT_VERSION', '1.0.2' );
define( 'BPT_PLUGIN_FILE', __FILE__ );
define( 'BPT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BPT_OPTION_GROUP', 'bpt_settings_group' );
define( 'BPT_PAGE_SLUG', 'bonsai-page-transitions' );
define( 'BPT_CAPABILITY', apply_filters( 'bonsai_page_transitions_capability', 'manage_options' ) );

// ---------------------------------------------------------------------------
// Settings registration
// ---------------------------------------------------------------------------

add_action( 'admin_init', 'bpt_register_settings' );
function bpt_register_settings() {
	register_setting(
		BPT_OPTION_GROUP,
		'bpt_transition_style',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'bpt_sanitize_style',
			'default'           => 'none',
		)
	);

	register_setting(
		BPT_OPTION_GROUP,
		'bpt_overlay_colour',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'bpt_sanitize_colour',
			'default'           => '#111111',
		)
	);

	register_setting(
		BPT_OPTION_GROUP,
		'bpt_skip_homepage',
		array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => true,
		)
	);

	add_settings_section(
		'bpt_main_section',
		'',
		'__return_false',
		BPT_PAGE_SLUG
	);

	add_settings_field(
		'bpt_transition_style',
		__( 'Transition Style', 'bonsai-page-transitions' ),
		'bpt_render_style_field',
		BPT_PAGE_SLUG,
		'bpt_main_section'
	);

	add_settings_field(
		'bpt_overlay_colour',
		__( 'Overlay Colour', 'bonsai-page-transitions' ),
		'bpt_render_colour_field',
		BPT_PAGE_SLUG,
		'bpt_main_section'
	);

	add_settings_field(
		'bpt_skip_homepage',
		__( 'Skip Homepage', 'bonsai-page-transitions' ),
		'bpt_render_skip_homepage_field',
		BPT_PAGE_SLUG,
		'bpt_main_section'
	);
}

function bpt_get_styles() {
	return array(
		'none'     => __( 'None (disabled)', 'bonsai-page-transitions' ),
		'fade'     => __( 'Fade', 'bonsai-page-transitions' ),
		'slide-up' => __( 'Slide Up', 'bonsai-page-transitions' ),
		'curtain'  => __( 'Curtain', 'bonsai-page-transitions' ),
	);
}

function bpt_sanitize_style( $value ) {
	$styles = bpt_get_styles();
	return array_key_exists( $value, $styles ) ? $value : 'none';
}

function bpt_sanitize_colour( $value ) {
	$value = sanitize_text_field( $value );
	return sanitize_hex_color( $value ) ? $value : '#111111';
}

// ---------------------------------------------------------------------------
// Admin menu + page
// ---------------------------------------------------------------------------

add_action( 'admin_menu', 'bpt_add_settings_page' );
function bpt_add_settings_page() {
	add_options_page(
		__( 'Bonsai Page Transitions', 'bonsai-page-transitions' ),
		__( 'Page Transitions', 'bonsai-page-transitions' ),
		BPT_CAPABILITY,
		BPT_PAGE_SLUG,
		'bpt_render_settings_page'
	);
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bpt_add_settings_link' );
function bpt_add_settings_link( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=' . BPT_PAGE_SLUG ) ) . '">' . esc_html__( 'Settings', 'bonsai-page-transitions' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

function bpt_render_style_field() {
	$value = get_option( 'bpt_transition_style', 'none' );
	?>
	<select name="bpt_transition_style" id="bpt_transition_style">
		<?php foreach ( bpt_get_styles() as $key => $label ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select>
	<p class="description">
		<?php esc_html_e( 'Plays whenever a visitor clicks a link to another page on this site. Set to "None" to turn transitions off completely.', 'bonsai-page-transitions' ); ?>
	</p>
	<?php
}

function bpt_render_colour_field() {
	$value = get_option( 'bpt_overlay_colour', '#111111' );
	?>
	<input type="text" class="bpt-colour-picker" name="bpt_overlay_colour" id="bpt_overlay_colour" value="<?php echo esc_attr( $value ); ?>" data-default-color="#111111" />
	<p class="description">
		<?php esc_html_e( 'Background colour of the transition overlay panels.', 'bonsai-page-transitions' ); ?>
	</p>
	<?php
}

function bpt_render_skip_homepage_field() {
	$value = get_option( 'bpt_skip_homepage', true );
	?>
	<label>
		<input type="checkbox" name="bpt_skip_homepage" id="bpt_skip_homepage" value="1" <?php checked( $value, true ); ?> />
		<?php esc_html_e( 'Don\'t run the transition entrance animation on the front page', 'bonsai-page-transitions' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'Enable this if the theme already has its own homepage loader/intro animation, to avoid two overlays fighting for attention. Links pointing away from the homepage still play the exit transition as normal.', 'bonsai-page-transitions' ); ?>
	</p>
	<?php
}

add_action( 'admin_enqueue_scripts', 'bpt_admin_enqueue' );
function bpt_admin_enqueue( $hook ) {
	if ( 'settings_page_' . BPT_PAGE_SLUG !== $hook ) {
		return;
	}

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_add_inline_script( 'wp-color-picker', "jQuery(function($){ $('.bpt-colour-picker').wpColorPicker(); });" );
}

function bpt_render_settings_page() {
	if ( ! current_user_can( BPT_CAPABILITY ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bonsai-page-transitions' ) );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Bonsai Page Transitions', 'bonsai-page-transitions' ); ?></h1>
		<p><?php esc_html_e( 'Choose the full-screen wipe animation played on internal link clicks, sitewide. Add a "no-transition" class to any link to opt it out.', 'bonsai-page-transitions' ); ?></p>
		<form method="post" action="options.php">
			<?php
			settings_fields( BPT_OPTION_GROUP );
			do_settings_sections( BPT_PAGE_SLUG );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

// ---------------------------------------------------------------------------
// Front-end output
// ---------------------------------------------------------------------------

/**
 * True once for the current request — the style is active and (per the
 * Skip Homepage setting) this isn't the front page.
 */
function bpt_is_active() {
	static $active = null;

	if ( null !== $active ) {
		return $active;
	}

	$style = get_option( 'bpt_transition_style', 'none' );

	if ( 'none' === $style ) {
		$active = false;
		return $active;
	}

	if ( get_option( 'bpt_skip_homepage', true ) && is_front_page() ) {
		$active = false;
		return $active;
	}

	$active = true;
	return $active;
}

add_action( 'wp_enqueue_scripts', 'bpt_enqueue_assets' );
function bpt_enqueue_assets() {
	if ( ! bpt_is_active() ) {
		return;
	}

	wp_enqueue_style(
		'bonsai-page-transitions',
		BPT_PLUGIN_URL . 'assets/css/page-transition.css',
		array(),
		BPT_VERSION
	);

	$colour = get_option( 'bpt_overlay_colour', '#111111' );
	wp_add_inline_style(
		'bonsai-page-transitions',
		':root{--bpt-overlay-colour:' . esc_attr( $colour ) . ';}'
	);

	wp_enqueue_script(
		'bonsai-page-transitions',
		BPT_PLUGIN_URL . 'assets/js/page-transition.js',
		array( 'jquery' ),
		BPT_VERSION,
		true
	);
}

/**
 * Requires the active theme to call wp_body_open() in header.php — WP
 * core best practice since 5.2, and already required by Bonsai Code
 * Injector's Body Code field.
 */
add_action( 'wp_body_open', 'bpt_output_overlay' );
function bpt_output_overlay() {
	if ( ! bpt_is_active() ) {
		return;
	}

	$style = get_option( 'bpt_transition_style', 'none' );
	?>
	<div class="page-transition" id="page-transition" data-transition-style="<?php echo esc_attr( $style ); ?>" aria-hidden="true">
		<span class="page-transition__panel page-transition__panel--left"></span>
		<span class="page-transition__panel page-transition__panel--right"></span>
	</div>
	<?php
}

// Lock scroll while the entrance animation plays; JS removes this once it finishes.
add_filter( 'body_class', 'bpt_body_class' );
function bpt_body_class( $classes ) {
	if ( bpt_is_active() ) {
		$classes[] = 'has-page-transition';
	}
	return $classes;
}

// ---------------------------------------------------------------------------
// Uninstall cleanup
// ---------------------------------------------------------------------------

register_uninstall_hook( __FILE__, 'bpt_uninstall' );
function bpt_uninstall() {
	delete_option( 'bpt_transition_style' );
	delete_option( 'bpt_overlay_colour' );
	delete_option( 'bpt_skip_homepage' );
}
