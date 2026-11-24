/*
 * Supe admin — small progressive enhancements for Supe admin pages.
 *
 * Enqueued by Supe\Core\Admin\AdminAssets on Supe screens only. No
 * dependencies, no build step. Everything here is driven by data-supe-*
 * attributes that Supe\Core\Support markup builders output.
 *
 *  - Editable tables (SettingsField::table()): "Add" clones the row
 *    <template>, replacing __ROW__ with a unique "new-…" key; "Remove"
 *    deletes its row. Removed rows are simply not submitted.
 */
( function () {
	'use strict';

	var counter = 0;

	function addRow( button ) {
		var field = button.closest( '[data-supe-table]' );
		var template = field && field.querySelector( 'template[data-supe-table-template]' );
		var body = field && field.querySelector( '[data-supe-table-body]' );

		if ( ! template || ! body ) {
			return;
		}

		counter += 1;

		var key = 'new-' + Date.now().toString( 36 ) + '-' + counter;

		body.insertAdjacentHTML( 'beforeend', template.innerHTML.split( '__ROW__' ).join( key ) );

		var input = body.lastElementChild && body.lastElementChild.querySelector( 'input:not([type="hidden"])' );

		if ( input ) {
			input.focus();
		}
	}

	function removeRow( button ) {
		var row = button.closest( '[data-supe-table-row]' );
		var field = button.closest( '[data-supe-table]' );

		if ( ! row ) {
			return;
		}

		row.remove();

		// Keep focus somewhere sensible for keyboard users.
		var add = field && field.querySelector( '[data-supe-table-add]' );

		if ( add ) {
			add.focus();
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var target = event.target instanceof Element ? event.target : null;

		if ( ! target ) {
			return;
		}

		var add = target.closest( '[data-supe-table-add]' );

		if ( add ) {
			event.preventDefault();
			addRow( add );
			return;
		}

		var remove = target.closest( '[data-supe-table-remove]' );

		if ( remove ) {
			event.preventDefault();
			removeRow( remove );
		}
	} );
}() );
