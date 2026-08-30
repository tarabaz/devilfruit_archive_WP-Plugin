/**
 * Devil Fruit Archive — JS di frontend.
 *
 * Unica interazione: il bottone che accende e spegne la lampada del
 * barattolo nella scheda singola. La scheda parte ACCESA (l'immagine in
 * evidenza è quella a lampada accesa) e il bottone parte da "SPEGNI LA
 * LAMPADA"; il campo "Immagine esemplare a lampada spenta" fornisce
 * l'altro scatto. Le due immagini sono sovrapposte nel markup; qui ci
 * si limita ad alternare la classe "is-unlit", mentre la dissolvenza da
 * 1 secondo è gestita dalla transizione CSS.
 *
 * Nessuna dipendenza esterna.
 */
( function () {
	'use strict';

	document.documentElement.classList.add( 'dfa-js-ready' );

	document.addEventListener( 'DOMContentLoaded', function () {
		var buttons = document.querySelectorAll( '.dfa-single__lamp-btn' );

		Array.prototype.forEach.call( buttons, function ( button ) {
			// Il bottone sta accanto all'esemplare, non dentro: si risale
			// al contenitore comune e da lì si trova il blocco immagine.
			var wrap = button.closest( '.dfa-single__specimen-wrap' );
			var specimen = wrap ? wrap.querySelector( '.dfa-single__specimen' ) : null;

			if ( ! specimen ) {
				return;
			}

			button.addEventListener( 'click', function () {
				var isUnlit = specimen.classList.toggle( 'is-unlit' );

				// aria-pressed = lampada accesa, cioè lo stato di partenza.
				button.setAttribute( 'aria-pressed', isUnlit ? 'false' : 'true' );
				button.textContent = isUnlit
					? ( button.dataset.labelOn || 'ACCENDI LA LAMPADA' )
					: ( button.dataset.labelOff || 'SPEGNI LA LAMPADA' );
			} );
		} );
	} );
} )();
