<?php
/**
 * Esportazione e importazione completa dell'archivio: tutti gli
 * esemplari con i loro campi, le impostazioni del plugin e i file
 * immagine veri e propri, in un unico file .zip.
 *
 * Struttura del pacchetto:
 *
 *   archivio.json      dati di esemplari e impostazioni
 *   images/…           i file immagine originali referenziati dal JSON
 *
 * L'import è idempotente sul Catalog ID: un esemplare già presente
 * viene aggiornato, non duplicato. Le immagini vengono ricaricate nella
 * Libreria media del sito di destinazione e ricollegate ai campi
 * corretti, così il pacchetto è trasferibile fra siti diversi (gli ID
 * allegato del sito di origine non hanno alcun valore altrove).
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFA_Transfer {

	/** Azione admin-post per l'esportazione. */
	const EXPORT_ACTION = 'dfa_export_archive';

	/** Azione admin-post per l'importazione. */
	const IMPORT_ACTION = 'dfa_import_archive';

	/** Nome del file dati dentro il pacchetto. */
	const DATA_FILE = 'archivio.json';

	/** Cartella delle immagini dentro il pacchetto. */
	const IMAGES_DIR = 'images/';

	/**
	 * Opzione che tiene lo stato dell'importazione in corso: cartella di
	 * lavoro, punto a cui si è arrivati, conteggi e mappa delle immagini
	 * già caricate. Serve perché l'import non avviene più in una sola
	 * richiesta ma a lotti, e ogni lotto è una richiesta HTTP diversa.
	 * Ne esiste una sola alla volta: un nuovo import ripulisce quello
	 * eventualmente rimasto a metà.
	 */
	const JOB_OPTION = 'dfa_import_job';

	/** Azione AJAX che esegue un lotto dell'importazione. */
	const STEP_ACTION = 'dfa_import_step';

	/**
	 * Meta con cui si marchia ogni allegato creato dall'importazione:
	 * contiene l'impronta (md5) del file. Serve a NON ricaricare due
	 * volte lo stesso file: senza, ogni importazione creava una copia
	 * nuova di ogni immagine e la Libreria media si riempiva di doppioni,
	 * con i vecchi che restavano lì non più collegati a nulla.
	 *
	 * La chiave inizia con "_" così non compare fra i campi
	 * personalizzati nell'editor.
	 */
	const HASH_META = '_dfa_import_hash';

	/**
	 * Budget di tempo di un lotto. Il controllo avviene FRA un esemplare
	 * e il successivo, mai a metà: un lotto può quindi sforare del costo
	 * dell'ultimo esemplare iniziato (con quattro foto pesanti, una
	 * decina di secondi). Con 8 secondi di budget il caso peggiore resta
	 * ampiamente sotto il max_execution_time tipico di 30s e sotto i
	 * timeout dei proxy.
	 */
	const BATCH_SECONDS = 8;

	/**
	 * Campi di testo esportati per ogni esemplare.
	 *
	 * @return string[]
	 */
	private static function text_fields() {
		return array( 'catalog_id', 'fruit_type', 'romaji_name', 'katakana_name', 'special_note', 'owner_current', 'owner_former', 'lore', 'coming_soon' );
	}

	/**
	 * Campi immagine (meta) esportati per ogni esemplare. La featured
	 * image è gestita a parte perché non è un meta del plugin.
	 *
	 * @return string[]
	 */
	private static function image_fields() {
		return array( 'owner_current_image', 'fruit_image', 'specimen_lit_image' );
	}

	/**
	 * Aggancia le azioni admin-post e la notice di esito.
	 */
	public static function init() {
		add_action( 'admin_post_' . self::EXPORT_ACTION, array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_' . self::IMPORT_ACTION, array( __CLASS__, 'handle_import' ) );
		add_action( 'wp_ajax_' . self::STEP_ACTION, array( __CLASS__, 'ajax_import_step' ) );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_render_notice' ) );
	}

	/**
	 * L'estensione ZipArchive di PHP è indispensabile: senza, la
	 * funzione viene mostrata come non disponibile invece di fallire a
	 * metà operazione.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return class_exists( 'ZipArchive' );
	}

	/* ------------------------------------------------------------------
	 * ESPORTAZIONE
	 * ------------------------------------------------------------------ */

	/**
	 * Costruisce il pacchetto .zip e lo invia al browser come download.
	 */
	public static function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permessi insufficienti per esportare l\'archivio.', 'devil-fruit-archive' ) );
		}

		check_admin_referer( self::EXPORT_ACTION );

		if ( ! self::is_available() ) {
			self::redirect_with( 'error', 'zip_missing' );
		}

		$posts = get_posts(
			array(
				'post_type'        => DFA_CPT::POST_TYPE,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
				'numberposts'      => -1,
				'suppress_filters' => false,
			)
		);

		$data = array(
			'plugin'      => 'devil-fruit-archive',
			'version'     => DFA_VERSION,
			'exported_at' => gmdate( 'c' ),
			'site'        => home_url( '/' ),
			'settings'    => array(),
			'esemplari'   => array(),
		);

		// Elenco file da inserire nello zip: percorso reale => nome nel pacchetto.
		$files = array();

		// --- Impostazioni del plugin (con le loro immagini) ---
		$settings                     = get_option( DFA_Settings::OPTION_NAME, array() );
		$data['settings']['cta_url']  = isset( $settings['cta_url'] ) ? $settings['cta_url'] : '';
		$data['settings']['archive_background_image'] = self::stage_image( isset( $settings['archive_background_image'] ) ? (int) $settings['archive_background_image'] : 0, $files );
		$data['settings']['single_background_image']  = self::stage_image( isset( $settings['single_background_image'] ) ? (int) $settings['single_background_image'] : 0, $files );

		// --- Esemplari ---
		foreach ( $posts as $post ) {
			$entry = array(
				'title'  => $post->post_title,
				'status' => $post->post_status,
				'meta'   => array(),
				'images' => array(),
			);

			foreach ( self::text_fields() as $key ) {
				$entry['meta'][ $key ] = (string) DFA_Meta::get( $post->ID, $key );
			}

			$entry['images']['featured'] = self::stage_image( (int) get_post_thumbnail_id( $post->ID ), $files );

			foreach ( self::image_fields() as $key ) {
				$entry['images'][ $key ] = self::stage_image( (int) DFA_Meta::get( $post->ID, $key ), $files );
			}

			$data['esemplari'][] = $entry;
		}

		// --- Costruzione dello zip in file temporaneo ---
		$tmp_file = wp_tempnam( 'dfa-export.zip' );
		$zip      = new ZipArchive();

		if ( true !== $zip->open( $tmp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			@unlink( $tmp_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			self::redirect_with( 'error', 'zip_create' );
		}

		$zip->addFromString( self::DATA_FILE, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );

		foreach ( $files as $real_path => $zip_name ) {
			if ( file_exists( $real_path ) ) {
				$zip->addFile( $real_path, $zip_name );
			}
		}

		$zip->close();

		$filename = 'devil-fruit-archive-' . gmdate( 'Y-m-d-His' ) . '.zip';

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $tmp_file ) );

		readfile( $tmp_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		@unlink( $tmp_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		exit;
	}

	/**
	 * Registra un allegato fra i file da includere nel pacchetto e
	 * restituisce il nome con cui vi comparirà.
	 *
	 * Il nome è prefissato con l'ID allegato per evitare che due file
	 * originariamente omonimi (es. due "sfondo.jpg" in cartelle diverse
	 * della Libreria media) si sovrascrivano dentro lo zip.
	 *
	 * @param int   $attachment_id ID allegato (0 = nessuna immagine).
	 * @param array $files         Elenco file, passato per riferimento.
	 * @return string Nome nel pacchetto, stringa vuota se non applicabile.
	 */
	private static function stage_image( $attachment_id, array &$files ) {
		if ( $attachment_id <= 0 ) {
			return '';
		}

		$path = get_attached_file( $attachment_id );

		if ( ! $path || ! file_exists( $path ) ) {
			return '';
		}

		$zip_name = self::IMAGES_DIR . $attachment_id . '-' . basename( $path );

		$files[ $path ] = $zip_name;

		return $zip_name;
	}

	/* ------------------------------------------------------------------
	 * IMPORTAZIONE
	 * ------------------------------------------------------------------ */

	/**
	 * Riceve il pacchetto caricato, lo estrae e ricrea esemplari,
	 * immagini e impostazioni.
	 */
	public static function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permessi insufficienti per importare l\'archivio.', 'devil-fruit-archive' ) );
		}

		check_admin_referer( self::IMPORT_ACTION );

		if ( ! self::is_available() ) {
			self::redirect_with( 'error', 'zip_missing' );
		}

		if ( empty( $_FILES['dfa_import_file']['name'] ) || ! isset( $_FILES['dfa_import_file']['tmp_name'] ) ) {
			self::redirect_with( 'error', 'no_file' );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$upload_error = isset( $_FILES['dfa_import_file']['error'] ) ? (int) $_FILES['dfa_import_file']['error'] : UPLOAD_ERR_NO_FILE;
		if ( UPLOAD_ERR_OK !== $upload_error ) {
			self::redirect_with( 'error', 'upload' );
		}

		$original_name = sanitize_file_name( wp_unslash( $_FILES['dfa_import_file']['name'] ) );
		$check         = wp_check_filetype( $original_name, array( 'zip' => 'application/zip' ) );

		if ( 'zip' !== $check['ext'] ) {
			self::redirect_with( 'error', 'not_zip' );
		}

		$tmp_upload = isset( $_FILES['dfa_import_file']['tmp_name'] ) ? $_FILES['dfa_import_file']['tmp_name'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! is_uploaded_file( $tmp_upload ) ) {
			self::redirect_with( 'error', 'upload' );
		}

		/*
		 * Un'importazione precedente interrotta a metà (finestra chiusa,
		 * errore di rete) lascerebbe cartella di lavoro e stato sul
		 * disco: si ripuliscono qui, prima di cominciarne una nuova.
		 */
		$previous = self::get_job();
		if ( ! empty( $previous ) ) {
			self::finish_job( $previous );
		}

		// Cartella di lavoro dentro uploads, rimossa a fine operazione.
		$uploads  = wp_upload_dir();
		$work_dir = trailingslashit( $uploads['basedir'] ) . 'dfa-import-' . wp_generate_password( 8, false );

		if ( ! wp_mkdir_p( $work_dir ) ) {
			self::redirect_with( 'error', 'workdir' );
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $tmp_upload ) ) {
			self::cleanup_dir( $work_dir );
			self::redirect_with( 'error', 'zip_open' );
		}

		/*
		 * Estrazione selettiva e controllata: si accettano solo il file
		 * dati e i file dentro images/. Ogni nome viene normalizzato a
		 * basename, così un pacchetto malevolo non può scrivere fuori
		 * dalla cartella di lavoro tramite percorsi tipo "../../".
		 */
		wp_mkdir_p( $work_dir . '/images' );

		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$name = $zip->getNameIndex( $i );

			if ( false === $name || '/' === substr( $name, -1 ) ) {
				continue;
			}

			if ( self::DATA_FILE === $name ) {
				$contents = $zip->getFromIndex( $i );
				if ( false !== $contents ) {
					file_put_contents( $work_dir . '/' . self::DATA_FILE, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				}
				continue;
			}

			if ( 0 === strpos( $name, self::IMAGES_DIR ) ) {
				$safe = sanitize_file_name( basename( $name ) );

				if ( '' === $safe ) {
					continue;
				}

				// Solo immagini: qualsiasi altro tipo viene ignorato.
				$type = wp_check_filetype( $safe );
				if ( empty( $type['type'] ) || 0 !== strpos( $type['type'], 'image/' ) ) {
					continue;
				}

				$contents = $zip->getFromIndex( $i );
				if ( false !== $contents ) {
					file_put_contents( $work_dir . '/images/' . $safe, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				}
			}
		}

		$zip->close();

		$json_path = $work_dir . '/' . self::DATA_FILE;
		if ( ! file_exists( $json_path ) ) {
			self::cleanup_dir( $work_dir );
			self::redirect_with( 'error', 'no_data' );
		}

		$data = json_decode( file_get_contents( $json_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents

		if ( ! is_array( $data ) || empty( $data['esemplari'] ) || ! is_array( $data['esemplari'] ) ) {
			self::cleanup_dir( $work_dir );
			self::redirect_with( 'error', 'bad_data' );
		}

		/*
		 * Da qui in poi NON si importa nulla: si prepara soltanto il
		 * "lavoro" e si torna subito alla pagina, che poi lo esegue a
		 * lotti via AJAX mostrando la barra di avanzamento.
		 *
		 * Prima l'importazione stava tutta in questa richiesta, ed è il
		 * motivo per cui su pacchetti grossi finiva in timeout: la parte
		 * lenta non è creare i post (millisecondi) ma le immagini, che
		 * per ognuna vengono ricaricate e soprattutto rigenerate in
		 * tutti i formati del sito — un'operazione da uno o più secondi
		 * a immagine, che con qualche centinaio di foto supera qualunque
		 * max_execution_time.
		 */
		$units = count( $data['esemplari'] );
		foreach ( $data['esemplari'] as $entry ) {
			if ( is_array( $entry ) && ! empty( $entry['images'] ) && is_array( $entry['images'] ) ) {
				$units += count( array_filter( $entry['images'] ) );
			}
		}

		self::save_job(
			array(
				'work_dir'    => $work_dir,
				'phase'       => 'esemplari',
				'cursor'      => 0,
				'total'       => count( $data['esemplari'] ),
				'units_done'  => 0,
				'units_total' => max( 1, $units ),
				'created'     => 0,
				'updated'     => 0,
				'images'      => 0,
				'img_map'     => array(),
				'started_at'  => time(),
			)
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type'      => DFA_CPT::POST_TYPE,
					'page'           => DFA_Settings::PAGE_SLUG,
					'dfa_import_job' => 1,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	/* ------------------------------------------------------------------
	 * IMPORTAZIONE A LOTTI
	 * ------------------------------------------------------------------ */

	/**
	 * Stato dell'importazione in corso, o array vuoto se non ce n'è una.
	 *
	 * @return array
	 */
	public static function get_job() {
		$job = get_option( self::JOB_OPTION, array() );
		return is_array( $job ) ? $job : array();
	}

	/**
	 * Salva lo stato dell'importazione. autoload "no": è un dato di
	 * lavoro, non deve pesare su ogni caricamento di pagina.
	 *
	 * @param array $job Stato da salvare.
	 */
	private static function save_job( array $job ) {
		update_option( self::JOB_OPTION, $job, false );
	}

	/**
	 * Chiude l'importazione: cancella la cartella di lavoro e lo stato.
	 *
	 * @param array $job Stato dell'importazione.
	 */
	private static function finish_job( array $job ) {
		if ( ! empty( $job['work_dir'] ) ) {
			self::cleanup_dir( $job['work_dir'] );
		}

		delete_option( self::JOB_OPTION );
	}

	/**
	 * Esegue un lotto dell'importazione e risponde con l'avanzamento.
	 *
	 * Il lotto lavora "a tempo" e non a numero fisso di elementi: gli
	 * esemplari non costano tutti uguale (uno con quattro foto in alta
	 * risoluzione può valere dieci volte uno senza immagini), quindi si
	 * va avanti finché restano secondi nel budget e ci si ferma al primo
	 * elemento completato dopo la scadenza.
	 */
	public static function ajax_import_step() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permessi insufficienti.', 'devil-fruit-archive' ) ), 403 );
		}

		check_ajax_referer( self::STEP_ACTION, 'nonce' );

		$job = self::get_job();

		if ( empty( $job ) || empty( $job['work_dir'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Nessuna importazione in corso.', 'devil-fruit-archive' ) ), 404 );
		}

		$json_path = $job['work_dir'] . '/' . self::DATA_FILE;
		if ( ! file_exists( $json_path ) ) {
			self::finish_job( $job );
			wp_send_json_error( array( 'message' => __( 'I file dell\'importazione non sono più disponibili.', 'devil-fruit-archive' ) ), 410 );
		}

		$data = json_decode( file_get_contents( $json_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
		if ( ! is_array( $data ) || ! isset( $data['esemplari'] ) || ! is_array( $data['esemplari'] ) ) {
			self::finish_job( $job );
			wp_send_json_error( array( 'message' => __( 'Dati del pacchetto non leggibili.', 'devil-fruit-archive' ) ), 422 );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Il lotto è breve per scelta, ma la generazione dei formati di
		// un'immagine grande vuole tempo e memoria: meglio chiederli.
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		wp_raise_memory_limit( 'image' );

		$deadline = microtime( true ) + self::BATCH_SECONDS;
		$entries  = array_values( $data['esemplari'] );

		while ( 'esemplari' === $job['phase'] && $job['cursor'] < count( $entries ) ) {
			self::import_entry( $entries[ $job['cursor'] ], $job );
			++$job['cursor'];

			if ( microtime( true ) >= $deadline ) {
				break;
			}
		}

		if ( 'esemplari' === $job['phase'] && $job['cursor'] >= count( $entries ) ) {
			$job['phase'] = 'impostazioni';
		}

		// Le impostazioni sono due sole immagini: si fanno in un colpo,
		// ma solo in un lotto che non ha già esaurito il suo tempo.
		if ( 'impostazioni' === $job['phase'] && microtime( true ) < $deadline ) {
			self::import_settings( $data, $job );
			$job['phase'] = 'fatto';
		}

		if ( 'fatto' === $job['phase'] ) {
			$summary = array(
				'created' => (int) $job['created'],
				'updated' => (int) $job['updated'],
				'images'  => (int) $job['images'],
			);

			self::finish_job( $job );

			wp_send_json_success(
				array(
					'done'     => true,
					'percent'  => 100,
					'message'  => self::progress_message( 'fatto', $job ),
					'summary'  => $summary,
				)
			);
		}

		self::save_job( $job );

		wp_send_json_success(
			array(
				'done'    => false,
				'percent' => min( 99, (int) round( 100 * $job['units_done'] / max( 1, $job['units_total'] ) ) ),
				'message' => self::progress_message( $job['phase'], $job ),
			)
		);
	}

	/**
	 * Riga di stato mostrata sotto la barra.
	 *
	 * @param string $phase Fase corrente.
	 * @param array  $job   Stato dell'importazione.
	 * @return string
	 */
	private static function progress_message( $phase, array $job ) {
		if ( 'impostazioni' === $phase ) {
			return __( 'Importazione delle impostazioni…', 'devil-fruit-archive' );
		}

		if ( 'fatto' === $phase ) {
			return __( 'Importazione completata.', 'devil-fruit-archive' );
		}

		return sprintf(
			/* translators: 1: esemplari fatti, 2: totale esemplari, 3: immagini caricate. */
			__( 'Esemplare %1$d di %2$d — %3$d immagini caricate', 'devil-fruit-archive' ),
			(int) $job['cursor'],
			(int) $job['total'],
			(int) $job['images']
		);
	}

	/**
	 * Importa un singolo esemplare del pacchetto: post, campi di testo e
	 * immagini collegate. Aggiorna i contatori dentro $job.
	 *
	 * @param mixed $entry Voce del pacchetto.
	 * @param array $job   Stato dell'importazione (per riferimento).
	 */
	private static function import_entry( $entry, array &$job ) {
		++$job['units_done'];

		if ( ! is_array( $entry ) ) {
			return;
		}

		$meta       = isset( $entry['meta'] ) && is_array( $entry['meta'] ) ? $entry['meta'] : array();
		$catalog_id = isset( $meta['catalog_id'] ) ? sanitize_text_field( $meta['catalog_id'] ) : '';
		$title      = isset( $entry['title'] ) ? sanitize_text_field( $entry['title'] ) : $catalog_id;
		$status     = isset( $entry['status'] ) && in_array( $entry['status'], array( 'publish', 'draft', 'pending', 'private' ), true )
			? $entry['status']
			: 'publish';

		$existing_id = $catalog_id ? self::find_by_catalog_id( $catalog_id ) : 0;

		if ( $existing_id ) {
			wp_update_post(
				array(
					'ID'         => $existing_id,
					'post_title' => $title,
				)
			);
			$post_id = $existing_id;
			++$job['updated'];
		} else {
			$post_id = wp_insert_post(
				array(
					'post_type'   => DFA_CPT::POST_TYPE,
					'post_title'  => $title,
					'post_status' => $status,
				),
				true
			);

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				return;
			}
			++$job['created'];
		}

		// Campi di testo.
		foreach ( self::text_fields() as $key ) {
			$value = isset( $meta[ $key ] ) ? (string) $meta[ $key ] : '';
			$value = ( 'lore' === $key ) ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );

			if ( 'fruit_type' === $key ) {
				$value = DFA_Meta::sanitize_fruit_type( $value );
			}

			if ( 'coming_soon' === $key ) {
				$value = DFA_Meta::sanitize_coming_soon( $value );
			}

			update_post_meta( $post_id, DFA_Meta::PREFIX . $key, $value );
		}

		// Immagini.
		$entry_images = isset( $entry['images'] ) && is_array( $entry['images'] ) ? $entry['images'] : array();

		$featured_ref = isset( $entry_images['featured'] ) ? $entry_images['featured'] : '';
		$featured_id  = self::import_image( $featured_ref, $job );
		if ( $featured_id ) {
			set_post_thumbnail( $post_id, $featured_id );
		}

		foreach ( self::image_fields() as $key ) {
			$ref = isset( $entry_images[ $key ] ) ? $entry_images[ $key ] : '';
			$id  = self::import_image( $ref, $job );
			update_post_meta( $post_id, DFA_Meta::PREFIX . $key, $id );
		}
	}

	/**
	 * Importa le impostazioni del plugin contenute nel pacchetto.
	 *
	 * @param array $data Contenuto del file dati.
	 * @param array $job  Stato dell'importazione (per riferimento).
	 */
	private static function import_settings( array $data, array &$job ) {
		if ( ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) ) {
			return;
		}

		$incoming = $data['settings'];
		$settings = get_option( DFA_Settings::OPTION_NAME, array() );

		if ( isset( $incoming['cta_url'] ) ) {
			$settings['cta_url'] = esc_url_raw( $incoming['cta_url'] );
		}

		foreach ( array( 'archive_background_image', 'single_background_image' ) as $key ) {
			if ( ! empty( $incoming[ $key ] ) ) {
				$id = self::import_image( $incoming[ $key ], $job );
				if ( $id ) {
					$settings[ $key ] = $id;
				}
			}
		}

		update_option( DFA_Settings::OPTION_NAME, $settings );
	}

	/**
	 * Carica nella Libreria media un'immagine estratta dal pacchetto.
	 *
	 * Le immagini già importate in questa stessa operazione vengono
	 * riusate (mappa $img_map), così un file condiviso da più esemplari
	 * non viene duplicato nella Libreria.
	 *
	 * @param string $ref Nome nel pacchetto (es. "images/12-foto.jpg").
	 * @param array  $job Stato dell'importazione (per riferimento).
	 * @return int ID allegato, 0 se non importabile.
	 */
	private static function import_image( $ref, array &$job ) {
		if ( ! is_string( $ref ) || '' === $ref ) {
			return 0;
		}

		// Conta come lavoro svolto anche se il file poi non c'è: la
		// barra deve arrivare in fondo comunque.
		++$job['units_done'];

		if ( isset( $job['img_map'][ $ref ] ) ) {
			return $job['img_map'][ $ref ];
		}

		$safe = sanitize_file_name( basename( $ref ) );
		$path = $job['work_dir'] . '/images/' . $safe;

		if ( '' === $safe || ! file_exists( $path ) ) {
			return 0;
		}

		$contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
		if ( false === $contents ) {
			return 0;
		}

		/*
		 * Se questo identico file è già in Libreria — perché importato
		 * da un pacchetto precedente — si riusa quell'allegato invece di
		 * crearne un altro. Il confronto è sul contenuto (md5) e non sul
		 * nome: WordPress rinomina i file in conflitto (foto-1.jpg,
		 * foto-2.jpg) e sul nome non si riconoscerebbero più.
		 */
		$hash     = md5( $contents );
		$existing = self::find_attachment_by_hash( $hash );

		if ( $existing ) {
			$job['img_map'][ $ref ] = $existing;
			return $existing;
		}

		$upload = wp_upload_bits( $safe, null, $contents );

		if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
			return 0;
		}

		$filetype = wp_check_filetype( $upload['file'] );

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $filetype['type'],
				'post_title'     => sanitize_text_field( pathinfo( $safe, PATHINFO_FILENAME ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$upload['file']
		);

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return 0;
		}

		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
		update_post_meta( $attachment_id, self::HASH_META, $hash );

		$job['img_map'][ $ref ] = $attachment_id;
		++$job['images'];

		return $attachment_id;
	}

	/**
	 * Cerca in Libreria un allegato già importato con la stessa
	 * impronta. Se il file non c'è più sul disco l'allegato viene
	 * ignorato, altrimenti si ricollegherebbero immagini fantasma.
	 *
	 * @param string $hash Impronta md5 del file.
	 * @return int ID allegato, 0 se non trovato.
	 */
	private static function find_attachment_by_hash( $hash ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => self::HASH_META,
						'value' => $hash,
					),
				),
			)
		);

		if ( ! $query->have_posts() ) {
			return 0;
		}

		$attachment_id = (int) $query->posts[0];
		$file          = get_attached_file( $attachment_id );

		return ( $file && file_exists( $file ) ) ? $attachment_id : 0;
	}

	/**
	 * Cerca un esemplare esistente dal Catalog ID, per rendere l'import
	 * idempotente (aggiorna invece di duplicare).
	 *
	 * @param string $catalog_id Catalog ID da cercare.
	 * @return int ID del post, 0 se non trovato.
	 */
	private static function find_by_catalog_id( $catalog_id ) {
		$query = new WP_Query(
			array(
				'post_type'      => DFA_CPT::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => DFA_Meta::PREFIX . 'catalog_id',
						'value' => $catalog_id,
					),
				),
			)
		);

		return $query->have_posts() ? (int) $query->posts[0] : 0;
	}

	/**
	 * Rimuove la cartella temporanea dell'import con il suo contenuto.
	 *
	 * @param string $dir Percorso della cartella.
	 */
	private static function cleanup_dir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		foreach ( array( $dir . '/images', $dir ) as $target ) {
			if ( ! is_dir( $target ) ) {
				continue;
			}

			$items = glob( $target . '/*' );
			if ( is_array( $items ) ) {
				foreach ( $items as $item ) {
					if ( is_file( $item ) ) {
						@unlink( $item ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					}
				}
			}
		}

		@rmdir( $dir . '/images' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * Reindirizza alla pagina impostazioni segnalando un errore.
	 *
	 * @param string $type Tipo di messaggio.
	 * @param string $code Codice errore.
	 */
	private static function redirect_with( $type, $code ) {
		$redirect = add_query_arg(
			array(
				'post_type'      => DFA_CPT::POST_TYPE,
				'page'           => DFA_Settings::PAGE_SLUG,
				'dfa_transfer'   => $type,
				'dfa_error_code' => $code,
			),
			admin_url( 'edit.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Mostra l'esito dell'operazione nella pagina impostazioni.
	 */
	public static function maybe_render_notice() {
		if ( ! isset( $_GET['page'] ) || DFA_Settings::PAGE_SLUG !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		if ( isset( $_GET['dfa_import_done'] ) ) {
			$created = isset( $_GET['created'] ) ? absint( $_GET['created'] ) : 0;
			$updated = isset( $_GET['updated'] ) ? absint( $_GET['updated'] ) : 0;
			$images  = isset( $_GET['images'] ) ? absint( $_GET['images'] ) : 0;

			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: esemplari creati, 2: esemplari aggiornati, 3: immagini importate. */
						__( 'Import completato: %1$d esemplari creati, %2$d aggiornati, %3$d immagini caricate nella Libreria media.', 'devil-fruit-archive' ),
						$created,
						$updated,
						$images
					)
				)
			);
			return;
		}

		if ( isset( $_GET['dfa_transfer'], $_GET['dfa_error_code'] ) && 'error' === sanitize_text_field( wp_unslash( $_GET['dfa_transfer'] ) ) ) {
			$code = sanitize_text_field( wp_unslash( $_GET['dfa_error_code'] ) );

			$messages = array(
				'zip_missing' => __( 'Estensione PHP "zip" non disponibile sul server: import/export non utilizzabili. Chiedi al tuo hosting di attivare ZipArchive.', 'devil-fruit-archive' ),
				'zip_create'  => __( 'Impossibile creare il file di esportazione.', 'devil-fruit-archive' ),
				'zip_open'    => __( 'Il file caricato non è un pacchetto .zip leggibile.', 'devil-fruit-archive' ),
				'no_file'     => __( 'Nessun file selezionato.', 'devil-fruit-archive' ),
				'not_zip'     => __( 'Il file deve essere un .zip esportato da questo plugin.', 'devil-fruit-archive' ),
				'upload'      => __( 'Caricamento del file non riuscito. Verifica la dimensione massima consentita dal server.', 'devil-fruit-archive' ),
				'workdir'     => __( 'Impossibile creare la cartella temporanea nella Libreria media.', 'devil-fruit-archive' ),
				'no_data'     => __( 'Il pacchetto non contiene il file archivio.json.', 'devil-fruit-archive' ),
				'bad_data'    => __( 'Il file archivio.json non è leggibile o non contiene esemplari.', 'devil-fruit-archive' ),
			);

			$message = isset( $messages[ $code ] ) ? $messages[ $code ] : __( 'Operazione non riuscita.', 'devil-fruit-archive' );

			printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $message ) );
		}
	}
}
