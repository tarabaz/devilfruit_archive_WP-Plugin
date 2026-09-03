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

$dfa_archive_bg_id = DFA_Settings::get_archive_background_image_id();

/*
 * URL diretto invece di wp_get_attachment_image(): quella funzione
 * aggiunge srcset/sizes, e con essi il browser su finestre strette
 * scarica una VARIANTE PIÙ PICCOLA del file. Siccome il CSS chiede di
 * usare la dimensione naturale dell'immagine, la "naturale" diventa
 * quella della variante ridotta e lo sfondo si rimpicciolisce invece di
 * essere tagliato ai lati. Con un <img> semplice il file servito è
 * sempre l'originale a piena risoluzione.
 */
$dfa_archive_bg_url = $dfa_archive_bg_id ? wp_get_attachment_image_url( $dfa_archive_bg_id, 'full' ) : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="dfa-archive-html">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php // Colore delle barre del browser su mobile (iOS/Android), altrimenti campionato dal tema. ?>
	<meta name="theme-color" content="#08090a">
	<title><?php echo esc_html( wp_get_document_title() ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'dfa-archive-page' ); ?>>

	<div class="dfa-archive">
		<?php if ( $dfa_archive_bg_url ) : ?>
			<div class="dfa-archive__bg">
				<img src="<?php echo esc_url( $dfa_archive_bg_url ); ?>" alt="" aria-hidden="true">
			</div>
		<?php endif; ?>

		<div class="dfa-archive__scanlines" aria-hidden="true"></div>

		<header class="dfa-archive__header">
			<div class="dfa-archive__title dfa-display">VEGAPUNK RESEARCH DIVISION</div>
			<div class="dfa-archive__subtitle">— DEVIL FRUIT ARCHIVE —</div>
			<div class="dfa-archive__tag">CLASSIFIED SPECIMENS</div>
			<div class="dfa-archive__rule"></div>
			<?php require DFA_PLUGIN_DIR . 'templates/parts/topbar.php'; ?>
			<div class="dfa-archive__brandbar">
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

	<?php // Fuori da .dfa-archive: sta sul fondo pagina, non sopra lo sfondo. ?>
	<?php require DFA_PLUGIN_DIR . 'templates/parts/footer-bar.php'; ?>

	<?php wp_footer(); ?>
</body>
</html>
