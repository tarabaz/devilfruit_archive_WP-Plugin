/**
 * Devil Fruit Archive — JS di frontend.
 *
 * Unica interazione: il bottone che accende e spegne la lampada del
 * barattolo nella scheda singola. Le due immagini (spenta = immagine in
 * evidenza, accesa = campo "Immagine esemplare acceso") sono sovrapposte
 * nel markup; qui ci si limita ad alternare la classe "is-lit", mentre
 * la dissolvenza da 1 secondo è gestita dalla transizione CSS.
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
				var isLit = specimen.classList.toggle( 'is-lit' );

				button.setAttribute( 'aria-pressed', isLit ? 'true' : 'false' );
				button.textContent = isLit
					? ( button.dataset.labelOff || 'SPEGNI LA LAMPADA' )
					: ( button.dataset.labelOn || 'ACCENDI LA LAMPADA' );
			} );
		} );
	} );
} )();
