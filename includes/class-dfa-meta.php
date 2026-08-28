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
