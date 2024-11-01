<?php
/**
 * Plugin Name: Sticky Header 2020
 * Plugin URI:  https://iuliacazan.ro/sticky-header-2020/
 * Description: This plugin appends custom functionality to the native customizer and provides the settings for making the header sticky, with settings for scroll minification, shadow, background, spacing, text, menu and icons colors, etc. This is compatible with Twenty Twenty -> Twenty Twenty-Four, Astra, and Hello Elementor themes.
 * Text Domain: sh2020
 * Domain Path: /langs
 * Version:     2.1.0
 * Author:      Iulia Cazan
 * Author URI:  https://profiles.wordpress.org/iulia-cazan
 * Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ
 * License:     GPL2
 *
 * @package sticky-header-2020
 *
 * Copyright (C) 2019-2024 Iulia Cazan
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

define( 'STICKY_HEADER_2020_VER', 2.1 );
define( 'SH2020_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SH2020_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SH2020_PLUGIN_SLUG', 'sh2020-plugin' );

if ( ! class_exists( 'Sticky_Header_2020' ) ) {
	/**
	 * Customizer additional settings.
	 */
	class Sticky_Header_2020 {
		const DEFAULT_HEIGHT            = '10rem';
		const DEFAULT_SPACING           = '1rem';
		const DEFAULT_HEIGHT_MINI       = '8rem';
		const DEFAULT_SPACING_MINI      = '1rem';
		const DEFAULT_HEIGHT_MOBILE     = '6rem';
		const DEFAULT_SPACING_MOBILE    = '1rem';
		const DEFAULT_SHADOW_SIZE       = '1em';
		const DEFAULT_SHADOW_COLOR      = 'rgba(0,0,0,0.8)';
		const DEFAULT_COLOR_BG          = '#ffffff';
		const DEFAULT_COLOR_BG_SUBMENU  = '#eeeeee';
		const DEFAULT_COLOR_TEXT        = '#000000';
		const DEFAULT_COLOR_ICONS       = '#444444';
		const DEFAULT_COLOR_LINKS       = '#000000';
		const DEFAULT_COLOR_LINKS_HOVER = '#ff0000';
		const DEFAULT_SELECTOR          = '#site-header';
		const PRO_LABEL                 = '<b class="sh2020-sticky-header-pro-label">PRO</b>';
		const LINK_PRO_VERSION          = 'https://iuliacazan.ro/wordpress-extension/sticky-header-2020-pro/';
		const LINK_LIGHT_VERSION        = 'https://iuliacazan.ro/sticky-header-2020/';
		const ASSETS_VER                = '20230406.1941';
		const PLUGIN_NAME               = 'Sticky Header 2020';
		const PLUGIN_SUPPORT_URL        = 'https://wordpress.org/support/plugin/sticky-header-2020/';
		const PLUGIN_TRANSIENT          = 'sh2020-plugin-notice';

		/**
		 * Class instance.
		 *
		 * @var object
		 */
		private static $instance;

		/**
		 * Class theme_is.
		 *
		 * @var string
		 */
		private static $theme_is;

		/**
		 * Display the PRO label in PRO version.
		 *
		 * @var boolean
		 */
		public static $show_pro = true;

		/**
		 * Get active object instance
		 *
		 * @access public
		 * @return object
		 */
		public static function get_instance(): object {
			if ( ! self::$instance ) {
				self::$instance = new Sticky_Header_2020();
			}
			return self::$instance;
		}

		/**
		 * Class constructor. Includes constants and init methods.
		 *
		 * @access public
		 */
		public function __construct() {
			$this->init();
		}

		/**
		 * Run action and filter hooks.
		 *
		 * @access private
		 */
		private function init() {
			$class = get_called_class();

			// Check if the plugin should show or hide hints.
			add_action( 'admin_init', [ $class, 'check_theme' ] );

			// Check if the plugin should show or hide hints.
			add_action( 'admin_init', [ $class, 'check_hints' ] );

			// Setup the Theme Customizer settings and controls.
			add_action( 'customize_register', [ $class, 'register' ] );

			// Trigger the transients reset on customizer save.
			add_action( 'customize_save_after', [ $class, 'update_custom_styles' ], 99 );
			add_action( 'customize_save_after', [ $class, 'update_custom_scripts' ], 99 );

			// Output custom CSS to live site.
			add_action( 'wp_enqueue_scripts', [ $class, 'enqueue_custom_styles' ] );

			// Enqueue the customizer assets.
			add_action( 'customize_preview_init', [ $class, 'sticky_header_2020_customizer_live_preview' ] );

			// Enqueue the customizer assets.
			add_filter( 'body_class', [ $class, 'sticky_header_2020_class' ], 90 );

			// Load translation.
			add_action( 'plugins_loaded', [ $class, 'load_textdomain' ] );

			// Versions updates notes.
			add_action( 'admin_notices', [ $class, 'admin_notices' ] );
			add_action( 'wp_ajax_plugin-deactivate-notice-sh2020-plugin', [ $class, 'admin_notices_cleanup' ] );
			add_action( 'wp_ajax_sh2020_preview', [ $class, 'css_preview' ] );

			// Setup custom plugin links.
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $class, 'plugin_action_links' ] );

			if ( is_admin() ) {
				add_action( 'admin_enqueue_scripts', [ $class, 'load_assets' ] );
				add_action( 'admin_menu', [ $class, 'admin_menu' ] );
				add_action( 'init', [ $class, 'maybe_save_settings' ], 1 );
			}

			add_action( 'switch_theme', [ $class, 'on_switch_theme' ] );
		}

		/**
		 * Check the current theme.
		 */
		public static function check_theme() {
			self::$theme_is = '';

			$theme = wp_get_theme();
			if ( ! empty( $theme ) ) {
				self::$theme_is = $theme->get( 'TextDomain' );
			}
		}

		/**
		 * Check if the hints are turned on/off.
		 */
		public static function check_hints() {
			$updated = false;
			if ( ! empty( $_SERVER['SCRIPT_NAME'] ) && substr_count( $_SERVER['SCRIPT_NAME'], 'customize.php' ) ) { // phpcs:ignore
				$show = filter_input( INPUT_GET, 'sh2020-show-hints', FILTER_DEFAULT );
				if ( 'on' === $show ) {
					set_theme_mod( 'sh2020_show_hints', true );
					self::$show_pro = true;

					$updated = true;
				} else {
					$hide = filter_input( INPUT_GET, 'sh2020-hide-hints', FILTER_DEFAULT );
					if ( 'on' === $hide ) {
						set_theme_mod( 'sh2020_show_hints', false );
						self::$show_pro = false;

						$updated = true;
					}
				}

				$reset = filter_input( INPUT_GET, 'sh2020-reset', FILTER_DEFAULT );
				if ( 'on' === $reset ) {
					self::reset_to_default();
					$updated = true;
				}
			}

			if ( true === $updated ) {
				$customizer = 'sh2020_sticky_header_options';
				if ( self::is_pro() ) {
					if ( true === self::$show_pro ) {
						$customizer = 'sh2020_sticky_header_options_pro';
					} else {
						$customizer = 'sh2020_sticky_header_options_pro_simple';
					}
				}
				wp_safe_redirect( esc_url( admin_url( 'customize.php?autofocus[section]=' . $customizer ) ) );
				exit;
			} else {
				self::$show_pro = get_theme_mod( 'sh2020_show_hints' );
			}
		}

		/**
		 * Check if this is the PRO version.
		 *
		 * @return boolean
		 */
		public static function is_pro(): bool {
			if ( file_exists( __DIR__ . '/pro-settings.php' ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Register customizer options.
		 *
		 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
		 */
		public static function register( WP_Customize_Manager $wp_customize ) {
			// Include the custom rgba control class.
			require_once __DIR__ . '/classes/class-sh2020-customize-rgba-color-control.php';
			require_once __DIR__ . '/classes/class-sh2020-customize-simple-text-control.php';

			if ( self::is_pro() ) {
				if ( true === self::$show_pro ) {
					define( 'STICKY_HEADER_2020_SECTION', 'sh2020_sticky_header_options_pro' );
				} else {
					define( 'STICKY_HEADER_2020_SECTION', 'sh2020_sticky_header_options_pro_simple' );
				}
			} else {
				define( 'STICKY_HEADER_2020_SECTION', 'sh2020_sticky_header_options' );
			}

			// Theme Options.
			$wp_customize->add_section(
				STICKY_HEADER_2020_SECTION,
				[
					'title'       => __( 'Sticky Header', 'sh2020' ),
					'priority'    => 41,
					'capability'  => 'edit_theme_options',
					'description' => __( 'Here you can adjust specific settings for the theme sticky header. Please note that the settings below apply if the sticky header is enabled.', 'sh2020' ),
					'class'       => 'no-pro-label',
					'attrs'       => [
						'class' => 'no-pro-label',
					],
					'attributes'  => [
						'class' => 'no-pro-label',
					],
				]
			);

			if ( self::is_pro() ) {
				if ( true === self::$show_pro ) {
					// Append general notice.
					do_action( 'sh2020_show_pro_general', $wp_customize );
				}
			} else {
				self::append_simple_text(
					$wp_customize,
					'header_sticky_custom_text_0',
					'',
					sprintf(
						// Translators: %1$s - pro version label, %2$s - link, %3$s - end link.
						__( 'You are using the free version, some settings are available only in the %1$s version.<br>See more %2$sdetails%3$s.', 'sh2020' ),
						self::PRO_LABEL,
						'<a href="' . self::LINK_PRO_VERSION . '" target="_blank">',
						'</a>'
					),
					0
				);
			}

			do_action( 'sh2020_pro_settings' );

			self::append_simple_text(
				$wp_customize,
				'header_sticky_custom_text_01',
				'',
				'<hr>',
				1
			);

			// The general header wrapper ID.
			$wp_customize->add_setting(
				'sh2020_header_sticky_selector',
				[
					'capability'        => 'edit_theme_options',
					'default'           => self::get_default_selector(),
					'sanitize_callback' => 'sanitize_text_field',
					'transport'         => 'postMessage',
				]
			);
			$wp_customize->add_control(
				'sh2020_header_sticky_selector',
				[
					'type'        => 'text',
					'section'     => STICKY_HEADER_2020_SECTION,
					'priority'    => 1,
					'label'       => __( 'Header Selector (ID)', 'sh2020' ),
					'description' => __( 'default', 'sh2020' ) . ' ' . self::get_default_selector(),
					'input_attrs' => [
						'style'       => 'width: 110px',
						'placeholder' => self::DEFAULT_HEIGHT,
					],
				]
			);

			self::append_simple_text(
				$wp_customize,
				'header_sticky_custom_text_02',
				'',
				sprintf(
					// Translators: %1$s - selector tt, %2$s - selector tto.
					__( 'The selector for the header element for theme: <ol><li>Twenty Twenty - %1$s</li><li>Twenty Twenty-One and Astra - %2$s</li><li>Twenty Twenty-Two, Twenty Twenty-Three, and Twenty Twenty-Four - %3$s</li><li>Hello Elementor - %4$s</li></ol>', 'sh2020' ),
					'<em>#site-header</em>',
					'<em>header#masthead</em>',
					'<em>header.wp-block-template-part > div</em>',
					'<em>header#site-header</em>',
				),
				2
			);

			// Enable sticky header.
			$wp_customize->add_setting(
				'sh2020_enable_header_sticky',
				[
					'capability'        => 'edit_theme_options',
					'default'           => false,
					'sanitize_callback' => [ get_called_class(), 'sanitize_checkbox' ],
					'transport'         => 'postMessage',
				]
			);
			$wp_customize->add_control(
				'sh2020_enable_header_sticky',
				[
					'type'     => 'checkbox',
					'section'  => STICKY_HEADER_2020_SECTION,
					'priority' => 2,
					'label'    => __( 'Make the header sticky', 'sh2020' ),
				]
			);

			// Alignfull.
			$wp_customize->add_setting(
				'sh2020_enable_header_sticky_full',
				[
					'capability'        => 'edit_theme_options',
					'default'           => false,
					'sanitize_callback' => [ get_called_class(), 'sanitize_checkbox' ],
					'transport'         => 'postMessage',
				]
			);
			$wp_customize->add_control(
				'sh2020_enable_header_sticky_full',
				[
					'type'     => 'checkbox',
					'section'  => STICKY_HEADER_2020_SECTION,
					'priority' => 2,
					'label'    => __( 'Make the header align full (useful for Gutenberg compatible themes, like Twenty Twenty-One for example)', 'sh2020' ),
				]
			);

			// Enable keeps sticky header settings.
			$wp_customize->add_setting(
				'sh2020_enable_header_sticky_keep_setting',
				[
					'capability'        => 'edit_theme_options',
					'default'           => false,
					'sanitize_callback' => [ get_called_class(), 'sanitize_checkbox' ],
					'transport'         => 'postMessage',
				]
			);
			$wp_customize->add_control(
				'sh2020_enable_header_sticky_keep_setting',
				[
					'type'     => 'checkbox',
					'section'  => STICKY_HEADER_2020_SECTION,
					'priority' => 2,
					'label'    => __( 'Remove the header sticky settings when the plugin is disabled', 'sh2020' ),
				]
			);

			// Enable sticky header.
			$wp_customize->add_setting(
				'sh2020_sticky_header_pro_version',
				[
					'capability'        => 'edit_theme_options',
					'default'           => ( self::is_pro() ) ? true : false,
					'sanitize_callback' => [ get_called_class(), 'sanitize_checkbox' ],
					'transport'         => 'postMessage',
				]
			);
			$wp_customize->add_control(
				'sh2020_sticky_header_pro_version',
				[
					'type'     => 'hidden',
					'section'  => STICKY_HEADER_2020_SECTION,
					'priority' => 2,
				]
			);

			// Append general header description.
			self::append_simple_text(
				$wp_customize,
				'header_sticky_custom_text_1',
				'<hr><br>' . esc_html__( 'Header General Settings', 'sh2020' ),
				__( 'The settings below will change the header height and will display a resized logo based on the header height and the vertical spacing (the logo and menu top and bottom spacing) you set here. The units can be <b>px</b>, <b>em</b>, <b>rem</b>, <b>%</b> or <b>vh</b> (it\'s recommended to use px for a more accurate result).', 'sh2020' ),
				2
			);

			// Sticky Header Colors.
			self::append_rgba_color(
				$wp_customize,
				'sh2020_header_sticky_bg_color',
				esc_html__( 'Background Color', 'sh2020' ),
				self::DEFAULT_COLOR_BG,
				3
			);

			self::append_rgba_color(
				$wp_customize,
				'sh2020_header_sticky_bg_color_minified',
				esc_html__( 'Minified Header Background Color', 'sh2020' ),
				self::DEFAULT_COLOR_BG,
				3
			);

			// Minimum height for the general header.
			$wp_customize->add_setting(
				'sh2020_header_sticky_min_height',
				[
					'capability'        => 'edit_theme_options',
					'default'           => self::DEFAULT_HEIGHT,
					'sanitize_callback' => 'sanitize_text_field',
					'transport'         => 'postMessage',
				]
			);
			$wp_customize->add_control(
				'sh2020_header_sticky_min_height',
				[
					'type'        => 'text',
					'section'     => STICKY_HEADER_2020_SECTION,
					'priority'    => 3,
					'label'       => __( 'Header Height', 'sh2020' ),
					'description' => __( 'default', 'sh2020' ) . ' ' . self::DEFAULT_HEIGHT,
					'input_attrs' => [
						'style'       => 'width: 110px',
						'placeholder' => self::DEFAULT_HEIGHT,
					],
				]
			);

			// General header vertical spacing.
			$wp_customize->add_setting(
				'sh2020_header_sticky_vertical_spacing',
				[
					'capability'        => 'edit_theme_options',
					'default'           => self::DEFAULT_SPACING,
					'sanitize_callback' => 'sanitize_text_field',
					'transport'         => 'postMessage',
				]
			);
			$wp_customize->add_control(
				'sh2020_header_sticky_vertical_spacing',
				[
					'type'        => 'text',
					'section'     => STICKY_HEADER_2020_SECTION,
					'priority'    => 4,
					'label'       => __( 'Vertical Spacing', 'sh2020' ),
					'description' => __( 'default', 'sh2020' ) . ' ' . self::DEFAULT_SPACING,
					'input_attrs' => [
						'style'       => 'width: 110px',
						'placeholder' => self::DEFAULT_SPACING,
					],
				]
			);

			// Append general header shadow.
			self::append_simple_text(
				$wp_customize,
				'header_sticky_custom_text_5',
				'',
				'<hr><br>' . __( 'The header shadow options.', 'sh2020' ),
				5
			);
			if ( ! self::is_pro() ) {
				self::append_only_pro_text( $wp_customize, [
					esc_html__( 'Box Shadow Size', 'sh2020' ),
					esc_html__( 'Box Shadow Color', 'sh2020' ),
				], '5.1', 5 );
			}

			// Append scroll minification description.
			self::append_simple_text(
				$wp_customize,
				'header_sticky_custom_text_2',
				'',
				'<hr>' . __( 'When you scroll the page, the header (also the logo) will reduce its height. The settings for the minified header can be adjusted below. To preview the changes you have to scroll up and down the page.', 'sh2020' ),
				7
			);

			// Minimum height for the general minified header.
			$wp_customize->add_setting(
				'sh2020_header_sticky_min_height_minified',
				[
					'capability'        => 'edit_theme_options',
					'default'           => self::DEFAULT_HEIGHT_MINI,
					'sanitize_callback' => 'sanitize_text_field',
					'transport'         => 'postMessage',
				]
			);
			$wp_customize->add_control(
				'sh2020_header_sticky_min_height_minified',
				[
					'type'        => 'text',
					'section'     => STICKY_HEADER_2020_SECTION,
					'priority'    => 8,
					'label'       => __( 'Minified Header Height', 'sh2020' ),
					'description' => __( 'default', 'sh2020' ) . ' ' . self::DEFAULT_HEIGHT_MINI,
					'input_attrs' => [
						'style'       => 'width: 110px',
						'placeholder' => self::DEFAULT_HEIGHT_MINI,
					],
				]
			);

			// General minified header vertical spacing.
			$wp_customize->add_setting(
				'sh2020_header_sticky_vertical_spacing_minified',
				[
					'capability'        => 'edit_theme_options',
					'default'           => self::DEFAULT_SPACING_MINI,
					'sanitize_callback' => 'sanitize_text_field',
					'transport'         => 'postMessage',
				]
			);
			$wp_customize->add_control(
				'sh2020_header_sticky_vertical_spacing_minified',
				[
					'type'        => 'text',
					'section'     => STICKY_HEADER_2020_SECTION,
					'priority'    => 8,
					'label'       => __( 'Minified Vertical Spacing', 'sh2020' ),
					'description' => __( 'default', 'sh2020' ) . ' ' . self::DEFAULT_SPACING_MINI,
					'input_attrs' => [
						'style'       => 'width: 110px',
						'placeholder' => self::DEFAULT_SPACING_MINI,
					],
				]
			);

			// Append mobile height description.
			self::append_simple_text(
				$wp_customize,
				'header_sticky_custom_text_3',
				'<hr><br>' . esc_html__( 'Mobile/Tablet Header', 'sh2020' ),
				__( 'The settings for the mobile/tablet devices header can be adjusted below.', 'sh2020' ),
				10
			);

			// Minimum height for the header on mobile.
			$wp_customize->add_setting(
				'sh2020_header_sticky_min_height_mobile',
				[
					'capability'        => 'edit_theme_options',
					'default'           => self::DEFAULT_HEIGHT_MOBILE,
					'sanitize_callback' => 'sanitize_text_field',
					'transport'         => 'postMessage',
				]
			);
			$wp_customize->add_control(
				'sh2020_header_sticky_min_height_mobile',
				[
					'type'        => 'text',
					'section'     => STICKY_HEADER_2020_SECTION,
					'priority'    => 11,
					'label'       => __( 'Header Height', 'sh2020' ),
					'description' => __( 'default', 'sh2020' ) . ' ' . self::DEFAULT_HEIGHT_MOBILE,
					'input_attrs' => [
						'style'       => 'width: 110px',
						'placeholder' => self::DEFAULT_HEIGHT_MOBILE,
					],
				]
			);

			// General minified header vertical spacing.
			$wp_customize->add_setting(
				'sh2020_header_sticky_vertical_spacing_mobile',
				[
					'capability'        => 'edit_theme_options',
					'default'           => self::DEFAULT_SPACING_MOBILE,
					'sanitize_callback' => 'sanitize_text_field',
					'transport'         => 'postMessage',
				]
			);
			$wp_customize->add_control(
				'sh2020_header_sticky_vertical_spacing_mobile',
				[
					'type'        => 'text',
					'section'     => STICKY_HEADER_2020_SECTION,
					'priority'    => 12,
					'label'       => __( 'Mobile/Tablet Vertical Spacing', 'sh2020' ),
					'description' => __( 'default', 'sh2020' ) . ' ' . self::DEFAULT_SPACING_MOBILE,
					'input_attrs' => [
						'style'       => 'width: 110px',
						'placeholder' => self::DEFAULT_SPACING_MOBILE,
					],
				]
			);

			if ( true === self::$show_pro ) {
				// Append colors description.
				self::append_simple_text(
					$wp_customize,
					'header_sticky_custom_text_4',
					'<hr><br>' . esc_html__( 'Header Elements Colors', 'sh2020' ),
					__( 'The settings below will applied to the elements inside the sticky header.', 'sh2020' ),
					14
				);
			}

			if ( ! self::is_pro() ) {
				self::append_only_pro_text( $wp_customize, [
					esc_html__( 'Text/Separators Color', 'sh2020' ),
					esc_html__( 'Icons Color', 'sh2020' ),
					esc_html__( 'Links Color', 'sh2020' ),
					esc_html__( 'Links Hover Color', 'sh2020' ),
				], '4.1', 14 );
			}

			// Append hide labels description.
			self::append_simple_text(
				$wp_customize,
				'header_sticky_custom_text_6',
				'<hr><br>' . esc_html__( 'Menu & Submenu Extra Options', 'sh2020' ),
				__( 'Apart from the links color, you can adjust the icon, the submenu background and the current item color.', 'sh2020' ),
				30
			);

			if ( ! self::is_pro() ) {
				self::append_only_pro_text( $wp_customize, [
					esc_html__( 'Icons Color for Menu & Submenu', 'sh2020' ),
					esc_html__( 'Link Color for Current Menu Item', 'sh2020' ),
				], '6.1', 31 );
			}

			self::append_rgba_color(
				$wp_customize,
				'sh2020_header_sticky_submenu_bg_color',
				esc_html__( 'Submenu Background Color', 'sh2020' ),
				self::DEFAULT_COLOR_BG_SUBMENU,
				31
			);

			// Enable hide search label.
			$wp_customize->add_setting(
				'sh2020_header_sticky_menu_no_decoration',
				[
					'capability'        => 'edit_theme_options',
					'default'           => false,
					'sanitize_callback' => [ get_called_class(), 'sanitize_checkbox' ],
					'transport'         => 'postMessage',
				]
			);
			$wp_customize->add_control(
				'sh2020_header_sticky_menu_no_decoration',
				[
					'type'     => 'checkbox',
					'section'  => STICKY_HEADER_2020_SECTION,
					'priority' => 32,
					'label'    => __( 'No decoration for menu items', 'sh2020' ),
				]
			);

			// Append hide labels description.
			self::append_simple_text(
				$wp_customize,
				'header_sticky_custom_text_7',
				'<hr><br>' . esc_html__( 'Hide Labels', 'sh2020' ),
				__( 'Hide the texts under the menu and search icons.', 'sh2020' ),
				40
			);

			// Enable hide menu label.
			$wp_customize->add_setting(
				'sh2020_header_sticky_hide_menu_label',
				[
					'capability'        => 'edit_theme_options',
					'default'           => false,
					'sanitize_callback' => [ get_called_class(), 'sanitize_checkbox' ],
					'transport'         => 'postMessage',
				]
			);
			$wp_customize->add_control(
				'sh2020_header_sticky_hide_menu_label',
				[
					'type'     => 'checkbox',
					'section'  => STICKY_HEADER_2020_SECTION,
					'priority' => 41,
					'label'    => __( 'Hide the menu label', 'sh2020' ),
				]
			);

			// Enable hide search label.
			$wp_customize->add_setting(
				'sh2020_header_sticky_hide_search_label',
				[
					'capability'        => 'edit_theme_options',
					'default'           => false,
					'sanitize_callback' => [ get_called_class(), 'sanitize_checkbox' ],
					'transport'         => 'postMessage',
				]
			);
			$wp_customize->add_control(
				'sh2020_header_sticky_hide_search_label',
				[
					'type'     => 'checkbox',
					'section'  => STICKY_HEADER_2020_SECTION,
					'priority' => 42,
					'label'    => __( 'Hide the search label', 'sh2020' ),
				]
			);

			// Reset all.
			self::append_simple_text(
				$wp_customize,
				'header_sticky_custom_text_8',
				'<hr>',
				sprintf(
					// Translators: %1$s - pro version label, %2$s - link, %3$s - end link.
					__( 'If any of the customizer settings above did not update the preview correctly, you can click to %1$sRefresh the preview%2$s', 'sh2020' ),
					'<a id="sh2020-update-preview" class="button button-primary">',
					'</a>'
				)
				. '<br><hr><br>'
				. __( 'Click to reset to default all the settings of this plugin (this will also turn off the sticky header and the styles will not override the theme defaults).', 'sh2020' )
				. '<br><a href="' . esc_url( admin_url( 'customize.php?autofocus[section]=sh2020_sticky_header_options&sh2020-reset=on' ) ) . '" class="button">' . __( 'Reset', 'sh2020' ) . '</a>',
				43
			);
		}

		/**
		 * Append a custom color control with alpha opacity.
		 *
		 * @param object  $wp_customize Customize manager instance.
		 * @param string  $id           Settings id.
		 * @param string  $title        Control title.
		 * @param string  $default      Default value for the control.
		 * @param integer $priority     Control priority.
		 */
		public static function append_rgba_color( $wp_customize, $id, $title, $default = '#ffffff', $priority = 20 ) { // phpcs:ignore
			$wp_customize->add_setting( $id, [
				'default'           => $default,
				'sanitize_callback' => [ get_called_class(), 'sanitize_rgba' ],
				'validate_callback' => [ get_called_class(), 'validate_rgba' ],
				'transport'         => 'postMessage',
			] );
			$wp_customize->add_control(
				new SH2020_Customize_RGBA_Color_Control(
					$wp_customize,
					$id,
					[
						'label'        => $title,
						'section'      => STICKY_HEADER_2020_SECTION,
						'priority'     => $priority,
						'show_opacity' => true,
						'palette'      => [
							'#fd0061', // Red.
							'#ffbf00', // Yellow.
							'#c9de00', // Lime.
							'#7bc62d', // Green.
							'#00abfb', // Cyan.
							'#4363d8', // Blue.
							'#6f32be', // Purple.
							'#a905b6', // Magenta.
							'#9e9e9e', // Grey.
							'#ffffff', // White.
							'#000000', // Black.
						],
						'transport'    => 'postMessage',
					]
				)
			);
		}

		/**
		 * Append a custom text.
		 *
		 * @param object $wp_customize Customize manager instance.
		 * @param string $id           Settings id.
		 * @param string $title        Control title.
		 * @param string $description  Control description.
		 * @param string $priority     Control proority.
		 */
		public static function append_simple_text( $wp_customize, $id, $title = '', $description = '', $priority = '10' ) { // phpcs:ignore
			if ( empty( $title ) && empty( $description ) ) {
				// Fail-fast.
				return;
			}
			$wp_customize->add_setting( $id, [] );
			$wp_customize->add_control(
				new SH2020_Customize_Simple_Text_Control(
					$wp_customize,
					$id,
					[
						'section'     => STICKY_HEADER_2020_SECTION,
						'priority'    => $priority,
						'label'       => $title,
						'description' => $description,
					]
				)
			);
		}

		/**
		 * Append only PRO text.
		 *
		 * @param object  $wp_customize Customize manager instance.
		 * @param mixed   $list         Items to be mentioned.
		 * @param string  $suffix       Control suffix.
		 * @param integer $priority     Control priority.
		 */
		public static function append_only_pro_text( $wp_customize, $list = [], $suffix = '', $priority = 10 ) { // phpcs:ignore
			if ( false === self::$show_pro ) {
				return;
			}
			$text = '';
			if ( is_scalar( $list ) ) {
				$list = [ $list ];
			}
			foreach ( $list as $value ) {
				$text .= '<li><label class="customize-control-title">' . $value . '</label> <em>' . sprintf(
					// Translators: %1$s - link to pro version.
					__( 'Available in the %1$s version.', 'sh2020' ),
					self::PRO_LABEL
				) . '</em><br>&nbsp;</li>';
			}
			self::append_simple_text(
				$wp_customize,
				'header_sticky_custom_text_' . esc_attr( $suffix ),
				'',
				'</li>' . $text . '<li>',
				$priority
			);
		}

		/**
		 * Sanitize boolean for checkbox.
		 *
		 * @param bool $checked Wethere or not a blox is checked.
		 */
		public static function sanitize_checkbox( $checked ) { // phpcs:ignore
			return ( ( isset( $checked ) && true === $checked ) ? true : false );
		}

		/**
		 * Validate alpha settings.
		 *
		 * @param  boolean $validity If the setting value is valid.
		 * @param  string  $value    The setting value.
		 * @return boolean
		 */
		public static function validate_rgba( $validity, $value = '' ) { // phpcs:ignore
			$validity = true;
			return $validity;
		}

		/**
		 * Sanitize alpha settings.
		 *
		 * @param  string $value The setting value.
		 * @return string
		 */
		public static function sanitize_rgba( $value ) { // phpcs:ignore
			// @TODO - maybe a regex.
			return $value;
		}

		/**
		 * Get current theme name.
		 *
		 * @return string
		 */
		public static function get_current_theme(): string {
			$theme = wp_get_theme();
			$name  = $theme->get( 'Name' );
			return $name;
		}

		/**
		 * Check if the theme is Astra.
		 *
		 * @return bool
		 */
		public static function theme_is_astra(): bool {
			if ( 'Astra' === self::get_current_theme() ) {
				return true;
			}
			return false;
		}

		/**
		 * Check if the theme is Twenty Twenty.
		 *
		 * @return bool
		 */
		public static function theme_is_2020(): bool {
			return 'twentytwenty' === self::$theme_is;
		}

		/**
		 * Check if the theme is Twenty Twenty-One.
		 *
		 * @return bool
		 */
		public static function theme_is_2021(): bool {
			return 'twentytwentyone' === self::$theme_is;
		}

		/**
		 * Check if the theme is Twenty Twenty-Two.
		 *
		 * @return bool
		 */
		public static function theme_is_2022(): bool {
			return 'twentytwentytwo' === self::$theme_is;
		}

		/**
		 * Check if the theme is Twenty Twenty-Three.
		 *
		 * @return bool
		 */
		public static function theme_is_2023(): bool {
			return 'twentytwentythree' === self::$theme_is;
		}

		/**
		 * Check if the theme is Twenty Twenty-Four.
		 *
		 * @return bool
		 */
		public static function theme_is_2024(): bool {
			return 'twentytwentyfour' === self::$theme_is;
		}

		/**
		 * Check if the theme is Hello Elementors.
		 *
		 * @return bool
		 */
		public static function theme_is_hello_elementor(): bool {
			return 'hello-elementor' === self::$theme_is;
		}

		/**
		 * Used by the core hook: 'customize_preview_init' to enqueue the theme customizer script.
		 */
		public static function sticky_header_2020_customizer_live_preview() {
			if ( ! file_exists( SH2020_PLUGIN_DIR . 'build/index.js' ) ) {
				return;
			}

			if ( file_exists( SH2020_PLUGIN_DIR . 'build/index.asset.php' ) ) {
				$dependencies = require_once SH2020_PLUGIN_DIR . 'build/index.asset.php';
			} else {
				$dependencies = [
					'dependencies' => [],
					'version'      => filemtime( SH2020_PLUGIN_DIR . 'build/index.js' ),
				];
			}

			wp_register_script(
				'sticky_header_2020-themecustomizer',
				SH2020_PLUGIN_URL . 'build/index.js',
				[ 'customize-preview' ],
				$dependencies['version'],
				true
			);
			wp_localize_script(
				'sticky_header_2020-themecustomizer',
				'sh2020Settings',
				[
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				]
			);
			wp_enqueue_script( 'sticky_header_2020-themecustomizer' );
		}

		/**
		 * Compute and preview the style.
		 */
		public static function css_preview() {
			$arr = filter_input( INPUT_POST, 'sh2020', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
			$css = self::css_vars();

			if ( isset( $arr['headerId'] ) ) {
				$css['selector'] = $arr['headerId'];
			}
			if ( isset( $arr['isfull'] ) ) {
				$css['alignfull'] = (bool) $arr['isfull'];
			}
			if ( isset( $arr['bcolor'] ) ) {
				$css['color_mainbg'] = $arr['bcolor'];
			}
			if ( isset( $arr['bcolorMin'] ) ) {
				$css['color_mainbg_mini'] = $arr['bcolorMin'];
			}
			if ( isset( $arr['height'] ) ) {
				$css['height'] = $arr['height'];
			}
			if ( isset( $arr['heightMin'] ) ) {
				$css['height_mini'] = $arr['heightMin'];
			}
			if ( isset( $arr['spacing'] ) ) {
				$css['spacing'] = $arr['spacing'];
			}
			if ( isset( $arr['spacingMin'] ) ) {
				$css['spacing_mini'] = $arr['spacingMin'];
			}
			if ( isset( $arr['heightMob'] ) ) {
				$css['height_mobile'] = $arr['heightMob'];
			}
			if ( isset( $arr['spacingMob'] ) ) {
				$css['spacing_mobile'] = $arr['spacingMob'];
			}
			if ( isset( $arr['smcolor'] ) ) {
				$css['color_submenu_bg'] = $arr['smcolor'];
			}
			if ( isset( $arr['ccolor'] ) ) {
				$css['color_menu_clinks'] = $arr['ccolor'];
			}
			if ( isset( $arr['tcolor'] ) ) {
				$css['color_text'] = $arr['tcolor'];
			}
			if ( isset( $arr['nodeco'] ) ) {
				$css['menu_no_deco'] = ( 'true' === (string) $arr['nodeco'] ) ? true : false;
			}
			if ( isset( $arr['hidem'] ) ) {
				$css['hide_label_menu'] = ( 'true' === (string) $arr['hidem'] ) ? true : false;
			}
			if ( isset( $arr['hides'] ) ) {
				$css['hide_label_search'] = ( 'true' === (string) $arr['hides'] ) ? true : false;
			}

			self::output_custom_styles( $css );
			wp_die();
			die();
		}

		/**
		 * Return a default selector based on the current theme.
		 *
		 * @return string
		 */
		public static function get_default_selector(): string {
			$selector = get_theme_mod( 'sh2020_header_sticky_selector', '' );
			if ( empty( $selector ) ) {
				$is_astra = self::theme_is_astra();
				$is_2020  = self::theme_is_2020();
				$is_2021  = self::theme_is_2021();
				$is_2022  = self::theme_is_2022();
				$is_2023  = self::theme_is_2023();
				$is_2024  = self::theme_is_2024();

				$is_hello_elementor = self::theme_is_hello_elementor();

				if ( $is_astra ) {
					$selector = '#masthead';
				} elseif ( $is_2020 ) {
					$selector = '#site-header';
				} elseif ( $is_2021 ) {
					$selector = '#masthead';
				} elseif ( $is_2022 || $is_2023 || $is_2024 ) {
					$selector = 'header.wp-block-template-part > div';
				} elseif ( $is_hello_elementor ) {
					$selector = 'header#site-header';
				} else {
					$selector = self::DEFAULT_SELECTOR;
				}
			}
			return $selector;
		}

		/**
		 * Compute the default settings.
		 *
		 * @return array
		 */
		public static function css_vars(): array {
			$is_astra = self::theme_is_astra();
			$is_2020  = self::theme_is_2020();
			$is_2021  = self::theme_is_2021();
			$is_2022  = self::theme_is_2022();
			$is_2023  = self::theme_is_2023();
			$is_2024  = self::theme_is_2024();

			$is_hello_elementor = self::theme_is_hello_elementor();

			$css = [
				'is_astra'           => $is_astra,
				'is2020'             => $is_2020,
				'is2021'             => $is_2021,
				'is2022'             => $is_2022,
				'is2023'             => $is_2023,
				'is2024'             => $is_2024,
				'is_hello_elementor' => $is_hello_elementor,
				'selector'           => self::get_default_selector(),
				'height'             => get_theme_mod( 'sh2020_header_sticky_min_height', self::DEFAULT_HEIGHT ),
				'spacing'            => get_theme_mod( 'sh2020_header_sticky_vertical_spacing', self::DEFAULT_SPACING ),
				'height_mini'        => get_theme_mod( 'sh2020_header_sticky_min_height_minified', self::DEFAULT_HEIGHT_MINI ),
				'spacing_mini'       => get_theme_mod( 'sh2020_header_sticky_vertical_spacing_minified', self::DEFAULT_SPACING_MINI ),
				'height_mobile'      => get_theme_mod( 'sh2020_header_sticky_min_height_mobile', self::DEFAULT_HEIGHT_MOBILE ),
				'spacing_mobile'     => get_theme_mod( 'sh2020_header_sticky_vertical_spacing_mobile', self::DEFAULT_SPACING_MOBILE ),
				'color_mainbg'       => get_theme_mod( 'sh2020_header_sticky_bg_color', self::DEFAULT_COLOR_BG ),
				'color_mainbg_mini'  => get_theme_mod( 'sh2020_header_sticky_bg_color_minified', self::DEFAULT_COLOR_BG ),
				'color_submenu_bg'   => get_theme_mod( 'sh2020_header_sticky_submenu_bg_color', self::DEFAULT_COLOR_BG_SUBMENU ),
				'color_menu_clinks'  => get_theme_mod( 'sh2020_header_sticky_menu_current_links_color', self::DEFAULT_COLOR_LINKS ),
				'hide_label_menu'    => get_theme_mod( 'sh2020_header_sticky_hide_menu_label', false ),
				'hide_label_search'  => get_theme_mod( 'sh2020_header_sticky_hide_search_label', false ),
				'menu_no_deco'       => get_theme_mod( 'sh2020_header_sticky_menu_no_decoration', false ),
				'alignfull'          => get_theme_mod( 'sh2020_enable_header_sticky_full', false ),
			];

			return $css;
		}

		/**
		 * Output custom styles from settings.
		 *
		 * @param array $css Pre-computed variables.
		 */
		public static function output_custom_styles( array $css = [] ) {
			$is_astra = self::theme_is_astra();
			$is_2020  = self::theme_is_2020();
			$is_2021  = self::theme_is_2021();
			$is_2022  = self::theme_is_2022();
			$is_2023  = self::theme_is_2023();
			$is_2024  = self::theme_is_2024();

			$is_hello_elementor = self::theme_is_hello_elementor();

			if ( empty( $css ) ) {
				$css = self::css_vars();
			}

			if ( empty( $css['selector'] ) ) {
				return;
			}

			foreach ( $css as $k => $v ) {
				$css[ $k ] = wp_strip_all_tags( $v );
			}

			$selector           = $css['selector'];
			$logo_height        = 'calc(' . $css['height'] . ' - 2 * ' . $css['spacing'] . ')';
			$logo_height_mini   = 'calc(' . $css['height_mini'] . ' - 2 * ' . $css['spacing_mini'] . ')';
			$logo_height_mobile = 'calc(' . $css['height_mobile'] . ' - 2 * ' . $css['spacing_mobile'] . ')';

			// phpcs:disable
			?>

			body.sticky-header.processing {
				opacity: 0.8;
			}

			body.sticky-header <?php echo $selector; ?> * {
				outline: none;
			}

			body.sticky-header.customizer-preview <?php echo $selector; ?> * {
				transition: width 0.3s, height 0.3s, padding 0.3s, margin 0.3s;
			}

			body.sticky-header.admin-bar <?php echo $selector; ?> {
				top: 32px !important
			}

			body.sticky-header .menu-modal.active {
				z-index: 110;
			}

			body.sticky-header {
				scroll-behavior: smooth;
				padding-top: <?php echo esc_attr( $css['height'] ); ?>;
				scroll-padding: <?php echo esc_attr( $css['height'] ); ?>;
			}

			body.sticky-header <?php echo $selector; ?> {
				background: <?php echo esc_attr( $css['color_mainbg'] ); ?>;
				background-attachment: fixed;
				background-color: <?php echo esc_attr( $css['color_mainbg'] ); ?>;
				<?php do_action( 'sh2020_pro_css', 1 ); ?>
				display: block;
				height: <?php echo esc_attr( $css['height'] ); ?>;
				min-height: <?php echo esc_attr( $css['height'] ); ?>;
				position:fixed !important;
				top:0 !important;
				transition:all 0.2s ease;
				-moz-transition:all 0.2s ease;
				-webkit-transition:all 0.2s ease;
				-o-transition:all 0.2s ease;
				width: 100%;
				z-index: 100;
			}
			<?php if ( $is_astra ) : ?>
				body.sticky-header <?php echo $selector; ?> .main-header-bar {
					background: transparent;
					border: 0;
				}

				body.sticky-header <?php echo $selector; ?> #ast-desktop-header {
					width: 100%;
				}

				body.sticky-header <?php echo $selector; ?> .ast-logo-title-inline .ast-site-identity {
					padding: 0
				}

				body.sticky-header <?php echo $selector; ?> .main-header-bar,
				body.sticky-header <?php echo $selector; ?> .site-branding {
					height: <?php echo esc_attr( $css['height'] ); ?>;
					min-height: <?php echo esc_attr( $css['height'] ); ?>;
				}

				body.sticky-header <?php echo $selector; ?>.sticky-header-minified .main-header-bar,
				body.sticky-header <?php echo $selector; ?>.sticky-header-minified .site-branding {
					min-height: <?php echo esc_attr( $css['height_mini'] ); ?>;
					height: <?php echo esc_attr( $css['height_mini'] ); ?>;
				}

				body.sticky-header <?php echo $selector; ?> .site-primary-header-wrap {
					min-height: unset;
				}
			<?php endif; ?>

			<?php if ( ! empty( $css['alignfull'] ) ) : ?>
				body.sticky-header <?php echo $selector; ?> {
					display: flex !important;
					flex-direction: row;
					margin-left: auto;
					margin-right: auto;
					padding: 0;
					left: calc((100% - var(--responsive--alignwide-width)) / 2);
				}

				body.sticky-header <?php echo $selector; ?> .main-header-bar-wrap {
					width: 100%;
				}

				body.sticky-header <?php echo $selector; ?> > * {
					z-index: 10;
				}

				body.sticky-header <?php echo $selector; ?> > .site-branding {
					margin: 0;
				}

				body.sticky-header <?php echo $selector; ?> > .site-logo {
					border: 0;
					margin: 0;
					padding: 0;
					width: unset;
				}

				body.sticky-header <?php echo $selector; ?>:before {
					background: inherit;
					background-color: inherit;
					<?php do_action( 'sh2020_pro_css', 1 ); ?>
					height: 100%;
					left: calc(-1 * 100vw);
					margin: 0;
					max-width: 400vw;
					position: absolute;
					top: 0;
					width: 400vw;
					z-index: 1;
				}
			<?php endif; ?>

			body.sticky-header <?php echo $selector; ?> .header-inner {
				padding: <?php echo esc_attr( $css['spacing'] ); ?> 0;
			}

			body.sticky-header <?php echo $selector; ?> .custom-logo {
				height: <?php echo $logo_height; ?>;
				max-height: <?php echo $logo_height; ?> !important;
				width: auto;
			}

			body.sticky-header <?php echo $selector; ?> img.custom-logo {
				display: flex;
			}

			/* The minified desktop and tablet header and logo */
			body.sticky-header <?php echo $selector; ?>.sticky-header-minified {
				background: <?php echo esc_attr( $css['color_mainbg_mini'] ); ?>;
				background-color: <?php echo esc_attr( $css['color_mainbg_mini'] ); ?>;
				min-height: <?php echo esc_attr( $css['height_mini'] ); ?>;
				height: <?php echo esc_attr( $css['height_mini'] ); ?>;
			}

			body.sticky-header <?php echo $selector; ?>.sticky-header-minified .header-inner {
				padding: <?php echo esc_attr( $css['spacing_mini'] ); ?> 0;
			}

			<?php if ( $is_astra ) : ?>
				body.sticky-header <?php echo $selector; ?>.sticky-header-minified .site-logo {
					margin: 0;
				}
			<?php endif; ?>

			body.sticky-header <?php echo $selector; ?> .custom-logo {
				transition:all 0.2s ease;
				-moz-transition:all 0.2s ease;
				-webkit-transition:all 0.2s ease;
				-o-transition:all 0.2s ease;
			}

			body.sticky-header <?php echo $selector; ?>.sticky-header-minified .custom-logo {
				height: <?php echo $logo_height_mini; ?>;
				max-height: <?php echo $logo_height_mini; ?> !important;
				max-width: unset;
				width: auto;
			}

			body.template-cover.sticky-header .entry-header {
				bottom: <?php echo esc_attr( $css['height'] ); ?>;
			}

			@media (max-width: 782px) {
				body.sticky-header {
					padding-top: <?php echo esc_attr( $css['height_mobile'] ); ?>;
					scroll-padding: <?php echo esc_attr( $css['height_mobile'] ); ?>;
				}

				body.sticky-header.admin-bar <?php echo $selector; ?> {
					top: 46px !important;
				}

				body.sticky-header.admin-bar <?php echo $selector; ?>.sticky-header-minified {
					top: 0px !important;
				}

				body.sticky-header <?php echo $selector; ?> .header-inner,
				body.sticky-header <?php echo $selector; ?>.sticky-header-minified .header-inner {
					padding: <?php echo esc_attr( $css['spacing_mobile'] ); ?> 0;
				}

				body.sticky-header <?php echo $selector; ?>,
				body.sticky-header <?php echo $selector; ?>.sticky-header-minified {
					min-height: <?php echo esc_attr( $css['height_mobile'] ); ?>;
					height: <?php echo esc_attr( $css['height_mobile'] ); ?>;
				}

				body.template-cover.sticky-header .entry-header,
				body.template-cover.sticky-header.sticky-header-minified .entry-header {
					bottom: <?php echo esc_attr( $css['height_mobile'] ); ?>;
				}

				body.sticky-header <?php echo $selector; ?> .site-logo,
				body.sticky-header <?php echo $selector; ?>.sticky-header-minified .site-logo,
				body.sticky-header <?php echo $selector; ?> .site-branding,
				body.sticky-header <?php echo $selector; ?>.sticky-header-minified .site-branding,
				body.sticky-header <?php echo $selector; ?> .custom-logo,
				body.sticky-header <?php echo $selector; ?>.sticky-header-minified .custom-logo {
					height: <?php echo $logo_height_mobile; ?>;
					margin: 0;
					max-height: <?php echo $logo_height_mobile; ?> !important;
					position: relative;
					max-width: unset;
					width: auto;
				}

				body.sticky-header.admin-bar.primary-navigation-open <?php echo $selector; ?> .primary-navigation,
				body.sticky-header.admin-bar.primary-navigation-open <?php echo $selector; ?>.sticky-header-minified .primary-navigation {
					top: 46px !important;
				}

				body.sticky-header <?php echo $selector; ?> .primary-navigation,
				body.sticky-header <?php echo $selector; ?>.sticky-header-minified .primary-navigation {
					margin-left: auto;
					padding: 0;
					padding-top: <?php echo esc_attr( $css['spacing_mobile'] ); ?>;
					top: 0;
					z-index: 4000;
				}

				body.sticky-header <?php echo $selector; ?> .menu-button-container,
				body.sticky-header <?php echo $selector; ?> .site-logo {
					padding: 0;
					padding-top: 0;
					position: relative;
					top: 0;
				}

				<?php if ( $is_astra ) : ?>
					body.sticky-header <?php echo $selector; ?> .main-header-bar,
					body.sticky-header <?php echo $selector; ?> .site-branding,
					body.sticky-header <?php echo $selector; ?>.sticky-header-minified .main-header-bar,
					body.sticky-header <?php echo $selector; ?>.sticky-header-minified .site-branding {
						height: <?php echo esc_attr( $css['height_mobile'] ); ?>;
						min-height: <?php echo esc_attr( $css['height_mobile'] ); ?>;
					}
				<?php endif; ?>
			}

			<?php do_action( 'sh2020_pro_css', 2 ); ?>
			body.sticky-header <?php echo $selector; ?> nav ul ul,
			body.sticky-header <?php echo $selector; ?> nav ul.submenu,
			body.sticky-header <?php echo $selector; ?> nav ul.submenu li,
			body.sticky-header <?php echo $selector; ?> .primary-navigation > div > .menu-wrapper > li > .sub-menu li {
				background: <?php echo esc_attr( $css['color_submenu_bg'] ); ?> !important;
			}

			body.sticky-header <?php echo $selector; ?> nav ul > li > ul:after {
				border-bottom-color: <?php echo esc_attr( $css['color_submenu_bg'] ); ?>;
				border-left-color: transparent;
			}

			body.sticky-header <?php echo $selector; ?> nav ul ul.sub-menu ul.sub-menu:after {
				border-bottom-color: transparent;
				border-left-color: <?php echo esc_attr( $css['color_submenu_bg'] ); ?>;
			}

			body.sticky-header <?php echo $selector; ?> .wp-block-navigation-item,
			body.sticky-header <?php echo $selector; ?> nav li.current-menu-item > a,
			body.sticky-header <?php echo $selector; ?> nav li.current-menu-ancestor > a {
				color: <?php echo esc_attr( $css['color_menu_clinks'] ); ?> !important;
				<?php if ( ! empty( $css['menu_no_deco'] ) ) : ?>
					text-decoration: none;
				<?php endif; ?>
			}

			<?php if ( ! empty( $css['menu_no_deco'] ) ) : ?>
				body.sticky-header <?php echo $selector; ?> .primary-menu a,
				body.sticky-header <?php echo $selector; ?> nav a,
				body.sticky-header <?php echo $selector; ?> nav a:focus,
				body.sticky-header <?php echo $selector; ?> nav a:hover,
				body.sticky-header <?php echo $selector; ?> nav .current-menu-item > a,
				body.sticky-header <?php echo $selector; ?> nav .current_page_ancestor > a {
					text-decoration: none !important;
				}
			<?php endif; ?>

			<?php do_action( 'sh2020_pro_css', 3 ); ?>

			<?php if ( ! empty( $css['hide_label_menu'] ) ) : ?>
				body.sticky-header <?php echo $selector; ?> .toggle.nav-toggle .toggle-text {
					display:none;
				}
			<?php endif; ?>

			<?php if ( ! empty( $css['hide_label_search'] ) ) : ?>
				body.sticky-header <?php echo $selector; ?> .toggle.search-toggle .toggle-text {
					display:none;
				}
			<?php endif; ?>

			<?php
			if ( $is_2021 ) {
				?>
				body.sticky-header <?php echo $selector; ?> {
					box-shadow: none;
				}

				body.sticky-header .menu-button-container #primary-mobile-menu {
					align-items: center;
					height: <?php echo esc_attr( $css['height_mobile'] ); ?>;
				}

				body.sticky-header.primary-navigation-open <?php echo $selector; ?> #site-navigation {
					padding-top: 0;
				}
				<?php
			}
			?>

			<?php
			if ( $is_2022 || $is_2023 || $is_2024 ) {
				?>
				body.sticky-header <?php echo $selector; ?> {
					align-content: center;
					display: grid;
					<?php
					if ( $is_2022 ) {
						?>
						margin-left: calc(-1 * var(--wp--custom--spacing--outer));
						<?php
					}
					?>
				}

				body.sticky-header <?php echo $selector; ?> > .wp-block-group-is-layout-flex {
					width: 100%;
				}

				@media (max-width: 782px) {
					body.sticky-header <?php echo $selector; ?> > .wp-block-group-is-layout-flex {
						gap: 1rem;
						padding: 0 var(--wp--custom--spacing--outer);
					}
				}
				<?php
			}
			?>

			<?php
			if ( $is_hello_elementor ) {
				?>
				body.sticky-header <?php echo $selector; ?> {
					align-content: center;
				}

				body.sticky-header <?php echo $selector; ?>.site-header {
					padding-block-end: 0 !important;
					padding-block-start: 0 !important;
				}
				<?php
			}
			?>

			<?php
			// phpcs:enable
		}

		/**
		 * Update the custom CSS file from the computed customizer CSS and the update version,
		 * so this can be enqueeued as any stylesheet.
		 */
		public static function update_custom_styles() {
			// Update the option that will be used as the custom CSS version.
			update_option( 'sticky-header-2020-last-update', gmdate( 'Ymd.His', time() ) );

			// Actually compute the content to be saved as the custom CSS.
			ob_start();
			self::output_custom_styles();
			$content = ob_get_clean();

			// Minify the output.
			$content = self::custom_minify( $content, true );

			// Write the custom CSS content to the database.
			update_option( 'sticky-header-2020-styles', $content );
		}

		/**
		 * Output custom styles from settings.
		 */
		public static function output_custom_scripts() {
			$selector = get_theme_mod( 'sh2020_header_sticky_selector', self::DEFAULT_SELECTOR );
			if ( empty( $selector ) ) {
				return;
			}

			$selector      = wp_strip_all_tags( $selector );
			$height        = get_theme_mod( 'sh2020_header_sticky_min_height', self::DEFAULT_HEIGHT );
			$height_mini   = get_theme_mod( 'sh2020_header_sticky_min_height_minified', self::DEFAULT_HEIGHT_MINI );
			$height_mobile = get_theme_mod( 'sh2020_header_sticky_min_height_mobile', self::DEFAULT_HEIGHT_MOBILE );
			$scroll        = min( (int) $height, (int) $height_mini, (int) $height_mobile );
			$scroll        = ( empty( $scroll ) || ( ! empty( $scroll ) && $scroll > 110 ) ) ? 110 : $scroll;

			// phpcs:disable
			?>
			/* Script to toggle the custom sticky header class on scroll */
			let stk_scroll_pos = 0;
			let stk_ticking = false;
			let eventMin = new CustomEvent( 'stickyHeaderMinified', {
				done: true
			} );
			let eventMax = new CustomEvent( 'stickyHeaderMaxified', {
				done: true
			} );

			function toggleLogoClass( scroll_pos ) {
				setTimeout( function () {
					let sel = document.querySelector( '<?php echo $selector; ?>' );
					if ( sel ) {
						/* Do something with the scroll position. */
						if ( scroll_pos > <?php echo (int) $scroll; ?> ) {
							if ( ! sel.classList.contains( 'sticky-header-minified' ) ) {
								sel.classList.add( 'sticky-header-minified' );
								document.getElementsByTagName( 'body' )[0].classList.add(
									'with-sticky-header-minified'
								);
								window.dispatchEvent( eventMin );
							}
						} else {
							if ( sel.classList.contains( 'sticky-header-minified' ) ) {
								sel.classList.remove( 'sticky-header-minified' );
								document.getElementsByTagName( 'body' )[0].classList.remove(
									'with-sticky-header-minified'
								);
								window.dispatchEvent( eventMax );
							}
						}
					}
				}, 200 );
			}

			window.addEventListener( 'scroll', function ( e ) {
				stk_scroll_pos = window.scrollY;
				if ( ! stk_ticking ) {
					window.requestAnimationFrame( function () {
						toggleLogoClass( stk_scroll_pos );
						stk_ticking = false;
					} );
					stk_ticking = true;
				}
			} );

			/* Listen for scroll to top click */
			const sh2020A = document.querySelectorAll( 'a[href="#site-header"]' );
			if ( sh2020A ) {
				sh2020A.forEach( item => {
					item.addEventListener( 'click', event => {
						document.body.scrollTop = 0;
						document.documentElement.scrollTop = 0;
					} );
				} );
			}
			<?php
			// phpcs:enable
		}

		/**
		 * Update the custom CSS file from the computed customizer CSS and the update version,
		 * so this can be enqueeued as any stylesheet.
		 */
		public static function update_custom_scripts() {
			// Update the option that will be used as the custom CSS version.
			update_option( 'sticky-header-2020-last-update', gmdate( 'Ymd.His', time() ) );

			// Actually compute the content to be saved as the custom CSS.
			ob_start();
			self::output_custom_scripts();
			$content = ob_get_clean();

			// Minify the output.
			$content = self::custom_minify( $content );

			// Write the custom scripts content to the database.
			update_option( 'sticky-header-2020-scripts', $content );
		}

		/**
		 * Custom minify content.
		 *
		 * @param  string  $content String to be minified.
		 * @param  boolean $is_css  String to CSS or not.
		 * @return string
		 */
		public static function custom_minify( string $content, bool $is_css = false ): string {
			// Minify the output.
			$content = trim( $content );

			// Remove space after colons.
			$content = str_replace( ': ', ':', $content );

			// Remove whitespace.
			$content = str_replace( array( "\r\n", "\r", "\n", "\t" ), '', $content ); // phpcs:ignore

			// Remove spaces that might still be left where we know they aren't needed.
			$content = preg_replace( '/\s*([\{\}>~:;,])\s*/', '$1', $content );

			if ( true === $is_css ) {
				// Remove last semi-colon in a block.
				$content = preg_replace( '/;\}/', '}', $content );
			}

			return $content;
		}

		/**
		 * Update the inline assets after the theme was changed.
		 */
		public static function on_switch_theme() {
			self::update_custom_styles();
			self::update_custom_scripts();
		}

		/**
		 * Enqueue the computed custom CSS and the custom scripts (this is generated by the customizer),
		 * so we don't compute this for each page load.
		 */
		public static function enqueue_custom_styles() {
			$ver    = get_option( 'sticky-header-2020-last-update', STICKY_HEADER_2020_VER );
			$style  = get_option( 'sticky-header-2020-styles' );
			$script = get_option( 'sticky-header-2020-scripts' );

			wp_register_style( 'sticky-header-2020-custom', false, [], $ver, false );
			wp_enqueue_style( 'sticky-header-2020-custom' );
			wp_add_inline_style( 'sticky-header-2020-custom', $style );

			wp_register_script( 'sticky-header-2020-custom', '', [], 1, true );
			wp_enqueue_script( 'sticky-header-2020-custom' );
			wp_add_inline_script( 'sticky-header-2020-custom', $script );
		}

		/**
		 * Add the body sticky header class if the theme option is enabled.
		 *
		 * @param  array $classes Body classes.
		 * @return array
		 */
		public static function sticky_header_2020_class( array $classes ): array {
			$use_sticky = get_theme_mod( 'sh2020_enable_header_sticky' );
			if ( ! empty( $use_sticky ) ) {
				$classes[] = 'sticky-header';
			}
			return $classes;
		}

		/**
		 * Execute actions on activate plugin.
		 */
		public static function activate_plugin() {
			set_theme_mod( 'sh2020_enable_header_sticky', true );
			set_theme_mod( 'sh2020_header_sticky_selector', self::get_default_selector() );
			self::update_custom_styles();
			self::update_custom_scripts();

			set_transient( self::PLUGIN_TRANSIENT, true );
			set_transient( self::PLUGIN_TRANSIENT . '_adons_notice', true );
		}

		/**
		 * Execute cleanup optinos.
		 */
		public static function delete_mods() {
			$mods = get_theme_mods();
			if ( ! empty( $mods ) ) {
				foreach ( $mods as $k => $v ) {
					if ( substr_count( $k, 'sh2020_' ) ) {
						remove_theme_mod( $k );
					}
				}
			}
			delete_option( 'sticky-header-2020-last-update' );
			delete_option( 'sticky-header-2020-styles' );
			delete_option( 'sticky-header-2020-scripts' );
		}

		/**
		 * Execute cleanup actions on activate plugin.
		 */
		public static function deactivate_plugin() {
			$remove = get_theme_mod( 'sh2020_enable_header_sticky_keep_setting', false );
			if ( true === $remove ) {
				self::delete_mods();
				self::admin_notices_cleanup( false );
			}
		}

		/**
		 * Execute reset to defaults.
		 */
		public static function reset_to_default() {
			self::delete_mods();
			set_theme_mod( 'sh2020_header_sticky_selector', self::get_default_selector() );

			if ( self::theme_is_2021() ) {
				set_theme_mod( 'sh2020_enable_header_sticky_full', true );
			}

			self::update_custom_styles();
			self::update_custom_scripts();
		}

		/**
		 * Execute notices cleanup.
		 *
		 * @param boolean $ajax Is AJAX call.
		 */
		public static function admin_notices_cleanup( $ajax = true ) { // phpcs:ignore
			// Delete transient, only display this notice once.
			delete_transient( self::PLUGIN_TRANSIENT );

			if ( true === $ajax ) {
				// No need to continue.
				wp_die();
			}
		}

		/**
		 * Load the plugin assets.
		 */
		public static function load_assets() {
			$uri = ( ! empty( $_SERVER['REQUEST_URI'] ) ) ? $_SERVER['REQUEST_URI'] : ''; // phpcs:ignore
			if ( ! substr_count( $uri, 'page=sh2020-settings' ) ) {
				// Fail-fast, we only add assets to this page.
				return;
			}

			if ( file_exists( SH2020_PLUGIN_DIR . 'build/style-index.css' ) ) {
				$ver = get_option( 'sticky-header-2020-last-update', STICKY_HEADER_2020_VER );
				wp_enqueue_style(
					SH2020_PLUGIN_SLUG,
					SH2020_PLUGIN_URL . 'build/style-index.css',
					[],
					$ver,
					false
				);
			}
		}

		/**
		 * Add the new menu in settings section that allows to configure the restriction.
		 */
		public static function admin_menu() {
			add_submenu_page(
				'options-general.php',
				esc_html__( 'Sticky Header 2020', 'sh2020' ),
				esc_html__( 'Sticky Header 2020', 'sh2020' ),
				'manage_options',
				'sh2020-settings',
				[ get_called_class(), 'sh2020_settings' ]
			);
		}

		/**
		 * Show the current settings and allow you to change the settings.
		 */
		public static function sh2020_settings() {
			// Verify user capabilities in order to deny the access if the user does not have the capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Action not allowed.', 'sh2020' ) );
			}

			$customizer = 'sh2020_sticky_header_options';
			$plugin_url = self::LINK_LIGHT_VERSION;
			$hints      = get_theme_mod( 'sh2020_show_hints' );
			if ( self::is_pro() ) {
				if ( true === self::$show_pro ) {
					$customizer = true === self::$show_pro
						? 'sh2020_sticky_header_options_pro'
						: 'sh2020_sticky_header_options_pro_simple';
				}
				$plugin_url = self::LINK_PRO_VERSION;
			}

			$settings = [];
			$mods     = get_theme_mods();
			if ( ! empty( $mods ) ) {
				foreach ( $mods as $k => $v ) {
					if ( substr_count( $k, 'sh2020_' ) ) {
						$settings[ str_replace( 'sh2020_', '', $k ) ] = $v;
					}
				}
				$settings = wp_json_encode( $settings );
			} else {
				$settings = '';
			}
			?>
			<div class="wrap sh2020-feature">
				<h1 class="plugin-title">
					<span>
						<span class="dashicons dashicons-admin-appearance"></span>
						<span class="h1"><?php esc_html_e( 'Sticky Header 2020 Settings', 'sh2020' ); ?></span>
					</span>
					<span><?php self::show_donate_text(); ?></span>
				</h1>

				<div class="tab-content-wrap">
					<form action="<?php echo esc_url( admin_url( 'options-general.php?page=sh2020-settings' ) ); ?>" method="POST">
						<?php wp_nonce_field( '_sh2020_settings_save', '_sh2020_settings_nonce' ); ?>

						<?php
						if ( ! self::is_pro() ) {
							self::pro_teaser();
						}
						do_action( 'sh2020_pro_tabs_content' );
						?>

						<div class="rows dense">
							<div class="span4">
								<h3><?php esc_html_e( 'Customize', 'sh2020' ); ?></h3>
								<p><?php esc_html_e( 'Click the settings button to open the customizer and adjust the sticky header settings while previewing the changes.', 'sh2020' ); ?></p>
								<a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=' . $customizer ) ); ?>" class="button button-primary"><?php esc_html_e( 'Settings', 'sh2020' ); ?></a>

								<p><hr></p>
								<h3><?php esc_html_e( 'Reset', 'sh2020' ); ?></h3>
								<p>
									<?php esc_html_e( 'Click to reset to default all the settings of this plugin (this will also turn off the sticky header and the styles will not override the theme defaults).', 'sh2020' ); ?>
								</p>
								<button type="submit" name="reset" value="on" class="button">
									<?php esc_html_e( 'Reset', 'sh2020' ); ?>
								</button>

								<p><hr></p>
								<h3><?php esc_html_e( 'Hints', 'sh2020' ); ?></h3>
								<p><?php esc_html_e( 'If you want to see more about the pro settings, you can enable the hints (these show up in the customizer section).', 'sh2020' ); ?></p>

								<?php
								$hints = get_theme_mod( 'sh2020_show_hints' );
								if ( ! empty( $hints ) ) {
									?>
									<button type="submit" name="hints" value="hide" class="button">
										<?php esc_html_e( 'Hide Hints', 'sh2020' ); ?>
									</button>
									<?php
								} else {
									?>
									<button type="submit" name="hints" value="show" class="button">
										<?php esc_html_e( 'Show Hints', 'sh2020' ); ?>
									</button>
									<?php
								}
								?>
							</div>

							<div class="span4">
								<h3><?php esc_html_e( 'Export', 'sh2020' ); ?></h3>
								<textarea class="wide" rows="5"><?php echo $settings; // phpcs:ignore ?></textarea>
								<?php esc_html_e( 'The JSON string can be copied and imported into another instance.', 'sh2020' ); ?>

								<p><hr></p>

								<h3><?php esc_html_e( 'Import', 'sh2020' ); ?></h3>
								<textarea name="sh2020-import" class="wide" rows="6"></textarea>
								<?php esc_html_e( 'Paste the JSON string then click the button to import the settings.', 'sh2020' ); ?>
								<p><?php submit_button( __( 'Import', 'sh2020' ), '', '', false ); ?></p>
							</div>

							<div class="span4">
								<h3><?php esc_html_e( 'Demo', 'sh2020' ); ?></h3>

								<iframe width="100%" height="260" src="https://www.youtube.com/embed/0l86zWlFuzU" title="Sticky Header 2020 - Demo" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>

								<p><hr></p>
								<h3><?php esc_html_e( 'Read more', 'sh2020' ); ?></h3>
								<p>
									<?php
									echo wp_kses_post( sprintf(
										// Translators: %1$s - link, %2$s - end link, %3$s - link, %4$s - end link.
										__( 'If you want to see more about this plugin, here are some links to the %1$s version%2$s and the %3$sfree version%4$s.', 'sh2020' ),
										'<a href="' . self::LINK_PRO_VERSION . '" target="_blank">' . self::PRO_LABEL,
										'</a>',
										'<a href="' . self::LINK_LIGHT_VERSION . '" target="_blank">',
										'</a>'
									) );
									?>
								</p>
							</div>
						</div>
					</form>
				</div>
			</div>
			<?php
		}

		/**
		 * Maybe execute the options update if the nonce is valid, then redirect.
		 */
		public static function maybe_save_settings() {
			$nonce = filter_input( INPUT_POST, '_sh2020_settings_nonce', FILTER_DEFAULT );
			if ( ! empty( $nonce ) ) {
				if ( ! wp_verify_nonce( $nonce, '_sh2020_settings_save' ) ) {
					wp_die( esc_html__( 'Action not allowed.', 'sh2020' ), esc_html__( 'Security Breach', 'sh2020' ) );
				}

				$maybe_reset = filter_input( INPUT_POST, 'reset' );
				if ( ! empty( $maybe_reset ) ) {
					self::reset_to_default();
					self::add_admin_notice( esc_html__( 'The settings were reset to default.', 'sh2020' ) );
				}

				$maybe_hints = filter_input( INPUT_POST, 'hints' );
				if ( 'show' === $maybe_hints ) {
					set_theme_mod( 'sh2020_show_hints', true );
					self::add_admin_notice( esc_html__( 'The hints are turned on.', 'sh2020' ) );
				} elseif ( 'hide' === $maybe_hints ) {
					set_theme_mod( 'sh2020_show_hints', false );
					self::add_admin_notice( esc_html__( 'The hints are turned off.', 'sh2020' ) );
				}

				do_action( 'sh2020_pro_save_settings' );
				do_action( 'sh2020_after_save_settings' );
				wp_safe_redirect( esc_url( admin_url( 'options-general.php?page=sh2020-settings' ) ) );
				exit;
			}
		}

		/**
		 * PRO teaser.
		 *
		 * @param string $type Teaser type.
		 */
		public static function pro_teaser( $type = 'regular' ) { // phpcs:ignore
			?>
			<div class="rows">
				<div class="span2">
					<?php if ( 'regular' === $type ) : ?>
						<h2><?php esc_html_e( 'You are using the free version.', 'sh2020' ); ?></h2>
						<p>
							<?php
							echo wp_kses_post( sprintf(
								// Translators: %1$s - extensions URL.
								__( 'Click the button to see more and get the <a class="pro-item button button-primary" href="%1$s" target="_blank">version</a> of the plugin!', 'sh2020' ),
								self::LINK_PRO_VERSION
							) );
							?>
						</p>
					<?php else : ?>
						<h2><?php esc_html_e( 'You are using the PRO version.', 'sh2020' ); ?></h2>
						<p>
							<?php esc_html_e( 'It seems that you either did not input yet your license key, or that is not valid or has expired already.', 'sh2020' ); ?>

							<?php
							echo wp_kses_post( sprintf(
								// Translators: %1$s - extensions URL.
								__( 'Click the button to get a valid license key for the <a class="pro-item button button-primary" href="%1$s" target="_blank">version</a> of the plugin!', 'sh2020' ),
								self::LINK_PRO_VERSION
							) );
							?>
						</p>
					<?php endif; ?>
				</div>

				<div class="span2">
					<h2><?php esc_html_e( 'Sticky Header 2020', 'sh2020' ); ?></h2>
					<p>
						<?php esc_html_e( 'This plugin appends custom functionality to the native customizer, and provides the settings for making the header sticky, with settings for scroll minification, background, spacing, text, menu and icons colors, etc.', 'sh2020' ); ?>
					</p>
					<img src="<?php echo esc_url( SH2020_PLUGIN_URL . 'assets/images/banner-772x250.jpeg' ); ?>" loading="lazy">
				</div>

				<div class="span2">
					<h2><?php esc_html_e( 'The PRO version includes additional useful features', 'sh2020' ); ?></h2>
					<ol>
						<li><?php esc_html_e( 'Header Elements Color', 'sh2020' ); ?></li>
						<li><?php esc_html_e( 'Links & Submenu Settings', 'sh2020' ); ?></li>
						<li><?php esc_html_e( 'The header shadow option', 'sh2020' ); ?></li>
					</ol>
				</div>
			</div>
			<?php
		}

		/**
		 * Maybe donate or rate.
		 */
		public static function show_donate_text() {
			?>
			<div>
				<?php
				$donate = 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ&item_name=Support for development and maintenance (' . rawurlencode( self::PLUGIN_NAME ) . ')';
				$thanks = __( 'A huge thanks in advance!', 'sh2020' );

				if ( ! self::is_pro() ) {
					echo wp_kses_post( sprintf(
							// Translators: %1$s - donate URL, %2$s - rating, %3$s - thanks.
						__( 'If you find the plugin useful and would like to support my work, please consider making a <a href="%1$s" target="_blank">donation</a>. <br>It would make me very happy if you would leave a %2$s rating. %3$s', 'sh2020' ),
						$donate,
						'<a href="' . self::PLUGIN_SUPPORT_URL . 'reviews/?rate=5#new-post" class="rating" target="_blank" rel="noreferrer" title="' . esc_attr( $thanks ) . '">★★★★★</a>',
						$thanks
					) );
				} else {
					echo wp_kses_post( sprintf(
						// Translators: %1$s - 5 stars, %2$s - thanks.
						__( 'It would make me very happy if you would leave a %1$s rating. %2$s', 'sh2020' ),
						'<a href="' . self::PLUGIN_SUPPORT_URL . 'reviews/?rate=5#new-post" class="rating" target="_blank" title="' . esc_attr__( 'A huge thanks in advance!', 'sh2020' ) . '">★★★★★</a>',
						$thanks
					) );
				}
				?>
			</div>
			<img src="<?php echo esc_url( SH2020_PLUGIN_URL . 'assets/images/icon-128x128.gif' ); ?>" width="32" height="32" alt="">
			<?php
		}

		/**
		 * Admin notices.
		 */
		public static function admin_notices() {
			if ( apply_filters( 'sh2020_filter_remove_update_info', false ) ) {
				return;
			}

			$maybe_trans = get_transient( self::PLUGIN_TRANSIENT );
			if ( ! empty( $maybe_trans ) ) {
				$slug      = md5( SH2020_PLUGIN_SLUG );
				$title     = __( 'Sticky Header 2020', 'sh2020' );
				$donate    = 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ&item_name=Support for development and maintenance (' . rawurlencode( self::PLUGIN_NAME ) . ')';
				$thanks    = __( 'A huge thanks in advance!', 'sh2020' );
				$maybe_pro = '';

				if ( empty( self::is_pro() ) ) {
					$maybe_pro = sprintf(
						// Translators: %1$s - extensions URL.
						__( 'You are using the free version. Get the <a href="%1$s" target="_blank"><b>PRO</b> version</a>. ', 'sh2020' ),
						self::LINK_PRO_VERSION
					) . '<br>';
				} else {
					$maybe_pro = sprintf(
						// Translators: %1$s - pro version label, %2$s - PRO URL.
						__( 'Thank you for purchasing the <a href="%1$s" target="_blank"><b>PRO</b> version</a>! ', 'sh2020' ),
						self::LINK_PRO_VERSION
					) . '<br>';
				}

				$other_notice = sprintf(
					// Translators: %1$s - plugins URL, %2$s - heart icon, %3$s - extensions URL, %4$s - star icon, %5$s - maybe PRO details.
					__( '%5$sCheck out my other <a href="%1$s" target="_blank" rel="noreferrer">%2$s free plugins</a> on WordPress.org and the <a href="%3$s" target="_blank" rel="noreferrer">%4$s other extensions</a> available!', 'sh2020' ),
					'https://profiles.wordpress.org/iulia-cazan/#content-plugins',
					'<span class="dashicons dashicons-heart"></span>',
					'https://iuliacazan.ro/shop/',
					'<span class="dashicons dashicons-star-filled"></span>',
					$maybe_pro
				);
				?>

				<div id="item-<?php echo esc_attr( $slug ); ?>" class="updated notice">
					<div class="icon">
						<a href="<?php echo esc_url( admin_url( 'options-general.php?page=sh2020-settings' ) ); ?>"><img src="<?php echo esc_url( SH2020_PLUGIN_URL . 'assets/images/icon-128x128.gif' ); ?>"></a>
					</div>
					<div class="content">
						<div>
							<h3>
								<?php
								echo wp_kses_post( sprintf(
									// Translators: %1$s - plugin name.
									__( '%1$s plugin was activated!', 'sh2020' ),
									'<b>' . $title . '</b>'
								) );
								?>
							</h3>
							<div class="notice-other-items"><div><?php echo wp_kses_post( $other_notice ); ?></div></div>
						</div>
						<div>
							<?php
							echo wp_kses_post( sprintf(
									// Translators: %1$s - donate URL, %2$s - rating, %3$s - thanks.
								__( 'If you find the plugin useful and would like to support my work, please consider making a <a href="%1$s" target="_blank">donation</a>. <br>It would make me very happy if you would leave a %2$s rating. %3$s', 'sh2020' ),
								$donate,
								'<a href="' . self::PLUGIN_SUPPORT_URL . 'reviews/?rate=5#new-post" class="rating" target="_blank" rel="noreferrer" title="' . esc_attr( $thanks ) . '">★★★★★</a>',
								$thanks
							) );
							?>
						</div>
						<a class="notice-plugin-donate" href="<?php echo esc_url( $donate ); ?>" target="_blank"><img src="<?php echo esc_url( SH2020_PLUGIN_URL . 'assets/images/buy-me-a-coffee.png?v=' . STICKY_HEADER_2020_VER ); ?>" width="200"></a>
					</div>
					<div class="action">
						<div class="dashicons dashicons-no" onclick="dismiss_notice_for_<?php echo esc_attr( $slug ); ?>()"></div>
					</div>
				</div>
				<?php
				$style = '
				#trans123super{--color-bg:rgba(140,78,164,0.1); --color-border:rgb(140,78,164); display:grid; padding:0; gap:0; grid-template-columns:6rem auto 3rem; max-width:100%; width:100%; border-left-color: var(--color-border); box-sizing:border-box;} #trans123super .dashicons-no{font-size:2rem; cursor:pointer;} #trans123super .icon{ display:grid; align-content:start; background-color:var(--color-bg); padding: 1rem} #trans123super .icon img{object-fit:cover; object-position:center; width:100%; display:block} #trans123super .action{ display:grid; align-content:start; padding: 1rem 0.5rem} #trans123super .content{ align-items: center; display: grid; gap: 1rem; grid-template-columns: 1fr 1fr 12rem; padding: 1rem;} #trans123super .content br {display: none} #trans123super .content .dashicons{color:var(--color-border);} #trans123super .content > div{color:#666;} #trans123super h3{margin:0 0 0.1rem 0;color:#666} #trans123super h3 b{color:#000} #trans123super a{color:#000;text-decoration:none;} #trans123super .notice-plugin-donate img{max-width: 100%;} @media all and (max-width: 1024px) {#trans123super .content{grid-template-columns:100%;}}';
				$style = str_replace( '#trans123super', '#item-' . esc_attr( $slug ), $style );
				echo '<style>' . $style . '</style>'; // phpcs:ignore
				?>
				<script>function dismiss_notice_for_<?php echo esc_attr( $slug ); ?>() { document.getElementById( 'item-<?php echo esc_attr( $slug ); ?>' ).style='display:none'; fetch( '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>?action=plugin-deactivate-notice-<?php echo esc_attr( SH2020_PLUGIN_SLUG ); ?>' ); }</script>
				<?php
			}

			$items = get_option( SH2020_PLUGIN_SLUG . '_actions_notices', [] );
			if ( ! empty( $items ) ) {
				foreach ( $items as $item ) {
					?>
					<div class="notice <?php echo esc_attr( $item['type'] ); ?>">
						<p><?php echo wp_kses_post( $item['text'] ); ?></p>
					</div>
					<?php
				}
			}
			update_option( SH2020_PLUGIN_SLUG . '_actions_notices', [] );
		}

		/**
		 * Add admin notices.
		 *
		 * @param string $text  The text to be outputted as the admin notice.
		 * @param string $class The admin notice class (notice-success is-dismissible, notice-error).
		 */
		public static function add_admin_notice( $text, $class = 'notice-success is-dismissible' ) { // phpcs:ignore
			$items   = get_option( SH2020_PLUGIN_SLUG . '_actions_notices', [] );
			$items[] = [
				'type' => $class,
				'text' => $text,
			];
			update_option( SH2020_PLUGIN_SLUG . '_actions_notices', $items );
		}

		/**
		 * Load text domain for internalization.
		 */
		public static function load_textdomain() {
			load_plugin_textdomain( 'sh2020', false, basename( __DIR__ ) . '/langs/' );
		}

		/**
		 * Add the plugin settings and plugin URL links.
		 *
		 * @param  array $links The plugin links.
		 * @return array
		 */
		public static function plugin_action_links( $links ) { // phpcs:ignore
			$customizer = 'sh2020_sticky_header_options';
			$plugin_url = self::LINK_LIGHT_VERSION;
			if ( self::is_pro() ) {
				if ( true === self::$show_pro ) {
					$customizer = 'sh2020_sticky_header_options_pro';
				} else {
					$customizer = 'sh2020_sticky_header_options_pro_simple';
				}
				$plugin_url = self::LINK_PRO_VERSION;
			}

			$all   = [];
			$all[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=sh2020-settings' ) ) . '">' . esc_html__( 'Settings', 'sh2020' ) . '</a>';
			$all[] = '<a href="' . esc_url( admin_url( 'customize.php?autofocus[section]=' . $customizer ) ) . '" target="_blank">' . esc_html__( 'Cusomizer', 'sh2020' ) . '</a>';
			$all[] = '<a href="' . esc_url( $plugin_url ) . '">' . esc_html__( 'Plugin URL', 'sh2020' ) . '</a>';
			$all   = array_merge( $all, $links );
			return $all;
		}
	}
}

// Instantiate the class.
$sticky_header_2020 = Sticky_Header_2020::get_instance();

// Register activation and deactivation actions.
register_activation_hook( __FILE__, [ $sticky_header_2020, 'activate_plugin' ] );
register_deactivation_hook( __FILE__, [ $sticky_header_2020, 'deactivate_plugin' ] );

if ( file_exists( __DIR__ . '/pro-settings.php' ) ) {
	require_once __DIR__ . '/pro-settings.php';
}
