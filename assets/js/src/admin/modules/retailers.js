/* global $, bdbVars, wp */

import { apiRequest, spinButton, unspinButton } from 'utils';

/**
 * Retailers
 */
var BDB_Retailers = {

	tableBody: false,

	rowTemplate: wp.template( 'bdb-retailers-table-row' ),

	rowEmptyTemplate: wp.template( 'bdb-retailers-table-row-empty' ),

	errorWrap: '',

	/**
	 * Initialize
	 */
	init: function() {

		this.tableBody = $( '#bdb-retailers tbody' );
		this.errorWrap = $( '#bdb-retailers-errors' );

		if ( ! this.tableBody.length ) {
			return;
		}

		$( '#bdb-new-retailer-fields' ).on( 'keydown', 'input', this.clickOnEnter );
		$( '#bdb-new-retailer-fields' ).on( 'click', 'button', this.addRetailer );
		$( document ).on( 'click', '.bdb-update-retailer', this.updateRetailer );
		$( document ).on( 'click', '.bdb-remove-retailer', this.deleteRetailer );

		this.getRetailers();

	},

	/**
	 * Get the list of retailers
	 */
	getRetailers: function() {

		apiRequest( 'v1/retailer', { number: 50 }, 'GET' ).then( function( response ) {

			BDB_Retailers.tableBody.empty();

			if ( 0 === response.length || 'undefined' === typeof response.length ) {
				BDB_Retailers.tableBody.append( BDB_Retailers.rowEmptyTemplate );
			} else {
				$( '#bdb-retailers-empty' ).remove();
				$.each( response, function( key, taxonomy ) {
					BDB_Retailers.tableBody.append( BDB_Retailers.rowTemplate( taxonomy ) );
				} );
			}

		} ).catch( function( error ) {
			BDB_Retailers.errorWrap.empty().append( error ).show();
		} );

	},

	/**
	 * Trigger a button click when pressing `enter` inside an `<input>` field.
	 *
	 * @param e
	 */
	clickOnEnter: function ( e ) {

		if ( 13 === e.keyCode ) {
			e.preventDefault();

			$( '#bdb-new-retailer-fields' ).find( 'button' ).trigger( 'click' );
		}

	},

	/**
	 * Add a new retailer
	 *
	 * @param e
	 */
	addRetailer: function ( e ) {

		e.preventDefault();

		let button = $( this );

		spinButton( button );
		BDB_Retailers.errorWrap.empty().hide();

		let args = {
			name: $( '#bdb-new-retailer-name' ).val()
		};

		BDB_Retailers.checkRequiredFields( args ).then( function( requirementsResponse ) {
			return apiRequest( 'v1/retailer/add', args, 'POST' );
		} ).then( function( apiResponse ) {
			$( '#bdb-retailers-empty' ).remove();

			BDB_Retailers.tableBody.append( BDB_Retailers.rowTemplate( apiResponse ) );

			// Wipe field values.
			$( '#bdb-newretailer-fields' ).find( 'input' ).val( '' );

			unspinButton( button );
		} ).catch( function( errorMessage ) {
			BDB_Retailers.errorWrap.append( errorMessage ).show();
			unspinButton( button );
		} );

	},

	/**
	 * Update a retailer
	 *
	 * @param e
	 */
	updateRetailer: function ( e ) {

		e.preventDefault();

		let button = $( this );

		spinButton( button );
		BDB_Retailers.errorWrap.empty().hide();

		let wrap = button.closest( 'tr' );

		let args = {
			name: wrap.find( '.bdb-retailer-name input' ).val()
		};

		BDB_Retailers.checkRequiredFields( args ).then( function( requirementsResponse ) {
			return apiRequest( 'v1/retailer/update/' + wrap.data( 'id' ), args, 'POST' )
		} ).then( function( apiResponse ) {
			unspinButton( button );
		} ).catch( function( errorMessage ) {
			BDB_Retailers.errorWrap.append( errorMessage ).show();
			unspinButton( button );
		} );

	},

	/**
	 * Delete a retailer
	 *
	 * @param e
	 * @returns {boolean}
	 */
	deleteRetailer: function ( e ) {

		e.preventDefault();

		let button = $( this ),
			unconfirmed = false;

		spinButton( button );
		BDB_Retailers.errorWrap.empty().hide();

		let wrap = button.closest( 'tr' ),
			retailerID = wrap.data( 'id' ),
			confirmMessage = bdbVars.confirm_delete_retailer;

		// @todo check if purchase links exist
		apiRequest( 'v1/book-link', { retailer_id: retailerID, number: 1 }, 'GET' ).then( function( purchaseLinks ) {

			if ( 'undefined' !== typeof purchaseLinks && 'undefined' !== typeof purchaseLinks.length && purchaseLinks.length > 0 ) {
				confirmMessage = bdbVars.confirm_delete_retailer_links;
			}

			if ( ! confirm( confirmMessage ) ) {
				unconfirmed = true;
				throw Error();
			}

			return apiRequest( 'v1/retailer/delete/' + retailerID, {}, 'DELETE' );

		} ).then( function( apiResponse ) {
			wrap.remove();
		} ).catch( function( errorMessage ) {
			if ( ! unconfirmed ) {
				BDB_Retailers.errorWrap.append( errorMessage ).show();
			}
		} ).finally( function() {
			unspinButton( button );
		} );

	},

	/**
	 * Check required fields are filled out
	 *
	 * @param {object} args
	 * @returns {Promise}
	 */
	checkRequiredFields: function( args ) {

		return new Promise( function( resolve, reject ) {

			if ( ! args.hasOwnProperty( 'name' ) || '' === args.name ) {
				reject( bdbVars.error_required_fields );

				return;
			}

			resolve();

		} );

	}

};

export { BDB_Retailers }