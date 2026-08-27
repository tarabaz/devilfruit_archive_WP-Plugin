<?php
/**
 * Seed one-shot e idempotente dei 17 esemplari da
 * _seed/Devil_Fruit_Archive_Catalogo.md.
 *
 * Lanciabile dal bottone nella pagina impostazioni oppure da WP-CLI
 * con: wp devil-fruit-archive seed
 *
 * Idempotente: se un catalog_id esiste già tra gli esemplari
 * pubblicati/in bozza, la relativa scheda del markdown viene saltata
 * invece di creare un duplicato. Le immagini non vengono importate:
 * vanno caricate a mano dalla Libreria media dopo il seed.
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFA_Seed {

	/** Percorso del file di seed relativo alla root del plugin. */
	const SEED_FILE_RELATIVE = '_seed/Devil_Fruit_Archive_Catalogo.md';

	/** Azione usata da admin-post.php per lanciare il seed da wp-admin. */
	const ACTION = 'dfa_run_seed';

	/** Azione usata per generare/verificare il nonce del bottone seed. */
	const NONCE_ACTION = 'dfa_run_seed_action';

	/**
	 * Aggancia l'azione admin e, se disponibile, il comando WP-CLI.
	 */
	public static function init() {
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_admin_request' ) );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_render_notice' ) );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'devil-fruit-archive seed', array( __CLASS__, 'cli_seed' ) );
		}
	}

	/**
	 * Gestisce il submit del bottone "Lancia il seed" nella pagina
	 * impostazioni: verifica permessi e nonce, esegue il seed e
	 * reindirizza con un riepilogo in query string.
	 */
	public static function handle_admin_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permessi insufficienti per eseguire questa azione.', 'devil-fruit-archive' ) );
		}

		check_admin_referer( self::NONCE_ACTION );

		$result = self::run();

		$redirect = add_query_arg(
			array(
				'post_type'     => DFA_CPT::POST_TYPE,
				'page'          => DFA_Settings::PAGE_SLUG,
				'dfa_seed_done' => 1,
				'created'       => (int) $result['created'],
				'skipped'       => (int) $result['skipped'],
			),
			admin_url( 'edit.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Mostra l'esito del seed come notice admin dopo il redirect.
	 */
	public static function maybe_render_notice() {
		if ( ! isset( $_GET['dfa_seed_done'], $_GET['page'] ) || DFA_Settings::PAGE_SLUG !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		$created = isset( $_GET['created'] ) ? absint( $_GET['created'] ) : 0;
		$skipped = isset( $_GET['skipped'] ) ? absint( $_GET['skipped'] ) : 0;

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: 1: numero di esemplari creati, 2: numero di esemplari già presenti e saltati. */
					__( 'Seed completato: %1$d esemplari creati, %2$d già presenti (saltati).', 'devil-fruit-archive' ),
					$created,
					$skipped
				)
			)
		);
	}

	/**
	 * Comando WP-CLI: wp devil-fruit-archive seed
	 */
	public static function cli_seed() {
		$result = self::run();

		if ( isset( $result['error'] ) ) {
			WP_CLI::error( 'File di seed non trovato: ' . self::SEED_FILE_RELATIVE );
			return;
		}

		WP_CLI::success( sprintf( '%d esemplari creati, %d già presenti (saltati).', $result['created'], $result['skipped'] ) );
	}

	/**
	 * Esegue il seed: legge il markdown, crea gli esemplari mancanti.
	 *
	 * @return array{created:int,skipped:int,error?:string}
	 */
	public static function run() {
		$file = DFA_PLUGIN_DIR . self::SEED_FILE_RELATIVE;

		if ( ! file_exists( $file ) ) {
			return array(
				'created' => 0,
				'skipped' => 0,
				'error'   => 'file_not_found',
			);
		}

		$content = (string) file_get_contents( $file );
		$entries = self::parse_catalog( $content );

		$created = 0;
		$skipped = 0;

		foreach ( $entries as $entry ) {
			if ( self::catalog_id_exists( $entry['catalog_id'] ) ) {
				++$skipped;
				continue;
			}

			self::create_esemplare( $entry );
			++$created;
		}

		return array(
			'created' => $created,
			'skipped' => $skipped,
		);
	}

	/**
	 * Esegue il parsing del markdown del catalogo.
	 *
	 * Ogni scheda inizia con un heading "## DF-xxx" seguito da righe
	 * "- Chiave: valore". Il parsing è volutamente semplice e
	 * tollerante: righe che non combaciano col pattern vengono
	 * ignorate.
	 *
	 * @param string $content Contenuto del file markdown.
	 * @return array<int,array{catalog_id:string,fields:array<string,string>}>
	 */
	public static function parse_catalog( $content ) {
		$entries = array();
		$blocks  = preg_split( '/^##\s+/m', $content );

		if ( ! $blocks ) {
			return $entries;
		}

		// Il primo blocco è l'introduzione del file, prima del primo "## ".
		array_shift( $blocks );

		foreach ( $blocks as $block ) {
			$lines      = preg_split( '/\r\n|\r|\n/', $block );
			$catalog_id = trim( (string) array_shift( $lines ) );

			if ( '' === $catalog_id ) {
				continue;
			}

			$fields = array();

			foreach ( $lines as $line ) {
				$line = trim( $line );

				if ( '' === $line || '-' !== substr( $line, 0, 1 ) ) {
					continue;
				}

				if ( preg_match( '/^-\s*([^:]+):\s*(.*)$/', $line, $matches ) ) {
					$key            = strtolower( trim( $matches[1] ) );
					$fields[ $key ] = trim( $matches[2] );
				}
			}

			$entries[] = array(
				'catalog_id' => $catalog_id,
				'fields'     => $fields,
			);
		}

		return $entries;
	}

	/**
	 * Verifica se un esemplare con questo catalog_id esiste già
	 * (in qualsiasi stato), per rendere il seed idempotente.
	 *
	 * @param string $catalog_id Catalog ID da cercare (es. "DF-001").
	 * @return bool
	 */
	private static function catalog_id_exists( $catalog_id ) {
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

		return $query->have_posts();
	}

	/**
	 * Crea un nuovo esemplare a partire da una scheda del catalogo.
	 *
	 * @param array{catalog_id:string,fields:array<string,string>} $entry Scheda parsata.
	 */
	private static function create_esemplare( $entry ) {
		$catalog_id = $entry['catalog_id'];
		$fields     = $entry['fields'];

		$title = '';
		if ( ! empty( $fields['nome scheda'] ) ) {
			$title = $fields['nome scheda'];
		} elseif ( ! empty( $fields['romaji'] ) ) {
			$title = $fields['romaji'];
		} else {
			$title = $catalog_id;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => DFA_CPT::POST_TYPE,
				'post_title'  => sanitize_text_field( $title ),
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return;
		}

		$fruit_types = DFA_Meta::get_fruit_types();
		$type_input  = isset( $fields['type'] ) ? $fields['type'] : '';
		$type_key    = array_search( $type_input, $fruit_types, true );

		if ( false === $type_key ) {
			$type_key = DFA_Meta::sanitize_fruit_type( $type_input );
		}

		update_post_meta( $post_id, DFA_Meta::PREFIX . 'catalog_id', sanitize_text_field( $catalog_id ) );
		update_post_meta( $post_id, DFA_Meta::PREFIX . 'fruit_type', $type_key );
		update_post_meta( $post_id, DFA_Meta::PREFIX . 'romaji_name', sanitize_text_field( isset( $fields['romaji'] ) ? $fields['romaji'] : '' ) );
		update_post_meta( $post_id, DFA_Meta::PREFIX . 'katakana_name', sanitize_text_field( isset( $fields['katakana'] ) ? $fields['katakana'] : '' ) );
		update_post_meta( $post_id, DFA_Meta::PREFIX . 'special_note', sanitize_text_field( isset( $fields['special note'] ) ? $fields['special note'] : '' ) );
		update_post_meta( $post_id, DFA_Meta::PREFIX . 'owner_current', sanitize_text_field( isset( $fields['proprietario attuale'] ) ? $fields['proprietario attuale'] : '' ) );
		update_post_meta( $post_id, DFA_Meta::PREFIX . 'owner_former', sanitize_text_field( isset( $fields['ex proprietario'] ) ? $fields['ex proprietario'] : '' ) );
		update_post_meta( $post_id, DFA_Meta::PREFIX . 'lore', sanitize_textarea_field( isset( $fields['lore'] ) ? $fields['lore'] : '' ) );
	}
}
