<?php
/**
 * Colonne personalizzate nella lista admin degli esemplari:
 * "Catalog ID", "Type", "Proprietario", "Ex proprietario", "Pubblicato"
 * e "Coming soon".
 *
 * Le ultime due sono spunte che cambiano lo stato con un clic, senza
 * aprire il post: condividono markup, script e endpoint AJAX (il "flag"
 * nella richiesta dice quale delle due si sta cambiando), e cambia solo
 * cosa viene scritto — lo stato del post per "Pubblicato", un meta per
 * "Coming soon".
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFA_Admin_Columns {

	/** Nome dell'azione AJAX (e action del nonce) delle due spunte. */
	const TOGGLE_ACTION = 'dfa_toggle_flag';

	/** Spunta che pubblica/spubblica l'esemplare. */
	const FLAG_PUBLISHED = 'published';

	/** Spunta "annunciato ma non ancora consultabile". */
	const FLAG_COMING_SOON = 'coming_soon';

	/**
	 * Campo nascosto presente SOLO nel form delle Modifiche rapide: fa
	 * da marcatore per il salvataggio. Senza, una modifica in blocco
	 * (che non contiene le nostre spunte ma passa dallo stesso hook)
	 * azzererebbe il flag su tutti gli esemplari selezionati.
	 */
	const QUICK_EDIT_MARKER = 'dfa_quick_edit';

	/**
	 * Aggancia i filtri per le colonne della lista admin.
	 */
	public static function init() {
		add_filter( 'manage_' . DFA_CPT::POST_TYPE . '_posts_columns', array( __CLASS__, 'add_columns' ) );
		add_action( 'manage_' . DFA_CPT::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-' . DFA_CPT::POST_TYPE . '_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_columns_query' ) );
		add_action( 'quick_edit_custom_box', array( __CLASS__, 'render_quick_edit_box' ), 10, 2 );
		add_action( 'save_post_' . DFA_CPT::POST_TYPE, array( __CLASS__, 'save_quick_edit' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_' . self::TOGGLE_ACTION, array( __CLASS__, 'ajax_toggle_flag' ) );
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
				$new_columns['dfa_coming_soon']   = __( 'Coming soon', 'devil-fruit-archive' );
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
			self::render_flag_cell( $post_id, self::FLAG_PUBLISHED );
		}

		if ( 'dfa_coming_soon' === $column ) {
			self::render_flag_cell( $post_id, self::FLAG_COMING_SOON );
		}
	}

	/**
	 * Cella con la spunta: cambia lo stato con un clic, senza aprire il
	 * post.
	 *
	 * "Pubblicato" ha una condizione in più: la spunta compare solo per
	 * gli stati "pubblicato" e "bozza", perché un checkbox non sa
	 * rappresentare "in attesa di revisione", "programmato" o "cestino"
	 * e un clic li stravolgerebbe. In quei casi (e per chi non può
	 * modificare quel post) si stampa solo l'etichetta.
	 *
	 * L'attributo data-value è anche il valore che lo script legge per
	 * pre-compilare le Modifiche rapide.
	 *
	 * @param int    $post_id ID del post in riga.
	 * @param string $flag    Una delle costanti FLAG_*.
	 */
	private static function render_flag_cell( $post_id, $flag ) {
		$is_on      = self::get_flag_value( $post_id, $flag );
		$can_edit   = current_user_can( 'edit_post', $post_id );
		$toggleable = $can_edit;
		$label      = self::flag_label( $flag, $is_on );

		if ( self::FLAG_PUBLISHED === $flag ) {
			$status = get_post_status( $post_id );

			if ( ! in_array( $status, array( 'publish', 'draft' ), true ) ) {
				$toggleable    = false;
				$status_object = get_post_status_object( $status );
				$label         = $status_object ? $status_object->label : $status;
			}
		}

		if ( ! $toggleable ) {
			printf(
				'<span class="dfa-flag" data-flag="%1$s" data-value="%2$d"><span class="dfa-flag__label">%3$s</span></span>',
				esc_attr( $flag ),
				$is_on ? 1 : 0,
				esc_html( $label )
			);
			return;
		}

		printf(
			'<span class="dfa-flag" data-flag="%1$s" data-value="%2$d">
				<label class="dfa-flag__toggle">
					<input type="checkbox" class="dfa-flag__input" data-post-id="%3$d" data-flag="%1$s"%4$s>
					<span class="dfa-flag__label">%5$s</span>
				</label>
			</span>',
			esc_attr( $flag ),
			$is_on ? 1 : 0,
			(int) $post_id,
			$is_on ? ' checked' : '',
			esc_html( $label )
		);
	}

	/**
	 * Valore corrente di una spunta.
	 *
	 * @param int    $post_id ID del post.
	 * @param string $flag    Una delle costanti FLAG_*.
	 * @return bool
	 */
	private static function get_flag_value( $post_id, $flag ) {
		if ( self::FLAG_PUBLISHED === $flag ) {
			return 'publish' === get_post_status( $post_id );
		}

		return DFA_Meta::is_coming_soon( $post_id );
	}

	/**
	 * Etichetta accanto alla spunta. Tenuta in un solo posto perché la
	 * usano sia il PHP (primo caricamento) sia il JS (dopo il clic).
	 *
	 * @param string $flag  Una delle costanti FLAG_*.
	 * @param bool   $is_on Stato da etichettare.
	 * @return string
	 */
	private static function flag_label( $flag, $is_on ) {
		if ( self::FLAG_PUBLISHED === $flag ) {
			return $is_on
				? __( 'Pubblicato', 'devil-fruit-archive' )
				: __( 'Non pubblicato', 'devil-fruit-archive' );
		}

		return $is_on
			? __( 'Sì', 'devil-fruit-archive' )
			: __( 'No', 'devil-fruit-archive' );
	}

	/**
	 * Le due spunte dentro le Modifiche rapide.
	 *
	 * WordPress non pre-compila i campi custom del quick edit: ci pensa
	 * admin-list.js leggendo data-value dalla riga.
	 *
	 * "Pubblicato" non ha un salvataggio proprio: pilota il menu "Stato"
	 * nativo del form (publish/draft), così a salvare resta il core e
	 * due controlli dello stesso valore non possono contraddirsi.
	 * "Coming soon" invece è un meta nostro e viene salvato da
	 * save_quick_edit().
	 *
	 * @param string $column    Colonna per cui si sta stampando il box.
	 * @param string $post_type Post type della lista.
	 */
	public static function render_quick_edit_box( $column, $post_type ) {
		if ( DFA_CPT::POST_TYPE !== $post_type ) {
			return;
		}

		if ( 'dfa_published' === $column ) {
			?>
			<fieldset class="inline-edit-col-right">
				<div class="inline-edit-col">
					<label class="alignleft dfa-quick-flag" data-flag="<?php echo esc_attr( self::FLAG_PUBLISHED ); ?>">
						<input type="checkbox" class="dfa-quick-flag__input">
						<span class="checkbox-title"><?php esc_html_e( 'Pubblicato', 'devil-fruit-archive' ); ?></span>
					</label>
				</div>
			</fieldset>
			<?php
			return;
		}

		if ( 'dfa_coming_soon' === $column ) {
			?>
			<fieldset class="inline-edit-col-right">
				<div class="inline-edit-col">
					<input type="hidden" name="<?php echo esc_attr( self::QUICK_EDIT_MARKER ); ?>" value="1">
					<label class="alignleft dfa-quick-flag" data-flag="<?php echo esc_attr( self::FLAG_COMING_SOON ); ?>">
						<input type="checkbox" class="dfa-quick-flag__input" name="dfa_coming_soon" value="1">
						<span class="checkbox-title"><?php esc_html_e( 'Coming soon', 'devil-fruit-archive' ); ?></span>
					</label>
				</div>
			</fieldset>
			<?php
		}
	}

	/**
	 * Salva la spunta "Coming soon" delle Modifiche rapide.
	 *
	 * Il campo nascosto QUICK_EDIT_MARKER garantisce che si stia
	 * salvando davvero dal form del quick edit: la modifica in blocco
	 * passa dallo stesso hook con lo stesso nonce ma non contiene le
	 * nostre spunte, e senza questo controllo azzererebbe il flag su
	 * tutti gli esemplari selezionati.
	 *
	 * @param int $post_id ID del post in salvataggio.
	 */
	public static function save_quick_edit( $post_id ) {
		if ( ! isset( $_POST[ self::QUICK_EDIT_MARKER ] ) ) {
			return;
		}

		if ( ! isset( $_POST['_inline_edit'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_inline_edit'] ) ), 'inlineeditnonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta(
			$post_id,
			DFA_Meta::PREFIX . 'coming_soon',
			isset( $_POST['dfa_coming_soon'] ) ? '1' : ''
		);
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
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'action'        => self::TOGGLE_ACTION,
				'nonce'         => wp_create_nonce( self::TOGGLE_ACTION ),
				'flagPublished' => self::FLAG_PUBLISHED,
				'labels'        => array(
					self::FLAG_PUBLISHED   => array(
						'on'  => self::flag_label( self::FLAG_PUBLISHED, true ),
						'off' => self::flag_label( self::FLAG_PUBLISHED, false ),
					),
					self::FLAG_COMING_SOON => array(
						'on'  => self::flag_label( self::FLAG_COMING_SOON, true ),
						'off' => self::flag_label( self::FLAG_COMING_SOON, false ),
					),
				),
				'errorMessage'  => __( 'Non è stato possibile salvare la modifica.', 'devil-fruit-archive' ),
			)
		);
	}

	/**
	 * Cambia una delle due spunte dalla lista.
	 *
	 * Controlli, nell'ordine: nonce, flag riconosciuto, esistenza e post
	 * type corretto, permesso di modifica su quel post e — solo per
	 * "Pubblicato" — permesso di pubblicare e stato di partenza fra
	 * publish e draft.
	 */
	public static function ajax_toggle_flag() {
		check_ajax_referer( self::TOGGLE_ACTION, 'nonce' );

		$flag    = isset( $_POST['flag'] ) ? sanitize_key( wp_unslash( $_POST['flag'] ) ) : '';
		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		$value   = isset( $_POST['value'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['value'] ) );
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! in_array( $flag, array( self::FLAG_PUBLISHED, self::FLAG_COMING_SOON ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Campo non riconosciuto.', 'devil-fruit-archive' ) ), 400 );
		}

		if ( ! $post || DFA_CPT::POST_TYPE !== $post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Esemplare non trovato.', 'devil-fruit-archive' ) ), 404 );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permessi insufficienti.', 'devil-fruit-archive' ) ), 403 );
		}

		if ( self::FLAG_COMING_SOON === $flag ) {
			update_post_meta( $post_id, DFA_Meta::PREFIX . 'coming_soon', $value ? '1' : '' );

			wp_send_json_success(
				array(
					'value' => $value,
					'label' => self::flag_label( $flag, $value ),
				)
			);
		}

		$post_type_object = get_post_type_object( DFA_CPT::POST_TYPE );
		if ( $value && ( ! $post_type_object || ! current_user_can( $post_type_object->cap->publish_posts ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Permessi insufficienti per pubblicare.', 'devil-fruit-archive' ) ), 403 );
		}

		if ( ! in_array( $post->post_status, array( 'publish', 'draft' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Stato non gestito dalla spunta.', 'devil-fruit-archive' ) ), 400 );
		}

		$result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => $value ? 'publish' : 'draft',
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
		}

		wp_send_json_success(
			array(
				'value' => $value,
				'label' => self::flag_label( $flag, $value ),
			)
		);
	}

	/**
	 * Rende ordinabili le colonne custom.
	 *
	 * Proprietari e spunte restano non ordinabili di proposito:
	 * l'ordinamento per meta value fa una JOIN che escluderebbe dalla
	 * lista gli esemplari senza quel campo.
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
	 * A differenza della griglia pubblica, qui i "coming soon" NON
	 * vanno in fondo: in redazione serve trovarli al loro numero.
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
