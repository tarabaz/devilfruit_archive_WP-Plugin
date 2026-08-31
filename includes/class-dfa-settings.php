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
				'default'           => array(
					'cta_url'                  => '#',
					'archive_background_image' => 0,
					'single_background_image'  => 0,
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

		add_settings_section(
			'dfa_settings_single',
			__( 'Aspetto scheda singola', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_single_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'dfa_single_background_image',
			__( 'Sfondo di riserva scheda singola', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_single_background_field' ),
			self::PAGE_SLUG,
			'dfa_settings_single'
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

		$output['single_background_image'] = isset( $input['single_background_image'] ) ? absint( $input['single_background_image'] ) : 0;

		return $output;
	}

	/**
	 * Testo introduttivo della sezione impostazioni.
	 */
	public static function render_main_section() {
		echo '<p>' . esc_html__( 'Link di contatto (es. DM Instagram, modulo di contatto, canale Discord). Al momento non è usato da nessuna pagina: il bottone "Richiedi questo esemplare" è stato rimosso dalla scheda singola. Il valore resta salvato qui, pronto se in futuro si vorrà rimettere una CTA di contatto.', 'devil-fruit-archive' ) . '</p>';
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

			<hr>

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
