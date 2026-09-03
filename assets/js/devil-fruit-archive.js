/**
 * Devil Fruit Archive — JS di frontend.
 *
 * Unica interazione: i due bottoni sotto l'esemplare nella scheda
 * singola.
 *
 *  - LAMPADA: la scheda parte ACCESA (l'immagine in evidenza è quella a
 *    lampada accesa) e il bottone parte da "SPEGNI LA LAMPADA".
 *  - VARIANTE: passa alla seconda versione dello stesso esemplare (es.
 *    il frutto intero e quello morsicato) e ritorno.
 *
 * I due sono indipendenti: cambiando modello lo stato della lampada
 * resta, e viceversa. Qui si alternano solo le classi "is-unlit" e
 * "is-variant" sul contenitore; quale dei quattro scatti si vede lo
 * decide il CSS, e la dissolvenza da 1 secondo è la sua transizione.
 *
 * Un modello può non avere la versione spenta: in quel caso il bottone
 * della lampada viene disattivato e, se si arriva da un modello spento,
 * si torna acceso — altrimenti si mostrerebbe il vuoto.
 *
 * Nessuna dipendenza esterna.
 */
( function () {
	'use strict';

	document.documentElement.classList.add( 'dfa-js-ready' );

	document.addEventListener( 'DOMContentLoaded', function () {
		var wraps = document.querySelectorAll( '.dfa-single__specimen-wrap' );

		Array.prototype.forEach.call( wraps, function ( wrap ) {
			var specimen = wrap.querySelector( '.dfa-single__specimen' );

			if ( ! specimen ) {
				return;
			}

			var lampButton    = wrap.querySelector( '.dfa-single__lamp-btn' );
			var variantButton = wrap.querySelector( '.dfa-single__variant-btn' );

			/**
			 * Il modello attualmente in vista ha una versione spenta?
			 *
			 * @return {boolean}
			 */
			function currentHasUnlit() {
				var attribute = specimen.classList.contains( 'is-variant' )
					? 'hasVariantUnlit'
					: 'hasUnlit';

				return '1' === specimen.dataset[ attribute ];
			}

			/**
			 * Riallinea il bottone della lampada al modello in vista.
			 */
			function syncLamp() {
				if ( ! currentHasUnlit() ) {
					specimen.classList.remove( 'is-unlit' );
				}

				if ( ! lampButton ) {
					return;
				}

				var isUnlit = specimen.classList.contains( 'is-unlit' );

				lampButton.disabled = ! currentHasUnlit();
				lampButton.setAttribute( 'aria-pressed', isUnlit ? 'false' : 'true' );
				lampButton.textContent = isUnlit
					? ( lampButton.dataset.labelOn || 'ACCENDI LA LAMPADA' )
					: ( lampButton.dataset.labelOff || 'SPEGNI LA LAMPADA' );
			}

			if ( lampButton ) {
				lampButton.addEventListener( 'click', function () {
					if ( ! currentHasUnlit() ) {
						return;
					}

					specimen.classList.toggle( 'is-unlit' );
					syncLamp();
				} );
			}

			if ( variantButton ) {
				variantButton.addEventListener( 'click', function () {
					var isVariant = specimen.classList.toggle( 'is-variant' );

					variantButton.setAttribute( 'aria-pressed', isVariant ? 'true' : 'false' );
					variantButton.textContent = isVariant
						? ( variantButton.dataset.labelBase || 'MODELLO BASE' )
						: ( variantButton.dataset.labelVariant || 'VARIANTE' );

					// Il modello è cambiato: la lampada può non essere
					// più disponibile, o esserlo di nuovo.
					syncLamp();
				} );
			}

			syncLamp();
		} );
	} );
} )();
