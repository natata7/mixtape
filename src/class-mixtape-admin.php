<?php
// phpcs:ignore WordPress.WP.Capabilities.RoleFound

/**
 * [Mixtape_Admin]
 */
class Mixtape_Admin extends Mixtape_Abstract {


	/**
	 * @var [type]
	 */
	private static $instance;

	/**
	 * Constructor
	 */
	protected function __construct() {
		parent::__construct();

		// Load textdomain
		$this->load_textdomain();

		// admin-wide actions
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_notices', array( $this, 'plugin_activated_notice' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// if multisite inheritance is enabled, add corresponding action
		if ( 'yes' === $this->options['multisite_inheritance'] && is_multisite() ) {
			add_action( 'wpmu_new_blog', __CLASS__ . '::activation' );
		}

		// Mixtape page-specific actions
		if ( isset( $_GET['page'] ) && 'mixtape_settings' === $_GET['page'] ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_load_scripts_styles' ) );
			add_action( 'admin_footer', array( $this, 'insert_dialog' ) );
		}

		// filters
		add_filter( 'plugin_action_links', array( $this, 'plugins_page_settings_link' ), 10, 2 );

		register_uninstall_hook( __FILE__, array( 'Abstract_Mixtape', 'uninstall_cleanup' ) );
	}

	public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Load plugin defaults
	 */
	public function init() {
		// init only once
		if ( $this->email_recipient_types ) {
			return;
		}

		$this->post_types            = $this->get_post_types_list();
		$this->email_recipient_types = array(
			'admin'  => __( 'Administrator', 'mixtape' ),
			'editor' => __( 'Editor', 'mixtape' ),
			'other'  => __( 'Specify other', 'mixtape' ),
		);

		$this->caption_formats = array(
			'text'     => __( 'Text', 'mixtape' ),
			'image'    => __( 'Image', 'mixtape' ),
			'disabled' => __( 'Do not show caption at the bottom of post', 'mixtape' ),
		);

		$this->caption_text_modes = array(
			'default' => array(
				'name'        => __( 'Default', 'mixtape' ),
				'description' => __( 'automatically translated to supported languages', 'mixtape' ),
			),
			'custom'  => array(
				'name'        => __( 'Custom text', 'mixtape' ),
				'description' => '',
			),
		);

		$this->dialog_modes = array(
			'notify'  => __( 'Just notify of successful submission', 'mixtape' ),
			'confirm' => __( 'Show preview of reported text and ask confirmation', 'mixtape' ),
			'comment' => __( 'Preview and comment field', 'mixtape' ),
		);
	}

	/**
	 * Add submenu
	 */
	public function admin_menu() {
		if ( apply_filters( 'mixtape_show_settings_menu_item', true, $this ) ) {
			add_options_page(
				'Mixtape',
				'Mixtape',
				'manage_options',
				'mixtape_settings',
				array( $this, 'print_options_page' )
			);
		}
	}

