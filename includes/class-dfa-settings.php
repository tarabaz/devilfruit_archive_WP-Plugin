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

	/** Dimensione del titolo della scheda singola, in px, sui monitor. */
	const TITLE_SIZE_DEFAULT = 60;

	/** Limiti accettati per la dimensione del titolo. */
	const TITLE_SIZE_MIN = 20;
	const TITLE_SIZE_MAX = 160;

	/**
	 * Rapporto fra la dimensione del titolo su schermo stretto e quella
	 * sui monitor: il valore originale era 44px su 60px. Impostando la
	 * misura grande, quella piccola segue nella stessa proporzione.
	 */
	const TITLE_SIZE_MOBILE_RATIO = 44 / 60;

	/**
	 * Dimensione del Catalog ID come percentuale del titolo, non in px:
	 * il titolo si rimpicciolisce da solo su schermo stretto, e una
	 * misura fissa lo farebbe diventare piu grande del titolo stesso.
	 */
	const ID_SIZE_DEFAULT = 50;
	const ID_SIZE_MIN     = 10;
	const ID_SIZE_MAX     = 100;

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
		add_action( 'update_option_' . self::OPTION_NAME, array( __CLASS__, 'flush_page_caches' ) );
	}

	/**
	 * Svuota la cache di pagina dopo il salvataggio delle impostazioni.
	 *
	 * Le immagini scelte qui finiscono nell'HTML della pagina, non nel
	 * file .css: con un plugin di cache attivo la pagina già salvata
	 * continuerebbe a essere servita con i valori vecchi, facendo
	 * sembrare che l'impostazione "non funzioni" anche quando è stata
	 * salvata correttamente (il cache-busting su DFA_VERSION riguarda
	 * solo gli asset, non l'HTML). Qui si invitano i
	 * principali plugin di cache a rigenerare, se presenti: ogni
	 * chiamata è protetta da function_exists/class_exists, quindi su un
	 * sito senza cache non succede nulla.
	 */
	public static function flush_page_caches() {
		if ( function_exists( 'rocket_clean_domain' ) ) {          // WP Rocket
			rocket_clean_domain();
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {               // W3 Total Cache
			w3tc_flush_all();
		}
		if ( function_exists( 'wp_cache_clear_cache' ) ) {         // WP Super Cache
			wp_cache_clear_cache();
		}
		if ( function_exists( 'ccfm_clear_all_cache' ) ) {         // Cache Enabler / vari
			ccfm_clear_all_cache();
		}
		if ( class_exists( 'LiteSpeed\Purge' ) ) {                 // LiteSpeed Cache
			do_action( 'litespeed_purge_all' );
		}
		if ( class_exists( 'autoptimizeCache' ) ) {                // Autoptimize
			autoptimizeCache::clearall();
		}
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {    // SiteGround Optimizer
			sg_cachepress_purge_cache();
		}

		// Cache oggetti (Redis/Memcached) e transient del core.
		wp_cache_flush();
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

		/*
		 * Script dell'importazione a lotti: serve solo quando ce n'è una
		 * da portare avanti, cioè subito dopo il caricamento del
		 * pacchetto o al rientro su una rimasta a metà.
		 */
		if ( ! DFA_Transfer::get_job() ) {
			return;
		}

		wp_enqueue_script(
			'dfa-admin-import',
			DFA_PLUGIN_URL . 'assets/js/admin-import.js',
			array( 'jquery' ),
			DFA_VERSION,
			true
		);

		wp_localize_script(
			'dfa-admin-import',
			'dfaImport',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'action'       => DFA_Transfer::STEP_ACTION,
				'nonce'        => wp_create_nonce( DFA_Transfer::STEP_ACTION ),
				'startMessage' => __( 'Importazione avviata…', 'devil-fruit-archive' ),
				'errorMessage' => __( 'Importazione interrotta da un errore.', 'devil-fruit-archive' ),
				/* translators: 1: esemplari creati, 2: aggiornati, 3: immagini. */
				'doneMessage'  => __( 'Fatto: %1$d esemplari creati, %2$d aggiornati, %3$d immagini caricate.', 'devil-fruit-archive' ),
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
				'default'           => self::defaults(),
			)
		);

		/* --- Scheda "Aspetto" --- */

		add_settings_section(
			'dfa_settings_archive',
			__( 'Pagina archivio', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_archive_section' ),
			self::PAGE_SLUG . '-aspetto'
		);

		add_settings_field(
			'dfa_archive_background_image',
			__( 'Immagine di sfondo', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_archive_background_field' ),
			self::PAGE_SLUG . '-aspetto',
			'dfa_settings_archive'
		);

		add_settings_field(
			'dfa_archive_bg_opacity',
			__( 'Visibilità dello sfondo', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_archive_bg_opacity_field' ),
			self::PAGE_SLUG . '-aspetto',
			'dfa_settings_archive'
		);

		add_settings_section(
			'dfa_settings_single',
			__( 'Scheda esemplare', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_single_section' ),
			self::PAGE_SLUG . '-aspetto'
		);

		add_settings_field(
			'dfa_single_background_image',
			__( 'Sfondo di riserva', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_single_background_field' ),
			self::PAGE_SLUG . '-aspetto',
			'dfa_settings_single'
		);

		add_settings_field(
			'dfa_single_bg_opacity',
			__( 'Visibilità dello sfondo', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_single_bg_opacity_field' ),
			self::PAGE_SLUG . '-aspetto',
			'dfa_settings_single'
		);

		add_settings_field(
			'dfa_single_title_size',
			__( 'Dimensione del titolo', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_single_title_size_field' ),
			self::PAGE_SLUG . '-aspetto',
			'dfa_settings_single'
		);

		add_settings_field(
			'dfa_single_title_opacity',
			__( 'Visibilità del titolo', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_single_title_opacity_field' ),
			self::PAGE_SLUG . '-aspetto',
			'dfa_settings_single'
		);

		/* --- Scheda "Footer" --- */

		add_settings_field(
			'dfa_single_id_size',
			__( 'Dimensione del Catalog ID', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_single_id_size_field' ),
			self::PAGE_SLUG . '-aspetto',
			'dfa_settings_single'
		);

		add_settings_field(
			'dfa_single_id_opacity',
			__( 'Visibilità del Catalog ID', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_single_id_opacity_field' ),
			self::PAGE_SLUG . '-aspetto',
			'dfa_settings_single'
		);

		add_settings_section(
			'dfa_settings_footer',
			__( 'Riga in fondo alle pagine', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_footer_section' ),
			self::PAGE_SLUG . '-footer'
		);

		add_settings_field(
			'dfa_footer_privacy_url',
			__( 'Link alle informative', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_footer_privacy_field' ),
			self::PAGE_SLUG . '-footer',
			'dfa_settings_footer'
		);

		add_settings_field(
			'dfa_footer_owner',
			__( 'Intestatario del copyright', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_footer_owner_field' ),
			self::PAGE_SLUG . '-footer',
			'dfa_settings_footer'
		);

		/* --- Scheda "Avanzate" --- */

		add_settings_section(
			'dfa_settings_advanced',
			__( 'Impostazioni avanzate', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_advanced_section' ),
			self::PAGE_SLUG . '-avanzate'
		);

		add_settings_field(
			'dfa_cta_url',
			__( 'URL della CTA (non in uso)', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_cta_url_field' ),
			self::PAGE_SLUG . '-avanzate',
			'dfa_settings_advanced'
		);
	}

	/**
	 * Valori predefiniti di tutte le impostazioni, in un posto solo:
	 * li usano register_setting(), il salvataggio e la lettura.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'cta_url'                     => '#',
			'archive_background_image'    => 0,
			'archive_bg_opacity'          => 100,
			'archive_bg_opacity_mobile'   => 100,
			'single_background_image'     => 0,
			'single_bg_opacity'           => 100,
			'single_bg_opacity_mobile'    => 100,
			'single_title_size'           => self::TITLE_SIZE_DEFAULT,
			'single_title_opacity'        => 100,
			'single_title_opacity_mobile' => 100,
			'single_id_size'              => self::ID_SIZE_DEFAULT,
			'single_id_opacity'           => 100,
			'single_id_opacity_mobile'    => 100,
			'footer_privacy_url'          => '',
			'footer_owner'                => '',
		);
	}

	/**
	 * Un'impostazione, con il suo valore predefinito se non c'è.
	 *
	 * @param string $key Chiave dell'impostazione.
	 * @return mixed
	 */
	public static function get( $key ) {
		$settings = get_option( self::OPTION_NAME, array() );
		$defaults = self::defaults();

		if ( isset( $settings[ $key ] ) && '' !== $settings[ $key ] ) {
			return $settings[ $key ];
		}

		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	}

	/**
	 * Sanitizza le impostazioni prima del salvataggio.
	 *
	 * @param array $input Valori grezzi inviati dal form.
	 * @return array<string,string>
	 */
	public static function sanitize_settings( $input ) {
		/*
		 * Si parte da quello che c'e gia e si toccano SOLO i campi
		 * presenti nell'invio. La pagina e divisa in schede e ogni scheda
		 * manda solo i propri campi: ricostruendo l'array da zero si
		 * azzererebbero le impostazioni delle altre schede a ogni
		 * salvataggio.
		 */
		$output = wp_parse_args( get_option( self::OPTION_NAME, array() ), self::defaults() );

		if ( ! is_array( $input ) ) {
			return $output;
		}

		if ( isset( $input['cta_url'] ) ) {
			$url               = esc_url_raw( trim( $input['cta_url'] ) );
			$output['cta_url'] = '' !== $url ? $url : '#';
		}

		foreach ( array( 'archive_background_image', 'single_background_image' ) as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$output[ $key ] = absint( $input[ $key ] );
			}
		}

		// Percentuali: fuori scala si riportano dentro invece di
		// rifiutare, sia qui sia in lettura, cosi anche un valore
		// modificato a mano nel database resta innocuo.
		foreach ( self::opacity_keys() as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$output[ $key ] = max( 0, min( 100, absint( $input[ $key ] ) ) );
			}
		}

		if ( isset( $input['single_title_size'] ) ) {
			$size = absint( $input['single_title_size'] );
			if ( ! $size ) {
				$size = self::TITLE_SIZE_DEFAULT;
			}
			$output['single_title_size'] = max( self::TITLE_SIZE_MIN, min( self::TITLE_SIZE_MAX, $size ) );
		}

		if ( isset( $input['single_id_size'] ) ) {
			$id_size = absint( $input['single_id_size'] );
			if ( ! $id_size ) {
				$id_size = self::ID_SIZE_DEFAULT;
			}
			$output['single_id_size'] = max( self::ID_SIZE_MIN, min( self::ID_SIZE_MAX, $id_size ) );
		}

		if ( isset( $input['footer_privacy_url'] ) ) {
			$output['footer_privacy_url'] = esc_url_raw( trim( $input['footer_privacy_url'] ) );
		}

		if ( isset( $input['footer_owner'] ) ) {
			$output['footer_owner'] = sanitize_text_field( trim( $input['footer_owner'] ) );
		}

		return $output;
	}

	/**
	 * Le impostazioni espresse in percentuale, tutte trattate allo stesso
	 * modo da salvataggio e lettura.
	 *
	 * @return string[]
	 */
	private static function opacity_keys() {
		return array(
			'archive_bg_opacity',
			'archive_bg_opacity_mobile',
			'single_bg_opacity',
			'single_bg_opacity_mobile',
			'single_title_opacity',
			'single_title_opacity_mobile',
			'single_id_opacity',
			'single_id_opacity_mobile',
		);
	}

	/**
	 * Percentuale letta e riportata comunque fra 0 e 100.
	 *
	 * @param string $key Chiave dell'impostazione.
	 * @return int
	 */
	private static function opacity( $key ) {
		$settings = get_option( self::OPTION_NAME, array() );
		$value    = isset( $settings[ $key ] ) ? (int) $settings[ $key ] : 100;

		return max( 0, min( 100, $value ) );
	}

	/**
	 * Coppia di campi percentuale desktop/mobile sulla stessa riga: sono
	 * la stessa impostazione su due schermi, separarle in due righe le
	 * farebbe sembrare due cose diverse.
	 *
	 * @param string $key         Chiave del valore desktop.
	 * @param string $description Testo esplicativo sotto la coppia.
	 */
	private static function render_opacity_pair( $key, $description ) {
		$fields = array(
			$key             => __( 'Desktop', 'devil-fruit-archive' ),
			$key . '_mobile' => __( 'Mobile', 'devil-fruit-archive' ),
		);
		?>
		<div class="dfa-field-pair">
			<?php foreach ( $fields as $name => $label ) : ?>
				<label class="dfa-field-pair__item">
					<span class="dfa-field-pair__label"><?php echo esc_html( $label ); ?></span>
					<input type="number" name="<?php echo esc_attr( self::OPTION_NAME . '[' . $name . ']' ); ?>"
						value="<?php echo esc_attr( (string) self::opacity( $name ) ); ?>"
						min="0" max="100" step="1" class="small-text"> %
				</label>
			<?php endforeach; ?>
		</div>
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php
	}

	/**
	 * Testo introduttivo della sezione impostazioni.
	 */
	public static function render_advanced_section() {
		echo '<p>' . esc_html__( 'Roba che non serve tutti i giorni, tenuta fuori dalle altre schede per non appesantirle.', 'devil-fruit-archive' ) . '</p>';
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
	 * Testo introduttivo della sezione "Aspetto scheda singola".
	 */
	public static function render_single_section() {
		echo '<p>' . esc_html__( 'Ogni esemplare usa come sfondo la propria "Foto proprietario attuale". Questa immagine viene usata solo come riserva, sugli esemplari che non ne hanno ancora una caricata.', 'devil-fruit-archive' ) . '</p>';
	}

	/**
	 * Campo media uploader per lo sfondo di riserva della scheda singola.
	 */
	public static function render_single_background_field() {
		$settings = get_option( self::OPTION_NAME, array() );
		$image_id = ! empty( $settings['single_background_image'] ) ? (int) $settings['single_background_image'] : 0;
		$input_id = 'dfa_single_background_image';
		$preview  = $image_id ? wp_get_attachment_image( $image_id, 'medium' ) : '';
		?>
		<div class="dfa-image-field" data-target="<?php echo esc_attr( $input_id ); ?>">
			<div class="dfa-image-field__preview"><?php echo wp_kses_post( $preview ); ?></div>
			<input type="hidden" id="<?php echo esc_attr( $input_id ); ?>" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[single_background_image]" value="<?php echo esc_attr( $image_id ); ?>">
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
		$tabs = self::get_tabs();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sola navigazione fra schede.
		$current = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'aspetto';
		if ( ! isset( $tabs[ $current ] ) ) {
			$current = 'aspetto';
		}
		?>
		<div class="wrap dfa-settings">
			<h1><?php esc_html_e( 'Devil Fruit Archive', 'devil-fruit-archive' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<a class="nav-tab <?php echo $slug === $current ? 'nav-tab-active' : ''; ?>"
						href="<?php echo esc_url( self::tab_url( $slug ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<?php if ( 'aspetto' === $current || 'footer' === $current || 'avanzate' === $current ) : ?>
				<?php
				/*
				 * Un modulo per scheda, con gli stessi campi nascosti di
				 * sempre: options.php rimanda alla scheda da cui si e
				 * salvato, invece che alla prima.
				 */
				?>
				<form action="options.php" method="post">
					<?php
					settings_fields( 'dfa_settings_group' );
					printf(
						'<input type="hidden" name="_wp_http_referer" value="%s">',
						esc_url( self::tab_url( $current ) )
					);
					do_settings_sections( self::PAGE_SLUG . '-' . $current );
					submit_button( __( 'Salva impostazioni', 'devil-fruit-archive' ) );
					?>
				</form>
			<?php endif; ?>

			<?php if ( 'avanzate' === $current ) : ?>

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

			<?php endif; ?>

			<?php if ( 'backup' === $current ) : ?>

			<h2><?php esc_html_e( 'Esporta / Importa archivio', 'devil-fruit-archive' ); ?></h2>

			<?php if ( ! DFA_Transfer::is_available() ) : ?>
				<div class="notice notice-warning inline"><p>
					<?php esc_html_e( 'L\'estensione PHP "zip" non è attiva su questo server, quindi esportazione e importazione non sono utilizzabili. Chiedi al tuo hosting di abilitare ZipArchive.', 'devil-fruit-archive' ); ?>
				</p></div>
			<?php else : ?>

				<p>
					<?php esc_html_e( 'Il pacchetto .zip contiene gli esemplari con i loro campi, le impostazioni del plugin e i file immagine veri e propri (foto esemplare, versione accesa, immagine frutto, foto proprietario e sfondi). Serve sia da backup sia per spostare l\'archivio su un altro sito.', 'devil-fruit-archive' ); ?>
				</p>

				<h3><?php esc_html_e( 'Esporta', 'devil-fruit-archive' ); ?></h3>

				<?php
				$dfa_parts    = DFA_Transfer::get_export_parts();
				$dfa_max_size = wp_max_upload_size();
				?>

				<?php if ( empty( $dfa_parts ) ) : ?>

					<p class="description"><?php esc_html_e( 'Non c\'è ancora nessun esemplare da esportare.', 'devil-fruit-archive' ); ?></p>

				<?php else : ?>

					<p class="description" style="max-width:640px">
						<?php
						printf(
							/* translators: 1: esemplari per pacchetto, 2: numero di pacchetti. */
							esc_html__( 'Il backup è diviso in pacchetti da %1$d esemplari (%2$d in tutto): un archivio intero diventa presto un file troppo grande per essere ricaricato. Scaricali tutti e importali uno alla volta, in qualsiasi ordine. Le impostazioni del plugin viaggiano nel primo pacchetto.', 'devil-fruit-archive' ),
							(int) DFA_Transfer::EXPORT_CHUNK,
							count( $dfa_parts )
						);
						?>
					</p>

					<table class="widefat striped" style="max-width:640px;margin-bottom:8px">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Pacchetto', 'devil-fruit-archive' ); ?></th>
								<th><?php esc_html_e( 'Esemplari', 'devil-fruit-archive' ); ?></th>
								<th><?php esc_html_e( 'Peso stimato', 'devil-fruit-archive' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $dfa_parts as $dfa_part ) : ?>
							<?php
							$dfa_url = wp_nonce_url(
								add_query_arg(
									array(
										'action' => DFA_Transfer::EXPORT_ACTION,
										'part'   => $dfa_part['number'],
									),
									admin_url( 'admin-post.php' )
								),
								DFA_Transfer::EXPORT_ACTION
							);
							// Un pacchetto più pesante del limite di caricamento si
							// scarica lo stesso, ma non si potrebbe reimportare da qui.
							$dfa_too_big = $dfa_max_size && $dfa_part['bytes'] > $dfa_max_size;
							?>
							<tr>
								<td><strong><?php echo esc_html( $dfa_part['label'] ); ?></strong></td>
								<td><?php echo esc_html( (string) $dfa_part['count'] ); ?></td>
								<td<?php echo $dfa_too_big ? ' style="color:#d63638"' : ''; ?>>
									<?php echo esc_html( size_format( $dfa_part['bytes'] ) ); ?>
									<?php if ( $dfa_too_big ) : ?>
										<br><small><?php esc_html_e( 'oltre il limite di caricamento', 'devil-fruit-archive' ); ?></small>
									<?php endif; ?>
								</td>
								<td>
									<a class="button" href="<?php echo esc_url( $dfa_url ); ?>">
										<?php esc_html_e( 'Scarica', 'devil-fruit-archive' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>

				<h3 style="margin-top:24px"><?php esc_html_e( 'Importa', 'devil-fruit-archive' ); ?></h3>
				<p class="description" style="max-width:640px">
					<?php esc_html_e( 'L\'import è idempotente sul Catalog ID: un esemplare già presente viene aggiornato, non duplicato. Le immagini del pacchetto vengono caricate nella Libreria media di questo sito e ricollegate ai campi corretti. Le impostazioni del plugin presenti nel pacchetto sovrascrivono quelle attuali.', 'devil-fruit-archive' ); ?>
				</p>
				<p class="description" style="max-width:640px">
					<?php
					printf(
						/* translators: %s: dimensione massima di caricamento del server. */
						esc_html__( 'L\'importazione avviene a lotti, con una barra di avanzamento: si può seguire fino alla fine senza rischiare un timeout. La pagina va però lasciata aperta finché non finisce. Dimensione massima del file accettata da questo server: %s.', 'devil-fruit-archive' ),
						esc_html( size_format( wp_max_upload_size() ) )
					);
					?>
				</p>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data" class="dfa-import-form">
					<?php wp_nonce_field( DFA_Transfer::IMPORT_ACTION ); ?>
					<input type="hidden" name="action" value="<?php echo esc_attr( DFA_Transfer::IMPORT_ACTION ); ?>">
					<p><input type="file" name="dfa_import_file" accept=".zip" required></p>
					<?php submit_button( __( 'Importa dal pacchetto', 'devil-fruit-archive' ), 'secondary', 'submit', false ); ?>
				</form>

				<?php // Compare solo quando c'è un'importazione da eseguire: la riempie admin-import.js. ?>
				<div class="dfa-import-progress" hidden>
					<div class="dfa-import-progress__bar"><span class="dfa-import-progress__fill" style="width:0"></span></div>
					<p class="dfa-import-progress__text"></p>
				</div>

			<?php endif; ?>

			<?php endif; ?>

			<p style="margin-top:24px;color:#787c82">
				<?php
				printf(
					/* translators: %s: numero di versione del plugin. */
					esc_html__( 'Devil Fruit Archive — versione %s', 'devil-fruit-archive' ),
					esc_html( DFA_VERSION )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Le schede della pagina impostazioni.
	 *
	 * @return array<string,string>
	 */
	private static function get_tabs() {
		return array(
			'aspetto'  => __( 'Aspetto', 'devil-fruit-archive' ),
			'footer'   => __( 'Footer', 'devil-fruit-archive' ),
			'backup'   => __( 'Backup', 'devil-fruit-archive' ),
			'avanzate' => __( 'Avanzate', 'devil-fruit-archive' ),
		);
	}

	/**
	 * Indirizzo di una scheda della pagina impostazioni.
	 *
	 * @param string $slug Scheda.
	 * @return string
	 */
	private static function tab_url( $slug ) {
		return add_query_arg(
			array(
				'post_type' => DFA_CPT::POST_TYPE,
				'page'      => self::PAGE_SLUG,
				'tab'       => $slug,
			),
			admin_url( 'edit.php' )
		);
	}

	/**
	 * Visibilità dello sfondo della pagina archivio.
	 */
	public static function render_archive_bg_opacity_field() {
		self::render_opacity_pair(
			'archive_bg_opacity',
			__( 'Quanto si vede la foto di sfondo: 100 piena, 0 invisibile. I veli e la sfumatura verso il fondo pagina restano invariati.', 'devil-fruit-archive' )
		);
	}

	/**
	 * Visibilità dello sfondo della scheda esemplare.
	 */
	public static function render_single_bg_opacity_field() {
		self::render_opacity_pair(
			'single_bg_opacity',
			__( 'Vale sia per la foto del proprietario sia per lo sfondo di riserva. Su mobile puoi tenerla più alta: lì la foto è già molto ridotta dai veli.', 'devil-fruit-archive' )
		);
	}

	/**
	 * Campo dimensione del titolo della scheda singola.
	 */
	public static function render_single_title_size_field() {
		$settings = get_option( self::OPTION_NAME, array() );
		$value    = isset( $settings['single_title_size'] ) ? (int) $settings['single_title_size'] : self::TITLE_SIZE_DEFAULT;
		?>
		<input type="number" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[single_title_size]"
			value="<?php echo esc_attr( (string) $value ); ?>"
			min="<?php echo esc_attr( (string) self::TITLE_SIZE_MIN ); ?>"
			max="<?php echo esc_attr( (string) self::TITLE_SIZE_MAX ); ?>" step="1" class="small-text"> px
		<p class="description">
			<?php
			printf(
				/* translators: 1: dimensione predefinita, 2: dimensione corrispondente su schermo stretto. */
				esc_html__( 'Dimensione di "VEGAPUNK RESEARCH DIVISION" nelle schede, sui monitor (predefinita %1$d px). Su schermo stretto parte da %2$d px e si rimpicciolisce quanto basta per stare su una riga sola, senza andare a capo. Il Catalog ID sotto resta sempre a metà del titolo.', 'devil-fruit-archive' ),
				(int) self::TITLE_SIZE_DEFAULT,
				(int) round( $value * self::TITLE_SIZE_MOBILE_RATIO )
			);
			?>
		</p>
		<?php
	}

	/**
	 * Campo dimensione del Catalog ID, in percentuale del titolo.
	 */
	public static function render_single_id_size_field() {
		$value      = (int) self::get( 'single_id_size' );
		$title_size = (int) self::get( 'single_title_size' );
		?>
		<input type="number" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[single_id_size]"
			value="<?php echo esc_attr( (string) $value ); ?>"
			min="<?php echo esc_attr( (string) self::ID_SIZE_MIN ); ?>"
			max="<?php echo esc_attr( (string) self::ID_SIZE_MAX ); ?>" step="1" class="small-text"> %
		<p class="description">
			<?php
			printf(
				/* translators: 1: percentuale, 2: dimensione risultante in px, 3: dimensione del titolo. */
				esc_html__( 'Dimensione del "DF-001" sotto al titolo, in percentuale del titolo stesso (predefinita 50%%). Col titolo a %3$d px viene %2$d px. È una percentuale e non una misura fissa perché su schermo stretto il titolo si rimpicciolisce da solo per stare in riga: in px il Catalog ID finirebbe per diventare più grande del titolo.', 'devil-fruit-archive' ),
				$value,
				(int) round( $title_size * $value / 100 ),
				$title_size
			);
			?>
		</p>
		<?php
	}

	/**
	 * Visibilità del Catalog ID sotto al titolo.
	 */
	public static function render_single_id_opacity_field() {
		self::render_opacity_pair(
			'single_id_opacity',
			__( 'Quanto è visibile il "DF-001" sotto al titolo: 100 pieno, 0 invisibile.', 'devil-fruit-archive' )
		);
	}

	/**
	 * Visibilità del titolo della scheda esemplare.
	 */
	public static function render_single_title_opacity_field() {
		self::render_opacity_pair(
			'single_title_opacity',
			__( 'Quanto è visibile "VEGAPUNK RESEARCH DIVISION": 100 pieno, 0 invisibile. Vale solo per la scritta, non per il Catalog ID sotto.', 'devil-fruit-archive' )
		);
	}

	/**
	 * Variabili CSS con le scelte di aspetto, pronte da accodare al
	 * foglio di stile: dimensione e visibilita del titolo e visibilita
	 * degli sfondi, ciascuna nella versione desktop e in quella mobile.
	 *
	 * @return string
	 */
	public static function get_style_vars() {
		$size = (int) self::get( 'single_title_size' );
		$size = max( self::TITLE_SIZE_MIN, min( self::TITLE_SIZE_MAX, $size ) );

		$id_size = (int) self::get( 'single_id_size' );
		$id_size = max( self::ID_SIZE_MIN, min( self::ID_SIZE_MAX, $id_size ) );

		$vars = array(
			'--dfa-title-size'        => $size . 'px',
			'--dfa-title-size-mobile' => (int) round( $size * self::TITLE_SIZE_MOBILE_RATIO ) . 'px',
			// Percentuale: si risolve sul font-size del titolo.
			'--dfa-id-size'           => $id_size . '%',
		);

		// Le percentuali diventano frazioni: due decimali bastano ed
		// evitano notazioni tipo 0.6699999.
		$map = array(
			'--dfa-title-opacity'             => 'single_title_opacity',
			'--dfa-title-opacity-mobile'      => 'single_title_opacity_mobile',
			'--dfa-single-bg-opacity'         => 'single_bg_opacity',
			'--dfa-single-bg-opacity-mobile'  => 'single_bg_opacity_mobile',
			'--dfa-archive-bg-opacity'        => 'archive_bg_opacity',
			'--dfa-archive-bg-opacity-mobile' => 'archive_bg_opacity_mobile',
			'--dfa-id-opacity'                => 'single_id_opacity',
			'--dfa-id-opacity-mobile'         => 'single_id_opacity_mobile',
		);

		foreach ( $map as $var => $key ) {
			$vars[ $var ] = number_format( self::opacity( $key ) / 100, 2, '.', '' );
		}

		$declarations = array();
		foreach ( $vars as $var => $value ) {
			$declarations[] = $var . ':' . $value;
		}

		return ':root{' . implode( ';', $declarations ) . '}';
	}

	/**
	 * Testo introduttivo della sezione footer.
	 */
	public static function render_footer_section() {
		echo '<p>' . esc_html__( 'Riga minuta e centrata in fondo alle pagine dell\'archivio: "Privacy Policy · Cookie Policy · © anno Intestatario · Tutti i diritti riservati". Le pagine dell\'archivio sono documenti indipendenti dal tema, quindi il footer di Avada non compare: questa riga ne riporta i contenuti essenziali nello stile dell\'archivio. L\'anno si aggiorna da solo.', 'devil-fruit-archive' ) . '</p>';
	}

	/**
	 * Campo URL della privacy policy.
	 */
	public static function render_footer_privacy_field() {
		$settings = get_option( self::OPTION_NAME, array() );
		$value    = isset( $settings['footer_privacy_url'] ) ? $settings['footer_privacy_url'] : '';
		?>
		<input type="url" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[footer_privacy_url]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="https://www.francystore3d.it/privacy-policy/">
		<p class="description"><?php esc_html_e( 'Ci puntano entrambe le diciture, "Privacy Policy" e "Cookie Policy", perché sono un unico link. Se lasciato vuoto si usa la pagina indicata in Impostazioni → Privacy di WordPress; se non c\'è nemmeno quella, la riga mostra solo il copyright.', 'devil-fruit-archive' ); ?></p>
		<?php
	}

	/**
	 * Campo intestatario del copyright.
	 */
	public static function render_footer_owner_field() {
		$settings = get_option( self::OPTION_NAME, array() );
		$value    = isset( $settings['footer_owner'] ) ? $settings['footer_owner'] : '';
		?>
		<input type="text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[footer_owner]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
		<p class="description"><?php esc_html_e( 'Se lasciato vuoto si usa il nome del sito. L\'anno viene aggiunto in automatico.', 'devil-fruit-archive' ); ?></p>
		<?php
	}

	/**
	 * Contenuti della barra in fondo alle pagine dell'archivio, già
	 * risolti con i loro ripieghi: la usa templates/parts/footer-bar.php.
	 *
	 * @return array{privacy:string,owner:string,year:string}
	 */
	public static function get_footer_data() {
		$settings = get_option( self::OPTION_NAME, array() );

		$privacy = isset( $settings['footer_privacy_url'] ) ? $settings['footer_privacy_url'] : '';
		if ( '' === $privacy ) {
			// Ripiego: la pagina scelta in Impostazioni → Privacy.
			$privacy = (string) get_privacy_policy_url();
		}

		$owner = isset( $settings['footer_owner'] ) ? $settings['footer_owner'] : '';
		if ( '' === $owner ) {
			$owner = (string) get_bloginfo( 'name' );
		}

		return array(
			'privacy' => $privacy,
			'owner'   => $owner,
			// wp_date() rispetta il fuso orario del sito: a Capodanno
			// l'anno cambia quando cambia lì, non a Greenwich.
			'year'    => wp_date( 'Y' ),
		);
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

	/**
	 * Ritorna l'ID dello sfondo di riserva per la scheda singola, usato
	 * sugli esemplari senza "Immagine frutto" caricata.
	 *
	 * @return int ID allegato, 0 se non impostato.
	 */
	public static function get_single_background_image_id() {
		$settings = get_option( self::OPTION_NAME, array() );
		return ! empty( $settings['single_background_image'] ) ? (int) $settings['single_background_image'] : 0;
	}
}
