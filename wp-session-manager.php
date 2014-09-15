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
	 * @access protected
	 * @var WP_Session_Tokens[]
	 */
	protected $session = array();

	protected $bc = null;
	protected $bc_cache = array();

	/**
	 * Constructor.
	 *
	 * @access private
	 */
	private function __construct() {
		// Textdomain.
		add_action( 'init',                            array( $this, 'action_init'                   ) );

		// Profile options.
		add_action( 'admin_head-profile.php',          array( $this, 'enqueue_scripts_styles'        ) );
		add_action( 'admin_head-user-edit.php',        array( $this, 'enqueue_scripts_styles'        ) );
		add_action( 'personal_options',                array( $this, 'user_options_display'          ) );

		// Attach extra session information.
		add_filter( 'attach_session_information',      array( $this, 'filter_collected_session_info' ) );
		add_filter( 'session_token_manager',           array( $this, 'filter_session_token_manager'  ) );

		// AJAX actions for destroying sessions.
		add_action( 'wp_ajax_wpsm_destroy_sessions',   array( $this, 'destroy_multiple_sessions'     ) );
		add_action( 'wp_ajax_wpsm_destroy_session',    array( $this, 'destroy_single_session'        ) );
	}

	public function action_init() {
		load_plugin_textdomain( 'wpsm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	public function filter_session_token_manager( $manager ) {
		return 'WP_Session_Manager_User_Meta_Session_Tokens';
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
	public function user_options_display( WP_User $user ) {
		$user_session = $this->get_sessions( $user );
		?>
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row"><?php _e( 'Login Activity', 'wpsm' ); ?></th>
				<td>
					<?php
					$count = count( $user_session->get_all() );
					if ( $count > 1 ) :
						printf( __( 'You&#8217;re logged in to %s other locations:', 'wpsm' ),
							number_format_i18n( $count )
						);
						?>
						<table class="widefat sessions-table">
							<thead>
							<tr>
								<th scope="col" colspan="2"><?php _e( 'Access Type', 'wpsm' ); ?></th>
								<th scope="col"><?php _e( 'Location', 'wpsm' ); ?></th>
								<th scope="col"><?php _e( 'Signed In', 'wpsm' ); ?></th>
								<th scope="col"><?php _e( 'Expires', 'wpsm' ); ?></th>
							</tr>
							</thead>
							<tbody>
							<?php foreach ( $user_session->get_all() as $session ) :
								$browser = $this->get_browser( $session );
								$ip = isset( $session['ip-address'] ) ? $session['ip-address'] : __( 'Unknown', 'wpsm' );
								$started = isset( $session['started'] ) ? date_i18n( 'd/m/Y H:i:s', $session['started'] ) : __( 'Unknown', 'wpsm' );
								$expiration = date_i18n( 'd/m/Y H:i:s', $session['expiration'] );
								?>
								<tr>
									<td><span class="<?php echo $this->device_class( $browser ); ?>"></span></td>
									<td><?php
										if ( $browser ) {
											printf( __( '%1$s on %2$s %3$s', 'wpsm' ), $browser['browser'], $browser['platform'], $browser['platform_version'] );
										} else {
											_e( 'Unknown', 'wpsm' );
										}
									?></td>
									<td><?php echo $ip; ?></td>
									<td><?php echo $started; ?></td>
									<td><?php echo $expiration; ?></td>
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

	public function device_class( array $browser ) {
		if ( !$browser ) {
			return null;
		}
		if ( $browser['ismobiledevice'] ) {
			$class = 'smartphone';
		} else if ( $browser['istablet'] ) {
			$class = 'tablet';
		} else {
			$class = 'desktop';
		}
		return 'dashicons dashicons-' . $class;
	}

	public function get_browser( array $session ) {

		if ( !isset( $session['user-agent'] ) or empty( $session['user-agent'] ) ) {
			return array();
		}

		if ( isset( $this->bc_cache[$session['user-agent']] ) ) {
			return $this->bc_cache[$session['user-agent']];
		}

		if ( !isset( $this->bc ) ) {
			$bc = dirname( __FILE__ ) . '/browscap';
			require_once $bc . '/Browscap.php';
			$this->bc = new Browscap( $bc );
			$this->bc->lowercase = true;
		}

		return $this->bc_cache[$session['user-agent']] = $this->bc->getBrowser( $session['user-agent'], true );

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
	public function filter_collected_session_info( array $info ) {
		// IP address.
		$info['ip-address'] = $_SERVER['REMOTE_ADDR'];

		// User-agent.
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$info['user-agent'] = wp_unslash( $_SERVER['HTTP_USER_AGENT'] );
		}

		// Timestamp
		$info['started'] = time();

		return $info;
	}

	/**
	 * Get a session object for the given user.
	 *
	 * @since 1.0
	 *
	 * @access protected
	 *
	 * @param  WP_User $user A WP_User object.
	 * @return WP_Session_Tokens The WP_Session_Tokens object for the user.
	 */
	protected function get_sessions( WP_User $user ) {

		if ( isset( $this->session[$user->ID] ) ) {
			return $this->session[$user->ID];
		}

		return $this->session[$user->ID] = WP_Session_Tokens::get_instance( $user->ID );
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

class WP_Session_Manager_User_Meta_Session_Tokens extends WP_User_Meta_Session_Tokens {

	public function get_all_keyed() {
		return $this->get_sessions();
	}

	public function public_hash_token( $token ) {
		return hash( 'sha256', $token );
	}

	public function get_other_sessions( $token ) {
		$all     = $this->get_all_keyed();
		$current = $this->public_hash_token( $token );

		unset( $all[$current] );

		return $all;
	}

}

WP_Session_Manager::init();
