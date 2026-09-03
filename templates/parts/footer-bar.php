<?php
/**
 * Barra in fondo alle pagine dell'archivio.
 *
 * Le pagine del plugin sono documenti HTML indipendenti dal tema (non
 * chiamano get_footer()), quindi il footer di Avada non compare: questa
 * barra ne riporta i contenuti — email, policy, copyright — nello stile
 * dell'archivio. Il banner dei cookie non c'entra: quello arriva da
 * wp_footer(), che i template chiamano comunque.
 *
 * Ogni pezzo compare solo se configurato in Impostazioni; l'anno del
 * copyright si aggiorna da solo.
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dfa_footer = DFA_Settings::get_footer_data();
?>
<footer class="dfa-footer">
	<div class="dfa-footer__inner">
		<div class="dfa-footer__left">
			<?php if ( $dfa_footer['email'] ) : ?>
				<a href="mailto:<?php echo esc_attr( $dfa_footer['email'] ); ?>"><?php echo esc_html( $dfa_footer['email'] ); ?></a>
			<?php endif; ?>
		</div>

		<div class="dfa-footer__right">
			<?php if ( $dfa_footer['privacy'] ) : ?>
				<a href="<?php echo esc_url( $dfa_footer['privacy'] ); ?>">Privacy Policy</a>
				<span class="dfa-footer__sep" aria-hidden="true">&middot;</span>
			<?php endif; ?>

			<?php if ( $dfa_footer['cookie'] ) : ?>
				<a href="<?php echo esc_url( $dfa_footer['cookie'] ); ?>">Cookie Policy</a>
				<span class="dfa-footer__sep" aria-hidden="true">&middot;</span>
			<?php endif; ?>

			<span>&copy; <?php echo esc_html( $dfa_footer['year'] ); ?> <?php echo esc_html( $dfa_footer['owner'] ); ?></span>
			<span class="dfa-footer__sep" aria-hidden="true">&middot;</span>
			<span><?php esc_html_e( 'Tutti i diritti riservati', 'devil-fruit-archive' ); ?></span>
		</div>
	</div>
</footer>
