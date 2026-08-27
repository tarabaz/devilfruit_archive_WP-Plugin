<?php
/**
 * Colonne personalizzate "Catalog ID" e "Type" nella lista admin degli
 * esemplari, entrambe ordinabili.
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFA_Admin_Columns {

	/**
	 * Aggancia i filtri per le colonne della lista admin.
	 */
	public static function init() {
		add_filter( 'manage_' . DFA_CPT::POST_TYPE . '_posts_columns', array( __CLASS__, 'add_columns' ) );
		add_action( 'manage_' . DFA_CPT::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-' . DFA_CPT::POST_TYPE . '_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_columns_query' ) );
	}

	/**
	 * Inserisce le colonne "Catalog ID" e "Type" subito dopo il titolo.
	 *
	 * @param array<string,string> $columns Colonne esistenti.
	 * @return array<string,string>
	 */
	public static function add_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			if ( 'title' === $key ) {
				$new_columns['dfa_catalog_id'] = __( 'Catalog ID', 'devil-fruit-archive' );
				$new_columns['dfa_fruit_type'] = __( 'Type', 'devil-fruit-archive' );
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
	}

	/**
	 * Rende ordinabili le colonne custom.
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
	 * Applica l'ordinamento per meta value quando si clicca l'header
	 * delle colonne custom nella lista admin.
	 *
	 * @param WP_Query $query Query in corso.
	 */
	public static function sort_columns_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'dfa_catalog_id' === $orderby ) {
			$query->set( 'meta_key', DFA_Meta::PREFIX . 'catalog_id' );
			$query->set( 'orderby', 'meta_value' );
		}

		if ( 'dfa_fruit_type' === $orderby ) {
			$query->set( 'meta_key', DFA_Meta::PREFIX . 'fruit_type' );
			$query->set( 'orderby', 'meta_value' );
		}
	}
}
