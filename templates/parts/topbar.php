<?php
/**
 * Top bar di navigazione per i template full-bleed (single e archivio):
 * link per tornare al sito principale e, dalla scheda singola, per
 * tornare all'archivio senza dover usare il tasto "indietro" del
 * browser.
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dfa_archive_link = get_post_type_archive_link( DFA_CPT::POST_TYPE );
?>
<div class="dfa-topbar">
	<a class="dfa-topbar__link" href="<?php echo esc_url( home_url( '/' ) ); ?>">&larr; <?php echo esc_html( get_bloginfo( 'name' ) ); ?></a>
	<?php if ( $dfa_archive_link && ! is_post_type_archive( DFA_CPT::POST_TYPE ) ) : ?>
		<a class="dfa-topbar__link" href="<?php echo esc_url( $dfa_archive_link ); ?>">&larr; ARCHIVIO</a>
	<?php endif; ?>
</div>
