/**
 * Importazione dell'archivio a lotti.
 *
 * Lo script viene caricato solo quando c'è un'importazione da portare
 * avanti. Chiama admin-ajax una volta per lotto: ogni chiamata lavora
 * per una decina di secondi, torna la percentuale e la riga di stato, e
 * si va avanti finché il server non risponde "done". Così nessuna
 * singola richiesta rischia il timeout, per quanto grande sia il
 * pacchetto.
 *
 * Un lotto alla volta, mai in parallelo: le richieste scrivono tutte
 * sullo stesso stato salvato a database.
 */
( function ( $ ) {
	'use strict';

	if ( typeof window.dfaImport === 'undefined' ) {
		return;
	}

	var settings = window.dfaImport;

	$( function () {
		var $box = $( '.dfa-import-progress' );

		if ( ! $box.length ) {
			return;
		}

		var $fill = $box.find( '.dfa-import-progress__fill' );
		var $text = $box.find( '.dfa-import-progress__text' );

		// Durante l'importazione il form di caricamento sparisce: farne
		// partire una seconda a metà strada rovinerebbe la prima.
		$( '.dfa-import-form' ).hide();
		$box.prop( 'hidden', false );
		$text.text( settings.startMessage );

		/**
		 * Sostituisce i segnaposto %1$d, %2$d, %3$d di una stringa
		 * tradotta con i valori passati.
		 *
		 * @param {string} template Stringa con i segnaposto.
		 * @param {Array}  values   Valori, in ordine.
		 * @return {string}
		 */
		function format( template, values ) {
			return template.replace( /%(\d+)\$d/g, function ( match, index ) {
				return values[ parseInt( index, 10 ) - 1 ];
			} );
		}

		function fail( message ) {
			$box.addClass( 'is-error' );
			$text.text( message || settings.errorMessage );
		}

		function step() {
			$.post( settings.ajaxUrl, {
				action: settings.action,
				nonce: settings.nonce
			} ).done( function ( response ) {
				if ( ! response || ! response.success || ! response.data ) {
					fail( response && response.data ? response.data.message : null );
					return;
				}

				var data = response.data;

				$fill.css( 'width', data.percent + '%' );

				if ( ! data.done ) {
					$text.text( data.message );
					step();
					return;
				}

				$box.addClass( 'is-done' );
				$text.text(
					format( settings.doneMessage, [
						data.summary.created,
						data.summary.updated,
						data.summary.images
					] )
				);
			} ).fail( function () {
				fail();
			} );
		}

		step();
	} );
} )( jQuery );
