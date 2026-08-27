<?php
/**
 * Registrazione del Custom Post Type "esemplare".
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CPT "esemplare": ogni post rappresenta un Devil Fruit archiviato.
 * Slug pubblico "archivio", niente prezzo/carrello: è un dossier, non
 * un prodotto e-commerce.
 */
class DFA_CPT {

	/** Slug interno del post type. */
	const POST_TYPE = 'esemplare';

	/** Slug pubblico usato nell'URL (rewrite) e nell'archivio. */
	const REWRITE_SLUG = 'archivio';

	/**
	 * Aggancia la registrazione del CPT al hook "init".
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'after_setup_theme', array( __CLASS__, 'ensure_thumbnail_support' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'order_archive_query' ) );
	}

	/**
	 * Ordina l'archivio pubblico per Catalog ID crescente (DF-001,
	 * DF-002, ...) e mostra tutti gli esemplari su un'unica pagina,
	 * senza paginazione.
	 *
	 * @param WP_Query $query Query in corso.
	 */
	public static function order_archive_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! $query->is_post_type_archive( self::POST_TYPE ) ) {
			return;
		}

		$query->set( 'posts_per_page', -1 );
		$query->set( 'meta_key', DFA_Meta::PREFIX . 'catalog_id' ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', 'ASC' );
	}

	/**
	 * Il box "Immagine in evidenza" compare in wp-admin solo se il TEMA
	 * attivo dichiara add_theme_support('post-thumbnails'): il
	 * 'supports' => array('thumbnail') del post type da solo non
	 * basta. Il plugin è auto-contenuto e non deve dipendere dal tema:
	 * forziamo qui il supporto, solo per il CPT "esemplare".
	 */
	public static function ensure_thumbnail_support() {
		add_theme_support( 'post-thumbnails', array( self::POST_TYPE ) );
	}

	/**
	 * Registra il post type "esemplare".
	 *
	 * Richiamato sia dall'hook "init" sia direttamente in fase di
	 * attivazione del plugin (prima del flush delle rewrite rules).
	 */
	public static function register_post_type() {
		$labels = array(
			'name'                  => __( 'Devil Fruit Archive', 'devil-fruit-archive' ),
			'singular_name'         => __( 'Esemplare', 'devil-fruit-archive' ),
			'menu_name'             => __( 'Devil Fruit Archive', 'devil-fruit-archive' ),
			'name_admin_bar'        => __( 'Esemplare', 'devil-fruit-archive' ),
			'add_new'               => __( 'Aggiungi esemplare', 'devil-fruit-archive' ),
			'add_new_item'          => __( 'Aggiungi nuovo esemplare', 'devil-fruit-archive' ),
			'edit_item'             => __( 'Modifica esemplare', 'devil-fruit-archive' ),
			'new_item'              => __( 'Nuovo esemplare', 'devil-fruit-archive' ),
			'view_item'             => __( 'Visualizza esemplare', 'devil-fruit-archive' ),
			'view_items'            => __( 'Visualizza archivio', 'devil-fruit-archive' ),
			'search_items'          => __( 'Cerca esemplari', 'devil-fruit-archive' ),
			'not_found'             => __( 'Nessun esemplare trovato', 'devil-fruit-archive' ),
			'not_found_in_trash'    => __( 'Nessun esemplare nel cestino', 'devil-fruit-archive' ),
			'all_items'             => __( 'Tutti gli esemplari', 'devil-fruit-archive' ),
			'archives'              => __( 'Archivio esemplari', 'devil-fruit-archive' ),
			'featured_image'        => __( 'Foto esemplare', 'devil-fruit-archive' ),
			'set_featured_image'    => __( 'Imposta foto esemplare', 'devil-fruit-archive' ),
			'remove_featured_image' => __( 'Rimuovi foto esemplare', 'devil-fruit-archive' ),
			'use_featured_image'    => __( 'Usa come foto esemplare', 'devil-fruit-archive' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Esemplari catalogati nel Devil Fruit Archive.', 'devil-fruit-archive' ),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-media-archive',
			'query_var'           => true,
			'capability_type'     => 'post',
			'has_archive'         => self::REWRITE_SLUG,
			'rewrite'             => array(
				'slug'       => self::REWRITE_SLUG,
				'with_front' => false,
			),
			'hierarchical'        => false,
			'supports'            => array( 'title', 'thumbnail' ),
			'menu_position'       => 20,
		);

		register_post_type( self::POST_TYPE, $args );
	}
}
