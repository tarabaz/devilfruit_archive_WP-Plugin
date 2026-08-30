<?php
/**
 * Card di un singolo esemplare nella griglia archivio.
 *
 * Richiesto dentro un loop attivo (the_post() già chiamato) sia da
 * templates/archive-esemplare.php sia da DFA_Shortcode. Non fare
 * require di questo file fuori da un loop: usa dati del post corrente.
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dfa_post_id       = get_the_ID();
$dfa_catalog_id    = DFA_Meta::get( $dfa_post_id, 'catalog_id' );
$dfa_romaji_name   = DFA_Meta::get( $dfa_post_id, 'romaji_name' );
$dfa_fruit_type    = DFA_Meta::get( $dfa_post_id, 'fruit_type' );
$dfa_fruit_types   = DFA_Meta::get_fruit_types();
$dfa_type_label    = isset( $dfa_fruit_types[ $dfa_fruit_type ] ) ? $dfa_fruit_types[ $dfa_fruit_type ] : $dfa_fruit_type;

$dfa_badge_icon_class = 'dfa-archive__badge-icon';
if ( 'LOGIA' === $dfa_fruit_type ) {
	$dfa_badge_icon_class .= ' dfa-archive__badge-icon--logia';
} elseif ( 'ZOAN' === $dfa_fruit_type ) {
	$dfa_badge_icon_class .= ' dfa-archive__badge-icon--zoan';
} elseif ( 'ZOAN_MYTHICAL' === $dfa_fruit_type ) {
	$dfa_badge_icon_class .= ' dfa-archive__badge-icon--mythical';
}

// Sfondo della card: foto del proprietario attuale (in bianco e nero,
// a colori solo in hover). Immagine "prodotto" nel riquadro: SOLO
// l'immagine frutto dedicata, mai la featured image del post — se non
// caricata il riquadro resta trasparente e mostra lo sfondo dietro.
$dfa_card_bg_image = (int) DFA_Meta::get( $dfa_post_id, 'owner_current_image' );
$dfa_fruit_image   = (int) DFA_Meta::get( $dfa_post_id, 'fruit_image' );

// Esemplare annunciato ma non ancora consultabile: la card si vede ma
// non è un link, quindi non apre la scheda.
$dfa_coming_soon = DFA_Meta::is_coming_soon( $dfa_post_id );
?>
<article class="dfa-archive__card<?php echo $dfa_coming_soon ? ' dfa-archive__card--coming' : ''; ?>">
	<?php
	/*
	 * Stesso contenuto in entrambi i casi: cambia solo il contenitore,
	 * <a> per gli esemplari consultabili e <div> per i coming soon. Il
	 * tag di chiusura corrispondente è in fondo al file.
	 */
	?>
	<?php if ( $dfa_coming_soon ) : ?>
	<div class="dfa-archive__card-link">
		<div class="dfa-archive__card-watermark">COMING SOON</div>
	<?php else : ?>
	<a class="dfa-archive__card-link" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( $dfa_romaji_name ? $dfa_romaji_name : get_the_title() ); ?>">
	<?php endif; ?>
		<?php if ( $dfa_card_bg_image ) : ?>
			<div class="dfa-archive__card-bg">
				<div class="dfa-archive__card-bg-character"><?php echo wp_get_attachment_image( $dfa_card_bg_image, 'medium_large' ); ?></div>
				<div class="dfa-archive__card-veil"></div>
			</div>
			<div class="dfa-archive__card-overlay"></div>
		<?php endif; ?>

		<span class="dfa-archive__card-corner dfa-archive__card-corner--tl"></span>
		<span class="dfa-archive__card-corner dfa-archive__card-corner--tr"></span>
		<span class="dfa-archive__card-corner dfa-archive__card-corner--bl"></span>
		<span class="dfa-archive__card-corner dfa-archive__card-corner--br"></span>

		<div class="dfa-archive__card-image">
			<?php if ( $dfa_fruit_image ) : ?>
				<?php echo wp_get_attachment_image( $dfa_fruit_image, 'medium' ); ?>
			<?php endif; ?>
		</div>

		<div class="dfa-archive__card-id"><?php echo esc_html( $dfa_catalog_id ); ?></div>
		<?php // format_fruit_name() restituisce HTML già escapato al suo interno. ?>
		<div class="dfa-archive__card-name dfa-display"><?php echo wp_kses_post( DFA_Meta::format_fruit_name( $dfa_romaji_name ? $dfa_romaji_name : get_the_title() ) ); ?></div>

		<?php if ( $dfa_type_label ) : ?>
			<div class="dfa-archive__badge">
				<span class="<?php echo esc_attr( $dfa_badge_icon_class ); ?>"></span>
				<span><?php echo esc_html( $dfa_type_label ); ?></span>
			</div>
		<?php endif; ?>
	<?php if ( $dfa_coming_soon ) : ?>
	</div>
	<?php else : ?>
	</a>
	<?php endif; ?>
</article>
