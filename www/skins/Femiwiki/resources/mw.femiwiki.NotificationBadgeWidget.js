( function ( mw, $ ) {
	/**
	 * Notification 배지 버튼과 dialog.
	 * Echo 확장기능의 mw.echo.ui.NotificationBadgeWidget을 복사한 뒤 변형하였습니다.
	 *
	 * @class
	 * @extends OO.ui.ButtonWidget
	 *
	 * @constructor
	 * @param {mw.echo.dm.NotificationsModel} model Notifications view model
	 * @param {mw.echo.dm.UnreadNotificationCounter} unreadCounter Counter of unread notifications
 	 * @param {Object} [config] Configuration object
	 * @cfg {number} [numItems=0] How many items are in the button display
	 * @cfg {boolean} [hasUnseen=false] Whether there are unseen items
	 * @cfg {boolean} [markReadWhenSeen=false] Mark all notifications as read on open
	 * @cfg {string|Object} [badgeIcon] The icons to use for this button.
	 *	If this is a string, it will be used as the icon regardless of the state.
	 *	If it is an object, it must include
	 *	the properties 'unseen' and 'seen' with icons attached to both. For example:
	 *	{ badgeIcon: {
	 *		unseen: 'bellOn',
	 *		seen: 'bell'
	 *	} }
	 * @cfg {string} [href] URL the badge links to
	 * @cfg {jQuery} [$overlay] A jQuery element functioning as an overlay
	 *	for dialogs.
	 */
	mw.femiwiki.NotificationBadgeWidget = function MwFemiwikiNotificationBadgeButtonDialogWidget( model, unreadCounter, config ) {
		var buttonFlags, allNotificationsButton, preferencesButton, footerButtonGroupWidget, 
			initialNotifCount, notice,
			widget = this;

		config = config || {};
		config.links = config.links || {};

		// Parent constructor
		mw.femiwiki.NotificationBadgeWidget.parent.call( this, config );

		// Mixin constructors
		OO.ui.mixin.PendingElement.call( this, config );

		this.$overlay = config.$overlay || this.$element;
		// Create a menu overlay
		this.$menuOverlay = $( '<div>' )
			.addClass( 'mw-echo-ui-NotificationBadgeWidget-overlay-menu' );
		this.$overlay.append( this.$menuOverlay );

		// View model
		this.notificationsModel = model;
		this.unreadCounter = unreadCounter;
		this.type = this.notificationsModel.getType();

		this.maxNotificationCount = mw.config.get( 'wgEchoMaxNotificationCount' );
		this.numItems = config.numItems || 0;
		this.markReadWhenSeen = !!config.markReadWhenSeen;
		this.badgeIcon = config.badgeIcon || {};
		this.hasRunFirstTime = false;

		buttonFlags = [ 'primary' ];
		if ( !!config.hasUnseen ) {
			buttonFlags.push( 'unseen' );
		}

		this.badgeButton = new mw.echo.ui.BadgeLinkWidget( {
			label: this.numItems,
			flags: buttonFlags,
			badgeIcon: config.badgeIcon,
			// The following messages can be used here:
			// tooltip-pt-notifications-alert
			// tooltip-pt-notifications-message
			title: mw.msg( 'tooltip-pt-notifications-' + this.type ),
			href: config.href
		} );

		// Notifications widget
		this.notificationsWidget = new mw.echo.ui.NotificationsWidget(
			this.notificationsModel,
			{
				type: this.type,
				$overlay: this.$menuOverlay,
				markReadWhenSeen: this.markReadWhenSeen
			}
		);

		// Footer
		allNotificationsButton = new OO.ui.ButtonWidget( {
			icon: 'next',
			label: mw.msg( 'echo-overlay-link' ),
			href: config.links.notifications,
			classes: [ 'mw-echo-ui-notificationBadgeButtonDialogWidget-footer-allnotifs' ]
		} );

		preferencesButton = new OO.ui.ButtonWidget( {
			icon: 'advanced',
			label: mw.msg( 'mypreferences' ),
			href: config.links.preferences,
			classes: [ 'mw-echo-ui-notificationBadgeButtonDialogWidget-footer-preferences' ]
		} );

		footerButtonGroupWidget = new OO.ui.ButtonGroupWidget( {
			items: [ allNotificationsButton, preferencesButton ],
			classes: [ 'mw-echo-ui-notificationBadgeButtonDialogWidget-footer-buttons' ]
		} );
		this.$footer = $( '<div>' )
			.addClass( 'mw-echo-ui-notificationBadgeButtonDialogWidget-footer' )
			.append( footerButtonGroupWidget.$element );

		// Footer notice
		initialNotifCount = mw.config.get( 'wgEchoInitialNotifCount' );
		initialNotifCount = this.type === 'all' ? ( initialNotifCount.alert + initialNotifCount.message ) : initialNotifCount[ this.type ];
		if (
			mw.config.get( 'wgEchoShowBetaInvitation' ) &&
			!mw.user.options.get( 'echo-dismiss-beta-invitation' )
		) {
			notice = new mw.echo.ui.FooterNoticeWidget( {
				// This is probably not the right way of doing this
				iconUrl: mw.config.get( 'wgExtensionAssetsPath' ) + '/Echo/modules/icons/feedback.svg',
				url: mw.util.getUrl( 'Special:Preferences' ) + '#mw-prefsection-betafeatures'
			} );
			// Event
			notice.connect( this, { dismiss: 'onFooterNoticeDismiss' } );
			// Prepend to the footer
			this.$footer.prepend( notice.$element );
		}

		function NotificationDialog( config ) {
			NotificationDialog.super.call( this, config );
		}
		OO.inheritClass( NotificationDialog, OO.ui.ProcessDialog ); 

		// Specify a name for .addWindows()
		NotificationDialog.static.name = this.type+'Dialog';
		// Specify a title.
		NotificationDialog.static.title = mw.msg( 'echo-notification-' + this.type + '-text-only' );
		NotificationDialog.static.actions = [
			{ 
				action: 'markAllRead',
				modes: 'edit',
				label: mw.msg( 'echo-mark-all-as-read' ),
				flags: [ 'primary', 'constructive' ],
				classes: [ 'mw-echo-ui-notificationsWidget-markAllReadButton' ]
			},
			{ modes: 'edit', label: '닫기', flags: 'safe' }
		];

		NotificationDialog.prototype.initialize = function () {
			// Call the parent method
			NotificationDialog.super.prototype.initialize.call( this );
		};
			
		// Override the getBodyHeight() method to specify a custom height (or don't to use the automatically generated height)
		NotificationDialog.prototype.getBodyHeight = function () {
			// return this.panel1.$element.outerHeight( true ); // 자동으로 할 경우 매우 짧아집니다.
			return 450;
		};

		NotificationDialog.prototype.initialize = function () {
			NotificationDialog.parent.prototype.initialize.apply( this, arguments );
			this.content = widget.notificationsWidget;
			this.$body.append( this.content.$element );
			this.$foot.append( widget.$footer );
		};

		NotificationDialog.prototype.getActionProcess = function ( action ) {
			var dialog = this;
			if ( action === 'markAllRead' ) {
				return new OO.ui.Process( function () {
					widget.notificationsModel.markAllRead();
				} );
			}
		// Fallback to parent handler.
			return NotificationDialog.super.prototype.getActionProcess.call( this, action );
		};

		// Make the window.
		this.dialog = new NotificationDialog( {
			size: 'medium'
		} );

		// Add the window to the window manager using the addWindows() method.
		mw.femiwiki.NotificationBadgeWidget.static.windowManager.addWindows( [ this.dialog ] );

		this.updateIcon( !!config.hasUnseen );

		// 모두 읽음으로 표시 버튼을 우선 비활성화합니다.
		this.dialog.getActions().setAbilities( { markAllRead: false } );

		// Events
		this.notificationsModel.connect( this, {
			updateSeenTime: 'updateBadge',
			unseenChange: 'updateBadge'
		} );
		this.unreadCounter.connect( this, { countChange: 'updateBadge' } );
		mw.femiwiki.NotificationBadgeWidget.static.windowManager.connect( this, {
			opening: 'onDialogOpening',
			closing: 'onDialogClosing'
		} );
		this.badgeButton.connect( this, {
			click: 'onBadgeButtonClick'
		} );

		this.$element
			.prop( 'id', 'pt-notifications-' + this.type )
			// The following classes can be used here:
			// mw-echo-ui-notificationBadgeButtonDialogWidget-alert
			// mw-echo-ui-notificationBadgeButtonDialogWidget-message
			.addClass(
				'mw-echo-ui-notificationBadgeButtonDialogWidget ' +
				'mw-echo-ui-notificationBadgeButtonDialogWidget-' + this.type
			)
			.append(
				this.badgeButton.$element
			);
	};

	/* Initialization */

	OO.inheritClass( mw.femiwiki.NotificationBadgeWidget, OO.ui.Widget );
	OO.mixinClass( mw.femiwiki.NotificationBadgeWidget, OO.ui.mixin.PendingElement );

	/* Static properties */

	mw.femiwiki.NotificationBadgeWidget.static.tagName = 'li';

	if ( !mw.femiwiki.NotificationBadgeWidget.static.windowManager ) {
		mw.femiwiki.NotificationBadgeWidget.static.windowManager = new OO.ui.WindowManager();
		$( 'body' ).append( mw.femiwiki.NotificationBadgeWidget.static.windowManager.$element );
	}

	/* Events */

	/**
	 * @event allRead
	 * All notifications were marked as read
	 */

	/**
	 * @event finishLoading
	 * Notifications have successfully finished being processed and are fully loaded
	 */

	/* Methods */

	mw.femiwiki.NotificationBadgeWidget.prototype.onFooterNoticeDismiss = function () {

		// Save the preference in general
		new mw.Api().saveOption( 'echo-dismiss-beta-invitation', 1 );
		// Save the preference for this session
		mw.user.options.set( 'echo-dismiss-beta-invitation', 1 );
	};

	/**
	 * Respond to badge button click
	 */
	mw.femiwiki.NotificationBadgeWidget.prototype.onBadgeButtonClick = function () {
		this.notificationsModel.fetchNotifications( true );
		mw.femiwiki.NotificationBadgeWidget.static.windowManager.openWindow( this.dialog );
	};

	/**
	 * Update the badge icon with the read/unread versions if they exist.
	 *
	 * @param {boolean} hasUnseen Widget has unseen notifications
	 */
	mw.femiwiki.NotificationBadgeWidget.prototype.updateIcon = function ( hasUnseen ) {
		var icon = typeof this.badgeIcon === 'string' ?
			this.badgeIcon :
			this.badgeIcon[ hasUnseen ? 'unseen' : 'seen' ];

		this.badgeButton.setIcon( icon );
	};

	// Client-side version of NotificationController::getCappedNotificationCount.
	/**
	 * Gets the count to use for display
	 *
	 * @param {number} count Count before cap is applied
	 *
	 * @return {number} Count with cap applied
	 */
	mw.femiwiki.NotificationBadgeWidget.prototype.getCappedNotificationCount = function ( count ) {
		if ( count <= this.maxNotificationCount ) {
			return count;
		} else {
			return this.maxNotificationCount + 1;
		}
	};

	/**
	 * Update the badge state and label based on changes to the model
	 */
	mw.femiwiki.NotificationBadgeWidget.prototype.updateBadge = function () {
		var unseenCount = this.notificationsModel.getUnseenCount(),
			unreadCount = this.unreadCounter.getCount(),
			nonBundledUnreadCount = this.notificationsModel.getNonbundledUnreadCount(),
			cappedUnreadCount,
			badgeLabel;

		// Update numbers and seen/unseen state
		// If the dialog is open, only allow a "demotion" of the badge
		// to grey; ignore change of color to 'unseen'
		if ( mw.femiwiki.NotificationBadgeWidget.static.windowManager.isVisible() ) {
			if ( !unseenCount ) {
				this.badgeButton.setFlags( { unseen: false } );
				this.updateIcon( false );
			}
		} else {
			this.badgeButton.setFlags( { unseen: !!unseenCount } );
			this.updateIcon( !!unseenCount );
		}

		// Update badge count
		cappedUnreadCount = this.getCappedNotificationCount( unreadCount );
		cappedUnreadCount = mw.language.convertNumber( cappedUnreadCount );
		badgeLabel = mw.message( 'echo-badge-count', cappedUnreadCount ).text();
		this.badgeButton.setLabel( (this.type == 'alert' ? '알림: ' : '메시지: ') +badgeLabel );

		// Check if we need to display the 'mark all unread' button
		this.dialog.getActions().setAbilities( { markAllRead: !this.markReadWhenSeen && nonBundledUnreadCount > 0 } );
	};

	mw.femiwiki.NotificationBadgeWidget.prototype.onDialogOpening = function ( win, opened, data ) {
		var widget = this;

		if ( this.promiseRunning ) {
			return;
		}

		// Log the click event
		mw.echo.logger.logInteraction(
			'ui-badge-link-click',
			mw.echo.Logger.static.context.badge,
			null,
			this.type
		);

		this.pushPending();
		this.dialog.getActions().setAbilities( { markAllRead: false } );
		this.promiseRunning = true;

		this.notificationsModel.fetchNotifications()
			.then( function () {
				// Update seen time
				widget.notificationsModel.updateSeenTime();
				// Mark notifications as 'read' if markReadWhenSeen is set to true
				if ( widget.markReadWhenSeen ) {
					widget.notificationsModel.autoMarkAllRead();
				}

				widget.emit( 'finishLoading' );
			} )
			.always( function () {
				// Pop pending
				widget.popPending();
				widget.promiseRunning = false;
			} );
		this.hasRunFirstTime = true;
	};

	mw.femiwiki.NotificationBadgeWidget.prototype.onDialogClosing = function ( win, opened, data ) {
		var widget = this;

		if ( this.promiseRunning ) {
			return;
		}

		// Remove "initiallyUnseen" and leave
		this.notificationsWidget.resetNotificationItems();

		if ( this.promiseRunning ) {
			return;
		}
		// Log the click event
		mw.echo.logger.logInteraction(
			'ui-badge-link-click',
			mw.echo.Logger.static.context.badge,
			null,
			this.type
		);

		this.pushPending();
		this.dialog.getActions().setAbilities( { markAllRead: false } );
		this.promiseRunning = true;

		this.notificationsModel.fetchNotifications()
			.then( function () {
				widget.emit( 'finishLoading' );
			} )
			.always( function () {
				// Pop pending
				widget.popPending();
				widget.promiseRunning = false;
			} );
		this.hasRunFirstTime = true;
	};

	/**
	 * Get the notifications model attached to this widget
	 *
	 * @return {mw.echo.dm.NotificationsModel} Notifications model
	 */
	mw.femiwiki.NotificationBadgeWidget.prototype.getModel = function () {
		return this.notificationsModel;
	};

} )( mediaWiki, jQuery );
