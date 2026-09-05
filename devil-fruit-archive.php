<?php
/**
 * Plugin Name:       Devil Fruit Archive
 * Plugin URI:        https://francystore3d.com
 * Description:       Archivio/dossier classificato in stile "Vegapunk Research Division" per esemplari di Devil Fruit. Nessun e-commerce: solo catalogo consultabile.
 * Version:           4.3
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            FrancyStore3D
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       devil-fruit-archive
 * Domain Path:       /languages
 */

// Evita accesso diretto al file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Versione del plugin. Bump manuale ad ogni modifica (anche minima):
 * usata per il cache-busting di CSS/JS (wp_enqueue_style/script) e
 * mostrata in fondo alla pagina impostazioni, per verificare a colpo
 * d'occhio che un aggiornamento sia stato effettivamente caricato.
 */
define( 'DFA_VERSION', '4.3' );

/** Percorso assoluto della cartella del plugin, con trailing slash. */
define( 'DFA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/** URL della cartella del plugin, con trailing slash. */
define( 'DFA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/** Percorso assoluto del file principale del plugin. */
define( 'DFA_PLUGIN_FILE', __FILE__ );

/**
 * Carica le classi del plugin.
 *
 * Ogni file in includes/ definisce una classe DFA_* responsabile di
 * un'unica area funzionale (CPT, meta, meta box, colonne admin,
 * shortcode, impostazioni, seed) e si auto-registra sui relativi hook
 * dal proprio metodo init().
 */
require_once DFA_PLUGIN_DIR . 'includes/class-dfa-cpt.php';
require_once DFA_PLUGIN_DIR . 'includes/class-dfa-meta.php';
require_once DFA_PLUGIN_DIR . 'includes/class-dfa-metabox.php';
require_once DFA_PLUGIN_DIR . 'includes/class-dfa-admin-columns.php';
require_once DFA_PLUGIN_DIR . 'includes/class-dfa-shortcode.php';
require_once DFA_PLUGIN_DIR . 'includes/class-dfa-settings.php';
require_once DFA_PLUGIN_DIR . 'includes/class-dfa-seed.php';
require_once DFA_PLUGIN_DIR . 'includes/class-dfa-template-loader.php';
require_once DFA_PLUGIN_DIR . 'includes/class-dfa-transfer.php';

/**
 * Inizializza tutte le componenti del plugin.
 */
function dfa_init_plugin() {
	DFA_CPT::init();
	DFA_Meta::init();
	DFA_Metabox::init();
	DFA_Admin_Columns::init();
	DFA_Shortcode::init();
	DFA_Settings::init();
	DFA_Seed::init();
	DFA_Template_Loader::init();
	DFA_Transfer::init();
}
add_action( 'plugins_loaded', 'dfa_init_plugin' );

/**
 * Attivazione plugin: registra il CPT e fa il flush delle rewrite rules
 * così lo slug pubblico "archivio" funziona subito, senza dover
 * risalvare manualmente i permalink da wp-admin.
 */
function dfa_activate_plugin() {
	DFA_CPT::register_post_type();
	flush_rewrite_rules();
}
register_activation_hook( DFA_PLUGIN_FILE, 'dfa_activate_plugin' );

/**
 * Disattivazione plugin: rimuove le rewrite rules aggiunte dal CPT.
 * Non elimina contenuti (esemplari, impostazioni): la disattivazione
 * non è una disinstallazione.
 */
function dfa_deactivate_plugin() {
	flush_rewrite_rules();
}
register_deactivation_hook( DFA_PLUGIN_FILE, 'dfa_deactivate_plugin' );
