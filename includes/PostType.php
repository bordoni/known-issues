<?php
/**
 * Custom Post Type registration.
 *
 * @package KnownIssues
 */

namespace KnownIssues;

/**
 * Class PostType
 *
 * Registers the known-issues custom post type.
 */
class PostType {
	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	const POST_TYPE = 'known-issues';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Register the custom post type.
	 *
	 * @return void
	 */
	public function register() {
		$labels = [
			'name'                     => _x( 'Known Issues', 'post type general name', 'known-issues' ),
			'singular_name'            => _x( 'Known Issue', 'post type singular name', 'known-issues' ),
			'menu_name'                => _x( 'Known Issues', 'admin menu', 'known-issues' ),
			'name_admin_bar'           => _x( 'Known Issue', 'add new on admin bar', 'known-issues' ),
			'add_new'                  => _x( 'Add New', 'known issue', 'known-issues' ),
			'add_new_item'             => __( 'Add New Known Issue', 'known-issues' ),
			'new_item'                 => __( 'New Known Issue', 'known-issues' ),
			'edit_item'                => __( 'Edit Known Issue', 'known-issues' ),
			'view_item'                => __( 'View Known Issue', 'known-issues' ),
			'all_items'                => __( 'All Known Issues', 'known-issues' ),
			'search_items'             => __( 'Search Known Issues', 'known-issues' ),
			'parent_item_colon'        => __( 'Parent Known Issues:', 'known-issues' ),
			'not_found'                => __( 'No known issues found.', 'known-issues' ),
			'not_found_in_trash'       => __( 'No known issues found in Trash.', 'known-issues' ),
			'featured_image'           => _x( 'Featured Image', 'known issue', 'known-issues' ),
			'set_featured_image'       => _x( 'Set featured image', 'known issue', 'known-issues' ),
			'remove_featured_image'    => _x( 'Remove featured image', 'known issue', 'known-issues' ),
			'use_featured_image'       => _x( 'Use as featured image', 'known issue', 'known-issues' ),
			'archives'                 => _x( 'Known Issue archives', 'known issue', 'known-issues' ),
			'insert_into_item'         => _x( 'Insert into known issue', 'known issue', 'known-issues' ),
			'uploaded_to_this_item'    => _x( 'Uploaded to this known issue', 'known issue', 'known-issues' ),
			'filter_items_list'        => _x( 'Filter known issues list', 'known issue', 'known-issues' ),
			'items_list_navigation'    => _x( 'Known Issues list navigation', 'known issue', 'known-issues' ),
			'items_list'               => _x( 'Known Issues list', 'known issue', 'known-issues' ),
			'item_published'           => __( 'Known Issue published.', 'known-issues' ),
			'item_published_privately' => __( 'Known Issue published privately.', 'known-issues' ),
			'item_reverted_to_draft'   => __( 'Known Issue reverted to draft.', 'known-issues' ),
			'item_scheduled'           => __( 'Known Issue scheduled.', 'known-issues' ),
			'item_updated'             => __( 'Known Issue updated.', 'known-issues' ),
		];

		$args = [
			'labels'             => $labels,
			'description'        => __( 'Known issues synced from Jira', 'known-issues' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => [ 'slug' => 'known-issues' ],
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 25,
			'menu_icon'          => 'dashicons-warning',
			'supports'           => [ 'title', 'editor', 'author', 'revisions', 'custom-fields' ],
			'template'           => [
				[
					'core/paragraph',
					[
						'placeholder' => __( 'Describe the issue and any workarounds...', 'known-issues' ),
					],
				],
				[ 'known-issues/affected-users', [] ],
			],
			'template_lock'      => false,
		];

		register_post_type( self::POST_TYPE, $args );
	}
}
