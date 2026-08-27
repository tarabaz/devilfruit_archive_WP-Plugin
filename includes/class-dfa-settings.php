<?php
/**
 * Pagina impostazioni del plugin: URL della CTA "Richiedi questo
 * esemplare" (contatto/DM). Il bottone per lanciare il seed del
 * catalogo viene aggiunto in questa stessa pagina nello step 5.
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFA_Settings {

	/** Nome dell'opzione che contiene le impostazioni del plugin. */
	const OPTION_NAME = 'dfa_settings';

	/** Slug della pagina impostazioni in wp-admin. */
	const PAGE_SLUG = 'dfa-settings';

	/**
	 * Aggancia la registrazione della pagina e delle opzioni.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Aggiunge la voce "Impostazioni" come sottomenu del CPT esemplare.
	 */
	public static function register_settings_page() {
		add_submenu_page(
			'edit.php?post_type=' . DFA_CPT::POST_TYPE,
			__( 'Impostazioni Devil Fruit Archive', 'devil-fruit-archive' ),
			__( 'Impostazioni', 'devil-fruit-archive' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Registra l'opzione e il relativo campo tramite Settings API.
	 */
	public static function register_settings() {
		register_setting(
			'dfa_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => array( 'cta_url' => '#' ),
			)
		);

		add_settings_section(
			'dfa_settings_main',
			__( 'Contatto', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_main_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'dfa_cta_url',
			__( 'URL della CTA (DM / contatti)', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_cta_url_field' ),
			self::PAGE_SLUG,
			'dfa_settings_main'
		);
	}

	/**
	 * Sanitizza le impostazioni prima del salvataggio.
	 *
	 * @param array $input Valori grezzi inviati dal form.
	 * @return array<string,string>
	 */
	public static function sanitize_settings( $input ) {
		$output            = array();
		$output['cta_url'] = isset( $input['cta_url'] ) ? esc_url_raw( trim( $input['cta_url'] ) ) : '#';

		if ( '' === $output['cta_url'] ) {
			$output['cta_url'] = '#';
		}

		return $output;
	}

	/**
	 * Testo introduttivo della sezione impostazioni.
	 */
	public static function render_main_section() {
		echo '<p>' . esc_html__( 'Imposta il link a cui punta il bottone "Richiedi questo esemplare" presente su ogni scheda (es. link a un DM Instagram, un modulo di contatto o un canale Discord).', 'devil-fruit-archive' ) . '</p>';
	}

	/**
	 * Campo input per l'URL della CTA.
	 */
	public static function render_cta_url_field() {
		$settings = get_option( self::OPTION_NAME, array() );
		$cta_url  = ! empty( $settings['cta_url'] ) ? $settings['cta_url'] : '#';
		?>
		<input type="url" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[cta_url]" value="<?php echo esc_attr( $cta_url ); ?>" class="regular-text" placeholder="https://instagram.com/direct/t/...">
		<?php
	}

	/**
	 * Renderizza la pagina impostazioni completa.
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Impostazioni Devil Fruit Archive', 'devil-fruit-archive' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'dfa_settings_group' );
				do_settings_sections( self::PAGE_SLUG );
				submit_button( __( 'Salva impostazioni', 'devil-fruit-archive' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Ritorna l'URL configurato per la CTA "Richiedi questo esemplare".
	 *
	 * @return string URL della CTA, default "#" se non impostato.
	 */
	public static function get_cta_url() {
		$settings = get_option( self::OPTION_NAME, array() );
		return ! empty( $settings['cta_url'] ) ? $settings['cta_url'] : '#';
	}
}
