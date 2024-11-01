<?php
/**
 * Simple Text Customizer Control: this control adds a simple text as a
 * description where you need to place it in the customized.
 *
 * @package sticky-header-2020
 */

/**
 * Class for colors control.
 */
class SH2020_Customize_Simple_Text_Control extends WP_Customize_Control {
	/**
	 * Control slug.
	 *
	 * @var string
	 */
	public $type = 'simple-text';

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue() {
		wp_enqueue_style(
			'sh2020-simple-text-control',
			plugin_dir_url( __DIR__ ) . 'build/style-index.css',
			[],
			STICKY_HEADER_2020_VER
		);
	}

	/**
	 * Render the custom attributes for the control's input element.
	 */
	public function input_attrs() {
		$found = false;
		foreach ( $this->input_attrs as $attr => $value ) {
			if ( 'class' === $attr ) {
				$found  = true;
				$value .= ' hidden-simple-text';
			}
			echo esc_attr( $attr ) . '="' . esc_attr( $value ) . '" ';
		}
		if ( false === $found ) {
			echo ' class="hidden-simple-text" ';
		}
	}

	/**
	 * Render the control.
	 */
	public function render_content() {
		?>
		<?php if ( ! empty( $this->label ) ) : ?>
			<h2>
				<?php echo wp_kses_post( $this->label ); ?>
			</h2>
		<?php endif; ?>
		<?php if ( ! empty( $this->description ) ) : ?>
			<?php echo wp_kses_post( $this->description ); ?>
		<?php endif; ?>
		<?php
	}
}
