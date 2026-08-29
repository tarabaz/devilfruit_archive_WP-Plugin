<?php
/**
 * Colonne personalizzate nella lista admin degli esemplari:
 * "Catalog ID", "Type", "Proprietario", "Ex proprietario" e
 * "Pubblicato" (spunta che pubblica/spubblica senza aprire il post).
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFA_Admin_Columns {

	/**
	 * Nome dell'azione AJAX (e dell'action del nonce) usata dalla spunta
	 * "Pubblicato" nella lista.
	 */
	const TOGGLE_ACTION = 'dfa_toggle_published';

	/**
	 * Aggancia i filtri per le colonne della lista admin.
	 */
	public static function init() {
		add_filter( 'manage_' . DFA_CPT::POST_TYPE . '_posts_columns', array( __CLASS__, 'add_columns' ) );
		add_action( 'manage_' . DFA_CPT::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-' . DFA_CPT::POST_TYPE . '_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_columns_query' ) );
		add_action( 'quick_edit_custom_box', array( __CLASS__, 'render_quick_edit_box' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_' . self::TOGGLE_ACTION, array( __CLASS__, 'ajax_toggle_published' ) );
	}

	/**
	 * Inserisce le colonne custom subito dopo il titolo.
	 *
	 * @param array<string,string> $columns Colonne esistenti.
	 * @return array<string,string>
	 */
	public static function add_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			if ( 'title' === $key ) {
				$new_columns['dfa_catalog_id']    = __( 'Catalog ID', 'devil-fruit-archive' );
				$new_columns['dfa_fruit_type']    = __( 'Type', 'devil-fruit-archive' );
				$new_columns['dfa_owner_current'] = __( 'Proprietario', 'devil-fruit-archive' );
				$new_columns['dfa_owner_former']  = __( 'Ex proprietario', 'devil-fruit-archive' );
				$new_columns['dfa_published']     = __( 'Pubblicato', 'devil-fruit-archive' );
			}
		}

		return $new_columns;
	}

	/**
	 * Stampa il contenuto delle colonne custom.
	 *
	 * @param string $column  Nome colonna.
	 * @param int    $post_id ID del post in riga.
	 */
	public static function render_column( $column, $post_id ) {
		if ( 'dfa_catalog_id' === $column ) {
			echo esc_html( DFA_Meta::get( $post_id, 'catalog_id' ) );
		}

		if ( 'dfa_fruit_type' === $column ) {
			$types = DFA_Meta::get_fruit_types();
			$value = DFA_Meta::get( $post_id, 'fruit_type' );
			echo esc_html( isset( $types[ $value ] ) ? $types[ $value ] : $value );
		}

		if ( 'dfa_owner_current' === $column ) {
			echo esc_html( DFA_Meta::get( $post_id, 'owner_current' ) );
		}

		if ( 'dfa_owner_former' === $column ) {
			echo esc_html( DFA_Meta::get( $post_id, 'owner_former' ) );
		}

		if ( 'dfa_published' === $column ) {
			self::render_published_cell( $post_id );
		}
	}

	/**
	 * Cella "Pubblicato": spunta che pubblica/spubblica la scheda con un
	 * clic, senza aprire il post.
	 *
	 * La spunta compare solo per gli stati "pubblicato" e "bozza" e solo
	 * a chi può modificare quel post: per gli altri stati (in attesa di
	 * revisione, programmato, cestino...) si stampa l'etichetta dello
	 * stato, così un clic non può stravolgere una condizione che il
	 * checkbox non è in grado di rappresentare.
	 *
	 * L'attributo data-published è anche il valore che lo script legge
	 * per pre-compilare la spunta nelle Modifiche rapide.
	 *
	 * @param int $post_id ID del post in riga.
	 */
	private static function render_published_cell( $post_id ) {
		$status       = get_post_status( $post_id );
		$is_published = ( 'publish' === $status );
		$toggleable   = in_array( $status, array( 'publish', 'draft' ), true )
			&& current_user_can( 'edit_post', $post_id );

		if ( ! $toggleable ) {
			$status_object = get_post_status_object( $status );
			$label         = $status_object ? $status_object->label : $status;

			printf(
				'<span class="dfa-published" data-published="%d"><span class="dfa-published__label">%s</span></span>',
				$is_published ? 1 : 0,
				esc_html( $label )
			);
			return;
		}

		printf(
			'<span class="dfa-published" data-published="%1$d">
				<label class="dfa-published__toggle">
					<input type="checkbox" class="dfa-published__input" data-post-id="%2$d"%3$s>
					<span class="dfa-published__label">%4$s</span>
				</label>
			</span>',
			$is_published ? 1 : 0,
			(int) $post_id,
			$is_published ? ' checked' : '',
			esc_html( $is_published ? self::published_label( true ) : self::published_label( false ) )
		);
	}

	/**
	 * Etichetta accanto alla spunta. Tenuta in un solo posto perché la
	 * usano sia il PHP (primo caricamento) sia il JS (dopo il toggle).
	 *
	 * @param bool $is_published Stato da etichettare.
	 * @return string
	 */
	private static function published_label( $is_published ) {
		return $is_published
			? __( 'Pubblicato', 'devil-fruit-archive' )
			: __( 'Non pubblicato', 'devil-fruit-archive' );
	}

	/**
	 * Spunta "Pubblicato" dentro le Modifiche rapide.
	 *
	 * WordPress non pre-compila i campi custom delle Modifiche rapide:
	 * ci pensa admin-list.js leggendo data-published dalla riga. La
	 * spunta non viene salvata da noi, pilota direttamente il menu
	 * "Stato" nativo del form (publish/draft), così il salvataggio
	 * resta quello del core e non c'è il rischio che due controlli
	 * dello stesso valore si contraddicano.
	 *
	 * @param string $column    Colonna per cui si sta stampando il box.
	 * @param string $post_type Post type della lista.
	 */
	public static function render_quick_edit_box( $column, $post_type ) {
		if ( 'dfa_published' !== $column || DFA_CPT::POST_TYPE !== $post_type ) {
			return;
		}
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<label class="alignleft dfa-quick-published">
					<input type="checkbox" class="dfa-quick-published__input">
					<span class="checkbox-title"><?php esc_html_e( 'Pubblicato', 'devil-fruit-archive' ); ?></span>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Carica script e stile solo nella lista degli esemplari.
	 *
	 * @param string $hook Hook della pagina admin corrente.
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || DFA_CPT::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'dfa-admin-list',
			DFA_PLUGIN_URL . 'assets/css/admin-list.css',
			array(),
			DFA_VERSION
		);

		wp_enqueue_script(
			'dfa-admin-list',
			DFA_PLUGIN_URL . 'assets/js/admin-list.js',
			array( 'jquery', 'inline-edit-post' ),
			DFA_VERSION,
			true
		);

		wp_localize_script(
			'dfa-admin-list',
			'dfaList',
			array(
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'action'           => self::TOGGLE_ACTION,
				'nonce'            => wp_create_nonce( self::TOGGLE_ACTION ),
				'labelPublished'   => self::published_label( true ),
				'labelUnpublished' => self::published_label( false ),
				'errorMessage'     => __( 'Non è stato possibile cambiare lo stato di pubblicazione.', 'devil-fruit-archive' ),
			)
		);
	}

	/**
	 * Pubblica/spubblica un esemplare dalla spunta nella lista.
	 *
	 * Controlli, nell'ordine: nonce, esistenza e post type corretto,
	 * permesso di modifica su quel post, permesso di pubblicare (solo
	 * quando si pubblica) e stato di partenza fra publish e draft.
	 */
	public static function ajax_toggle_published() {
		check_ajax_referer( self::TOGGLE_ACTION, 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		$publish = isset( $_POST['publish'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['publish'] ) );
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! $post || DFA_CPT::POST_TYPE !== $post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Esemplare non trovato.', 'devil-fruit-archive' ) ), 404 );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permessi insufficienti.', 'devil-fruit-archive' ) ), 403 );
		}

		$post_type_object = get_post_type_object( DFA_CPT::POST_TYPE );
		if ( $publish && ( ! $post_type_object || ! current_user_can( $post_type_object->cap->publish_posts ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Permessi insufficienti per pubblicare.', 'devil-fruit-archive' ) ), 403 );
		}

		if ( ! in_array( $post->post_status, array( 'publish', 'draft' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Stato non gestito dalla spunta.', 'devil-fruit-archive' ) ), 400 );
		}

		$result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => $publish ? 'publish' : 'draft',
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
		}

		wp_send_json_success(
			array(
				'published' => $publish,
				'label'     => self::published_label( $publish ),
			)
		);
	}

	/**
	 * Rende ordinabili le colonne custom.
	 *
	 * Proprietario ed ex proprietario restano non ordinabili di
	 * proposito: l'ordinamento per meta value fa una JOIN che
	 * escluderebbe dalla lista gli esemplari senza quel campo.
	 *
	 * @param array<string,string> $columns Colonne ordinabili esistenti.
	 * @return array<string,string>
	 */
	public static function sortable_columns( $columns ) {
		$columns['dfa_catalog_id'] = 'dfa_catalog_id';
		$columns['dfa_fruit_type'] = 'dfa_fruit_type';
		return $columns;
	}

	/**
	 * Ordinamento della lista admin: per Catalog ID crescente (DF-001,
	 * DF-002, ...) come impostazione predefinita, o per la colonna su
	 * cui si è cliccato.
	 *
	 * L'ordinamento predefinito NON usa meta_key + orderby meta_value:
	 * quella forma fa una INNER JOIN e farebbe sparire dalla lista gli
	 * esemplari che il Catalog ID non ce l'hanno ancora. Con le due
	 * clausole EXISTS / NOT EXISTS in OR la JOIN diventa una LEFT JOIN:
	 * chi non ha il campo resta in lista e finisce in cima.
	 *
	 * @param WP_Query $query Query in corso.
	 */
	public static function sort_columns_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( DFA_CPT::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		/*
		 * Si guarda il parametro della richiesta e non il valore già
		 * normalizzato da WP_Query: quest'ultimo può arrivare qui
		 * valorizzato ('date') anche quando l'utente non ha cliccato
		 * nessuna intestazione, e l'ordine predefinito per Catalog ID
		 * non verrebbe mai applicato.
		 */
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$requested = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';

		if ( 'dfa_catalog_id' === $requested ) {
			$query->set( 'meta_key', DFA_Meta::PREFIX . 'catalog_id' ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query->set( 'orderby', 'meta_value' );
			return;
		}

		if ( 'dfa_fruit_type' === $requested ) {
			$query->set( 'meta_key', DFA_Meta::PREFIX . 'fruit_type' ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query->set( 'orderby', 'meta_value' );
			return;
		}

		// Una colonna qualsiasi scelta dall'utente ha la precedenza.
		if ( '' !== $requested ) {
			return;
		}

		$query->set(
			'meta_query', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'relation'      => 'OR',
				'dfa_cid'       => array(
					'key'     => DFA_Meta::PREFIX . 'catalog_id',
					'compare' => 'EXISTS',
				),
				'dfa_cid_empty' => array(
					'key'     => DFA_Meta::PREFIX . 'catalog_id',
					'compare' => 'NOT EXISTS',
				),
			)
		);
		$query->set(
			'orderby',
			array(
				'dfa_cid' => 'ASC',
				'ID'      => 'ASC',
			)
		);
	}
}
