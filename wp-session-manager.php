<?php
/**
 * Plugin Name: WP Session Manager
 * Author: Drew Jaynes & John Blackbourn
 * Description: Adds controls to a user's profile screen for managing their logged-in sessions.
 * Version: 1.0
 * License: GPLv2
 */

/**
 * Class WP_Session_Manager
 *
 * @since 1.0
 */
class WP_Session_Manager {

	/**
	 * Current session.
	 *
	 * @since 1.0
	 * @access public
	 * @var WP_Session_Tokens
	 */
	public $session;

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		load_plugin_textdomain( 'wpsm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		add_action( 'admin_print_styles-profile.php', array( $this, 'admin_print_styles' ) );
		add_action( 'profile_personal_options',       array( $this, 'user_options_display'          ) );
		add_filter( 'attach_session_information',     array( $this, 'filter_collected_session_info' ) );
	}

	/**
	 * Print the admin-options stylesheet on profile.php.
	 *
	 * @since 1.0
	 *
	 * @access public
	 */
	public function admin_print_styles() {
		wp_enqueue_style( 'wpsm-options', plugins_url( 'css/profile-options.css' ), array(), '20140909' );
	}

	/**
	 * Handle outputting the session manager options to the user profile screen.
	 *
	 * @since 1.0
	 *
	 * @access public
	 *
	 * @param WP_User $user WP_User object for the current user.
	 */
	public function user_options_display( $user ) {
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

	/**
	 * Collect and store additional session information.
	 *
	 * @since 1.0
	 *
	 * @access public
	 *
	 * @param array $info Array of session information.
	 * @return array Filtered session information array.
	 */
	public function filter_collected_session_info( $info ) {
		// IP address.
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$info['ip-address'] = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$info['ip-address'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$info['ip-address'] = $_SERVER['REMOTE_ADDR'];
		}

		// User-agent.
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$info['user-agent'] = $_SERVER['HTTP_USER_AGENT'];
		}
		return $info;
	}
}

$wp_session_manager = new WP_Session_Manager();