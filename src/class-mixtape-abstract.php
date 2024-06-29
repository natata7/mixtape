<?php

/**
 * [Description Mixtape_Abstract]
 */
abstract class Mixtape_Abstract {


	const DB_TABLE = 'mixtape_reports';
	const IP_BANLIST_OPTION = 'mixtape_ip_banlist';

	/**
	 * @var $defaults
	 */
	protected static $defaults = array(
		'email_recipient'          => array(
			'type'              => 'admin',
			'id'                => '1',
			'email'             => '',
			'post_author_first' => 'yes',
		),
		'post_types'               => array(),
		'register_shortcode'       => 'no',
		'caption_format'           => 'text',
		'caption_text_mode'        => 'default',
		'custom_caption_text'      => '',
		'dialog_mode'              => 'confirm',
		'caption_image_url'        => '',
		'show_logo_in_caption'     => 0,
		'first_run'                => 'yes',
		'multisite_inheritance'    => 'no',
		'plugin_updated_timestamp' => null,
	);
	/**
	 * @var [type]
	 */
	protected static $abstract_constructed;
	/**
	 * @var array
	 */
	protected static $supported_addons = array( 'mixtape-table-addon' );
	/**
	 * @var [type]
	 */
	protected static $plugin_path;
	/**
	 * @var string
	 */
	public static $version = '1.0.0';
	/**
	 * @var [type]
	 */
	public $recipient_email;
	/**
	 * @var array
	 */
	public $email_recipient_types = array();
	/**
	 * @var array
	 */
	public $caption_formats = array();
	/**
	 * @var array
	 */
	public $dialog_modes = array();
	/**
	 * @var array
	 */
	public $post_types = array();
	/**
	 * @var array
	 */
	public $options = array();
	/**
	 * @var [type]
	 */
	public $default_caption_text;
	/**
	 * @var [type]
	 */
	public $caption_text;
	/**
	 * @var [type]
	 */
	public $caption_text_modes;
	/**
	 * @var [type]
	 */
	public $success_text;
	/**
	 * @var [type]
	 */
	public $ip_banlist;

	/**
	 * Constructor
	 */
	protected function __construct() {
		if ( ! self::$abstract_constructed ) {
			self::$plugin_path = MIXTAPE__PLUGIN_DIR . '/mixtape.php';
			// settings
			$this->options = self::get_options();
			self::load_filters();

			// actions
			do_action( 'mixtape_init_addons', $this );
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

			// plugin update
			add_action( 'upgrader_process_complete', array( $this, 'version_upgrade' ), 10, 2 );
		}
	}

	public static function load_filters() {
		add_filter( 'mixtape_get_icon', __CLASS__ . '::icons_in_content', 10 );
	}

	public static function get_options() {
		$options = get_option( 'mixtape_options', self::$defaults );
		$options = is_array( $options ) ? $options : array();

		return apply_filters( 'mixtape_options', array_merge( self::$defaults, $options ) );
	}

	public function get_caption_text() {
        if ( empty( $this->caption_text ) ) {
            if ( 'custom' === $this->options['caption_text_mode'] && isset( $this->options['custom_caption_text'] ) ) {
                $text = $this->options['custom_caption_text'];
            } else {
                $text = $this->get_default_caption_text();
            }

            $this->caption_text = apply_filters( 'mixtape_caption_text', $text );
        }

        return $this->caption_text;
    }

	public function get_default_caption_text() {
		if ( is_null( $this->default_caption_text ) ) {
			$this->default_caption_text = __( 'If you have found a spelling error, please, notify us by selecting that text and pressing <em>Ctrl+Enter</em>.', 'mixtape' );
		}

		return $this->default_caption_text;
	}

