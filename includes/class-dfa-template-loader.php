<?php
/**
 * Carica i template di frontend (scheda singola e archivio) come
 * documenti HTML completi e indipendenti dal tema attivo, in modo che
 * il layout full-bleed del dossier resti fedele al mockup su qualsiasi
 * tema. Il tema attivo può comunque sovrascrivere i template copiandoli
 * in wp-content/themes/<tema>/devil-fruit-archive/.
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFA_Template_Loader {

	/** Sottocartella nel tema usata per gli override. */
	const THEME_SUBDIR = 'devil-fruit-archive';

	/**
	 * Aggancia la sostituzione del template e l'enqueue degli asset.
	 */
	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Sostituisce il template di WordPress con quello del plugin (o
	 * con l'override presente nel tema) per la scheda singola e per
	 * l'archivio del CPT "esemplare".
	 *
	 * @param string $template Percorso del template risolto da WordPress.
	 * @return string
	 */
	public static function template_include( $template ) {
		if ( is_singular( DFA_CPT::POST_TYPE ) ) {
			return self::locate( 'single-esemplare.php', $template );
		}

		if ( is_post_type_archive( DFA_CPT::POST_TYPE ) ) {
			return self::locate( 'archive-esemplare.php', $template );
		}

		return $template;
	}

	/**
	 * Cerca prima un override nel tema attivo (child o parent), poi
	 * ricade sul template incluso nel plugin.
	 *
	 * @param string $file_name Nome file del template (es. "single-esemplare.php").
	 * @param string $fallback  Template originale da usare se nulla viene trovato.
	 * @return string
	 */
	private static function locate( $file_name, $fallback ) {
		$theme_file = locate_template( array( self::THEME_SUBDIR . '/' . $file_name ) );

		if ( $theme_file ) {
			return $theme_file;
		}

		$plugin_file = DFA_PLUGIN_DIR . 'templates/' . $file_name;

		return file_exists( $plugin_file ) ? $plugin_file : $fallback;
	}

	/**
	 * Carica il CSS/JS di frontend solo sulle pagine che ne hanno
	 * effettivamente bisogno: scheda singola, archivio, o una pagina
	 * qualsiasi che contiene lo shortcode [devil_fruit_archive].
	 */
	public static function enqueue_frontend_assets() {
		if ( ! self::current_page_needs_assets() ) {
			return;
		}

		wp_enqueue_style(
			'dfa-frontend',
			DFA_PLUGIN_URL . 'assets/css/devil-fruit-archive.css',
			array(),
			DFA_VERSION
		);

		wp_enqueue_script(
			'dfa-frontend',
			DFA_PLUGIN_URL . 'assets/js/devil-fruit-archive.js',
			array(),
			DFA_VERSION,
			true
		);
	}

	/**
	 * Determina se la pagina corrente deve caricare gli asset del plugin.
	 *
	 * @return bool
	 */
	private static function current_page_needs_assets() {
		if ( is_singular( DFA_CPT::POST_TYPE ) || is_post_type_archive( DFA_CPT::POST_TYPE ) ) {
			return true;
		}

		global $post;

		return $post instanceof WP_Post && has_shortcode( $post->post_content, 'devil_fruit_archive' );
	}
}
