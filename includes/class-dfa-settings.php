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
	/** Hook suffix della pagina impostazioni, valorizzato dopo la registrazione. */
	private static $page_hook = '';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Carica wp.media e lo script del media uploader (stesso usato dai
	 * meta box) solo nella pagina impostazioni, per il campo "Immagine
	 * di sfondo archivio".
	 *
	 * @param string $hook Hook della pagina admin corrente.
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( $hook !== self::$page_hook ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'dfa-admin-metabox',
			DFA_PLUGIN_URL . 'assets/css/admin-metabox.css',
			array(),
			DFA_VERSION
		);

		wp_enqueue_script(
			'dfa-admin-metabox',
			DFA_PLUGIN_URL . 'assets/js/admin-metabox.js',
			array( 'jquery' ),
			DFA_VERSION,
			true
		);

		wp_localize_script(
			'dfa-admin-metabox',
			'dfaMetabox',
			array(
				'mediaTitle'  => __( 'Seleziona un\'immagine', 'devil-fruit-archive' ),
				'mediaButton' => __( 'Usa questa immagine', 'devil-fruit-archive' ),
			)
		);
	}

	/**
	 * Aggiunge la voce "Impostazioni" come sottomenu del CPT esemplare.
	 */
	public static function register_settings_page() {
		self::$page_hook = add_submenu_page(
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
				'default'           => array(
					'cta_url'                  => '#',
					'archive_background_image' => 0,
				),
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

		add_settings_section(
			'dfa_settings_archive',
			__( 'Aspetto pagina archivio', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_archive_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'dfa_archive_background_image',
			__( 'Immagine di sfondo archivio', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_archive_background_field' ),
			self::PAGE_SLUG,
			'dfa_settings_archive'
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

		$output['archive_background_image'] = isset( $input['archive_background_image'] ) ? absint( $input['archive_background_image'] ) : 0;

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
	 * Testo introduttivo della sezione "Aspetto pagina archivio".
	 */
	public static function render_archive_section() {
		echo '<p>' . esc_html__( 'Immagine decorativa mostrata in alto nella pagina archivio (/archivio/), a piena larghezza, dietro l\'intestazione e la parte superiore della griglia.', 'devil-fruit-archive' ) . '</p>';
	}

	/**
	 * Campo media uploader per l'immagine di sfondo dell'archivio.
	 * Stessa struttura (.dfa-image-field) usata nei meta box, così
	 * assets/js/admin-metabox.js la gestisce senza bisogno di JS dedicato.
	 */
	public static function render_archive_background_field() {
		$settings = get_option( self::OPTION_NAME, array() );
		$image_id = ! empty( $settings['archive_background_image'] ) ? (int) $settings['archive_background_image'] : 0;
		$input_id = 'dfa_archive_background_image';
		$preview  = $image_id ? wp_get_attachment_image( $image_id, 'medium' ) : '';
		?>
		<div class="dfa-image-field" data-target="<?php echo esc_attr( $input_id ); ?>">
			<div class="dfa-image-field__preview"><?php echo wp_kses_post( $preview ); ?></div>
			<input type="hidden" id="<?php echo esc_attr( $input_id ); ?>" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[archive_background_image]" value="<?php echo esc_attr( $image_id ); ?>">
			<p>
				<button type="button" class="button dfa-image-field__select"><?php esc_html_e( 'Seleziona immagine', 'devil-fruit-archive' ); ?></button>
				<button type="button" class="button dfa-image-field__remove" <?php echo $image_id ? '' : 'style="display:none"'; ?>><?php esc_html_e( 'Rimuovi immagine', 'devil-fruit-archive' ); ?></button>
			</p>
		</div>
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

			<hr>

			<h2><?php esc_html_e( 'Seed del catalogo', 'devil-fruit-archive' ); ?></h2>
			<p>
				<?php esc_html_e( 'Crea i 17 esemplari di partenza leggendo i dati testuali da _seed/Devil_Fruit_Archive_Catalogo.md. L\'operazione è idempotente: un esemplare con lo stesso Catalog ID già presente viene saltato, non duplicato.', 'devil-fruit-archive' ); ?>
				<br>
				<?php esc_html_e( 'Le immagini (featured image e foto proprietari) non vengono importate: vanno caricate a mano dopo il seed.', 'devil-fruit-archive' ); ?>
			</p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<?php wp_nonce_field( DFA_Seed::NONCE_ACTION ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( DFA_Seed::ACTION ); ?>">
				<?php submit_button( __( 'Lancia il seed del catalogo', 'devil-fruit-archive' ), 'secondary' ); ?>
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

	/**
	 * Ritorna l'ID dell'immagine di sfondo della pagina archivio.
	 *
	 * @return int ID allegato, 0 se non impostata.
	 */
	public static function get_archive_background_image_id() {
		$settings = get_option( self::OPTION_NAME, array() );
		return ! empty( $settings['archive_background_image'] ) ? (int) $settings['archive_background_image'] : 0;
	}
}
