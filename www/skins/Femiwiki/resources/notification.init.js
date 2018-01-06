/**
 * Echo 확장기능의 ext.echo.init.js를 복사한 후 변형했습니다.
 */
( function ( mw, $ ) {
	'use strict';

	// Remove ?markasread=XYZ from the URL
	var uri = new mw.Uri();
	if ( uri.query.markasread !== undefined ) {
		delete uri.query.markasread;
		window.history.replaceState( null, document.title, uri );
	}

	mw.echo = mw.echo || {};

	// Activate ooui
	$( document ).ready( function () {
		var myWidget, echoApi,
			$existingAlertLink = $( '#pt-notifications-alert-dialog a' ),
			$existingMessageLink = $( '#pt-notifications-message-dialog a' ),
			numAlerts = $existingAlertLink.text(),
			numMessages = $existingMessageLink.text(),
			hasUnseenAlerts = $existingAlertLink.hasClass( 'mw-echo-unseen-notifications' ),
			hasUnseenMessages = $existingMessageLink.hasClass( 'mw-echo-unseen-notifications' ),
			// Store links
			links = {
				notifications: $( '#pt-notifications-alert-dialog a' ).attr( 'href' ),
				preferences: $( '#pt-preferences a' ).attr( 'href' ) + '#mw-prefsection-echo'
			};

		// Respond to click on the notification button and load the UI on demand
		$( '.mw-echo-notification-badge-dialog-nojs' ).click( function ( e ) {
			var myType = $( this ).parent().prop( 'id' ) === 'pt-notifications-alert-dialog' ? 'alert' : 'message',
				time = mw.now();

			if ( e.which !== 1 ) {
				return;
			}

			// Dim the button while we load
			$( this ).addClass( 'mw-echo-notifications-badge-dimmed' );

			// Fire the notification API requests
			echoApi = new mw.echo.api.EchoApi();
			echoApi.fetchNotifications( myType )
				.then( function ( data ) {
					mw.track( 'timing.MediaWiki.echo.overlay.api', mw.now() - time );
					return data;
				} );

			// Load the ui
			mw.loader.using( [ 'skins.femiwiki.js.ui.notification' ] , function () {
				var messageNotificationsModel,
					alertNotificationsModel,
					unreadMessageCounter,
					unreadAlertCounter,
					maxNotificationCount = mw.config.get( 'wgEchoMaxNotificationCount' );

				// Overlay
				$( 'body' ).append( mw.echo.ui.$overlay );

				// Load message button and popup if messages exist
				if ( $existingMessageLink.length ) {
					unreadMessageCounter = new mw.echo.dm.UnreadNotificationCounter( echoApi, 'message', maxNotificationCount );
					messageNotificationsModel = new mw.echo.dm.NotificationsModel(
						echoApi,
						unreadMessageCounter,
						{
							type: 'message'
						}
					);
					mw.echo.ui.messageWidget = new mw.femiwiki.NotificationBadgeWidget( messageNotificationsModel, unreadMessageCounter, {
						markReadWhenSeen: false,
						$overlay: mw.echo.ui.$overlay,
						numItems: numMessages,
						hasUnseen: hasUnseenMessages,
						badgeIcon: 'speechBubbles',
						links: links,
						href: $existingMessageLink.attr( 'href' )
					} );
					// HACK: avoid late debouncedUpdateThemeClasses
					mw.echo.ui.messageWidget.badgeButton.debouncedUpdateThemeClasses();
					// Replace the link button with the ooui button
					$existingMessageLink.parent().replaceWith( mw.echo.ui.messageWidget.$element );

					mw.echo.ui.messageWidget.getModel().on( 'allTalkRead', function () {
						// If there was a talk page notification, get rid of it
						$( '#pt-mytalk a' )
							.removeClass( 'mw-echo-alert' )
							.text( mw.msg( 'mytalk' ) );
					} );
				}
				// Load alerts popup and button
				unreadAlertCounter = new mw.echo.dm.UnreadNotificationCounter( echoApi, 'alert', maxNotificationCount );
				alertNotificationsModel = new mw.echo.dm.NotificationsModel(
					echoApi,
					unreadAlertCounter,
					{
						type: 'alert'
					}
				);
				mw.echo.ui.alertWidget = new mw.femiwiki.NotificationBadgeWidget( alertNotificationsModel, unreadAlertCounter, {
					markReadWhenSeen: true,
					numItems: numAlerts,
					hasUnseen: hasUnseenAlerts,
					badgeIcon: {
						seen: 'bell',
						unseen: 'bellOn'
					},
					links: links,
					$overlay: mw.echo.ui.$overlay,
					href: $existingAlertLink.attr( 'href' )
				} );
				// HACK: avoid late debouncedUpdateThemeClasses
				mw.echo.ui.alertWidget.badgeButton.debouncedUpdateThemeClasses();
				// Replace the link button with the ooui button
				$existingAlertLink.parent().replaceWith( mw.echo.ui.alertWidget.$element );

				// HACK: Now that the module loaded, show the popup
				myWidget = myType === 'alert' ? mw.echo.ui.alertWidget : mw.echo.ui.messageWidget;
				myWidget.once( 'finishLoading', function () {
					// Log timing after notifications are shown
					mw.track( 'timing.MediaWiki.echo.overlay', mw.now() - time );
				} );
				mw.femiwiki.NotificationBadgeWidget.static.windowManager.openWindow( myWidget.dialog );

				mw.track( 'timing.MediaWiki.echo.overlay.ooui', mw.now() - time );
			} );

			if ( hasUnseenAlerts || hasUnseenMessages ) {
				// Clicked on the flyout due to having unread notifications
				mw.track( 'counter.MediaWiki.echo.unseen.click' );
			}

			// Prevent default
			return false;
		} );
	} );

} )( mediaWiki, jQuery );
