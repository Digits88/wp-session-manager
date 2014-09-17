<?php

class Test_WP_Session_Manager extends WP_UnitTestCase {

	public $wpdm   = null;
	public $user   = null;
	public $tokens = array();
	public $hashes = array();

	function setUp() {

		parent::setUp();

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		$this->user    = $this->factory->user->create_and_get();
		$this->manager = WP_Session_Tokens::get_instance( $this->user->ID );
		$this->wpsm    = WP_Session_Manager::init();

		$expire  = strtotime( '+1 day' );

		// Add some sessions for the user:
		for ( $i = 0; $i <= 3; $i++ ) {
			$this->tokens[$i] = $this->manager->create( $expire );
			$this->hashes[$i] = $this->manager->public_hash_token( $this->tokens[$i] );
		}

	}

	function testSingleSessionsAreDestroyed() {

		// Destroy the second session:
		$this->wpsm->destroy_single_session( $this->user, $this->hashes[1] );

		// Test the session destruction:
		$this->assertEquals( array(
			$this->hashes[0],
			$this->hashes[2],
			$this->hashes[3],
		), array_keys( $this->manager->get_all_keyed() ) );

		// Destroy the third session:
		$this->wpsm->destroy_single_session( $this->user, $this->hashes[2] );

		// Test the session destruction:
		$this->assertEquals( array(
			$this->hashes[0],
			$this->hashes[3],
		), array_keys( $this->manager->get_all_keyed() ) );

	}

	function testMultipleSessionsAreDestroyed() {

		// Destroy all but the second session:
		$this->wpsm->destroy_multiple_sessions( $this->user, $this->hashes[1] );

		// Test the session destruction:
		$this->assertEquals( array(
			$this->hashes[1],
		), array_keys( $this->manager->get_all_keyed() ) );

	}

	function testAllSessionsAreDestroyed() {

		// Destroy all sessions:
		$this->wpsm->destroy_multiple_sessions( $this->user );

		// Test the session destruction:
		$this->assertEquals( array(), array_keys( $this->manager->get_all_keyed() ) );

	}

}
