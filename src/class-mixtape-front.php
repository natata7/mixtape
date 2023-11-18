<?php

/**
 * [Description Mixtape]
 */
class Mixtape extends Mixtape_Abstract {

	/**
	 * @var [type]
	 */
	private static $instance;
	/**
	 * @var [type]
	 */
	private $is_appropriate_post;

	protected function __construct() {

		parent::__construct();

		// shortcode
		if ( 'yes' === $this->options['register_shortcode'] ) {
			add_shortcode( 'mixtape', array( $this, 'render_shortcode' ) );
		}

		if ( 'yes' === $this->options['first_run'] ) {
			return;
		}

		// Load textdomain
		$this->load_textdomain();

		// actions
		add_action( 'wp_footer', array( $this, 'insert_dialog' ), 1000 );
		add_action( 'wp_enqueue_scripts', array( $this, 'front_load_scripts_styles' ) );

		// filters
		add_filter( 'the_content', array( $this, 'append_caption_to_content' ), 1 );

		add_action( 'wp_print_styles', array( $this, 'custom_styles' ), 10 );
	}

	public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Handle shortcode
	 *
	 * @param mixed $atr mixed
	 *
	 * @return string
	 */
	public function render_shortcode( $atr ) {

		$atr = shortcode_atts(
			array(
				'format' => $this->options['caption_format'],
				'class'  => 'mixtape_caption',
				'image'  => '',
				'text'   => $this->get_caption_text(),
			),
			$atr,
			'mixtape'
		);

		if ( ( 'image' === $atr['format'] && ! empty( $this->options['caption_image_url'] ) ) ||
		( 'text' !== $atr['format'] && ! empty( $atr['image'] ) )
		) {
			$imagesrc = $atr['image'] ? $atr['image'] : $this->options['caption_image_url'];
			$output   = '<div class="' . $atr['class'] . '"><img src="' . $imagesrc . '" alt="' . $atr['text'] . '"></div>';
		} else {
			$icon_id      = (int) $this->options['show_logo_in_caption'];
			$icon_svg     = apply_filters( 'mixtape_get_icon', array( 'icon_id' => $icon_id ) );
			$icon_svg_str = '';

			if ( ! empty( $icon_svg['icon'] ) ) {
				$icon_svg_str = '<span class="mixtape-link-wrap"><span class="mixtape-link mixtape-logo">' . $icon_svg['icon'] . '</span></span>';
			}

			$output = '<div class="' . $atr['class'] . '">' . $icon_svg_str . '<p>' . $atr['text'] . '</p></div>';
		}

		return $output;
	}

	/**
	 * Load scripts and styles - frontend
	 */
	public function front_load_scripts_styles() {
		global $post;

		if ( ! $this->is_appropriate_post() && 'yes' !== $this->options['register_shortcode'] && ! has_shortcode( $post->post_content, 'mixtape' ) ) {
			return;
		} else {
			$this->enqueue_dialog_assets();
		}
	}

	/**
	 * Add Mixtape caption to post content
	 *
	 * @param mixed $content mixed
	 *
	 * @return string
	 */
	public function append_caption_to_content( $content ) {

		static $is_already_displayed_after_the_content = false;

		if ( true === $is_already_displayed_after_the_content ) {
			return $content;
		}

		$format = $this->options['caption_format'];

		if ( 'disabled' === $format ) {
			return $content;
		}

		if ( ! $this->is_appropriate_post() ) {
			return $content;
		}

		$output = '';

		$raw_post_content = get_the_content();

		// check if we really deal with post content
		if ( $content !== $raw_post_content ) {
			return $content;
		}

		if ( 'text' === $format ) {
			$icon_id      = (int) $this->options['show_logo_in_caption'];
			$icon_svg     = apply_filters( 'mixtape_get_icon', array( 'icon_id' => $icon_id ) );
			$icon_svg_str = '';
			if ( ! empty( $icon_svg['icon'] ) ) {
				$icon_svg_str = '<span class="mixtape-link-wrap"><span class="mixtape-link mixtape-logo">' . $icon_svg['icon'] . '</span></span>';
			}
			// Only text withot link plugin site!!
			$output = "\n" . '<div class="mixtape_caption">' . $icon_svg_str . '<p>' . $this->get_caption_text() . '</p></div>';
		} elseif ( 'image' === $format ) {
			$img_alt = strip_tags( $this->get_caption_text() );
			$img_alt = str_replace( array( "\r", "\n" ), '', $img_alt );
			$output  = "\n" . '<div class="mixtape_caption"><img src="' . $this->options['caption_image_url'] . '" alt="' . esc_attr( $img_alt ) . '"/></div>';
		}

		$is_already_displayed_after_the_content = true;

		return $content . $output;
	}

	/**
	 * Mixtape custom styles
	 */
	public function custom_styles() {
		echo '
		<style type="text/css">
			body {--mixtape--main-color:' . esc_html( $this->options['color_scheme'] ) . '; }
		</style>
		';
	}


	/**
	 * Mixtape dialog output
	 */
	public function insert_dialog() {

		if ( ! $this->is_appropriate_post() &&
			'yes' !== $this->options['register_shortcode'] ) {
			return;
		}

		// dialog output
		$output = $this->get_dialog_html();

		echo wp_kses_post( apply_filters( 'mixtape_dialog_output', $output, $this->options ) );
	}

	/**
	 * Exit early if user agent is unlikely to behave reasonable
	 *
	 * @return bool
	 */
	public static function is_appropriate_useragent() {
		if ( self::wp_is_mobile() ) {
			return false;
		}

		return true;
	}

	public function is_appropriate_post() {

		if ( null === $this->is_appropriate_post ) {
			$result = false;

			// a bit inefficient logic is necessary for some illogical themes and plugins
			if ( ( ( is_single() && in_array( get_post_type(), $this->options['post_types'] ) )
			       || ( is_page() && in_array( 'page', $this->options['post_types'] ) ) ) && ! post_password_required()
			) {
				$result = true;
			}

			// $this->is_appropriate_post = apply_filters( 'mixtape_is_appropriate_post', $result );

		}

		return $result;
	}


	/**
	 * Test if the current browser runs on a mobile device (smart phone, tablet, etc.)
	 *
	 * @staticvar bool $is_mobile
	 *
	 * @return bool
	 */
	public static function wp_is_mobile() {
		static $is_mobile = null;

		if ( \function_exists( 'wp_is_mobile' ) ) {
			$is_mobile = wp_is_mobile();
		}

		if ( $is_mobile ) {
			return $is_mobile;
		}

		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$is_mobile = false;
		} elseif ( strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 'Mobile' ) !== false // many mobile devices (all iPhone, iPad, etc.)
		           || strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 'Android' ) !== false
		           || strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 'Silk/' ) !== false
		           || strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 'Kindle' ) !== false
		           || strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 'BlackBerry' ) !== false
		           || strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 'Opera Mini' ) !== false
		           || strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 'Opera Mobi' ) !== false
		) {
			$is_mobile = true;
		} else {
			$is_mobile = false;
		}

		return $is_mobile;
	}
}
