<?php
/**
 * Bottoni di navigazione (stile CTA) sotto il titolo dell'header, nei
 * template full-bleed (single e archivio): tornano al sito principale
 * e, dalla scheda singola, all'archivio, senza dover usare il tasto
 * "indietro" del browser.
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dfa_archive_link  = get_post_type_archive_link( DFA_CPT::POST_TYPE );
$dfa_is_archive    = is_post_type_archive( DFA_CPT::POST_TYPE );
?>
<div class="dfa-topbar">
	<?php if ( $dfa_is_archive ) : ?>
		<?php // Dall'archivio si torna al sito principale. ?>
		<a class="dfa-topbar__link" href="<?php echo esc_url( home_url( '/' ) ); ?>">&larr; TORNA AL SITO</a>
	<?php elseif ( $dfa_archive_link ) : ?>
		<?php // Dalla scheda si torna all'archivio: unico bottone, per non disperdere il percorso. ?>
		<a class="dfa-topbar__link" href="<?php echo esc_url( $dfa_archive_link ); ?>">&larr; ARCHIVIO</a>
	<?php endif; ?>
</div>