	/**
	 * Load textdomain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'mixtape', false, MIXTAPE__PLUGIN_FOLDER . '/languages' );
   }

	/**
	 * Mixtape dialog output
     *
	 * @return string
	 */
	public function get_dialog_html( $args = array() ) {

		$mode     = isset( $args['mode'] ) ? $args['mode'] : $this->options['dialog_mode'];
		$defaults = array(
			'wrap'    => true,
			'mode'    => $mode,
			'dry_run' => is_admin() ? '1' : '0',
		);

		if ( 'notify' === $mode ) {
			$defaults['title']   = __( 'Thanks!', 'mixtape' );
			$defaults['message'] = __( 'Our editors are notified.', 'mixtape' );
			$defaults['close']   = __( 'Close', 'mixtape' );
		} else {
			$defaults['reported_text']         = '';
			$defaults['context']               = '';
			$defaults['title']                 = __( 'Spelling error report', 'mixtape' );
			$defaults['message']               = __( 'The following text will be sent to our editors:', 'mixtape' );
			$defaults['reported_text_preview'] = '';
			$defaults['send']                  = __( 'Send', 'mixtape' );
		}

		if ( 'comment' === $mode ) {
			$defaults['comment_label'] = __( 'Your comment (optional)', 'mixtape' );
		}

		$args = apply_filters( 'mixtape_dialog_args', wp_parse_args( $args, $defaults ) );

		// Get real post_ID
		$post_id = get_the_ID();
		$nonce = wp_create_nonce( 'mixtape_nonce' );
		// begin
		$output = '';
		if ( $args['wrap'] ) {
			$output .= '<div id="mixtape_dialog" data-nonce="' . esc_attr( $nonce ) . '" data-mode="' . esc_attr( $args['mode'] ) .
				'" data-dry-run="' . esc_attr( (string) $args['dry_run'] ) . '">
			           <div class="dialog__overlay"></div><div class="dialog__content' .
				( 'comment' !== $args['mode'] ? ' without-comment' : '' ) . '">';
		}

		if ( 'notify' === $args['mode'] ) {
			$output .=
				'<div id="mixtape_success_dialog" class="mixtape_dialog_screen">
					<div class="dialog-wrap">
						<h2>' . $args['title'] . '</h2>
						 <h3>' . $args['message'] . '</h3>
					</div>
					<div class="mixtape_dialog_block">
						<a id="mixtape-close-btn" class="mixtape_action" data-dialog-close role="button">' . $args['close'] . '</a>
					</div>
				</div>';
		} else {
			$output .=
				'<div id="mixtape_confirm_dialog" class="mixtape_dialog_screen">
					<div class="dialog-wrap">
						<div class="dialog-wrap-top">
							<h2>' . $args['title'] . '</h2>
							 <div class="mixtape_dialog_block">' . '
								<h3>' . $args['message'] . '</h3>
								<div id="mixtape_reported_text">' . $args['reported_text_preview'] . '</div>
							 </div>
							 </div>
						<div class="dialog-wrap-bottom">';
			if ( 'comment' === $args['mode'] ) {
				$output .=
					'<div class="mixtape_dialog_block comment">
				        <h3><label for="mixtape_comment">' . $args['comment_label'] . ':</label></h3>
				        <textarea id="mixtape_comment" cols="60" rows="3" maxlength="1000"></textarea>
			         </div>';
			}
			$output .=
				'<div class="pos-relative">
						</div>
					</div>
			    </div>
	    	<div class="mixtape_dialog_btn_block">
					<a class="mixtape_action" data-action="send" data-id="' . $post_id . '" role="button">' . $args['send'] . '</a>
				</div>
				<div class="mixtape-letter-front letter-part">
				    <div class="front-left"></div>
				    <div class="front-right"></div>
				    <div class="front-bottom"></div>
				</div>
				<div class="mixtape-letter-back letter-part">
					<div class="mixtape-letter-back-top"></div>
				</div>
				<div class="mixtape-letter-top letter-part"></div>
				<div id="mixtape-close-btn" class="mixtape-close__btn"></div>
			</div>';
		}

		// end
		if ( $args['wrap'] ) {
			$output .= '</div></div>';
		}

		return $output;
	}

	public static function get_formatted_reported_text( $selection, $word = null, $replace_context = null, $context = null ) {
		$word            = $word ? $word : $selection;
		$replace_context = $replace_context ? $replace_context : $word;

		if (
			$context && $replace_context && $word
			&& false !== strpos( $word, $selection )
			&& false !== strpos( $replace_context, $word )
		) {
			$text_inner = str_replace( $selection, '<strong style="color: #C94E50;">' . $selection . '</strong>', $word );
			$text_outer = str_replace( $replace_context, '<span style="background-color: #EFEFEF;">' . $text_inner . '</span>', $word );
			$result     = str_replace( $replace_context, $text_outer, $context );
		} elseif ( isset( $context, $word ) && false !== strpos( $context, $word ) ) {
			$result = str_replace( $word, '<strong style="color: #C94E50; background-color: #EFEFEF;">' . $word . '</strong>', $context );
		} else {
			$result = $selection;
		}

		return $result;
	}

	public function is_ip_in_banlist( $ip ) {
		if ( $this->get_ip_banlist() ) {
			$banlist = $this->get_ip_banlist();
			if ( in_array( $ip, (array) $banlist ) ) {
				return true;
			}
		}

		return false;
	}

	public function get_ip_banlist() {
		if ( null === $this->ip_banlist ) {
			$this->ip_banlist = get_option( self::IP_BANLIST_OPTION, array() );
		}

		return $this->ip_banlist;
	}

