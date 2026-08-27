/**
 * Devil Fruit Archive — media uploader nativo di WordPress per i campi
 * immagine "Proprietario attuale" / "Ex proprietario" nel meta box.
 *
 * Ogni campo immagine è un blocco .dfa-image-field con:
 * - un input hidden #<id> che contiene l'attachment ID salvato,
 * - un contenitore .dfa-image-field__preview per l'anteprima,
 * - un bottone .dfa-image-field__select e uno .dfa-image-field__remove.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		$( '.dfa-image-field' ).each( function () {
			var $field   = $( this );
			var $input   = $field.find( 'input[type="hidden"]' );
			var $preview = $field.find( '.dfa-image-field__preview' );
			var $select  = $field.find( '.dfa-image-field__select' );
			var $remove  = $field.find( '.dfa-image-field__remove' );
			var frame;

			$select.on( 'click', function ( e ) {
				e.preventDefault();

				if ( frame ) {
					frame.open();
					return;
				}

				frame = wp.media( {
					title: ( window.dfaMetabox && dfaMetabox.mediaTitle ) || 'Seleziona un\'immagine',
					button: { text: ( window.dfaMetabox && dfaMetabox.mediaButton ) || 'Usa questa immagine' },
					library: { type: 'image' },
					multiple: false
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					$input.val( attachment.id );

					var thumbUrl = ( attachment.sizes && attachment.sizes.thumbnail )
						? attachment.sizes.thumbnail.url
						: attachment.url;

					$preview.html( '<img src="' + thumbUrl + '" alt="">' );
					$remove.show();
				} );

				frame.open();
			} );

			$remove.on( 'click', function ( e ) {
				e.preventDefault();
				$input.val( '' );
				$preview.html( '' );
				$remove.hide();
			} );
		} );
	} );
} )( jQuery );
