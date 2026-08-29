/**
 * Lista admin degli esemplari.
 *
 * 1. Spunta "Pubblicato" nella colonna: pubblica/spubblica con un clic,
 *    via admin-ajax, senza ricaricare la pagina.
 * 2. Spunta "Pubblicato" nelle Modifiche rapide: WordPress non
 *    pre-compila i campi custom del quick edit, quindi il valore va
 *    letto dalla riga. La spunta pilota il menu "Stato" nativo del
 *    form, così a salvare resta il core.
 */
( function ( $ ) {
	'use strict';

	if ( typeof window.dfaList === 'undefined' ) {
		return;
	}

	var settings = window.dfaList;

	/**
	 * Allinea la riga al nuovo stato: etichetta della cella e dati
	 * nascosti che WordPress usa per riempire le Modifiche rapide
	 * (senza questi ultimi il menu "Stato" mostrerebbe il valore vecchio
	 * finché non si ricarica la pagina).
	 *
	 * @param {jQuery}  $cell     Contenitore .dfa-published della riga.
	 * @param {number}  postId    ID del post.
	 * @param {boolean} published Nuovo stato.
	 */
	function updateRow( $cell, postId, published ) {
		$cell.attr( 'data-published', published ? '1' : '0' );
		$cell.find( '.dfa-published__label' ).text(
			published ? settings.labelPublished : settings.labelUnpublished
		);
		$( '#inline_' + postId + ' ._status' ).text( published ? 'publish' : 'draft' );
	}

	// --- 1. Spunta nella colonna ------------------------------------

	$( document ).on( 'change', '.dfa-published__input', function () {
		var $input    = $( this );
		var $cell     = $input.closest( '.dfa-published' );
		var postId    = $input.data( 'post-id' );
		var published = $input.is( ':checked' );

		$input.prop( 'disabled', true );
		$cell.addClass( 'is-busy' );

		$.post( settings.ajaxUrl, {
			action: settings.action,
			nonce: settings.nonce,
			post_id: postId,
			publish: published ? '1' : '0'
		} ).done( function ( response ) {
			if ( response && response.success ) {
				updateRow( $cell, postId, response.data.published );
				return;
			}

			// Richiesta rifiutata dal server: si rimette la spunta com'era.
			$input.prop( 'checked', ! published );
			window.alert(
				response && response.data && response.data.message
					? response.data.message
					: settings.errorMessage
			);
		} ).fail( function () {
			$input.prop( 'checked', ! published );
			window.alert( settings.errorMessage );
		} ).always( function () {
			$input.prop( 'disabled', false );
			$cell.removeClass( 'is-busy' );
		} );
	} );

	// --- 2. Spunta nelle Modifiche rapide ---------------------------

	if ( typeof window.inlineEditPost === 'undefined' ) {
		return;
	}

	var originalEdit = window.inlineEditPost.edit;

	window.inlineEditPost.edit = function ( id ) {
		var result = originalEdit.apply( this, arguments );

		var postId = typeof id === 'object' ? parseInt( this.getId( id ), 10 ) : id;
		if ( ! postId ) {
			return result;
		}

		var $row      = $( '#post-' + postId );
		var $form     = $( '#edit-' + postId );
		var $checkbox = $form.find( '.dfa-quick-published__input' );
		var $status   = $form.find( 'select[name="_status"]' );

		if ( ! $checkbox.length ) {
			return result;
		}

		$checkbox.prop( 'checked', '1' === $row.find( '.dfa-published' ).attr( 'data-published' ) );

		// La spunta scrive nel menu Stato: un solo valore va al salvataggio.
		$checkbox.off( 'change.dfa' ).on( 'change.dfa', function () {
			$status.val( $( this ).is( ':checked' ) ? 'publish' : 'draft' );
		} );

		// E viceversa, se si usa il menu Stato la spunta lo segue.
		$status.off( 'change.dfa' ).on( 'change.dfa', function () {
			$checkbox.prop( 'checked', 'publish' === $( this ).val() );
		} );

		return result;
	};
} )( jQuery );
