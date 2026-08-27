<?php
/**
 * Template archivio (griglia) esemplari.
 *
 * Basato su _design/archive-esemplare.html: documento HTML completo
 * indipendente dal tema, caricato da class-dfa-template-loader.php al
 * posto dell'archive.php del tema per l'archivio pubblico del CPT
 * "esemplare" (/archivio/). La stessa griglia (via DFA_Shortcode) è
 * riusata dallo shortcode [devil_fruit_archive] dentro il contenuto
 * normale di una pagina. Il tema attivo può sovrascrivere questo file
 * copiandolo in wp-content/themes/<tema>/devil-fruit-archive/archive-esemplare.php.
 *
 * @package DevilFruitArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( wp_get_document_title() ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'dfa-archive-page' ); ?>>

	<?php require DFA_PLUGIN_DIR . 'templates/parts/topbar.php'; ?>

	<div class="dfa-archive">
		<div class="dfa-archive__scanlines" aria-hidden="true"></div>

		<header class="dfa-archive__header">
			<div class="dfa-archive__title dfa-display">VEGAPUNK RESEARCH DIVISION</div>
			<div class="dfa-archive__subtitle">— DEVIL FRUIT ARCHIVE —</div>
			<div class="dfa-archive__tag">CLASSIFIED SPECIMENS</div>
			<div class="dfa-archive__rule"></div>
			<div class="dfa-archive__brandbar">
				<div class="dfa-archive__nav">
					<span>CATALOGO</span><span>TIPOLOGIE</span><span>ARCHIVIO</span><span>CONTATTI</span>
				</div>
				<div>FRANCYSTORE3D</div>
			</div>
		</header>

		<?php if ( have_posts() ) : ?>

			<div class="dfa-archive__grid">
				<?php
				while ( have_posts() ) :
					the_post();
					require DFA_PLUGIN_DIR . 'templates/parts/card-esemplare.php';
				endwhile;
				?>
			</div>

		<?php else : ?>

			<p class="dfa-archive__empty">Nessun esemplare ancora archiviato.</p>

		<?php endif; ?>

	</div>

	<?php wp_footer(); ?>
</body>
</html>
