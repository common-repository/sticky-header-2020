<?php
/**
 * RGBA Color Picker Customizer Control: this control adds a second slider that
 * allows to select the color opacity. The result is shown/recorded as rgba
 * color.
 *
 * @package sticky-header-2020
 */

/**
 * Class for colors control.
 */
class SH2020_Customize_RGBA_Color_Control extends WP_Customize_Control {
	/**
	 * Official control name.
	 *
	 * @var string
	 */
	public $type = 'sh2020-alpha-color';

	/**
	 * Add support for palettes to be passed in.
	 * Supported palette values are true, false, or an array of RGBa and Hex colors.
	 *
	 * @var string
	 */
	public $palette;

	/**
	 * Add support for showing the opacity value on the slider handle.
	 *
	 * @var boolean
	 */
	public $show_opacity;

	/**
	 * Enqueue scripts and styles.
	 *
	 * Ideally these would get registered and given proper paths before this control object
	 * gets initialized, then we could simply enqueue them here, but for completeness as a
	 * stand alone class we'll register and enqueue them here.
	 */
	public function enqueue() {
		wp_enqueue_script(
			'sh2020-rgba-color-picker',
			plugin_dir_url( __DIR__ ) . 'assets/js/alpha/alpha-color-picker.js',
			[ 'jquery', 'wp-color-picker' ],
			STICKY_HEADER_2020_VER,
			true
		);
		wp_enqueue_style(
			'sh2020-rgba-color-picker',
			plugin_dir_url( __DIR__ ) . 'assets/js/alpha/alpha-color-picker.css',
			[ 'wp-color-picker' ],
			STICKY_HEADER_2020_VER
		);
	}

	/**
	 * Render the control.
	 */
	public function render_content() {
		// Process the palette.
		if ( is_array( $this->palette ) ) {
			$palette = implode( '|', $this->palette );
		} else {
			// Default to true.
			$palette = ( false === $this->palette || 'false' === $this->palette ) ? 'false' : 'true';
		}

		// Support passing show_opacity as string or boolean. Default to true.
		$show_opacity = ( false === $this->show_opacity || 'false' === $this->show_opacity ) ? 'false' : 'true';

		?>
		<?php if ( ! empty( $this->label ) ) : ?>
			<label for="_customize-input-<?php echo esc_attr( $this->id ); ?>" class="customize-control-title"><?php echo wp_kses_post( $this->label ); ?></label>
		<?php endif; ?>

		<?php if ( ! empty( $this->description ) ) : ?>
			<span class="description customize-control-description"><?php echo wp_kses_post( $this->description ); ?></span>
		<?php endif; ?>

		<input
			class="sh2020-alpha-color-control"
			type="text"
			data-show-opacity="<?php echo esc_attr( $show_opacity ); ?>"
			data-palette="<?php echo esc_attr( $palette ); ?>"
			data-default-color="<?php echo esc_attr( $this->settings['default']->default ); ?>"
			<?php $this->link(); ?> />

		<?php
	}
}
