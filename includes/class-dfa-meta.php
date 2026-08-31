<?php
/**
 * Registrazione dei campi meta dell'esemplare (register_post_meta).
 *
 * Centralizza chiavi meta, tipi ammessi per fruit_type e helper di
 * lettura usati da meta box e template.
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFA_Meta {

	/** Prefisso comune a tutte le chiavi meta del plugin. */
	const PREFIX = 'dfa_';

	/**
	 * Aggancia la registrazione dei meta al hook "init".
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta_fields' ) );
	}

	/**
	 * Tipologie di frutto ammesse (valore salvato => etichetta mostrata).
	 * I valori canonici non vengono tradotti, come da specifica.
	 *
	 * @return array<string,string>
	 */
	public static function get_fruit_types() {
		return array(
			'LOGIA'         => 'LOGIA',
			'PARAMECIA'     => 'PARAMECIA',
			'ZOAN'          => 'ZOAN',
			'ZOAN_MYTHICAL' => 'ZOAN (MYTHICAL)',
		);
	}

	/**
	 * Registra tutti i meta field del CPT "esemplare".
	 */
	public static function register_meta_fields() {
		$text_fields = array( 'catalog_id', 'romaji_name', 'katakana_name', 'special_note', 'owner_current', 'owner_former' );

		foreach ( $text_fields as $key ) {
			register_post_meta(
				DFA_CPT::POST_TYPE,
				self::PREFIX . $key,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
					'show_in_rest'      => true,
					'auth_callback'     => array( __CLASS__, 'auth_callback' ),
				)
			);
		}

		register_post_meta(
			DFA_CPT::POST_TYPE,
			self::PREFIX . 'fruit_type',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => 'PARAMECIA',
				'sanitize_callback' => array( __CLASS__, 'sanitize_fruit_type' ),
				'show_in_rest'      => true,
				'auth_callback'     => array( __CLASS__, 'auth_callback' ),
			)
		);

		register_post_meta(
			DFA_CPT::POST_TYPE,
			self::PREFIX . 'lore',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
				'show_in_rest'      => true,
				'auth_callback'     => array( __CLASS__, 'auth_callback' ),
			)
		);

		/*
		 * Flag "coming soon": salvato come '1' oppure stringa vuota
		 * (invece che come boolean) per restare omogeneo agli altri meta
		 * di testo, che export/import e meta box trattano tutti allo
		 * stesso modo.
		 */
		register_post_meta(
			DFA_CPT::POST_TYPE,
			self::PREFIX . 'coming_soon',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( __CLASS__, 'sanitize_coming_soon' ),
				'show_in_rest'      => true,
				'auth_callback'     => array( __CLASS__, 'auth_callback' ),
			)
		);

		foreach ( array( 'owner_current_image', 'fruit_image', 'specimen_lit_image' ) as $key ) {
			register_post_meta(
				DFA_CPT::POST_TYPE,
				self::PREFIX . $key,
				array(
					'type'              => 'integer',
					'single'            => true,
					'default'           => 0,
					'sanitize_callback' => 'absint',
					'show_in_rest'      => true,
					'auth_callback'     => array( __CLASS__, 'auth_callback' ),
				)
			);
		}
	}

	/**
	 * Sanitize per fruit_type: accetta solo uno dei valori ammessi.
	 *
	 * @param string $value Valore grezzo.
	 * @return string Valore validato, "PARAMECIA" come fallback.
	 */
	public static function sanitize_fruit_type( $value ) {
		$valid = array_keys( self::get_fruit_types() );
		return in_array( $value, $valid, true ) ? $value : 'PARAMECIA';
	}

	/**
	 * Sanitize per il flag "coming soon": normalizza qualunque valore
	 * "vero" (1, '1', true, 'on') a '1' e tutto il resto a stringa vuota.
	 *
	 * @param mixed $value Valore grezzo.
	 * @return string '1' oppure ''.
	 */
	public static function sanitize_coming_soon( $value ) {
		return $value && '0' !== $value ? '1' : '';
	}

	/**
	 * Un esemplare "coming soon" compare nell'archivio con la fascia
	 * COMING SOON, non è cliccabile e finisce in fondo alla griglia.
	 *
	 * @param int $post_id ID dell'esemplare.
	 * @return bool
	 */
	public static function is_coming_soon( $post_id ) {
		return '1' === (string) self::get( $post_id, 'coming_soon' );
	}

	/**
	 * Ordina gli esemplari come vanno mostrati nell'archivio: prima i
	 * normali per Catalog ID crescente, poi i "coming soon" (sempre in
	 * fondo, anche se hanno un Catalog ID più basso), a loro volta per
	 * Catalog ID.
	 *
	 * L'ordinamento è in PHP e non in SQL: ordinare per due meta diversi
	 * richiederebbe clausole annidate con LEFT JOIN, fragili e capaci di
	 * far sparire dalla griglia gli esemplari a cui manca uno dei due
	 * campi. L'archivio è a pagina unica e di poche decine di schede,
	 * quindi il costo è irrilevante.
	 *
	 * @param WP_Post[] $posts Post da ordinare.
	 * @return WP_Post[]
	 */
	public static function sort_for_archive( $posts ) {
		if ( count( $posts ) < 2 ) {
			return $posts;
		}

		// I meta letti qui sotto arrivano tutti da una sola query.
		update_postmeta_cache( wp_list_pluck( $posts, 'ID' ) );

		usort(
			$posts,
			static function ( $a, $b ) {
				$a_soon = self::is_coming_soon( $a->ID ) ? 1 : 0;
				$b_soon = self::is_coming_soon( $b->ID ) ? 1 : 0;

				if ( $a_soon !== $b_soon ) {
					return $a_soon - $b_soon;
				}

				$compare = strcmp(
					(string) self::get( $a->ID, 'catalog_id' ),
					(string) self::get( $b->ID, 'catalog_id' )
				);

				// A parita' di Catalog ID si ordina per ID: usort non e'
				// stabile in PHP 7 e senza questo la griglia potrebbe
				// cambiare ordine da un caricamento all'altro.
				return 0 !== $compare ? $compare : ( (int) $a->ID - (int) $b->ID );
			}
		);

		return $posts;
	}

	/**
	 * Autorizzazione lettura/scrittura per register_post_meta().
	 *
	 * @return bool
	 */
	public static function auth_callback() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Helper: legge un meta field dell'esemplare con il prefisso corretto.
	 *
	 * @param int    $post_id ID del post "esemplare".
	 * @param string $key     Nome del campo senza prefisso (es. "catalog_id").
	 * @return string|int
	 */
	public static function get( $post_id, $key ) {
		return get_post_meta( $post_id, self::PREFIX . $key, true );
	}

	/**
	 * Formatta il nome del frutto per la griglia archivio mandando la
	 * parte "NO MI" a capo, in corpo ridotto:
	 *
	 *     JIKI JIKI
	 *     NO MI
	 *
	 * Il taglio avviene alla prima occorrenza di "NO MI", quindi anche i
	 * nomi con specifica di modello restano leggibili:
	 *
	 *     TORI TORI
	 *     NO MI, MODEL: PHOENIX
	 *
	 * Se "NO MI" non compare, il nome viene restituito intero su una
	 * riga sola.
	 *
	 * @param string $name Nome grezzo (romaji o titolo del post).
	 * @return string HTML già escapato, pronto per l'output.
	 */
	public static function format_fruit_name( $name ) {
		$name = trim( (string) $name );

		if ( '' === $name ) {
			return '';
		}

		if ( preg_match( '/^(.*?)\s+(NO\s+MI\b.*)$/iu', $name, $matches ) ) {
			return esc_html( $matches[1] )
				. '<span class="dfa-archive__card-name-suffix">' . esc_html( $matches[2] ) . '</span>';
		}

		return esc_html( $name );
	}
}
