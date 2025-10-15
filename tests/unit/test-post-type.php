<?php
/**
 * Tests for PostType class.
 *
 * @package KnownIssues
 */

namespace KnownIssues\Tests;

use KnownIssues\PostType;
use WP_UnitTestCase;

/**
 * Class Test_PostType
 *
 * Tests the custom post type registration.
 */
class Test_PostType extends WP_UnitTestCase {
	/**
	 * Test that the post type is registered.
	 *
	 * @return void
	 */
	public function test_post_type_is_registered() {
		$this->assertTrue( post_type_exists( PostType::POST_TYPE ) );
	}

	/**
	 * Test post type supports the expected features.
	 *
	 * @return void
	 */
	public function test_post_type_supports() {
		$this->assertTrue( post_type_supports( PostType::POST_TYPE, 'title' ) );
		$this->assertTrue( post_type_supports( PostType::POST_TYPE, 'editor' ) );
		$this->assertTrue( post_type_supports( PostType::POST_TYPE, 'author' ) );
		$this->assertTrue( post_type_supports( PostType::POST_TYPE, 'revisions' ) );
		$this->assertTrue( post_type_supports( PostType::POST_TYPE, 'custom-fields' ) );
	}

	/**
	 * Test post type is public and queryable.
	 *
	 * @return void
	 */
	public function test_post_type_is_public() {
		$post_type_object = get_post_type_object( PostType::POST_TYPE );

		$this->assertTrue( $post_type_object->public );
		$this->assertTrue( $post_type_object->publicly_queryable );
		$this->assertTrue( $post_type_object->show_in_rest );
	}
}
