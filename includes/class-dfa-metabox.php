<?php
/**
 * Meta box admin per la compilazione dei campi dell'esemplare.
 *
 * Tre meta box: "Dati dell'esemplare" (targa), "Proprietari" (con
 * media uploader nativo di WordPress per le immagini) e "Research
 * Note / Osservazioni" (lore). Salvataggio protetto da nonce e con
 * sanitizzazione esplicita di ogni campo.
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFA_Metabox {

	/** Nome del campo nonce nel form di edit. */
	const NONCE_FIELD = 'dfa_metabox_nonce';

	/** Azione usata per generare/verificare il nonce. */
	const NONCE_ACTION = 'dfa_save_esemplare_meta';

	/**
	 * Aggancia registrazione, salvataggio ed enqueue degli asset admin.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'save_post_' . DFA_CPT::POST_TYPE, array( __CLASS__, 'save_meta' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Carica wp.media e lo script del media uploader solo nella schermata
	 * di modifica dell'esemplare.
	 *
	 * @param string $hook Hook della pagina admin corrente.
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || DFA_CPT::POST_TYPE !== $screen->post_type ) {
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
	 * Registra i meta box nella schermata di modifica esemplare.
	 */
	public static function register_meta_boxes() {
		add_meta_box(
			'dfa_targa',
			__( 'Dati dell\'esemplare (targa)', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_targa_metabox' ),
			DFA_CPT::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'dfa_owners',
			__( 'Proprietari', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_owners_metabox' ),
			DFA_CPT::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'dfa_lore',
			__( 'Research Note / Osservazioni', 'devil-fruit-archive' ),
			array( __CLASS__, 'render_lore_metabox' ),
			DFA_CPT::POST_TYPE,
			'normal',
			'default'
		);
	}

	/**
	 * Meta box "targa": catalog_id, fruit_type, romaji_name, katakana_name,
	 * special_note.
	 *
	 * @param WP_Post $post Post corrente.
	 */
	public static function render_targa_metabox( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$catalog_id    = DFA_Meta::get( $post->ID, 'catalog_id' );
		$fruit_type    = DFA_Meta::get( $post->ID, 'fruit_type' );
		$romaji_name   = DFA_Meta::get( $post->ID, 'romaji_name' );
		$katakana_name = DFA_Meta::get( $post->ID, 'katakana_name' );
		$special_note  = DFA_Meta::get( $post->ID, 'special_note' );

		if ( '' === $fruit_type ) {
			$fruit_type = 'PARAMECIA';
		}
		?>
		<table class="form-table dfa-metabox-table">
			<tr>
				<th><label for="dfa_catalog_id"><?php esc_html_e( 'Catalog ID', 'devil-fruit-archive' ); ?></label></th>
				<td>
					<input type="text" id="dfa_catalog_id" name="dfa_catalog_id" class="regular-text" value="<?php echo esc_attr( $catalog_id ); ?>" placeholder="DF-001">
				</td>
			</tr>
			<tr>
				<th><label for="dfa_fruit_type"><?php esc_html_e( 'Type', 'devil-fruit-archive' ); ?></label></th>
				<td>
					<select id="dfa_fruit_type" name="dfa_fruit_type">
						<?php foreach ( DFA_Meta::get_fruit_types() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $fruit_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="dfa_romaji_name"><?php esc_html_e( 'Romaji', 'devil-fruit-archive' ); ?></label></th>
				<td>
					<input type="text" id="dfa_romaji_name" name="dfa_romaji_name" class="regular-text" value="<?php echo esc_attr( $romaji_name ); ?>" placeholder="GOMU GOMU NO MI">
				</td>
			</tr>
			<tr>
				<th><label for="dfa_katakana_name"><?php esc_html_e( 'Katakana', 'devil-fruit-archive' ); ?></label></th>
				<td>
					<input type="text" id="dfa_katakana_name" name="dfa_katakana_name" class="regular-text" value="<?php echo esc_attr( $katakana_name ); ?>" placeholder="ゴムゴムの実">
				</td>
			</tr>
			<tr>
				<th><label for="dfa_special_note"><?php esc_html_e( 'Special Note', 'devil-fruit-archive' ); ?></label></th>
				<td>
					<input type="text" id="dfa_special_note" name="dfa_special_note" class="large-text" value="<?php echo esc_attr( $special_note ); ?>">
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Meta box "proprietari": owner_current/owner_former + relative
	 * immagini caricate via media uploader.
	 *
	 * @param WP_Post $post Post corrente.
	 */
	public static function render_owners_metabox( $post ) {
		$owner_current       = DFA_Meta::get( $post->ID, 'owner_current' );
		$owner_former        = DFA_Meta::get( $post->ID, 'owner_former' );
		$owner_current_image = (int) DFA_Meta::get( $post->ID, 'owner_current_image' );
		$owner_former_image  = (int) DFA_Meta::get( $post->ID, 'owner_former_image' );
		?>
		<table class="form-table dfa-metabox-table">
			<tr>
				<th><label for="dfa_owner_current"><?php esc_html_e( 'Proprietario attuale', 'devil-fruit-archive' ); ?></label></th>
				<td>
					<input type="text" id="dfa_owner_current" name="dfa_owner_current" class="regular-text" value="<?php echo esc_attr( $owner_current ); ?>">
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Foto proprietario attuale', 'devil-fruit-archive' ); ?></th>
				<td><?php self::render_image_field( 'owner_current_image', $owner_current_image ); ?></td>
			</tr>
			<tr>
				<th><label for="dfa_owner_former"><?php esc_html_e( 'Ex proprietario (opzionale)', 'devil-fruit-archive' ); ?></label></th>
				<td>
					<input type="text" id="dfa_owner_former" name="dfa_owner_former" class="regular-text" value="<?php echo esc_attr( $owner_former ); ?>">
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Foto ex proprietario', 'devil-fruit-archive' ); ?></th>
				<td><?php self::render_image_field( 'owner_former_image', $owner_former_image ); ?></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Stampa un campo "immagine" con media uploader: input nascosto con
	 * l'ID allegato, anteprima e bottoni Seleziona/Rimuovi. Il
	 * comportamento del bottone è in assets/js/admin-metabox.js.
	 *
	 * @param string $field_key Nome campo senza prefisso (es. "owner_current_image").
	 * @param int    $image_id  ID allegato attualmente salvato (0 se assente).
	 */
	private static function render_image_field( $field_key, $image_id ) {
		$input_id = 'dfa_' . $field_key;
		$preview  = $image_id ? wp_get_attachment_image( $image_id, 'thumbnail' ) : '';
		?>
		<div class="dfa-image-field" data-target="<?php echo esc_attr( $input_id ); ?>">
			<div class="dfa-image-field__preview"><?php echo wp_kses_post( $preview ); ?></div>
			<input type="hidden" id="<?php echo esc_attr( $input_id ); ?>" name="<?php echo esc_attr( $input_id ); ?>" value="<?php echo esc_attr( $image_id ); ?>">
			<p>
				<button type="button" class="button dfa-image-field__select"><?php esc_html_e( 'Seleziona immagine', 'devil-fruit-archive' ); ?></button>
				<button type="button" class="button dfa-image-field__remove" <?php echo $image_id ? '' : 'style="display:none"'; ?>><?php esc_html_e( 'Rimuovi immagine', 'devil-fruit-archive' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * Meta box "lore": textarea per il campo research note.
	 *
	 * @param WP_Post $post Post corrente.
	 */
	public static function render_lore_metabox( $post ) {
		$lore = DFA_Meta::get( $post->ID, 'lore' );
		?>
		<label for="dfa_lore" class="screen-reader-text"><?php esc_html_e( 'Research Note / Osservazioni', 'devil-fruit-archive' ); ?></label>
		<textarea id="dfa_lore" name="dfa_lore" rows="6" class="large-text"><?php echo esc_textarea( $lore ); ?></textarea>
		<?php
	}

	/**
	 * Salva i meta field dell'esemplare in modo sicuro: verifica nonce,
	 * esclude autosave/revisioni, verifica i permessi e sanitizza ogni
	 * campo prima del salvataggio.
	 *
	 * @param int $post_id ID del post in salvataggio.
	 */
	public static function save_meta( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$text_fields = array( 'catalog_id', 'romaji_name', 'katakana_name', 'special_note', 'owner_current', 'owner_former' );
		foreach ( $text_fields as $key ) {
			$field_name = 'dfa_' . $key;
			$value      = isset( $_POST[ $field_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ) : '';
			update_post_meta( $post_id, DFA_Meta::PREFIX . $key, $value );
		}

		if ( isset( $_POST['dfa_fruit_type'] ) ) {
			$fruit_type = DFA_Meta::sanitize_fruit_type( sanitize_text_field( wp_unslash( $_POST['dfa_fruit_type'] ) ) );
			update_post_meta( $post_id, DFA_Meta::PREFIX . 'fruit_type', $fruit_type );
		}

		if ( isset( $_POST['dfa_lore'] ) ) {
			$lore = sanitize_textarea_field( wp_unslash( $_POST['dfa_lore'] ) );
			update_post_meta( $post_id, DFA_Meta::PREFIX . 'lore', $lore );
		}

		foreach ( array( 'owner_current_image', 'owner_former_image' ) as $key ) {
			$field_name = 'dfa_' . $key;
			$value      = isset( $_POST[ $field_name ] ) ? absint( $_POST[ $field_name ] ) : 0;
			update_post_meta( $post_id, DFA_Meta::PREFIX . $key, $value );
		}
	}
}
