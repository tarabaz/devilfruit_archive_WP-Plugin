<?php
/**
 * Registrazione dei campi meta dell'esemplare (register_post_meta).
 *
 * TODO (step 2): catalog_id, fruit_type, romaji_name, katakana_name,
 * special_note, owner_current, owner_former, lore, owner_current_image,
 * owner_former_image.
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFA_Meta {

	/**
	 * Aggancia la registrazione dei meta al hook "init".
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta_fields' ) );
	}

	/**
	 * Registra i meta field del CPT "esemplare" con register_post_meta().
	 */
	public static function register_meta_fields() {
		// Implementato nello step 2.
	}
}