	/**
	 * Options page output
	 */
	public function print_options_page() {
		global $wpdb;
		$this->init();

		// Show changelog only if less than one week has passed since updating the plugin
		$show_changelog = time() - (int) $this->options['plugin_updated_timestamp'] < WEEK_IN_SECONDS;

		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'configuration';

		$table_name = $wpdb->base_prefix . Mixtape_Abstract::DB_TABLE;
		$reports_count = 0;

		$table_exists = ! ! $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.tables
				WHERE table_schema = %s
				AND table_name = %s LIMIT 1',
				DB_NAME,
				$wpdb->base_prefix . 'mixtape_reports'
			)
		);
		$blog_id = get_current_blog_id();
		$reports_count = $table_exists ? $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->base_prefix}mixtape_reports where status = 'pending' && blog_id = %d",
				$blog_id
			)
		) : null;
		?>
		<div class="wrap">
			<h2>Mixtape</h2>
			<h2 class="nav-tab-wrapper">
				<?php
				printf(
					'<a href="%s" class="nav-tab%s" data-bodyid="mixtape-configuration" >%s</a>',
					esc_url( add_query_arg( 'tab', 'configuration' ) ),
					'configuration' == $active_tab ? ' nav-tab-active' : '',
					esc_html( __( 'Configuration', 'mixtape' ) )
				);
				printf(
					'<a href="%s" class="nav-tab%s" data-bodyid="mixtape-help">%s</a>',
					esc_url( add_query_arg( 'tab', 'help' ) ),
					'help' == $active_tab ? ' nav-tab-active' : '',
					esc_html( __( 'Help', 'mixtape' ) )
				);
				?>
			</h2>
			<?php printf(
				'<div id="mixtape-configuration" class="mixtape-tab-contents" %s>',
				'configuration' == $active_tab ? '' : 'style="display: none;"'
			); ?>
			<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
				<?php
				settings_fields( 'mixtape_options' );
				do_settings_sections( 'mixtape_options' );
				// echo '<input type="hidden" name="mixtape_nonce" value="' . esc_attr( $nonce ) . '">';

				?>
				<p class="submit">
					<?php submit_button( '', 'primary', 'save_mixtape_options', false ); ?>
				</p>
				<div id="mixtape-sidebar">
					<div id="mixtape_statistics" class="postbox -right-sidebar-widget">
						<h3 class="hndle">
							<span><?php esc_html_e( 'Statistics', 'mixtape' ); ?></span>
						</h3>
						<div class="inside">
							<p>
								<?php
								$reports_count = empty( $reports_count ) ? 0 : $reports_count;
								esc_html_e( 'Reports received up to date:', 'mixtape' );
								echo ' <strong>' . esc_html( $reports_count ) . '</strong>';
								?>
							</p>
						</div>
					</div>
				</div>
			</form>
	
		</div>
		<?php
		printf(
			'<div id="mixtape-help" class="mixtape-tab-contents" %s>',
			'help' == $active_tab ? '' : 'style="display: none;" '
		);
		$this->print_help_page();
		?>
		</div>
		<div class="clear"></div>
		</div>
		<?php
	}

	/**
	 * Register plugin settings
	 */
	public function register_settings() {
		register_setting( 'mixtape_options', 'mixtape_options', array( $this, 'validate_options' ) );

		add_settings_section( 'mixtape_configuration', '', array( $this, 'section_configuration' ), 'mixtape_options' );
		add_settings_field(
			'mixtape_email_recipient',
			__( 'Email recipient', 'mixtape' ),
			array( $this, 'field_email_recipient' ),
			'mixtape_options',
			'mixtape_configuration'
		);
		add_settings_field(
			'mixtape_post_types',
			__( 'Post types', 'mixtape' ),
			array( $this, 'field_post_types' ),
			'mixtape_options',
			'mixtape_configuration'
		);
		add_settings_field(
			'mixtape_register_shortcode',
			__( 'Shortcodes', 'mixtape' ),
			array( $this, 'field_register_shortcode' ),
			'mixtape_options',
			'mixtape_configuration'
		);
		add_settings_field(
			'mixtape_caption_format',
			__( 'Caption format', 'mixtape' ),
			array( $this, 'field_caption_format' ),
			'mixtape_options',
			'mixtape_configuration'
		);
		add_settings_field(
			'mixtape_caption_text_mode',
			__( 'Caption text mode', 'mixtape' ),
			array( $this, 'field_caption_text_mode' ),
			'mixtape_options',
			'mixtape_configuration'
		);
		add_settings_field(
			'mixtape_show_logo_in_caption',
			__( 'Icon before the caption text', 'mixtape' ),
			array( $this, 'field_show_logo_in_caption' ),
			'mixtape_options',
			'mixtape_configuration'
		);
		add_settings_field(
			'mixtape_color_scheme',
			__( 'Color scheme', 'mixtape' ),
			array( $this, 'field_show_color_scheme' ),
			'mixtape_options',
			'mixtape_configuration'
		);
		add_settings_field(
			'mixtape_dialog_mode',
			__( 'Dialog mode', 'mixtape' ),
			array( $this, 'field_dialog_mode' ),
			'mixtape_options',
			'mixtape_configuration'
		);

		if ( is_multisite() && is_main_site() ) {
			add_settings_field(
				'mixtape_multisite_inheritance',
				__( 'Multisite inheritance', 'mixtape' ),
				array( $this, 'field_multisite_inheritance' ),
				'mixtape_options',
				'mixtape_configuration'
			);
		}
	}

	/**
	 * Section callback
	 */
	public function section_configuration() {
	}

	/**
	 * Email recipient selection
	 */
	public function field_email_recipient() {
		echo '
		<fieldset>';

		foreach ( $this->email_recipient_types as $value => $label ) {
			echo '
				<label><input id="mixtape_email_recipient_type-' . esc_attr( $value ) . '" type="radio"
				  name="mixtape_options[email_recipient][type]" value="' . esc_attr( $value ) . '" ' .
				checked( $value, $this->options['email_recipient']['type'], false ) . ' />' . esc_html( $label ) . '
				</label><br>';
		}

		echo '
			<div id="mixtape_email_recipient_list-admin"' . ( 'admin' == $this->options['email_recipient']['type'] ? '' : 'style="display: none;"' ) . '>';

		echo '
			<select name="mixtape_options[email_recipient][id][admin]">';

		$admins = $this->get_user_list_by_role( 'administrator' );
		foreach ( $admins as $user ) {
			echo '
				<option value="' . esc_attr( $user->ID ) . '" ' . selected(
				$user->ID,
				$this->options['email_recipient']['id'],
				false
			) . '>' . esc_html( $user->user_nicename . ' (' . $user->user_email . ')' ) . '</option>';
		}

		echo '
			</select>
			</div>';

		echo '
			<div id="mixtape_email_recipient_list-editor"' . ( 'editor' === $this->options['email_recipient']['type'] ? '' : 'style="display: none;"' ) . '>';

		$editors = $this->get_user_list_by_role( 'editor' );
		if ( ! empty( $editors ) ) {
			echo '<select name="mixtape_options[email_recipient][id][editor]">';
			foreach ( $editors as $user ) {
				echo '
				<option value="' . esc_attr( $user->ID ) . '" ' . selected(
					$user->ID,
					$this->options['email_recipient']['id'],
					false
				) . '>' . esc_html( $user->user_nicename . ' (' . $user->user_email . ')' ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<select><option value="">-- ' . esc_html_x( 'no editors found', 'select option, shown when no users with editor role are present', 'mixtape' ) . ' --</option></select>';
		}

		echo '
			</div>
			<div id="mixtape_email_recipient_list-other" ' . ( 'other' === $this->options['email_recipient']['type'] ? '' : 'style="display: none;"' ) . '>
				<input type="text" class="regular-text" name="mixtape_options[email_recipient][email]" value="' . esc_attr( $this->options['email_recipient']['email'] ) . '" />
				<p class="description">' . esc_html__( 'separate multiple recipients with commas', 'mixtape' ) . '</p>
			</div>
			<br>
			<label><input id="mixtape_email_recipient-post_author_first" type="checkbox" name="mixtape_options[email_recipient][post_author_first]" value="1" ' . checked(
			'yes',
			$this->options['email_recipient']['post_author_first'],
			false
		) . '/>' . esc_html__( 'If post ID is determined, notify post author instead', 'mixtape' ) . '</label>
		</fieldset>';
	}

	/**
	 * Post types to show caption in
	 */
	public function field_post_types() {
		echo '
		<fieldset style="max-width: 600px;">';

		foreach ( $this->post_types as $value => $label ) {
			echo '
			<label style="padding-right: 8px; min-width: 60px;"><input id="mixtape_post_type-' . esc_html( $value ) . '" type="checkbox" name="mixtape_options[post_types][' . esc_html( $value ) . ']" value="1" ' . checked(
				true,
				in_array( $value, $this->options['post_types'] ),
				false
			) . ' />' . esc_html( $label ) . '</label>	';
		}

		echo '
			<p class="description">' . esc_html__( '"Press Ctrl+Enter&hellip;" captions will be displayed at the bottom of selected post types.', 'mixtape' ) . '</p>
		</fieldset>';
	}

	/**
	 * Shortcode option
	 */
	public function field_register_shortcode() {
		echo '
		<fieldset>
			<label><input id="mixtape_register_shortcode" type="checkbox" name="mixtape_options[register_shortcode]" value="1" ' . checked(
			'yes',
			$this->options['register_shortcode'],
			false
		) . '/>' . esc_html__(
			'Register ',
            'mixtape'
            ) . '<code>[mixtape]</code>' . esc_html__( ' shortcode.', 'mixtape' ) . '</label>
			<p class="description">' . esc_html__( 'Enable if manual caption insertion via shortcodes is needed.', 'mixtape' ) . '</p>
			<p class="description">' . esc_html__( 'Usage examples are in Help section.', 'mixtape' ) . '</p>
			<p class="description">' . esc_html__( 'When enabled, Mixtape Ctrl+Enter listener works on all pages, not only on enabled post types.', 'mixtape' ) . '</p>
		</fieldset>';
	}

	/**
	 * Caption format option
	 */
	public function field_caption_format() {
		echo '
		<fieldset>';

		foreach ( $this->caption_formats as $value => $label ) {
			echo '
			<label><input id="mixtape_caption_format-' . esc_html( $value ) . '" type="radio" name="mixtape_options[caption_format]" value="' . esc_attr( $value ) . '" ' . checked(
				$value,
				$this->options['caption_format'],
				false
			) . ' />' . esc_html( $label ) . '</label><br>';
		}

		echo '
		<div id="mixtape_caption_image"' . ( 'yes' == $this->options['register_shortcode'] || 'image' === $this->options['caption_format'] ? '' : 'style="display: none;"' ) . '>
			<p class="description">' . esc_html__( 'Enter the full image URL starting with http://', 'mixtape' ) . '</p>
			<input type="text" class="regular-text" name="mixtape_options[caption_image_url]" value="' . esc_attr( $this->options['caption_image_url'] ) . '" />
		</div>
		</fieldset>';
	}

	/**
	 * Caption custom text field
	 */
	public function field_caption_text_mode() {
		echo '<fieldset>';

		foreach ( $this->caption_text_modes as $value => $label ) {
			echo '<label><input id="mixtape_caption_text_mode-' . esc_html( $value ) . '" type="radio" name="mixtape_options[caption_text_mode]" value="' . esc_attr( $value ) . '" ' . checked(
				$value,
				$this->options['caption_text_mode'],
				false
			) . ' />' . esc_html( $label['name'] );
			echo empty( $label['description'] ) ? ':' : ' <span class="description">(' . esc_html( $label['description'] ) . ')</span>';
			echo '</label><br>';
		}

		$textarea_contents = $this->get_caption_text();
		$textarea_state    = 'default' == $this->options['caption_text_mode'] ? ' disabled="disabled"' : '';

		echo '<textarea id="mixtape_custom_caption_text" name="mixtape_options[custom_caption_text]" cols="70" rows="4"
			data-default="' . esc_attr( $this->get_default_caption_text() ) . '"' . esc_html( $textarea_state ) . ' />' . esc_textarea( $textarea_contents ) . '</textarea><br>
		</fieldset>';
	}

	/**
	 * Show Mixtape logo in caption
	 */
	public function field_show_logo_in_caption() {
		$custom_logo_icon = intval( $this->options['show_logo_in_caption'] );
		$mixtape_icons    = apply_filters( 'mixtape_get_icon', array( 'icon_all' => true ) );

		echo '
		<fieldset class="select-logo">
			<label class="select-logo__item select-logo__item--no-img">
			    <input type="radio" name="mixtape_options[show_logo_in_caption]" value="0" ' . checked( 0, $custom_logo_icon, false ) . '>
			    <div class="select-logo__img">
				' . esc_html__( 'no icon', 'mixtape' ) . '
			    </div>
			</label>

			<label class="select-logo__item">
		    <input type="radio" name="mixtape_options[show_logo_in_caption]" value="1" ' . checked( 1, $custom_logo_icon, false ) . '>
			<div class="select-logo__img">
			' . wp_kses(
                $mixtape_icons[1],
                array(
					'svg'     => array(
						'view-box' => array(),
						'height' => array(),
						'width' => array(),
						'enable-background' => array(),
						'viewbox' => array(),
					),
					'g' => array(),
					'path' => array(
						'd' => array(),
					),
				)
                ) . '
		</div>
			</label>

			<label class="select-logo__item">
		<input type="radio" name="mixtape_options[show_logo_in_caption]" value="2" ' . checked( 2, $custom_logo_icon, false ) . '>
			<div class="select-logo__img">
			' . wp_kses(
            $mixtape_icons[2],
            array(
				'svg'     => array(
					'view-box' => array(),
					'height' => array(),
					'width' => array(),
					'enable-background' => array(),
					'viewbox' => array(),
				),
				'g' => array(),
				'path' => array(
					'd' => array(),
				),
			)
            ) . '
			</div>
		</label>
	</fieldset>';
	}

	/**
	 * Color scheme
	 */
	public function field_show_color_scheme() {
		$color_scheme = ! empty( $this->options['color_scheme'] ) ? $this->options['color_scheme'] : '#E42029';
		$str = __( 'default color', 'mixtape' );
		echo '
		<fieldset>
			<label>
			    <input id="mixtape_color_scheme" type="text" name="mixtape_options[color_scheme]" value="' . esc_html( $color_scheme ) . '" class="mixtape_color_picker" />
			</label>
			<p class="description">' . esc_html( $str ) . '  <span style="color: #E42029;">#E42029</span></p>
		</fieldset>';
	}


	/**
	 * Dialog mode: ask for a comment or fire notification straight off
	 */
	public function field_dialog_mode() {
		echo '<fieldset>';

		foreach ( $this->dialog_modes as $value => $label ) {
			echo '
			<label><input class="dialog_mode_choice" id="mixtape_caption_format-' . esc_html( $value ) .
				'" type="radio" name="mixtape_options[dialog_mode]" value="' . esc_attr( $value ) . '" ' .
				checked( $value, $this->options['dialog_mode'], false ) . ' />' . esc_html( $label ) .
				'</label><br>';
		}
		echo '<button class="button" id="preview-dialog-btn">' . esc_html__( 'Preview dialog', 'mixtape' ) . '</button>';
		echo '<span id="preview-dialog-spinner" class="spinner"></span>';
	}

	/**
	 * Multisite inheritance: copy settings from main site to newly created blogs
	 */
	public function field_multisite_inheritance() {
		echo '
		<fieldset>
			<label><input id="mixtape_multisite_inheritance" type="checkbox" name="mixtape_options[multisite_inheritance]" value="1" ' .
			checked(
				'yes',
				$this->options['multisite_inheritance'],
				false
			) . '/>' . esc_html__( 'Copy settings from main site when new blog is created', 'mixtape' ) . '
	        </label>
		</fieldset>';
	}

	/**
	 * Validate options
	 *
	 * @return mixed
	 */
	public function validate_options( $input ) {
		$this->init();

		if ( ! current_user_can( 'manage_options' ) ) {
			return $input;
		}

		if ( isset( $_POST['option_page'] ) &&
			'mixtape_options' === $_POST['option_page'] ) {

			check_admin_referer( 'mixtape_options-options' );

			// mail recipient
			$input['email_recipient']['type']              = sanitize_text_field(
                isset( $input['email_recipient']['type'] ) && in_array(
				$input['email_recipient']['type'],
				array_keys( $this->email_recipient_types )
			) ? $input['email_recipient']['type'] : self::$defaults['email_recipient']['type']
                );
			$input['email_recipient']['post_author_first'] = '1' === $input['email_recipient']['post_author_first'] ? 'yes' : 'no';

			if (
				'admin' == $input['email_recipient']['type'] && isset( $input['email_recipient']['id']['admin'] ) && ( user_can(
					$input['email_recipient']['id']['admin'],
					'administrator' // phpcs:ignore WordPress.WP.Capabilities.RoleFound
				) )
			) {
				$input['email_recipient']['id'] = $input['email_recipient']['id']['admin'];
			} elseif (
				'editor' == $input['email_recipient']['type'] && isset( $input['email_recipient']['id']['editor'] ) && ( user_can(
					$input['email_recipient']['id']['editor'],
					'editor' // phpcs:ignore WordPress.WP.Capabilities.RoleFound
				) )
			) {
				$input['email_recipient']['id'] = $input['email_recipient']['id']['editor'];
			} elseif ( 'other' == $input['email_recipient']['type'] && isset( $input['email_recipient']['email'] ) ) {
				$input['email_recipient']['id'] = '0';
				$emails                         = explode(
					',',
					str_replace( array( ', ', ' ' ), ',', $input['email_recipient']['email'] )
				);
				$invalid_emails                 = array();
				foreach ( $emails as $key => &$email ) {
					if ( ! is_email( $email ) ) {
						$invalid_emails[] = $email;
						unset( $emails[ $key ] );
					}
					$email = sanitize_email( $email );
				}
				if ( $invalid_emails ) {
					add_settings_error(
						'mixtape_options',
						esc_attr( 'invalid_recipient' ),
						sprintf(
							__( 'ERROR: You entered invalid email address: %s', 'mixtape' ),
							trim( implode( ',', $invalid_emails ), ',' )
						),
						'error'
					);
				}

				$input['email_recipient']['email'] = trim( implode( ',', $emails ), ',' );
			} else {
				add_settings_error(
					'mixtape_options',
					esc_attr( 'invalid_recipient' ),
					__( 'ERROR: You didn\'t select valid email recipient.', 'mixtape' ),
					'error'
				);
				$input['email_recipient']['id'] = '1';
				$input['email_recipient']       = $this->options['email_recipient'];
			}

			// post types
			$input['post_types'] = isset( $input['post_types'] ) && is_array( $input['post_types'] ) && count(
                array_intersect(
				array_keys( $input['post_types'] ),
				array_keys( $this->post_types )
			)
                ) === count( $input['post_types'] ) ? array_keys( $input['post_types'] ) : array();

			// shortcode option
			$input['register_shortcode'] = (bool) isset( $input['register_shortcode'] ) ? 'yes' : 'no';

			// caption type
			$input['caption_format'] = isset( $input['caption_format'] ) && in_array(
				$input['caption_format'],
				array_keys( $this->caption_formats )
			) ? $input['caption_format'] : self::$defaults['caption_format'];
			if ( 'image' === $input['caption_format'] ) {
				if ( ! empty( $input['caption_image_url'] ) ) {
					$input['caption_image_url'] = esc_url( $input['caption_image_url'] );
				} else {
					add_settings_error(
						'mixtape_options',
						esc_attr( 'no_image_url' ),
						__( 'ERROR: You didn\'t enter caption image URL.', 'mixtape' ),
						'error'
					);
					$input['caption_format']    = self::$defaults['caption_format'];
					$input['caption_image_url'] = self::$defaults['caption_image_url'];
				}
			};

			// caption text mode
			$input['caption_text_mode']   = isset( $input['caption_text_mode'] ) && in_array(
				$input['caption_text_mode'],
				array_keys( $this->caption_text_modes )
			) ? $input['caption_text_mode'] : self::$defaults['caption_text_mode'];
			$input['custom_caption_text'] = 'custom' == $input['caption_text_mode'] && $input['custom_caption_text'] !== $this->default_caption_text ? wp_kses_post( $input['custom_caption_text'] ) : '';

			$input['multisite_inheritance'] = isset( $input['multisite_inheritance'] ) && '1' === $input['multisite_inheritance'] ? 'yes' : 'no';

			$input['first_run'] = 'no';

			// color scheme
			$input['color_scheme'] = isset( $input['color_scheme'] ) ? $input['color_scheme'] : '#E42029';
		}

		return $input;
	}

	/**
	 * Add links to settings page
     *
	 * @return mixed
	 */
	public function plugins_page_settings_link( $links, $file ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $links;
		}

		$plugin = plugin_basename( self::$plugin_path );

		if ( $file == $plugin ) {
			array_unshift(
				$links,
				sprintf(
					'<a href="%s">%s</a>',
					admin_url( 'options-general.php?page=mixtape_settings' ),
					__( 'Settings', 'mixtape' )
				)
			);
		}

		return $links;
	}

	/**
	 * Add initial options
	 */
	public static function activation( $blog_id = null ) {
		$blog_id = (int) $blog_id;

		if ( empty( $blog_id ) ) {
			$blog_id = get_current_blog_id();
		}
		$options = self::get_options();
		if ( get_current_blog_id() == $blog_id ) {
			add_option( 'mixtape_options', $options, '', 'yes' );
			add_option( 'mixtape_version', self::$version, '', 'no' );
		} else {
			switch_to_blog( $blog_id );
			add_option( 'mixtape_options', $options, '', 'yes' );
			add_option( 'mixtape_version', self::$version, '', 'no' );
			restore_current_blog();
		}

		if ( ! empty( $options['addons_to_activate'] ) ) {
			if ( function_exists( 'activate_plugins' ) ) {
				activate_plugins( $options['addons_to_activate'] );
				unset( $options['addons_to_activate'] );
				update_option( 'mixtape_options', $options );
			}
		}

		self::create_db();
	}

	public static function deactivate_addons() {
		if ( function_exists( 'deactivate_plugins' ) ) {
			$active_and_valid_plugins = wp_get_active_and_valid_plugins();
			$active_and_valid_plugins = implode( ',', $active_and_valid_plugins );
			$deactivated              = array();
			foreach ( static::$supported_addons as $addon ) {
				$plugin = $addon . '/' . $addon . '.php';
				// $plugin = $addon . '.php';
				if ( false !== strpos( $active_and_valid_plugins, $plugin ) ) {
					deactivate_plugins( $plugin, true );
					$deactivated[] = $plugin;
				}
			}
			if ( ! empty( $deactivated ) ) {
				$options                       = self::get_options();
				$options['addons_to_activate'] = $deactivated;
				update_option( 'mixtape_options', $options );
			}
		}
	}

	/**
	 * Delete settings on plugin uninstall
	 */
	public static function uninstall_cleanup() {
		global $wpdb;

		$table_name = 'mixtape_reports';

		$wpdb->query(
			$wpdb->prepare(
				'DROP TABLE IF EXISTS %s',
				$table_name
			)
		);

		delete_option( 'mixtape_options' );
		delete_option( 'mixtape_version' );
	}

	/**
	 * Load scripts and styles - admin
	 */
	public function admin_load_scripts_styles( $page ) {
		if ( strpos( $page, '_page_mixtape_settings', true ) === false ) {
			return;
		}

		// Add the color picker css file
		wp_enqueue_style( 'wp-color-picker' );

		$this->enqueue_dialog_assets();

		// admin page script
		wp_enqueue_script(
            'mixtape-admin',
            MIXTAPE__PLUGIN_URL . '/assets/js/admin.js',
            array(
			'mixtape-front',
			'wp-color-picker',
		),
            self::$version,
            true
            );

		// admin page style
		wp_register_style( 'mixtape_admin_style', MIXTAPE__PLUGIN_URL . '/assets/css/mixtape-admin.css', array(), MIXTAPE__VERSION );
		wp_enqueue_style( 'mixtape_admin_style' );
	}

	/**
	 * Add admin notice after activation if not configured
	 */
	public function plugin_activated_notice() {
		$wp_screen = get_current_screen();
		if ( 'yes' == $this->options['first_run'] && current_user_can( 'manage_options' ) ) {
			$html = '<div class="updated">';
			$html .= '<p>';
			if ( $wp_screen && 'settings_page_mixtape' == $wp_screen->id ) {
				$html .= __(
					'<strong>Mixtape</strong> settings notice will be dismissed after saving changes.',
					'mixtape'
				);
			} else {
				$html .= sprintf(
                    __(
					'<strong>Mixtape</strong> must now be <a href="%s">configured</a> before use.',
					'mixtape'
				),
                    admin_url( 'options-general.php?page=mixtape_settings' )
                    );
			}
			$html .= '</p>';
			$html .= '</div>';
			echo wp_kses_post( $html );
		}
	}

	/**
	 * Get admins list for options page
	 *
	 * @return array
	 */
	public function get_user_list_by_role( $role ) {
		$users_query = get_users(
            array(
			'role'    => $role,
			'fields'  => array(
				'ID',
				'user_nicename',
				'user_email',
			),
			'orderby' => 'display_name',
		)
            );

		return $users_query;
	}

	/**
	 * Return an array of registered post types with their labels
	 */
	public function get_post_types_list() {
		$post_types = get_post_types(
			array( 'public' => true ),
			'objects'
		);

		$post_types_list = array();
		foreach ( $post_types as $id => $post_type ) {
			$post_types_list[ $id ] = $post_type->label;
		}

		return $post_types_list;
	}

	/**
	 * Echo Help tab contents
	 */
	private static function print_help_page() {
     ?>
		<div class="card">
			<h3><?php esc_html_e( 'Shortcodes', 'mixtape' ); ?></h3>
			<h4><?php esc_html_e( 'Optional shortcode parameters are:', 'mixtape' ); ?></h4>
			<ul>
				<li><code>'format', </code> — <?php esc_html_e( "can be 'text' or 'image'", 'mixtape' ); ?></li>
				<li><code>'class', </code> — <?php esc_html_e( 'override default css class', 'mixtape' ); ?></li>
				<li><code>'text', </code> — <?php esc_html_e( 'override caption text', 'mixtape' ); ?></li>
				<li><code>'image', </code> — <?php esc_html_e( 'override image URL', 'mixtape' ); ?></li>
			</ul>
			<p><?php esc_html_e( 'When no parameters specified, general configuration is used.', 'mixtape' ); ?><br>
				<?php esc_html_e( 'If image url is specified, format parameter can be omitted.', 'mixtape' ); ?></p>
			<h4><?php esc_html_e( 'Shortcode usage example:', 'mixtape' ); ?></h4>
			<ul>
				<li>
					<p><code>[mixtape format="text" class="mixtape_caption_sidebar"]</code></p>
				</li>
			</ul>
			<h4><?php esc_html_e( 'PHP code example:', 'mixtape' ); ?></h4>
			<ul>
				<li>
					<p><code>&lt;?php do_shortcode( '[mixtape format="image" class="mixtape_caption_footer"
							image="/wp-admin/images/yes.png"]' ); ?&gt;</code></p>
				</li>
			</ul>
		</div>

		<div class="card">
			<h3><?php esc_html_e( 'Hooks', 'mixtape' ); ?></h3>

			<ul>

			<li class="mixtape-hook-block">
					<code>'mixtape_caption_text', <span class="mixtape-var-str">$text</span></code>
					<p class="description">
                    <?php esc_html_e(
												'Allows to modify caption text globally (preferred over HTML filter).',
												'mixtape'
											) ?></p>
				</li>

				<li class="mixtape-hook-block">
					<code>'mixtape_caption_output', <span class="mixtape-var-str">$html</span>, <span class="mixtape-var-arr">$options</span></code></code>
					<p class="description">
                    <?php esc_html_e(
												'Allows to modify the caption HTML before output.',
												'mixtape'
											) ?></p>
				</li>

				<li class="mixtape-hook-block">
					<code>'mixtape_dialog_args', <span class="mixtape-var-arr">$args</span></code>
					<p class="description">
                    <?php esc_html_e(
												'Allows to modify modal dialog strings (preferred over HTML filter).',
												'mixtape'
											) ?></p>
				</li>

				<li class="mixtape-hook-block">
					<code>'mixtape_dialog_output', <span class="mixtape-var-str">$html</span>, <span class="mixtape-var-arr">$options</span></code></code>
					<p class="description">
                    <?php esc_html_e(
												'Allows to modify the modal dialog HTML before output.',
												'mixtape'
											) ?></p>
				</li>

				<li class="mixtape-hook-block">
					<code>'mixtape_custom_email_handling', <span class="mixtape-var-bool">$stop</span>,
						<span class="mixtape-var-obj">$mixtape_object</span></code>
					<p class="description"><?php esc_html_e( 'Allows to override email sending logic.', 'mixtape' ); ?></p>
				</li>

				<li class="mixtape-hook-block">
					<code>'mixtape_mail_recipient', <span class="mixtape-var-str">$recipient</span>, <span class="mixtape-var-str">$url</span>, <span class="mixtape-var-obj">$user</span></code>
					<p class="description"><?php esc_html_e( 'Allows to change email recipient.', 'mixtape' ); ?></p>
				</li>

				<li class="mixtape-hook-block">
					<code>'mixtape_mail_subject', <span class="mixtape-var-str">$subject</span>, <span class="mixtape-var-str">$referrer</span>, <span class="mixtape-var-obj">$user</span></code>
					<p class="description"><?php esc_html_e( 'Allows to change email subject.', 'mixtape' ); ?></p>
				</li>

				<li class="mixtape-hook-block">
					<code>'mixtape_mail_message', <span class="mixtape-var-str">$message</span>, <span class="mixtape-var-str">$referrer</span>, <span class="mixtape-var-obj">$user</span></code>
					<p class="description"><?php esc_html_e( 'Allows to modify email message to send.', 'mixtape' ); ?></p>
				</li>

				<li class="mixtape-hook-block">
					<code>'mixtape_custom_email_handling', <span class="mixtape-var-bool">$stop</span>, <span class="mixtape-var-obj">$ajax_obj</span></code>
					<p class="description">
                    <?php esc_html_e(
												'Allows for custom reports handling. Refer to code for implementation details.',
												'mixtape'
											) ?></p>
				</li>

				<li class="mixtape-hook-block">
					<code>'mixtape_options', <span class="mixtape-var-arr">$options</span></code>
					<p class="description">
                    <?php esc_html_e(
												'Allows to modify global options array during initialization.',
												'mixtape'
											) ?></p>
				</li>

				<li class="mixtape-hook-block">
					<code>'mixtape_is_appropriate_post', <span class="mixtape-var-bool">$result</span></code>
					<p class="description">
                    <?php esc_html_e(
												'Allows to add custom logic for whether to output Mixtape to front end or not.',
												'mixtape'
											) ?></p>
				</li>

			</ul>
		</div>
<?php
	}

	public function insert_dialog() {
		$args = array(
			'reported_text_preview' => 'Lorem <span class="mixtape_mistake_highlight">upsum</span> dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
		);
		echo wp_kses_post( $this->get_dialog_html( $args ) );
	}
}
