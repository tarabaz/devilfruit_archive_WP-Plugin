<?php
/**
 * Template scheda singola esemplare.
 *
 * Basato su _design/single-esemplare.html: documento HTML completo e
 * indipendente dal tema (full-bleed), caricato da
 * class-dfa-template-loader.php al posto del single.php del tema. Il
 * tema attivo può sovrascrivere questo file copiandolo in
 * wp-content/themes/<tema>/devil-fruit-archive/single-esemplare.php.
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

while ( have_posts() ) :
	the_post();

	$post_id             = get_the_ID();
	$catalog_id           = DFA_Meta::get( $post_id, 'catalog_id' );
	$fruit_type_key        = DFA_Meta::get( $post_id, 'fruit_type' );
	$fruit_types           = DFA_Meta::get_fruit_types();
	$fruit_type_label       = isset( $fruit_types[ $fruit_type_key ] ) ? $fruit_types[ $fruit_type_key ] : $fruit_type_key;
	$romaji_name           = DFA_Meta::get( $post_id, 'romaji_name' );
	$katakana_name         = DFA_Meta::get( $post_id, 'katakana_name' );
	$special_note          = DFA_Meta::get( $post_id, 'special_note' );
	$owner_current         = DFA_Meta::get( $post_id, 'owner_current' );
	$owner_former          = DFA_Meta::get( $post_id, 'owner_former' );
	$lore                  = DFA_Meta::get( $post_id, 'lore' );
	// Versione "a lampada accesa" dell'esemplare: se presente, la scheda
	// mostra un bottone che alterna le due immagini in dissolvenza.
	$lit_image_id           = (int) DFA_Meta::get( $post_id, 'specimen_lit_image' );
	// Sfondo: "Foto proprietario attuale" dell'esemplare. Se l'esemplare
	// non ne ha ancora una, si usa lo sfondo di riserva impostato a
	// livello di plugin.
	$background_image_id    = (int) DFA_Meta::get( $post_id, 'owner_current_image' );
	if ( ! $background_image_id ) {
		$background_image_id = DFA_Settings::get_single_background_image_id();
	}

	/*
	 * URL diretto invece di wp_get_attachment_image(): quella funzione
	 * aggiunge srcset/sizes, e con essi il browser su finestre strette
	 * scarica una VARIANTE PIÙ PICCOLA del file. Siccome il CSS chiede di
	 * usare la dimensione naturale, la "naturale" diventa quella della
	 * variante ridotta e lo sfondo si rimpicciolisce invece di essere
	 * tagliato. Con un <img> semplice il file servito è sempre l'originale.
	 */
	$background_image_url = $background_image_id ? wp_get_attachment_image_url( $background_image_id, 'full' ) : '';

	// Titolo di pagina e intestazione riportano il Catalog ID.
	$document_title = wp_get_document_title();
	if ( $catalog_id ) {
		$document_title .= ' - ' . $catalog_id;
	}
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="dfa-single-html">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php // Colore delle barre del browser su mobile (iOS/Android), altrimenti campionato dal tema. ?>
	<meta name="theme-color" content="#08090a">
	<title><?php echo esc_html( $document_title ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'dfa-single' ); ?>>

	<article class="dfa-single__frame">

		<?php if ( $background_image_url ) : ?>
		<div class="dfa-single__bg">
			<img src="<?php echo esc_url( $background_image_url ); ?>" alt="" aria-hidden="true">
		</div>
		<?php endif; ?>

		<div class="dfa-single__overlay"></div>
		<div class="dfa-single__vignette"></div>
		<div class="dfa-single__scanlines" aria-hidden="true"></div>

		<header class="dfa-single__header">
			<?php // Stesso titolo dell'archivio; sotto, il Catalog ID a metà dimensione (font-size: 50% nel CSS). ?>
			<div class="dfa-single__header-title dfa-display">VEGAPUNK RESEARCH DIVISION<?php
				echo $catalog_id ? '<span class="dfa-single__header-id">' . esc_html( $catalog_id ) . '</span>' : '';
			?></div>
			<div class="dfa-single__header-rule"></div>
			<?php require DFA_PLUGIN_DIR . 'templates/parts/topbar.php'; ?>
		</header>

		<div class="dfa-single__content">

			<div class="dfa-single__specimen-wrap">
				<div class="dfa-single__specimen">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="dfa-single__specimen-layer dfa-single__specimen-layer--off"><?php the_post_thumbnail( 'large' ); ?></div>
					<?php endif; ?>
					<?php if ( $lit_image_id ) : ?>
						<div class="dfa-single__specimen-layer dfa-single__specimen-layer--on"><?php echo wp_get_attachment_image( $lit_image_id, 'large' ); ?></div>
					<?php endif; ?>
				</div>

				<?php if ( $lit_image_id ) : ?>
					<button type="button"
						class="dfa-single__lamp-btn"
						data-label-on="ACCENDI LA LAMPADA"
						data-label-off="SPEGNI LA LAMPADA"
						aria-pressed="false">ACCENDI LA LAMPADA</button>
				<?php endif; ?>
			</div>

		</div>

		<div class="dfa-single__cols">

			<div class="dfa-single__panel-col">
				<section class="dfa-single__panel">
					<span class="dfa-single__panel-screw dfa-single__panel-screw--tl"></span>
					<span class="dfa-single__panel-screw dfa-single__panel-screw--tr"></span>
					<span class="dfa-single__panel-screw dfa-single__panel-screw--bl"></span>
					<span class="dfa-single__panel-screw dfa-single__panel-screw--br"></span>

					<div class="dfa-single__panel-id">
						<div><span class="label">CATALOG ID:</span> <?php echo esc_html( $catalog_id ); ?></div>
					</div>
					<div class="dfa-single__panel-rule"></div>
					<div class="dfa-single__panel-name dfa-display"><?php echo esc_html( $romaji_name ); ?></div>
					<?php if ( $katakana_name ) : ?>
						<div class="dfa-single__panel-kana dfa-jp"><?php echo esc_html( $katakana_name ); ?></div>
					<?php endif; ?>
					<div class="dfa-single__panel-rule"></div>
					<div class="dfa-single__panel-details">
						<?php if ( $fruit_type_label ) : ?>
							<div><span class="label">TYPE:</span> <?php echo esc_html( $fruit_type_label ); ?></div>
						<?php endif; ?>
						<?php if ( $special_note ) : ?>
							<div><span class="label">SPECIAL NOTE:</span> <?php echo esc_html( $special_note ); ?></div>
						<?php endif; ?>
						<?php if ( $owner_current ) : ?>
							<div><span class="label">PROPRIETARIO:</span> <?php echo esc_html( $owner_current ); ?></div>
						<?php endif; ?>
						<?php if ( $owner_former ) : ?>
							<div><span class="label">EX PROPRIETARIO:</span> <?php echo esc_html( $owner_former ); ?></div>
						<?php endif; ?>
					</div>
				</section>
			</div>

			<?php if ( $lore ) : ?>
			<div class="dfa-single__note-col">
				<footer class="dfa-single__note">
					<div class="dfa-single__note-head">
						RESEARCH NOTE / OSSERVAZIONI
						<span class="dfa-single__note-rule"></span>
						<span><?php echo esc_html( $catalog_id ); ?></span>
					</div>
					<div class="dfa-single__note-text">&ldquo;<?php echo esc_html( $lore ); ?>&rdquo;</div>
				</footer>
			</div>
			<?php endif; ?>

		</div>

	</article>

	<?php wp_footer(); ?>
</body>
</html>
	<?php
	break; // La scheda singola mostra un solo esemplare per richiesta.
endwhile;
