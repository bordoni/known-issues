<?php
/**
 * Integration tests for Affected Users functionality.
 *
 * @package KnownIssues
 */

namespace KnownIssues\Tests\Integration;

use WP_UnitTestCase;
use WP_REST_Request;

/**
 * Class Test_Affected_Users
 *
 * Tests the affected users signup and unsubscribe flow.
 */
class Test_Affected_Users extends WP_UnitTestCase {
	/**
	 * Test issue post ID.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a test known issue.
		$this->post_id = $this->factory->post->create(
			[
				'post_type'   => 'known-issues',
				'post_title'  => 'Test Issue',
				'post_status' => 'publish',
			]
		);

		// Create a test user.
		$this->user_id = $this->factory->user->create(
			[
				'role' => 'subscriber',
			]
		);
	}

	/**
	 * Test user can sign up as affected.
	 *
	 * @return void
	 */
	public function test_user_signup_creates_comment() {
		wp_set_current_user( $this->user_id );

		$request = new WP_REST_Request( 'POST', '/known-issues/v1/affected-users' );
		$request->set_param( 'post_id', $this->post_id );

		$response = rest_do_request( $request );

		$this->assertEquals( 201, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );

		// Verify comment was created.
		$comments = get_comments(
			[
				'post_id' => $this->post_id,
				'type'    => 'ki_affected_user',
			]
		);

		$this->assertCount( 1, $comments );
	}

	/**
	 * Test affected user count is accurate.
	 *
	 * @return void
	 */
	public function test_affected_user_count() {
		// Initially should be zero.
		$count = \KnownIssues\get_affected_user_count( $this->post_id );
		$this->assertEquals( 0, $count );

		// Create affected user comment.
		wp_insert_comment(
			[
				'comment_post_ID' => $this->post_id,
				'user_id'         => $this->user_id,
				'comment_type'    => 'ki_affected_user',
				'comment_approved' => 'pending',
			]
		);

		// Should now be one.
		$count = \KnownIssues\get_affected_user_count( $this->post_id );
		$this->assertEquals( 1, $count );
	}

	/**
	 * Test duplicate signup is prevented.
	 *
	 * @return void
	 */
	public function test_duplicate_signup_prevented() {
		wp_set_current_user( $this->user_id );

		// First signup.
		$request = new WP_REST_Request( 'POST', '/known-issues/v1/affected-users' );
		$request->set_param( 'post_id', $this->post_id );
		$response = rest_do_request( $request );

		$this->assertEquals( 201, $response->get_status() );

		// Second signup attempt.
		$request = new WP_REST_Request( 'POST', '/known-issues/v1/affected-users' );
		$request->set_param( 'post_id', $this->post_id );
		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertStringContainsString( 'already signed up', $response->get_data()['message'] );
	}
}
