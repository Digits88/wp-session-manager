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
	 * @access private
	 */
	private function __construct() {
		// Textdomain.
		load_plugin_textdomain( 'wpsm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// Profile options.
		add_action( 'admin_head-profile.php',          array( $this, 'enqueue_scripts_styles'        ) );
		add_action( 'profile_personal_options',        array( $this, 'user_options_display'          ) );

		// Attach extra session information.
		add_filter( 'attach_session_information',      array( $this, 'filter_collected_session_info' ) );

		// AJAX actions for destroying sessions.
		add_action( 'wp_ajax_wpsm_destroy_sessions',   array( $this, 'destroy_multiple_sessions'     ) );
		add_action( 'wp_ajax_wpsm_destroy_session',    array( $this, 'destroy_single_session'        ) );
	}

	/**
	 * Enqueue scripts and styles for the profile.php screen.
	 *
	 * @since 1.0
	 *
	 * @access public
	 */
	public function enqueue_scripts_styles() {
		// Styles.
		wp_enqueue_style( 'wpsm-options', plugins_url( 'css/profile-options.css' ), array(), '20140909' );

		// Script.
		wp_enqueue_script( 'wpsm-options', plugins_url( 'js/profile-options.js' ), array( 'jquery' ), '20140909' );
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
		$this->session = WP_Session_Tokens::get_instance( $user->ID );
		?>
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row"><?php _e( 'Login Activity', 'wpsm' ); ?></th>
				<td>
					<?php
					$count = count( $this->session->get_all() );
					if ( $count > 1 ) :
						$nooped = _n_noop(
							'You&#8217;re logged-in to %s other location:',
							'You&#8217;re logged-in to %s other locations:',
							'wpsm'
						);
						printf( translate_nooped_plural( $nooped, $count, 'wpsm' ), number_format_i18n( $count ) );
						?>
						<table class="sessions-table">
							<tbody>
							<tr>
								<th><?php _e( 'Access Type', 'wpsm' ); ?></th>
								<th><?php _e( 'Location', 'wpsm' ); ?></th>
							</tr>
							<?php foreach ( $this->session->get_all() as $session ) :
								$browser = get_browser( $session['user-agent'], true );
								?>
								<tr>
									<td><?php echo $browser['parent']; ?></td>
									<td><?php echo $session['ip-address']; ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; // $count > 1 ?>
					<br />
					<a href="" class="button button-secondary"><?php _e( 'Sign Out of All Other Sessions', 'wpsm' ); ?></a>
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

	/**
	 * AJAX handler for destroying multiple open sessions for the current user.
	 *
	 * @since 1.0
	 *
	 * @access public
	 */
	public function destroy_multiple_sessions() {

	}

	/**
	 * AJAX handler for destroying a single open session for the current user.
	 *
	 * @since 1.0
	 *
	 * @access public
	 */
	public function destroy_single_session() {

	/**
	 * Singleton getter.
	 *
	 * @since 1.0
	 *
	 * @access public
	 *
	 * @return WP_Session_Manager Our WP_Session_Manager instance.
	 */
	public static function init() {
		static $instance = null;

		if ( ! $instance ) {
			$instance = new WP_Session_Manager;
		}

		return $instance;

	}

}

WP_Session_Manager::init();
