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
