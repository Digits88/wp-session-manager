<?php
/**
 * Plugin Name: WP Session Manager
 * Author: Drew Jaynes & John Blackbourn
 * Description: Adds controls to a user's profile screen for managing their logged-in sessions.
 * Version: 1.0
 * License: GPLv2
 */

function setup_sessions( $user ) {
	$session = WP_Session_Tokens::get_instance( $user->ID );

	?>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><?php _e( 'Login Activity', 'eus' ); ?></th>
				<td>
					<?php
					$count = count( $session->get_all() );
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
								<?php foreach ( $session->get_all() as $session ) :
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
