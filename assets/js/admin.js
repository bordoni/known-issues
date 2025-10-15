/**
 * Admin JavaScript for Known Issues plugin.
 */

(function($) {
	'use strict';

	/**
	 * Affected Users Modal
	 */
	const AffectedUsersModal = {
		/**
		 * Initialize the modal.
		 */
		init: function() {
			// Handle affected count button clicks.
			$(document).on('click', '.ki-affected-count', this.openModal.bind(this));

			// Handle modal close.
			$(document).on('click', '.ki-modal-overlay, .ki-modal-close', this.closeModal.bind(this));

			// Prevent modal content clicks from closing.
			$(document).on('click', '.ki-modal', function(e) {
				e.stopPropagation();
			});

			// ESC key to close.
			$(document).on('keydown', function(e) {
				if (e.key === 'Escape' && $('.ki-modal-overlay').length) {
					AffectedUsersModal.closeModal();
				}
			});
		},

		/**
		 * Open the modal and load affected users.
		 *
		 * @param {Event} e Click event.
		 */
		openModal: function(e) {
			e.preventDefault();

			const button = $(e.currentTarget);
			const postId = button.data('post-id');

			if (!postId) {
				return;
			}

			// Create modal.
			const modal = this.createModal(postId);
			$('body').append(modal);

			// Load data.
			this.loadAffectedUsers(postId);
		},

		/**
		 * Close the modal.
		 */
		closeModal: function() {
			$('.ki-modal-overlay').fadeOut(200, function() {
				$(this).remove();
			});
		},

		/**
		 * Create modal HTML.
		 *
		 * @param {number} postId Post ID.
		 * @return {jQuery} Modal element.
		 */
		createModal: function(postId) {
			return $(`
				<div class="ki-modal-overlay">
					<div class="ki-modal">
						<div class="ki-modal-header">
							<h2 class="ki-modal-title">Affected Users</h2>
							<button type="button" class="ki-modal-close" aria-label="Close">
								<span class="dashicons dashicons-no-alt"></span>
							</button>
						</div>
						<div class="ki-modal-body">
							<div class="ki-modal-loading">
								<span class="spinner is-active"></span>
								<p>Loading affected users...</p>
							</div>
						</div>
					</div>
				</div>
			`);
		},

		/**
		 * Load affected users via AJAX.
		 *
		 * @param {number} postId Post ID.
		 */
		loadAffectedUsers: function(postId) {
			$.ajax({
				url: kiAdmin.restUrl + '/affected-users/list/' + postId,
				type: 'GET',
				headers: {
					'X-WP-Nonce': kiAdmin.nonce
				},
				success: function(response) {
					if (response.users && response.users.length > 0) {
						AffectedUsersModal.renderUsers(response.users);
					} else {
						AffectedUsersModal.renderEmpty();
					}
				},
				error: function() {
					AffectedUsersModal.renderError();
				}
			});
		},

		/**
		 * Render users table.
		 *
		 * @param {Array} users List of affected users.
		 */
		renderUsers: function(users) {
			let html = '<table class="ki-affected-users-table">';
			html += '<thead><tr>';
			html += '<th>User</th>';
			html += '<th>Email</th>';
			html += '<th>Signed Up</th>';
			html += '<th>Status</th>';
			html += '</tr></thead>';
			html += '<tbody>';

			users.forEach(function(user) {
				const status = user.helpscout_status || 'pending';
				const statusClass = 'ki-status-badge--' + status.replace('_', '-');
				const statusLabel = status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

				html += '<tr>';
				html += '<td>' + escapeHtml(user.name) + '</td>';
				html += '<td>' + escapeHtml(user.email) + '</td>';
				html += '<td>' + (user.signup_date ? formatDate(user.signup_date) : '—') + '</td>';
				html += '<td><span class="ki-status-badge ' + statusClass + '">' + statusLabel + '</span></td>';
				html += '</tr>';
			});

			html += '</tbody></table>';

			$('.ki-modal-body').html(html);
		},

		/**
		 * Render empty state.
		 */
		renderEmpty: function() {
			const html = `
				<div class="ki-empty-state">
					<span class="dashicons dashicons-groups"></span>
					<p>No affected users yet.</p>
				</div>
			`;
			$('.ki-modal-body').html(html);
		},

		/**
		 * Render error state.
		 */
		renderError: function() {
			const html = `
				<div class="ki-empty-state">
					<span class="dashicons dashicons-warning"></span>
					<p>Failed to load affected users. Please try again.</p>
				</div>
			`;
			$('.ki-modal-body').html(html);
		}
	};

	/**
	 * Escape HTML.
	 *
	 * @param {string} text Text to escape.
	 * @return {string} Escaped text.
	 */
	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	/**
	 * Format date.
	 *
	 * @param {string} dateString Date string.
	 * @return {string} Formatted date.
	 */
	function formatDate(dateString) {
		const date = new Date(dateString);
		return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
	}

	/**
	 * Initialize on document ready.
	 */
	$(document).ready(function() {
		AffectedUsersModal.init();
	});

})(jQuery);
