<?php
/**
 * Settings for Stripe Payments
 */


/**
 * Displays & creates settings for Stripe Payments in Settings
 */

function mem_stripe_payments_settings( $settings ) {

	 $MEM_Extension = new MEM_GCal_Init();

	$next_url = add_query_arg(
		array(
			'post_type' => 'tmem-event',
			'page'      => 'tmem-settings',
			'tab'       => 'extensions',
			'section'   => 'gcal_sync',
		),
		admin_url( 'edit.php' )
	);

	$url = http_build_query(
		array(
			'next'          => $next_url,
			'scope'         => 'https://www.googleapis.com/auth/calendar',
			'https://www.googleapis.com/auth/calendar.readonly',
			'response_type' => 'code',
			'redirect_uri'  => 'urn:ietf:wg:oauth:2.0:oob',
			'client_id'     => '84670976789-jkno2hqpds3g0740kd2n2nuu578sdt9g.apps.googleusercontent.com',
		)
	);

	if ( ! tmem_get_option( 'gcal_auth_code', '' ) ) {
		$auth_hint = sprintf(
			/* translators: %s: Code left, %s: Code right */
			__( '%1$sAuthenticate%2$s', 'mdjm-google-calendar-sync' ),
			'<a target="_blank" href="javascript:void(0);" onclick="window.open(\'https://accounts.google.com/o/oauth2/auth?' . $url . '\',\'activate\',\'width=700,height=500,toolbar=0,menubar=0,location=0,status=1,scrollbars=1,resizable=1,left=0,top=0\');">',
			'</a>'
		);
	} else {
		$auth_hint = sprintf(
			/* translators: %s: Google Logout URL */
			__( '<a href="%s">Disconnect</a>', 'mdjm-google-calendar-sync' ),
			wp_nonce_url(
				add_query_arg( array( 'tmem-action' => 'logout_google' ), isset( $_SERVER['REQUEST_URI'] ) ? esc_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : '' ),
				'logout_google',
				'tmem-google-action'
			)
		);
	}

	$mem_stripe_settings = array(
		'gcal_sync' => array(
			array(
				'id'   => 'tmem_gcal_settings_title',
				'name' => '<h3><u>' . __( 'Google Calendar Integration Settings', 'mem-gcal-sync' ) . '</u></h3><p>Before setting up, please ensure you have setup a <a href="#linktodocs">Google Calendar API</a>.</p>',
				'type' => 'header',
				'desc' => __( '' ),
			),
			array(
				'id'   => 'gcal_get_auth',
				'name' => $auth_hint,
				'desc' => __( 'This generates an auth code to connect to your Google Calendar', 'mem-gcal-sync' ),
				'type' => 'header',

			),
			array(
				'id'   => 'gcal_client_id',
				'name' => __( 'Client ID', 'mem-gcal-sync' ),
				'type' => 'text',
				'desc' => __( 'Find out how to get this <a href="#linktodocs">here</a>', 'mem-gcal-sync' ),
			),
			array(
				'id'   => 'gcal_client_secret',
				'name' => __( 'Client Secret', 'mem-gcal-sync' ),
				'type' => 'password',
				'desc' => __( 'Find out how to get this <a href="#linktodocs">here</a>', 'mem-gcal-sync' ),
			),
			array(
				'id'   => 'gcal_auth_code',
				'name' => __( 'Google Auth Code', 'mem-gcal-sync' ),
				'desc' => __( 'This authorisation code is needed to enable us to communicate with your calendar', 'mem-gcal-sync' ),
				'type' => 'text',

			),
			array(
				'id'      => 'google_calendar',
				'name'    => __( 'Calendar', 'mdjm-google-calendar-sync' ),
				'desc'    => __( 'Select the Google calendar to which we should synchronise', 'mdjm-google-calendar-sync' ),
				'type'    => 'select',
				'options' => $MEM_Extension->gcal_connected ? $MEM_Extension->fetch_gcals() : array( '0' => __( 'Not Connected', 'mdjm-google-calendar-sync' ) ),
			),
			array(
				'id'   => 'stripe_mode',
				'name' => __( 'Use Stripe Test Mode', 'tmem-stripe-payments' ),
				'type' => 'checkbox',
			),
			array(
				'id'   => 'stripe_test_publishable_key',
				'name' => __( 'Test Publishable Key', 'tmem-stripe-payments' ),
				'type' => 'text',
			),
			array(
				'id'   => 'stripe_test_secret_key',
				'name' => __( 'Test Secret Key', 'tmem-stripe-payments' ),
				'type' => 'text',
			),
			array(
				'id'   => 'mem_stripe_redirections_title',
				'name' => '<h3><u>' . __( 'Payment Pages', 'tmem-stripe-payments' ) . '<h3><u>',
				'desc' => '',
				'type' => 'header',
			),
			array(
				'id'      => 'stripe_success_page',
				'name'    => __( 'Successful Payment Page', 'tmem-stripe-payments' ),
				'desc'    => __( 'Choose your success page for completed payment.', 'tmem-stripe-payments' ),
				'type'    => 'select',
				'options' => tmem_list_pages(),
				'std'     => tmem_get_option( 'payments_page' ),
			),
			array(
				'id'      => 'stripe_failed_page',
				'name'    => __( 'Failed Payment Page', 'tmem-stripe-payments' ),
				'desc'    => __( 'Choose your failed page for failed payment.', 'tmem-stripe-payments' ),
				'type'    => 'select',
				'options' => tmem_list_pages(),
				'std'     => tmem_get_option( 'payments_page' ),
			),
		),
	);

	return array_merge( $settings, $mem_stripe_settings );
} // tmem_stripe_payments_settings
add_filter( 'tmem_settings_extensions', 'mem_stripe_payments_settings' );
