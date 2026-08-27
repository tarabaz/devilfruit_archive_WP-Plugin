<?php
/**
 * Meta box admin per la compilazione dei campi dell'esemplare.
 *
 * TODO (step 2): meta box "Dati dell'esemplare" (targa) + meta box
 * "Proprietari" con media uploader per owner_current_image/
 * owner_former_image. Nonce e sanitize su ogni save.
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFA_Metabox {

	/**
	 * Aggancia registrazione e salvataggio dei meta box.
	 */
	public static function init() {
		// Implementato nello step 2 (add_meta_boxes, save_post_esemplare).
	}
}