	public function enqueue_dialog_assets() {
		// style
		wp_enqueue_style( 'mixtape-front', plugins_url( 'assets/css/mixtape-front.css', self::$plugin_path ), array(), self::$version );

		// modernizer
		//wp_enqueue_script( 'modernizr', plugins_url( 'assets/js/modernizr.custom.js', self::$plugin_path ), array( 'jquery' ), self::$version, true );

		// frontend script (combined)
		wp_enqueue_script(
			'mixtape-front',
			plugins_url( 'assets/js/mixtape-front.js', self::$plugin_path ),
			array(
				'jquery',
				//'modernizr',
			),
			filemtime( plugin_dir_path( self::$plugin_path ) . '/assets/js/mixtape-front.js' ),
			true
		);
		$localized_data = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'reportError' => __( 'Report an error', 'mixtape' ),
		);
		wp_localize_script( 'mixtape-front', 'MixtapeLocalize', $localized_data );
	}

	public static function create_db() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$sql = "
CREATE TABLE {$wpdb->base_prefix}mixtape_reports (
  ID bigint(20) unsigned NOT NULL auto_increment,
  blog_id int(11) DEFAULT '1',
  post_id bigint(20) unsigned UNSIGNED,
  post_author bigint(20) UNSIGNED,
  reporter_user_id bigint(20) UNSIGNED,
  reporter_IP varchar(100) NOT NULL,
  date datetime NOT NULL default '0000-00-00 00:00:00',
  date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  selection varchar(255) NOT NULL,
  selection_word varchar(255),
  selection_replace_context varchar(2000),
  selection_context varchar(2000),
  comment varchar(2000),
  url varchar(2083),
  agent varchar(255),
  language varchar(50),
  status varchar(20) NOT NULL default 'pending',
  token char(20),
  PRIMARY KEY (ID),
  KEY post_id (post_id),
  KEY post_author (post_author),
  KEY reporter_user_id (reporter_user_id),
  KEY date_gmt (date_gmt)
) $collate;";

		dbDelta( $sql );
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function version_upgrade( $upgrader_object, $options ) {
		if ( isset( $options['plugins'] ) && is_array( $options['plugins'] ) && ! in_array( self::$plugin_path, $options['plugins'] ) ) {
			return;
		}

		$db_version = get_option( 'mixtape_version', '1.0.0' );
		self::create_db();

		if ( version_compare( self::$version, $db_version ) === 1 ) {
			update_option( 'mixtape_version', self::$version, false );
		}

		$this->options['plugin_updated_timestamp'] = time();
		update_option( 'mixtape_options', $this->options );
	}

	/**
	 * Get default icons for
	 */
	public static function icons_in_content( $args ) {

		$array_icons = array(
			1 => '<svg width="64" version="1.1" xmlns="http://www.w3.org/2000/svg" height="64" viewBox="0 0 64 64" xmlns:xlink="http://www.w3.org/1999/xlink" enable-background="new 0 0 64 64"><g><g><path d="m62.463,1.543c-1.017-1.017-2.403-1.542-3.83-1.543-1.43,0.021-2.778,0.591-3.801,1.609l-2.446,2.443c-0.01,0.012-0.015,0.025-0.024,0.035l-31.909,31.819c-0.104,0.104-0.158,0.233-0.234,0.353-0.131,0.152-0.245,0.317-0.327,0.505l-3.254,7.5c-0.324,0.75-0.169,1.62 0.397,2.211 0.392,0.41 0.927,0.631 1.476,0.631 0.241,0 0.486-0.043 0.719-0.131l7.824-2.943c0.217-0.081 0.406-0.209 0.579-0.352 0.126-0.08 0.262-0.14 0.367-0.245l32.035-31.945c0.006-0.006 0.008-0.014 0.015-0.02l2.341-2.33c2.118-2.111 2.15-5.52 0.072-7.597zm-35.905,37.576l-1.777-1.773 29.151-29.069 1.776,1.773-29.15,29.069zm32.95-32.857l-.916,.912-1.784-1.779 .911-.91c0.265-0.264 0.609-0.411 0.972-0.416 0.344-0.008 0.653,0.119 0.883,0.348 0.491,0.49 0.459,1.319-0.066,1.845z"/><path d="M58.454,22.253c-1.128,0-2.04,0.911-2.04,2.034v33.611c0,1.121-0.915,2.033-2.04,2.033H6.12    c-1.126,0-2.04-0.912-2.04-2.033V9.787c0-1.121,0.914-2.034,2.04-2.034h33.403c1.127,0,2.04-0.911,2.04-2.034    s-0.913-2.034-2.04-2.034H6.12C2.745,3.685,0,6.422,0,9.787v48.111C0,61.263,2.745,64,6.12,64h48.254    c3.374,0,6.12-2.737,6.12-6.102V24.287C60.494,23.164,59.581,22.253,58.454,22.253z"/></g></g></svg>',
			2 => '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="95.864px" height="95.864px" viewBox="0 0 95.864 95.864" style="enable-background:new 0 0 95.864 95.864;" xml:space="preserve"><g><g><path d="M26.847,43.907c0.279,0.805,1.037,1.345,1.889,1.345h5.59c0.656,0,1.271-0.322,1.645-0.862s0.459-1.229,0.227-1.843 L23.632,9.19c-0.293-0.779-1.039-1.295-1.871-1.295h-6.869c-0.826,0-1.568,0.509-1.865,1.28L0.134,42.582 c-0.236,0.615-0.156,1.308,0.217,1.852c0.373,0.543,0.99,0.868,1.65,0.868h5.07c0.836,0,1.584-0.52,1.875-1.303l2.695-7.247 h12.723L26.847,43.907z M14.027,29.873l4.154-12.524l3.9,12.524H14.027z"/><path d="M39.711,45.25h10.01c3.274,0,9.371,0,13.272-4.488c2.14-2.482,2.39-7.353,1.609-9.807 c-0.836-2.395-2.43-4.028-5.103-5.193c2.015-1.046,3.437-2.515,4.234-4.382c1.207-2.857,0.596-6.954-1.434-9.55 c-2.781-3.471-7.6-3.939-11.949-3.939L39.709,7.896c-1.104,0-1.998,0.896-1.998,2V43.25C37.711,44.355,38.606,45.25,39.711,45.25z  M55.375,35.911c-0.586,1.227-1.813,2.361-6.811,2.361H47.28V29.56l1.813-0.001c2.971,0,4.705,0.295,5.93,1.894 C55.877,32.587,55.92,34.806,55.375,35.911z M54.625,20.298c-0.854,1.514-2.039,2.333-5.712,2.333H47.28v-7.808l1.847-0.001 c2.609,0.064,4.123,0.343,5.115,1.658C55.05,17.592,55.007,19.458,54.625,20.298z"/><path d="M95.677,38.77c-0.031-0.632-0.359-1.212-0.886-1.563c-0.524-0.353-1.188-0.436-1.782-0.224 c-4.802,1.706-8.121,1.787-11.17,0.258c-3.761-1.946-5.666-5.227-5.824-9.99c-0.062-4.17,0.528-8.79,5.358-11.445 c1.416-0.775,3.07-1.168,4.92-1.168c2.461,0,4.9,0.723,6.515,1.328c0.598,0.227,1.266,0.149,1.799-0.199 c0.535-0.351,0.869-0.935,0.9-1.572l0.18-3.542c0.047-0.94-0.568-1.787-1.478-2.031c-2.006-0.541-5.149-1.185-8.745-1.185 c-3.873,0-7.265,0.733-10.085,2.183c-7.836,4.055-9.102,11.791-9.278,14.92c-0.181,2.901-0.123,12.788,8.117,18.195 c3.883,2.5,8.541,3.024,11.764,3.024c2.816,0,5.812-0.417,8.438-1.175c0.892-0.258,1.488-1.094,1.443-2.02L95.677,38.77z"/><path d="M88.453,49.394c-0.067-0.531-0.346-1.016-0.772-1.34c-0.429-0.325-0.968-0.463-1.498-0.388 c-20.898,3.031-38.422,16.966-47.236,25.268l-16.85-19.696c-0.717-0.841-1.98-0.938-2.818-0.222l-6.471,5.533 c-0.404,0.346-0.654,0.836-0.695,1.364s0.131,1.054,0.475,1.455l21.268,24.861c1.061,1.238,2.525,2.003,4.041,2.146 c0.17,0.022,0.393,0.052,0.738,0.052c1.039,0,3.023-0.272,4.646-2.104c0.203-0.226,20.568-22.684,44.559-26.252 c1.075-0.16,1.825-1.152,1.688-2.23L88.453,49.394z"/></g></g></svg>',
		);

		$result  = array( 'icon' => '' );
		$icon_id = isset( $args['icon_id'] ) ? intval( $args['icon_id'] ) : 0;
		if ( isset( $array_icons[ $icon_id ] ) ) {
			$result['icon'] = $array_icons[ $icon_id ];
		}

		if ( isset( $args['icon_all'] ) && true == $args['icon_all'] ) {
			$result = $array_icons;
		}

		return $result;
	}
}
