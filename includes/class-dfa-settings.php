<?php
/**
 * Pagina impostazioni del plugin (URL CTA "Richiedi questo esemplare"
 * + bottone per lanciare il seed del catalogo).
 *
 * TODO (step 2 per la pagina impostazioni, step 5 per il bottone seed).
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFA_Settings {

	/** Nome dell'opzione che contiene le impostazioni del plugin. */
	const OPTION_NAME = 'dfa_settings';

	/**
	 * Aggancia la registrazione della pagina impostazioni.
	 */
	public static function init() {
		// Implementato nello step 2 (admin_menu, register_setting).
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
}
