/* global $, bdbVars, wp */

/**
 * Autocomplete for tags
 */
var BDB_Tags = {

	tag: false,

	/**
	 * Initialize
	 */
	init: function() {

		this.tag = $( '.bdb-ajaxtag' );

		if ( ! this.tag.length ) {
			return;
		}

		$( '.bdb-tags-wrap' ).each( function() {
			BDB_Tags.quickClicks( $( this ) );
		} );

		$( '.button', BDB_Tags.tag ).on( 'click', function() {
			BDB_Tags.flushTags( $( this ).closest( '.bdb-tags-wrap' ) );
		} );

		BDB_Tags.tag.each( function() {
			let newTag = $( '.bdb-new-tag', $( this ) );
			let taxonomy = $( this ).closest( '.bdb-tags-wrap' ).data( 'taxonomy' );
			let apiURL = bdbVars.api_base + 'book-database/v1/book-term/suggest/?taxonomy=' + taxonomy + '&format=text&_wpnonce=' + bdbVars.api_nonce;

			if ( 'author' === taxonomy ) {
				apiURL = bdbVars.api_base + 'book-database/v1/author/suggest/?format=text&_wpnonce=' + bdbVars.api_nonce;
			}

			newTag.on( 'keyup', function( e ) {
				if ( 13 === e.which ) {
					BDB_Tags.flushTags( $( this ).closest( '.bdb-tags-wrap' ) );

					return false;
				}
			} ).on( 'keypress', function( e ) {
				if ( 13 === e.which ) {
					e.preventDefault();

					return false;
				}
			} ).suggest( apiURL );
		} );

		$( '#bdb-book-series-name' ).suggest( bdbVars.api_base + 'book-database/v1/series/suggest/?format=text&_wpnonce=' + bdbVars.api_nonce );

		// Save tags on save/publish
		$( '.bdb-admin-page > form' ).on( 'submit', function( e ) {
			$( '.bdb-tags-wrap' ).each( function() {
				BDB_Tags.flushTags( this, false, 1 );
			} );
		} );

	},

	/**
	 * Clean tags
	 *
	 * @param tags
	 */
	clean: function ( tags ) {
		return tags.replace( /\s*,\s*/g, ',' ).replace( /,+/g, ',' ).replace( /[,\s]+$/, '' ).replace( /^[,\s]+/, '' );
	},

	/**
	 * Parse tags
	 *
	 * @param el
	 */
	parseTags: function ( el ) {

		let id = el.id;
		let num = id.split( '-check-num-' )[ 1 ];
		let tagBox = $( el ).closest( '.bdb-tags-wrap' );
		let theTags = tagBox.find( 'textarea' );
		let currentTags = theTags.val().split( ',' );
		let newTags = [];

		delete currentTags[ num ];

		$.each( currentTags, function ( key, val ) {
			val = $.trim( val );

			if ( val ) {
				newTags.push( val );
			}
		} );

		theTags.val( BDB_Tags.clean( newTags.join( ',' ) ) );

		BDB_Tags.quickClicks( tagBox );

		return false;

	},

	/**
	 * Handles adding tags
	 *
	 * @param el
	 */
	quickClicks: function ( el ) {

		let theTags = $( 'textarea', el );
		let tagChecklist = $( '.bdb-tags-checklist', el );
		let id = $( el ).attr( 'id' );
		let currentTags;
		let disabled;

		if ( ! theTags.length ) {
			return;
		}

		disabled = theTags.prop( 'disabled' );
		currentTags = theTags.val().split( ',' );
		tagChecklist.empty();

		$.each( currentTags, function( key, val ) {
			let span, xbutton;

			val = $.trim( val );

			if ( ! val ) {
				return;
			}

			// Create a new span and ensure the text is properly escaped.
			span = $( '<span />' ).text( val );

			// If tags editing isn't disabled, create the X button.
			if ( ! disabled ) {
				xbutton = $( '<a id="' + id + '-check-num-' + key + '" class="ntdelbutton">X</a>' );
				xbutton.on( 'click', function( e ) {
					BDB_Tags.parseTags( this );
				} );
				span.prepend( '&nbsp;' ).prepend( xbutton );
			}

			// Append the span to the tag list.
			tagChecklist.append( span );
		} );

	},

	/**
	 * Flush tags on add tag and save
	 *
	 * @param el
	 * @param a
	 * @param f
	 */
	flushTags: function ( el, a, f ) {

		a = a || false;

		let text;
		let tags = $( 'textarea', el );
		let newTag = $( '.bdb-new-tag', el );
		let tagsVal, newTags;

		text = a ? ( a ).text() : newTag.val();

		tagsVal = tags.val();
		newTags = tagsVal ? tagsVal + ',' + text : text;

		newTags = BDB_Tags.clean( newTags );
		newTags = BDB_Tags.uniqueArray( newTags.split( ',' ) ).join( ',' );

		tags.val( newTags );
		BDB_Tags.quickClicks( el );

		if ( ! a ) {
			newTag.val( '' );
		}

		if ( 'undefined' === typeof( f ) ) {
			newTag.focus();
		}

		return false;

	},

	/**
	 * Create a unique array with no empty values
	 *
	 * @param {array} array
	 *
	 * @returns {array}
	 */
	uniqueArray: function ( array ) {

		let out = [];

		$.each( array, function( key, val ) {
			val = $.trim( val );

			if ( val && -1 === $.inArray( val, out ) ) {
				out.push( val );
			}
		} );

		return out;

	}

};

export { BDB_Tags }