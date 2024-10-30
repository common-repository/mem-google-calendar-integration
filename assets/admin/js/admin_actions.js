jQuery( document ).ready(
	function() {
		var btnid = "tmem_settings[google_calendar_full_sync]_";

		jQuery( 'input[name="fullsyncbtn"]' ).click(
			function (e) {
				jQuery( this ).attr( 'disabled', true );
				jQuery( '.gcalbtn-message' ).html( 'Syncronising, please wait' );
				jQuery.ajax(
					{
						type: "POST",
						url: mem_gcal_vars.ajax_url,
						data: {
							action: 'gcal_full_sync'
						},
						success: function (output) {
							setTimeout(
								function() {
									jQuery( this ).attr( 'disabled', false );
									jQuery( '.gcalbtn-message' ).html( 'Finished' );
								},
								2000
							);
						}
					}
				);
			}
		);

		jQuery( "input[name='tmem_settings[gcal_client_id]'],input[name='tmem_settings[gcal_client_secret]']" ).on(
			'input',
			function($) {
				jQuery( '.changemsg' ).html( 'Please save your settings to use your updated oAuth credientials.' );
			}
		);

		// jQuery('.gcalconnectbtn').click(function (e) {
		// console.log('clicked');
		// var win = window.open( ajax_object.auth_url, "_blank", "width=600,height=600" );
		// });
	}
);
