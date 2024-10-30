<?php
/**
 * The core plugin class.
 *
 * This is used to define hooks, dependants and templates.
 *
 * @since      1.2
 * @copyright (c) 2020    Dan Porter, Jack Mawhinney <support@mobileeventsmanager.co.uk>
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MEM_GCal_Init {

	public $gcal_connected = false;

	/**
	 * Constructor, start building things
	 *
	 * @since 1.1
	 @access public
	 */

	public function __construct() {

		$this->mem_checks();
		$this->clientid     = tmem_get_option( 'gcal_client_id' );
		$this->clientsecret = tmem_get_option( 'gcal_client_secret' );
		$this->authtoken    = get_transient( 'TMEM_GCAL_OAUTH_REFRESH_TOKEN' );
		$this->code         = ( isset( $_GET['code'] ) ? sanitize_text_field( esc_html( $_GET['code'] ) ) : '' );

		$this->googlecal_init();
	}

	/**
	 * Initialise connection to Google oAuth & Calendar
	 *
	 * @since 1.1
	 * @access private
	 */

	private function googlecal_init() {
		$this->redirecturi = add_query_arg(
			array(
				'post_type' => 'tmem-event',
				'page'      => 'tmem-settings',
				'tab'       => 'extensions',
				'section'   => 'gcal_sync',
			),
			admin_url( 'edit.php' )
		);

		$this->set_hooks();
		require_once TMEM_GCAL_PATH . '/libs/Google/vendor/autoload.php';

		$this->gcal_get_tokens();
		$this->gcal_create_client();

		if ( isset( $_GET['code'] ) ) {
			set_transient( 'TMEM_GCAL_OAUTH_CODE', sanitize_text_field( $_GET['code'] ), 200 );
		}
		 /**
		 * Already have a refresh_token, so use that.
		 */

		if ( false !== ( $this->refresh_token ) && ! empty( $this->refresh_token ) ) {
			$this->gcal_use_refresh_token();
			$this->run_gcal();
			return;
		}

		if ( false === ( $this->auth_token ) || empty( $this->auth_token ) ) {
			add_action( 'admin_notices', 'tmem_gcalnotice_autherror' );
			return false;
		}

		/**
		 * have an OAUTH_CODE but no OAUTH_REFRESH_TOKEN.
		 */

		$this->gcal_get_auth_token();

		$reload_success = add_query_arg(
			array(
				'post_type' => 'tmem-event',
				'page'      => 'tmem-settings',
				'tab'       => 'extensions',
				'section'   => 'gcal_sync',
			),
			admin_url( 'edit.php' )
		);

		wp_safe_redirect( $reload_success );
	}

	public function run_gcal() {
		$this->gcal_get_tokens();

		if ( false == ( $this->refresh_token ) && empty( $this->refresh_token ) ) {
			add_action( 'admin_notices', 'tmem_gcalnotice_autherror' );
			return false;
		}

		$this->gcal_connected = true;

		$this->get_gcal();

		$this->full_sync = tmem_get_option( 'google_calendar_full_sync' );
		$this->gcal_id   = tmem_get_option( 'gcal_calendar' );
		$this->timezone  = $this->gcal_timezone();

	}

	public function gcal_get_tokens() {
		 $this->auth_token   = get_transient( 'TMEM_GCAL_OAUTH_CODE' );
		$this->refresh_token = get_transient( 'TMEM_GCAL_OAUTH_REFRESH_TOKEN' );
	}

	/**
	 * Google oAuth Client Credentials Setup
	 *
	 * @since 1.1
	 @access public
	 */

	public function gcal_create_client() {
		$this->client = new Google_Client();
		$this->client->setApplicationName( TMEM_NAME );
		$this->client->setApprovalPrompt( 'consent' );
		$this->client->setAccessType( 'offline' );
		$this->client->setClientId( $this->clientid );
		$this->client->setClientSecret( $this->clientsecret );
		$this->client->setRedirectUri( $this->redirecturi );
		$this->client->setScopes( Google_Service_Calendar::CALENDAR, Google_Service_Calendar::CALENDAR_EVENTS );

	}

	/**
	 * Set Refresh Token from Auth
	 *
	 * @since 1.1
	 @access public
	 */

	public function gcal_get_auth_token() {

		$this->client->authenticate( $this->auth_token );

		$this->refresh_token = $this->client->getRefreshToken();

		set_transient( 'TMEM_GCAL_OAUTH_REFRESH_TOKEN', $this->refresh_token );

	}

	/**
	 * Use Refresh Token from Auth
	 *
	 * @since 1.1
	 @access public
	 */

	public function gcal_use_refresh_token() {

		$refresh_token = get_transient( 'TMEM_GCAL_OAUTH_REFRESH_TOKEN' );

		$this->client->refreshToken( $refresh_token );

	}

	/**
	 * Set $this->gcal_calendar
	 *
	 * @since 1.1
	 @access public
	 */

	public function get_gcal() {

		try {
			$this->gcal_calendar = new Google_Service_Calendar( $this->client );
		} catch ( Google_Service_Exception $e ) {
			error_log( 'ERROR: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 Function hooks

	 @since 1.1
	 @access public
	 */

	public function set_hooks() {
		add_filter( 'plugin_action_links_' . TMEM_GCAL_BASENAME, array( __CLASS__, 'plugin_action_links' ) );
		add_filter( 'tmem_settings_extensions', array( $this, 'mem_gcal_settings' ) );

		add_action( 'tmem-unauth_gcal', array( $this, 'disconnect_auth' ) );
		add_action( 'tmem_after_event_save', array( $this, 'gcal_syncevent' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'gcal_adminjs' ) );
		add_action( 'wp_ajax_gcal_full_sync', array( $this, 'gcal_full_sync' ) );
	}

	/**
	 Load admin js

	 @since 1.1
	 @access public
	 */

	public function gcal_adminjs() {
		wp_enqueue_script( 'jquery' );

		wp_enqueue_script( 'mem-gcal-adminjs', TMEM_GCAL_URL . '/assets/admin/js/admin_actions.js', array(), TMEM_GCAL_VERSION, false );
		wp_localize_script(
			'mem-gcal-adminjs',
			'mem_gcal_vars',
			apply_filters(
				'mem_gcal_vars',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				)
			)
		);

		wp_enqueue_style( 'mem-gcal-admincss', TMEM_GCAL_URL . '/assets/admin/css/admin_styles.css', false );
	}

	/**
	 Fetch timezone setting from Google Calendar

	 @since 1.1
	 @access public
	 */

	public function gcal_timezone() {

		if ( ! $this->gcal_connected ) {
			return false;
		}

		try {
			$timezone = $this->gcal_calendar->settings->get( 'timezone' );
		} catch ( Google_Service_Exception $e ) {
			TMEM()->debug->log_it( 'Unable to get timezone setting from calendar ' . $e->getMessage() );
			return false;
		}

		return $timezone->getValue();

	} // gcal_timezone

	/**
	 Fetch list of available Google Calendars from authorised account

	 @since 1.1
	 @access public
	 */
	public function gcal_fetch_cals() {

		if ( ! $this->gcal_connected ) {
			return false;
		}

		$calendar_list = $this->gcal_calendar->calendarList->listCalendarList();

		if ( empty( $calendar_list ) ) {
			return false;
		} else {
			foreach ( $calendar_list->getItems() as $calendar ) {
				$calendar_id = $calendar->id;
				if ( ! empty( $calendar->primary ) ) {
					$calendar_id = 'primary';
				}
				$calendars[ $calendar_id ] = $calendar->summary;
			}
		}

		return $calendars;
	} // gcal_fetch_cals

	/**
	 Sync Event with Google Calendar on add / update

	 @since 1.1
	 @access public
	 */
	public function gcal_syncevent( $post_id ) {

		if ( ! $this->gcal_connected && ! $post_id ) {
			add_action( 'admin_notices', 'tmem_gcalnotice_accesstoken' );
			return false;
		}

		if ( ! get_post_type( $post_id ) == 'tmem-event' ) {
			return false;
		}
		$event_args = array(
			'p' => $post_id,
		);
		$events     = array();
		$events     = tmem_get_events( $event_args );

		if ( $events ) {
			foreach ( $events as $event ) {
				$this->gcal_sync_event( $event, $event->post_status );
			}
		} else {
			return;
		}
	}

	/**
	 Convert MEM statuses to readable Google Calendar strings

	 @since 1.1
	 @access public
	 */

	public function mem_event_status( $event ) {

		$event_status = get_post_status( $event->ID );

		if ( in_array( $event_status, array( 'tmem-unattended', 'tmem-awaitingdeposit', 'tmem-enquiry', 'tmem-contract' ) ) ) {
			$status = 'tentative';
		} elseif ( in_array( $event_status, array( 'tmem-cancelled', 'tmem-failed', 'tmem-rejected' ) ) ) {
			$status = 'cancelled';
		} elseif ( in_array( $event_status, array( 'tmem-approved', 'tmem-completed' ) ) ) {
			$status = 'confirmed';
		} else {
			// default
			$status = 'confirmed';
		}

		return $status;
	} // mem_event_status

	/**
	 Full sync to Google Calendar

	 @since 1.1
	 @access public
	 */

	public function gcal_full_sync() {

		if ( ! $this->gcal_connected ) {
			return false;
		}

		$events = tmem_get_events();

		if ( $events ) {
			foreach ( $events as $event ) {
				$this->gcal_sync_event( $event, $event->post_status );
			}
		} else {
			return;
		}

	} // sync_all_events

	/**
	 Sync event to Google Calendar API

	 @since 1.1
	 @access public
	 */
	public function gcal_sync_event( $post, $post_status ) {
		if ( ! $this->gcal_connected ) {
			return false;
		}

		if ( 'auto-draft' !== $post_status ) {

			$existing_entry = get_post_meta( $post->ID, '_tmem_event_google_calendar_entry', true );

			if ( ! empty( $existing_entry ) ) {
				return $this->update_event( $post->ID, $existing_entry['id'] );
			} else {

				return $this->add_event( $post->ID );
			}
		} else {
			return false;
		}

	} // gcal_sync_event

	/**
	 Add event to Google Calendar if doesn't exist.

	 @since 1.1
	 @access public
	 */
	public function add_event( $id, $event = '' ) {

		if ( empty( $event ) ) {
			$event = new TMEM_Event( $id );
			if ( empty( $event ) ) {
				return false;
			}
		}

		$calendar_entry = array();

		$calendar_entry['summary']     = $this->mem_event_summary( $event );
		$calendar_entry['location']    = $this->mem_event_location( $event );
		$calendar_entry['description'] = $this->mem_event_description( $event );
		$calendar_entry['start']       = $this->mem_event_starttime( $event );
		$calendar_entry['end']         = $this->mem_event_endtime( $event );
		$calendar_entry['status']      = $this->mem_event_status( $event );

		$google_entry = new Google_Service_Calendar_Event( $calendar_entry );

		try {

			$insert = $this->gcal_calendar->events->insert( $this->gcal_id, $google_entry );

		} catch ( Google_Service_Exception $e ) {
			TMEM()->debug->log_it( 'Calendar entry could not be created for event ' . $event->ID . '. ' . $e->getMessage() );
			return false;
		}

		if ( ! empty( $insert ) ) {

			TMEM()->debug->log_it( 'Calendar entry successfully created for event ' . $event->ID );

			$calendar_event = array(
				'calendar' => $this->gcal_id,
				'id'       => $insert->id,
				'link'     => esc_url( $insert->htmlLink ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				'iCalUID'  => $insert->iCalUID, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			);

			add_post_meta( $event->ID, '_tmem_event_google_calendar_entry', $calendar_event, true );

			return $insert->id;

		}

	} // add_event

	/**
	 Update event in Google Calendar if exists

	 @since 1.1
	 @access public
	 */
	public function update_event( $id, $calendar_entry, $event = '' ) {

		if ( empty( $event ) ) {
			$event = new TMEM_Event( $id );
			if ( empty( $event ) ) {
				return false;
			}
		}

		if ( ! $this->event_exists( $calendar_entry ) ) {
			delete_post_meta( $event->ID, '_tmem_event_google_calendar_entry' );
			return $this->add_event( $event->ID, $event );
		}

		$google_entry = $this->gcal_calendar->events->get( $this->gcal_id, $calendar_entry );

		$google_entry->setSummary( $this->mem_event_summary( $event ) );
		$google_entry->setLocation( $this->mem_event_location( $event ) );
		$google_entry->setDescription( $this->mem_event_description( $event ) );
		$google_entry->setStart( $this->mem_event_starttime( $event ) );
		$google_entry->setEnd( $this->mem_event_endtime( $event ) );
		$google_entry->setStatus( $this->mem_event_status( $event ) );
		try {

			$update_entry = $this->gcal_calendar->events->update( $this->gcal_id, $google_entry->getId(), $google_entry );

		} catch ( Google_Service_Exception $e ) {

			TMEM()->debug->log_it( 'Calendar entry could not be updated for event ' . $event->ID . '. ' . $e->getMessage() );

			return false;

		}

		if ( ! empty( $update_entry ) ) {

			TMEM()->debug->log_it( 'Calendar entry successfully updated for event ' . $event->ID );

			$calendar_event = array(
				'calendar' => $this->gcal_id,
				'id'       => $update_entry->id,
				'link'     => esc_url( $updated->htmlLink ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				'iCalUID'  => $update_entry->iCalUID, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			);

			update_post_meta( $event->ID, '_tmem_event_google_calendar_entry', $calendar_event );
			return $update_entry->id;
		}

	} // update_event


	/**
	 Check if event exists in Google Calendar

	 @since 1.1
	 @access public
	 */
	public function event_exists( $id ) {
		try {
			$entry = $this->gcal_calendar->events->get( $this->gcal_id, $id );
			return $entry;
		} catch ( Google_Service_Exception $e ) {
			return false;
		}
	} // event_exists

	/**
	 Save Google Calendar event title based on format chosen in settings

	 @since 1.1
	 @access public
	 */
	public function mem_event_summary( $event ) {
		$summary = html_entity_decode(
			tmem_do_content_tags(
				tmem_get_option( 'gcal_entry_format' ),
				$event->ID,
				$event->client,
				$event->venue
			)
		);

		return apply_filters( 'tmem_google_set_event_summary', $summary );
	} // mem_event_summary


	/**
	 Save Google Calendar event location

	 @since 1.1
	 @access public
	 */
	public function mem_event_location( $event ) {
		if ( empty( $event ) || empty( $event->ID ) ) {
			return;
		}

		$tmem_event = new TMEM_Event( $event->ID );
		$venue      = array(
			tmem_get_event_venue_meta( $tmem_event->get_venue_id(), 'name' ),
			tmem_get_event_venue_meta( $tmem_event->get_venue_id(), 'town' ),
			tmem_get_event_venue_meta( $tmem_event->get_venue_id(), 'postcode' ),
		);

		if ( ! empty( $venue ) ) {
			return implode( ', ', array_filter( $venue ) );
		}
	} // mem_event_location

	/**
	 Save Google Calendar event description based on Client Notes

	 @since 1.1
	 @access public
	 */
	public function mem_event_description( $event ) {
		$description = get_post_meta( $event->ID, '_tmem_event_notes', true );
		$description = str_replace( PHP_EOL, '<br />', $description );
		$description = utf8_encode( $description );

		return apply_filters( 'tmem_google_set_event_description', $description );
	} // mem_event_description

	/**
	 Save Google Calendar Event Start Time

	 @since 1.1
	 @access public
	 */
	public function mem_event_starttime( $event ) {
		$MEM_Extension = new MEM_GCal_Init();
		$start         = new Google_Service_Calendar_EventDateTime();

		$date_format = 'Y-m-d H:i:s';
		$event_date  = $event->date;
		$time        = $event->get_start_time();

		$start_time = DateTime::createFromFormat( $date_format, $event_date . ' ' . $time );

		$start->setTimeZone( $this->timezone );

		$start->setDateTime( date( 'c', strtotime( $start_time->format( 'Y-m-d H:i:s' ) ) ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		return $start;
	} // mem_event_starttime

	/**
	 Save Google Calendar Event End Time

	 @since 1.1
	 @access public
	 */
	public function mem_event_endtime( $event ) {
		$end = new Google_Service_Calendar_EventDateTime();

		$date_format = 'Y-m-d H:i:s';
		$time        = $event->get_finish_time();
		$end_date    = get_post_meta( $event->ID, '_tmem_event_end_date', true );

		if ( ! $end_date ) {
			$end_date = $event->date;
		}

		$end_time = DateTime::createFromFormat( $date_format, $end_date . ' ' . $time );
		$end->setTimeZone( $this->timezone );
		$end->setDateTime( date( 'c', strtotime( $end_time->format( 'Y-m-d H:i:s' ) ) ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		return $end;
	} // mem_event_endtime


	/**
	 * Does a base64_decode on the $_REQUEST['state'] data.
	 */


	/**
	 Create MEM Google Calendar Integration Settings Page

	 @since 1.1
	 @access public
	 */

	public function mem_gcal_settings( $settings ) {
		$client = new Google_Client();
		$client->setApplicationName( TMEM_NAME );
		$client->setApprovalPrompt( 'consent' );
		$client->setAccessType( 'offline' );
		$client->setClientId( $this->clientid );
		$client->setClientSecret( $this->clientsecret );
		$client->setRedirectUri( $this->redirecturi );
		$client->setScopes( Google_Service_Calendar::CALENDAR, Google_Service_Calendar::CALENDAR_EVENTS );

		$auth_url = http_build_query(
			array(
				'scope'         => 'https://www.googleapis.com/auth/calendar',
				'https://www.googleapis.com/auth/calendar.readonly',
				'https://www.googleapis.com/auth/calendar.events',
				'response_type' => 'code',
				'access_type'   => 'offline',
				'prompt'        => 'consent',
				'redirect_uri'  => $this->redirecturi,
				'client_id'     => ( ! empty( $this->clientid ) ? $this->clientid : '' ),
			)
		);

		if ( empty( $this->authtoken ) || ! $this->gcal_connected ) {
			$gcal_auth_button = '<a class="gcalconnectbtn connected" href="https://accounts.google.com/o/oauth2/auth?' . $auth_url . '">Connect</a>';
		} else {
			$gcal_auth_button = '<a class="gcalconnectbtn notconnected" href="' . wp_nonce_url(
				add_query_arg( array( 'tmem-action' => 'unauth_gcal' ), isset( $_SERVER['REQUEST_URI'] ) ? esc_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : '' ),
				'unauth_gcal',
				'tmem-gcal-action'
			) . '">Disconnect</a>';
		}

		$gcal_auth_button .= '<div class="changemsg" style="margin-top:15px;color:#fc0000;font-weight:bold;"></div>';

		$mem_gcal_settings = array(
			'gcal_sync' => array(
				array(
					'id'   => 'tmem_gcal_settings_title',
					'name' => '<h3><u>' . __( 'Google Calendar Integration Settings', 'mem-gcal-sync' ) . '</u></h3><p>Before setting up, please ensure you have setup a <a href="https://www.mobileeventsmanager.co.uk/articles/google-calendar-integration/" target="_blank">Google Calendar API</a>.</p>',
					'type' => 'header',
					'desc' => __( '' ),
				),
				array(
					'id'   => 'gcal_get_auth',
					'name' => $gcal_auth_button,
					'desc' => __( 'This generates an auth code to connect to your Google Calendar', 'mem-gcal-sync' ),
					'type' => 'header',

				),
				array(
					'id'       => 'gcal_client_id',
					'name'     => __( 'Client ID', 'mem-gcal-sync' ),
					'type'     => 'text',
					'desc'     => __( 'Find out how to get this <a href="https://www.mobileeventsmanager.co.uk/articles/google-calendar-integration/" target="_blank">here</a>', 'mem-gcal-sync' ),
					'readonly' => $this->gcal_connected ? true : false,
				),
				array(
					'id'       => 'gcal_client_secret',
					'name'     => __( 'Client Secret', 'mem-gcal-sync' ),
					'type'     => 'password',
					'desc'     => __( 'Find out how to get this <a href="https://www.mobileeventsmanager.co.uk/articles/google-calendar-integration/" target="_blank">here</a>', 'mem-gcal-sync' ),
					'readonly' => $this->gcal_connected ? true : false,
				),
				array(
					'id'   => 'tmem_gcal_redirecturi',
					'name' => __( 'Redirect URI', 'mem-gcal-sync' ),
					'hint' => __( 'Use this when setting up your OAuth Credentials' ),
					'type' => 'messagetext',
					'desc' => esc_url( $this->redirecturi ),
				),
				array(
					'id'      => 'gcal_calendar',
					'name'    => __( 'Calendar', 'mem-gcal-sync' ),
					'desc'    => __( 'Select the Google calendar to which we should synchronise', 'mem-gcal-sync' ),
					'type'    => 'select',
					'options' => $this->gcal_connected ? $this->gcal_fetch_cals() : array( '0' => 'API Not Connected' ),
				),
				array(
					'id'   => 'gcal_manual_actions',
					'name' => __( 'Manual Actions', 'mem-gcal-sync' ),
					'type' => 'gcalbtns',
					'btns' => array( 'Click to perform a full sync' => 'fullsyncbtn' ),
				),
				array(
					'id'   => 'gcal_entry_format',
					'name' => __( 'Entry Format', 'mem-gcal-sync' ),
					'desc' => __( 'How you would like the calendar entry to display. Full list of shortcodes can be found here - ', 'mem-gcal-sync' ),
					'type' => 'text',
					'std'  => '{event_type} - {venue} - ({event_status})',
				),
			),
		);

		return array_merge( $settings, $mem_gcal_settings );
	} // mem_gcal_settings

	/**
	 Disconnect from API & remove tokens

	 @since 1.1
	 @access   private
	 */

	public function disconnect_auth( $data ) {

		if ( ! isset( $data['tmem-gcal-action'] ) || ! wp_verify_nonce( $data['tmem-gcal-action'], 'unauth_gcal' ) ) {
			return;
		}

		delete_transient( 'TMEM_GCAL_OAUTH_REFRESH_TOKEN' );
		delete_transient( 'TMEM_GCAL_OAUTH_CODE' );
		delete_transient( 'TMEM_GCAL_OAUTH_REFRESH_TOKEN' );
		delete_transient( 'timeout_TMEM_GCAL_OAUTH_REFRESH_TOKEN' );

		$this->gcal_connected = false;
		unset( $this->client );

		$back = add_query_arg(
			array(
				'post_type' => 'tmem-event',
				'page'      => 'tmem-settings',
				'tab'       => 'extensions',
				'section'   => 'gcal_sync',
			),
			admin_url( 'edit.php' )
		);

		wp_safe_redirect( $back );
		exit;

	} // disconnect_auth

	/**
	 Do checks for MEM Installation

	 @since 1.1
	 @access   private
	 */

	private function mem_checks() {
		global $wp_version;

		if ( ! class_exists( 'Mobile_Events_Manager', false ) ) {
			deactivate_plugins( basename( TMEM_GCAL_FILE ) );
			wp_die( '<h1>Plugin cannot be activated</h1><p>Sorry, Mobile Events Manager is required to install this plugin.</p>', 'Plugin Activation Error', array( 'back_link' => true ) );
		}

			// Do not activate if MEM is not activated
		if ( ! class_exists( 'Mobile_Events_Manager', false ) || version_compare( TMEM_REQUIRED, TMEM_VERSION_NUM, '>' ) ) {
			deactivate_plugins( basename( T_AS ) );
			wp_die( '<h1>Plugin cannot be activated</h1><p>Sorry, your Mobile Events Manager is out of date. To install this plugin, please update MEM.</p>', 'Plugin Activation Error', array( 'back_link' => true ) );
		}

		// Check minimum MEM plugin version
		if ( version_compare( (float) TMEM_VERSION_NUM, TMEM_REQUIRED, '<=' ) ) {
			$message = sprintf( __( '<h1>Plugin cannot be activated</h1><p>MEM - Google Calendar Integration requires Mobile Events Manager version %1$s and higher. You are currently running version %2$2s You must update Mobile Events Manager to use this plugin.</p>', 'mem-gcal-sync' ), TMEM_REQUIRED, TMEM_VERSION_NUM );
			deactivate_plugins( basename( __FILE__ ) );
			wp_die( $message, array( 'back_link' => true ) );
		}

		// Do not activate is PHP / WP is not higher than recommended.
		if ( version_compare( PHP_VERSION, TMEM_PHP_MIN, '<' ) ) {
			$message = sprintf( __( '<h1>Plugin cannot be activated</h1><p>MEM - Google Calendar Integration requires PHP version %1$s and higher. You are currently running version %2$2s You must update PHP to use this plugin.</p>', 'mem-gcal-sync' ), TMEM_PHP_MIN, PHP_VERSION );

			deactivate_plugins( basename( __FILE__ ) );
			wp_die( $message, array( 'back_link' => true ) );
		}
		if ( version_compare( $wp_version, TMEM_WP_MIN, '<' ) ) {
			$message = sprintf( __( '<h1>Plugin cannot be activated</h1><p>MEM - Google Calendar Integration requires WordPress version %1$s and higher. You are currently running version %2$2s You must update WordPress to use this plugin.</p>', 'mem-gcal-sync' ), TMEM_WP_MIN, $wp_version );
			deactivate_plugins( basename( __FILE__ ) );
			wp_die( $message, array( 'back_link' => true ) );
		}

	}

	/**
	 Add settings section

	 @since 1.1
	 @access   public
	 */
	public static function plugin_action_links( $links ) {
		$link = add_query_arg(
			array(
				'post_type' => 'tmem-event',
				'page'      => 'tmem-settings',
				'tab'       => 'extensions',
				'section'   => 'gcal_sync',
			),
			admin_url( 'edit.php' )
		);

		$calendar_links = array(
			'<a href="' . $link . '">' . __( 'Settings', 'mem-gcal-sync' ) . '</a>',
		);

		return array_merge( $links, $calendar_links );
	} // plugin_action_links


} // end of MEM_GCal_Init

/**
 * Add settings section
 *
 * @since 1.1
 */
function mem_gcal_section( $sections ) {
	$sections['gcal_sync'] = __( 'Google Calendar Integration Settings', 'mem-gcal-sync' );

	return $sections;
} // mem_gcal_section
add_filter( 'tmem_settings_sections_extensions', 'mem_gcal_section' );

/**
 * Create custom callback for manual buttons in settings.
 *
 * @since 1.1
 */

function tmem_gcalbtns_callback( $args ) {
	$MEM_Extension = new MEM_GCal_Init();

	global $tmem_options;

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$name = '';
	} else {
		$name = 'name="tmem_settings[' . $args['id'] . ']"';
	}

	if ( ! is_array( $args['btns'] ) ) {
		return false;
	}

	$readonly = $args['readonly'] === true ? ' readonly="readonly"' : '';
	$html     = '';
	foreach ( $args['btns'] as $label => $action ) {
		$html .= '<input type="button" class="button button-primary" id="tmem_settings[' . $args['id'] . ']_' . $action . '" name="' . $action . '" value="' . $label . '"' . $readonly . '/>';
	}
	$html .= "<div class='gcalbtn-message'></div>";

	/**
	 * Already escaped in the main Mobile Events Manager Plugin /includes/admin/settings/register-settings.php #1646
	 *
	 */
	echo $html ; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Create custom callback for custom 2 column in settings.
 *
 * @since 1.1
 */

function tmem_messagetext_callback( $args ) {
	global $tmem_options;

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$name = '';
	} else {
		$name = 'name="tmem_settings[' . $args['id'] . ']"';
	}
	$html = '';
	if ( isset( $args['desc'] ) ) {
		$html = $args['desc'];
	}

	echo esc_html( $html );
}

/**
 * Admin notices
 *
 * @since 1.1
 */
function tmem_gcalnotice_secretid() {?>
	<div class="notice error my-acf-notice is-dismissible" >
		<p><?php _e( '<strong>TMEM Events</strong> - Your client ID or Secret is incorrect or does not have the correct OAuth permissions.', 'mem-gcal-sync' ); ?></p>
	</div>
	<?php
}
function tmem_gcalnotice_accesstoken() {
	?>
	<div class="notice error my-acf-notice is-dismissible" >
		<p><?php _e( '<strong>Mobile Events Manager is currently not syncing with your Google Calendar. Please check your settings.</strong>', 'mem-gcal-sync' ); ?></p>
	</div>
	<?php
}
function tmem_gcalnotice_autherror() {
	?>
	<div class="notice error my-acf-notice is-dismissible" >
		<p><?php _e( '<strong>Mobile Events Manager is having trouble authenticating your Google API. Please check your credentials. If you still have problems, try revoking access to your Google App <a href="https://myaccount.google.com/u/1/permissions" target="_blank">on this page</a>, then try connecting again.</strong>', 'mem-gcal-sync' ); ?></p>
	</div>
	<?php
}

