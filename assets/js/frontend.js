/**
 * Frontend JavaScript for Known Issues plugin.
 */

( function() {
	'use strict';

	/**
	 * Handle signup button click.
	 *
	 * @param {Event} e Click event.
	 */
	function handleSignup( e ) {
		e.preventDefault();

		const button = e.target;
		const postId = button.dataset.postId;

		if ( ! postId ) {
			return;
		}

		// Disable button.
		button.disabled = true;
		button.textContent = knownIssuesData.signingUp || 'Signing up...';

		// Make API request.
		fetch( `${knownIssuesData.restUrl}/affected-users`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': knownIssuesData.nonce,
			},
			body: JSON.stringify( {
				post_id: parseInt( postId, 10 ),
			} ),
		} )
			.then( ( response ) => response.json() )
			.then( ( data ) => {
				if ( data.success ) {
					// Reload page to show updated state.
					window.location.reload();
				} else {
					throw new Error( data.message || 'Signup failed' );
				}
			} )
			.catch( ( error ) => {
				// Re-enable button.
				button.disabled = false;
				button.textContent = button.dataset.originalText || "I'm affected by this issue";

				// Show error.
				// eslint-disable-next-line no-alert
				alert( error.message || 'Failed to sign up. Please try again.' );
			} );
	}

	/**
	 * Handle unsubscribe button click.
	 *
	 * @param {Event} e Click event.
	 */
	function handleUnsubscribe( e ) {
		e.preventDefault();

		const button = e.target;
		const commentId = button.dataset.commentId;

		if ( ! commentId ) {
			return;
		}

		// Confirm unsubscribe.
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( knownIssuesData.confirmUnsubscribe || 'Are you sure you want to unsubscribe?' ) ) {
			return;
		}

		// Disable button.
		button.disabled = true;
		button.textContent = knownIssuesData.unsubscribing || 'Unsubscribing...';

		// Make API request.
		fetch( `${knownIssuesData.restUrl}/affected-users/${commentId}`, {
			method: 'DELETE',
			headers: {
				'X-WP-Nonce': knownIssuesData.nonce,
			},
		} )
			.then( ( response ) => response.json() )
			.then( ( data ) => {
				if ( data.success ) {
					// Reload page to show updated state.
					window.location.reload();
				} else {
					throw new Error( data.message || 'Unsubscribe failed' );
				}
			} )
			.catch( ( error ) => {
				// Re-enable button.
				button.disabled = false;
				button.textContent = button.dataset.originalText || 'Unsubscribe';

				// Show error.
				// eslint-disable-next-line no-alert
				alert( error.message || 'Failed to unsubscribe. Please try again.' );
			} );
	}

	/**
	 * Initialize event listeners.
	 */
	function init() {
		// Signup buttons.
		const signupButtons = document.querySelectorAll( '.known-issues-affected-users__button' );
		signupButtons.forEach( ( button ) => {
			button.dataset.originalText = button.textContent;
			button.addEventListener( 'click', handleSignup );
		} );

		// Unsubscribe buttons.
		const unsubscribeButtons = document.querySelectorAll( '.known-issues-affected-users__unsubscribe-button' );
		unsubscribeButtons.forEach( ( button ) => {
			button.dataset.originalText = button.textContent;
			button.addEventListener( 'click', handleUnsubscribe );
		} );
	}

	// Initialize when DOM is ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
