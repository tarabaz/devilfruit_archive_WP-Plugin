<?php
/**
 * Barra in fondo alle pagine dell'archivio.
 *
 * Le pagine del plugin sono documenti HTML indipendenti dal tema (non
 * chiamano get_footer()), quindi il footer di Avada non compare: questa
 * riga ne riporta i contenuti minimi — policy e copyright — nello stile
 * dell'archivio. Il banner dei cookie non c'entra: quello arriva da
 * wp_footer(), che i template chiamano comunque.
 *
 * Una riga sola, centrata e volutamente discreta. L'anno si aggiorna da
 * solo; se manca l'indirizzo delle policy, quella parte e il separatore
 * che la segue non vengono stampati.
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dfa_footer = DFA_Settings::get_footer_data();
?>
<footer class="dfa-footer">
	<p class="dfa-footer__line">
		<?php if ( $dfa_footer['privacy'] ) : ?>
			<?php // Un unico link per entrambe le informative: stanno sulla stessa pagina. ?>
			<a href="<?php echo esc_url( $dfa_footer['privacy'] ); ?>">Privacy Policy &middot; Cookie Policy</a>
			&middot;
		<?php endif; ?>
		&copy; <?php echo esc_html( $dfa_footer['year'] ); ?> <?php echo esc_html( $dfa_footer['owner'] ); ?>
		&middot;
		<?php esc_html_e( 'Tutti i diritti riservati', 'devil-fruit-archive' ); ?>
	</p>
</footer>
