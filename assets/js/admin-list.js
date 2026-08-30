/**
 * Lista admin degli esemplari.
 *
 * 1. Spunte "Pubblicato" e "Coming soon" nelle rispettive colonne:
 *    cambiano lo stato con un clic, via admin-ajax, senza ricaricare la
 *    pagina. Le due condividono markup e richiesta; è il parametro
 *    "flag" a dire al server quale delle due si sta cambiando.
 * 2. Stesse spunte nelle Modifiche rapide: WordPress non pre-compila i
 *    campi custom del quick edit, quindi il valore va letto dalla riga.
 *    "Pubblicato" pilota il menu "Stato" nativo del form (a salvare
 *    resta il core); "Coming soon" è un campo nostro e viene inviato
 *    con il form.
 */
( function ( $ ) {
	'use strict';

	if ( typeof window.dfaList === 'undefined' ) {
		return;
	}

	var settings = window.dfaList;

	/**
	 * Etichetta da mostrare accanto a una spunta.
	 *
	 * @param {string}  flag  Nome del flag.
	 * @param {boolean} value Stato.
	 * @return {string}
	 */
	function labelFor( flag, value ) {
		var labels = settings.labels && settings.labels[ flag ];

		if ( ! labels ) {
			return '';
		}

		return value ? labels.on : labels.off;
	}

	/**
	 * Allinea la riga al nuovo stato: etichetta della cella e — per
	 * "Pubblicato" — i dati nascosti che WordPress usa per riempire le
	 * Modifiche rapide (senza questi ultimi il menu "Stato" mostrerebbe
	 * il valore vecchio finché non si ricarica la pagina).
	 *
	 * @param {jQuery}  $cell  Contenitore .dfa-flag della riga.
	 * @param {number}  postId ID del post.
	 * @param {string}  flag   Nome del flag.
	 * @param {boolean} value  Nuovo stato.
	 */
	function updateRow( $cell, postId, flag, value ) {
		$cell.attr( 'data-value', value ? '1' : '0' );
		$cell.find( '.dfa-flag__label' ).text( labelFor( flag, value ) );

		if ( flag === settings.flagPublished ) {
			$( '#inline_' + postId + ' ._status' ).text( value ? 'publish' : 'draft' );
		}
	}

	// --- 1. Spunte nelle colonne ------------------------------------

	$( document ).on( 'change', '.dfa-flag__input', function () {
		var $input = $( this );
		var $cell  = $input.closest( '.dfa-flag' );
		var postId = $input.data( 'post-id' );
		var flag   = $input.data( 'flag' );
		var value  = $input.is( ':checked' );

		$input.prop( 'disabled', true );
		$cell.addClass( 'is-busy' );

		$.post( settings.ajaxUrl, {
			action: settings.action,
			nonce: settings.nonce,
			post_id: postId,
			flag: flag,
			value: value ? '1' : '0'
		} ).done( function ( response ) {
			if ( response && response.success ) {
				updateRow( $cell, postId, flag, response.data.value );
				return;
			}

			// Richiesta rifiutata dal server: si rimette la spunta com'era.
			$input.prop( 'checked', ! value );
			window.alert(
				response && response.data && response.data.message
					? response.data.message
					: settings.errorMessage
			);
		} ).fail( function () {
			$input.prop( 'checked', ! value );
			window.alert( settings.errorMessage );
		} ).always( function () {
			$input.prop( 'disabled', false );
			$cell.removeClass( 'is-busy' );
		} );
	} );

	// --- 2. Spunte nelle Modifiche rapide ---------------------------

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

		var $row  = $( '#post-' + postId );
		var $form = $( '#edit-' + postId );

		$form.find( '.dfa-quick-flag' ).each( function () {
			var $label    = $( this );
			var flag      = $label.attr( 'data-flag' );
			var $checkbox = $label.find( '.dfa-quick-flag__input' );

			$checkbox.prop(
				'checked',
				'1' === $row.find( '.dfa-flag[data-flag="' + flag + '"]' ).attr( 'data-value' )
			);

			if ( flag !== settings.flagPublished ) {
				return;
			}

			// "Pubblicato" scrive nel menu Stato: un solo valore va al
			// salvataggio, e i due controlli non possono contraddirsi.
			var $status = $form.find( 'select[name="_status"]' );

			$checkbox.off( 'change.dfa' ).on( 'change.dfa', function () {
				$status.val( $( this ).is( ':checked' ) ? 'publish' : 'draft' );
			} );

			$status.off( 'change.dfa' ).on( 'change.dfa', function () {
				$checkbox.prop( 'checked', 'publish' === $( this ).val() );
			} );
		} );

		return result;
	};
} )( jQuery );
