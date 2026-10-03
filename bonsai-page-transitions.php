<?php
/**
 * Plugin Name: Bonsai Page Transitions
 * Plugin URI:  https://bonsaidigitalcollective.co.uk/
 * Description: Plays a full-screen wipe animation (fade / slide up / curtain) whenever a visitor clicks a link to another page on the site. Real page loads underneath — no AJAX content-swap.
 * Version:     1.2.0
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
	'https://github.com/Bonsai-Systems/bonsai-page-transitions',
	__FILE__,
	'bonsai-page-transitions',
	6
);

$bpt_update_checker->setBranch( 'main' );
$bpt_update_checker->getVcsApi()->enableReleaseAssets();

define( 'BPT_VERSION', '1.2.0' );
define( 'BPT_PLUGIN_FILE', __FILE__ );
define( 'BPT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BPT_OPTION_GROUP', 'bpt_settings_group' );
define( 'BPT_PAGE_SLUG', 'bonsai-page-transitions' );
define( 'BPT_CAPABILITY', apply_filters( 'bonsai_page_transitions_capability', 'manage_options' ) );

// Shared Bonsai admin menu, page shell and suite installer. Bundled copy of
// the bonsai-hub repo; update it with bonsai-hub/bin/sync.sh, not by hand.
require_once plugin_dir_path( __FILE__ ) . 'lib/bonsai-hub/bonsai-hub.php';

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
		'bpt_main_section',
		array( 'label_for' => 'bpt_transition_style' )
	);

	add_settings_field(
		'bpt_overlay_colour',
		__( 'Overlay Colour', 'bonsai-page-transitions' ),
		'bpt_render_colour_field',
		BPT_PAGE_SLUG,
		'bpt_main_section',
		array( 'label_for' => 'bpt_overlay_colour' )
	);

	add_settings_field(
		'bpt_skip_homepage',
		__( 'Skip Homepage', 'bonsai-page-transitions' ),
		'bpt_render_skip_homepage_field',
		BPT_PAGE_SLUG,
		'bpt_main_section'
	);
}

/*
 * options.php checks manage_options for every option group unless told
 * otherwise, so a filtered BPT_CAPABILITY could see the page but not save.
 */
add_filter( 'option_page_capability_' . BPT_OPTION_GROUP, 'bpt_option_page_capability' );
function bpt_option_page_capability() {
	return BPT_CAPABILITY;
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

add_filter( 'bonsai_hub_modules', 'bpt_register_hub_module' );
/**
 * Registers the settings screen under the shared Bonsai menu. Old
 * options-general.php?page=bonsai-page-transitions links are redirected
 * here by the hub.
 *
 * @param array $modules Modules registered so far.
 * @return array
 */
function bpt_register_hub_module( $modules ) {
	$modules[ BPT_PAGE_SLUG ] = array(
		'label'       => __( 'Page Transitions', 'bonsai-page-transitions' ),
		'title'       => __( 'Bonsai Page Transitions', 'bonsai-page-transitions' ),
		'description' => __( 'Choose the full-screen wipe animation played on internal link clicks, sitewide.', 'bonsai-page-transitions' ),
		'version'     => BPT_VERSION,
		'repo'        => 'https://github.com/Bonsai-Systems/bonsai-page-transitions',
		'capability'  => BPT_CAPABILITY,
		'enqueue'     => 'bpt_admin_enqueue',
		'render'      => 'bpt_render_settings_page',
	);
	return $modules;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bpt_add_settings_link' );
function bpt_add_settings_link( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=' . BPT_PAGE_SLUG ) ) . '">' . esc_html__( 'Settings', 'bonsai-page-transitions' ) . '</a>';
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

/**
 * Colour picker for the settings screen. Called by the hub on this
 * plugin's screen only, after the shared Bonsai styles.
 *
 * @return void
 */
function bpt_admin_enqueue() {
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_add_inline_script( 'wp-color-picker', "jQuery(function($){ $('.bpt-colour-picker').wpColorPicker(); });" );
}

/**
 * Settings form. The hub prints the page wrap, header, notices and nav
 * around it, and has already checked BPT_CAPABILITY.
 *
 * @return void
 */
function bpt_render_settings_page() {
	?>
	<form method="post" action="options.php">
		<section class="bonsai-ui-card" aria-labelledby="bpt-settings-title">
			<h2 class="bonsai-ui-card__title" id="bpt-settings-title"><?php esc_html_e( 'Transition', 'bonsai-page-transitions' ); ?></h2>
			<p class="bonsai-ui-card__intro">
				<?php
				printf(
					/* translators: %s: the no-transition CSS class */
					esc_html__( 'Add a %s class to any link to opt it out.', 'bonsai-page-transitions' ),
					'<code>no-transition</code>'
				);
				?>
			</p>
			<?php
			settings_fields( BPT_OPTION_GROUP );
			do_settings_sections( BPT_PAGE_SLUG );
			?>
		</section>
		<?php submit_button(); ?>
	</form>
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
