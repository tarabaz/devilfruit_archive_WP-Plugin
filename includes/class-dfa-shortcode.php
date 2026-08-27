<?php
/**
 * Shortcode [devil_fruit_archive]: stampa la stessa griglia
 * dell'archivio pubblico in una pagina qualsiasi.
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFA_Shortcode {

	/**
	 * Registra lo shortcode.
	 */
	public static function init() {
		add_shortcode( 'devil_fruit_archive', array( __CLASS__, 'render' ) );
	}

	/**
	 * Renderizza la griglia degli esemplari pubblicati.
	 *
	 * Uso: [devil_fruit_archive] oppure [devil_fruit_archive per_page="8"]
	 *
	 * @param array|string $atts Attributi dello shortcode.
	 * @return string Markup HTML della griglia.
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'per_page' => -1,
			),
			$atts,
			'devil_fruit_archive'
		);

		$query = new WP_Query(
			array(
				'post_type'      => DFA_CPT::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => (int) $atts['per_page'],
				'orderby'        => 'meta_value',
				'meta_key'       => DFA_Meta::PREFIX . 'catalog_id',
				'order'          => 'ASC',
			)
		);

		ob_start();
		?>
		<div class="dfa-archive dfa-archive--shortcode">
			<?php if ( $query->have_posts() ) : ?>
				<div class="dfa-archive__grid">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						require DFA_PLUGIN_DIR . 'templates/parts/card-esemplare.php';
					endwhile;
					?>
				</div>
			<?php else : ?>
				<p class="dfa-archive__empty">Nessun esemplare ancora archiviato.</p>
			<?php endif; ?>
		</div>
		<?php
		wp_reset_postdata();

		return ob_get_clean();
	}
}
