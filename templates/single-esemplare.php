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
	$owner_current_image   = (int) DFA_Meta::get( $post_id, 'owner_current_image' );
	$owner_former_image    = (int) DFA_Meta::get( $post_id, 'owner_former_image' );
	$cta_url                = DFA_Settings::get_cta_url();
	$has_two_owners         = $owner_former_image > 0;
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( wp_get_document_title() ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'dfa-single' . ( $has_two_owners ? ' dfa-single--dual' : '' ) ); ?>>

	<article class="dfa-single__frame">

		<?php if ( $owner_current_image || $has_two_owners ) : ?>
		<div class="dfa-single__bg">
			<?php if ( $owner_current_image ) : ?>
				<?php echo wp_get_attachment_image( $owner_current_image, 'full' ); ?>
			<?php endif; ?>
			<?php if ( $has_two_owners ) : ?>
				<?php echo wp_get_attachment_image( $owner_former_image, 'full' ); ?>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<div class="dfa-single__overlay"></div>
		<div class="dfa-single__vignette"></div>
		<div class="dfa-single__scanlines" aria-hidden="true"></div>

		<header class="dfa-single__header">
			<div class="dfa-single__header-title">VEGAPUNK RESEARCH DIVISION — DEVIL FRUIT ARCHIVE</div>
			<div class="dfa-single__header-rule"></div>
		</header>

		<div class="dfa-single__meta">
			<div>ARCHIVIO / SCHEDA</div>
			<div class="dfa-single__meta-rule"></div>
			<div>SEC. LEVEL: 04</div>
		</div>

		<div class="dfa-single__specimen-wrap">
			<div class="dfa-single__specimen">
				<?php if ( has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'large' ); ?>
				<?php endif; ?>
			</div>
			<div class="dfa-single__specimen-floor"></div>
		</div>

		<div class="dfa-single__dogtag">
			<div class="dfa-single__dogtag-chain"></div>
			<div class="dfa-single__dogtag-body">CLASSIFIED SPECIMEN<br>STATUS: ARCHIVED</div>
		</div>

		<section class="dfa-single__panel">
			<span class="dfa-single__panel-screw dfa-single__panel-screw--tl"></span>
			<span class="dfa-single__panel-screw dfa-single__panel-screw--tr"></span>
			<span class="dfa-single__panel-screw dfa-single__panel-screw--bl"></span>
			<span class="dfa-single__panel-screw dfa-single__panel-screw--br"></span>

			<div class="dfa-single__panel-id">
				<div>CATALOG ID: <?php echo esc_html( $catalog_id ); ?></div>
				<div>TYPE: <?php echo esc_html( $fruit_type_label ); ?></div>
			</div>
			<div class="dfa-single__panel-rule"></div>
			<div class="dfa-single__panel-name dfa-display"><?php echo esc_html( $romaji_name ); ?></div>
			<?php if ( $katakana_name ) : ?>
				<div class="dfa-single__panel-kana dfa-jp"><?php echo esc_html( $katakana_name ); ?></div>
			<?php endif; ?>
			<div class="dfa-single__panel-rule"></div>
			<div class="dfa-single__panel-details">
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

		<div class="dfa-single__cta-wrap">
			<a href="<?php echo esc_url( $cta_url ); ?>" class="dfa-single__cta" target="_blank" rel="noopener noreferrer">RICHIEDI QUESTO ESEMPLARE</a>
		</div>

		<?php if ( $lore ) : ?>
		<footer class="dfa-single__note">
			<div class="dfa-single__note-head">
				RESEARCH NOTE / OSSERVAZIONI
				<span class="dfa-single__note-rule"></span>
				<span><?php echo esc_html( $catalog_id ); ?></span>
			</div>
			<div class="dfa-single__note-text">&ldquo;<?php echo esc_html( $lore ); ?>&rdquo;</div>
		</footer>
		<?php endif; ?>

	</article>

	<?php wp_footer(); ?>
</body>
</html>
	<?php
	break; // La scheda singola mostra un solo esemplare per richiesta.
endwhile;
