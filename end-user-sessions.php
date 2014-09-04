<?php
/**
 * Plugin Name: End User Sessions
 * Author: Drew Jaynes
 * Description: Adds controls to a user's profile screen for managing their logged-in sessions.
 * Version: 1.0
 * License: GPLv2
 */

function setup_sessions( $user ) {
	$session = WW_User_Sessions::get_instance( $user->ID );

	?>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><?php _e( 'Login Activity', 'eus' ); ?></th>
				<td>
					<?php
					$count = count( $session->get_sessions() );
					if ( $count > 1 ) {
						echo 'You&#8217;re logged-in to ' . sprintf( translate_nooped_plural( _n_noop( '%s other location:', '%s other locations:', 'eus' ), $count, 'eus' ), number_format_i18n( $count ) );
						?>
						<style type="text/css">
							.sessions-table {
								margin-top: 20px;
							}
							.sessions-table,
							.sessions-table td,
							.sessions-table th {
								border: 1px solid #222;
							}
							.sessions-table td,
							.sessions-table th {
								padding: 2px;
							}
						</style>
						<table class="sessions-table" style="width:400px;">
							<tbody>
								<tr>
									<th><?php _e( 'Access Type', 'eus' ); ?></th>
									<th><?php _e( 'Location', 'eus' ); ?></th>
								</tr>
								<?php foreach ( $session->get_sessions() as $session ) :
									$browser = get_browser( $session['user-agent'], true );
									var_dump( $browser );
									?>
									<tr>
										<td><?php echo $browser['parent']; ?></td>
										<td><?php echo $session['ip-address']; ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<?php
					} else {
						echo 'Something';
					}
					?>
					<br />
					<a href="" class="button button-secondary"><?php _e( 'Sign Out of All Other Sessions', 'eus' ); ?></a>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}
add_action( 'profile_personal_options', 'setup_sessions' );

function ww_filter_sessions_class( $class ) {
	return 'WW_User_Sessions';
}
add_filter( 'session_token_manager', 'ww_filter_sessions_class' );

function ww_filter_session_information( $info ) {
	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$info['ip-address'] = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$info['ip-address'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$info['ip-address'] = $_SERVER['REMOTE_ADDR'];
	}

	if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		$info['user-agent'] = $_SERVER['HTTP_USER_AGENT'];
	}
	return $info;
}
add_filter( 'attach_session_information', 'ww_filter_session_information' );

/**
 * Class WW_End_User_Sessions
 *
 * @since 1.0
 */
class WW_User_Sessions extends WP_Session_Tokens {

	public function get_sessions() {
		$sessions = get_user_meta( $this->user_id, 'session_tokens', true );

		if ( ! is_array( $sessions ) ) {
			return array();
		}

		$sessions = array_map( array( $this, 'prepare_session' ), $sessions );
		return array_filter( $sessions, array( $this, 'is_still_valid' ) );
	}

	/**
	 * Converts an expiration to an array of session information.
	 *
	 * @param mixed $session Session or expiration.
	 * @return array Session.
	 */
	protected function prepare_session( $session ) {
		if ( is_int( $session ) ) {
			return array( 'expiration' => $session );
		}

		return $session;
	}

	/**
	 * Retrieve a session by its verifier (token hash).
	 *
	 * @since 4.0.0
	 * @access protected
	 *
	 * @param string $verifier Verifier of the session to retrieve.
	 * @return array|null The session, or null if it does not exist
	 */
	protected function get_session( $verifier ) {
		$sessions = $this->get_sessions();

		if ( isset( $sessions[ $verifier ] ) ) {
			return $sessions[ $verifier ];
		}

		return null;
	}

	/**
	 * Update a session by its verifier.
	 *
	 * @since 4.0.0
	 * @access protected
	 *
	 * @param string $verifier Verifier of the session to update.
	 * @param array  $session  Optional. Session. Omitting this argument destroys the session.
	 */
	protected function update_session( $verifier, $session = null ) {
		$sessions = $this->get_sessions();

		if ( $session ) {
			$sessions[ $verifier ] = $session;
		} else {
			unset( $sessions[ $verifier ] );
		}

		$this->update_sessions( $sessions );
	}

	/**
	 * Update a user's sessions in the usermeta table.
	 *
	 * @since 4.0.0
	 * @access protected
	 *
	 * @param array $sessions Sessions.
	 */
	protected function update_sessions( $sessions ) {
		if ( ! has_filter( 'attach_session_information' ) ) {
			$sessions = wp_list_pluck( $sessions, 'expiration' );
		}

		if ( $sessions ) {
			update_user_meta( $this->user_id, 'session_tokens', $sessions );
		} else {
			delete_user_meta( $this->user_id, 'session_tokens' );
		}
	}

	/**
	 * Destroy all session tokens for a user, except a single session passed.
	 *
	 * @since 4.0.0
	 * @access protected
	 *
	 * @param string $verifier Verifier of the session to keep.
	 */
	protected function destroy_other_sessions( $verifier ) {
		$session = $this->get_session( $verifier );
		$this->update_sessions( array( $verifier => $session ) );
	}

	/**
	 * Destroy all session tokens for a user.
	 *
	 * @since 4.0.0
	 * @access protected
	 */
	protected function destroy_all_sessions() {
		$this->update_sessions( array() );
	}

	/**
	 * Destroy all session tokens for all users.
	 *
	 * @since 4.0.0
	 * @access public
	 * @static
	 */
	public static function drop_sessions() {
		delete_metadata( 'user', false, 'session_tokens', false, true );
	}
}
