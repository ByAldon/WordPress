<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
} ?>

<div class="wrap wps-limit-login-page-settings">
	<?php include( WPS_LIMIT_LOGIN_DIR . 'blocks/title.php' ); ?>

    <p><?php _e( 'WPS Limit Login limits attempts to connect to your WordPress administration.', 'wps-limit-login' ); ?></p>

    <div class="wps-content-limit-login">
        <div class="wps-content-tab">
			<?php include( WPS_LIMIT_LOGIN_DIR . 'blocks/menu.php' ); ?>
            <div class="wps-tab">
				<?php
				$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

				if ( '' === $tab ) {
					include( WPS_LIMIT_LOGIN_DIR . 'blocks/settings.php' );
				} elseif ( 'whitelist' === $tab ) {
					include( WPS_LIMIT_LOGIN_DIR . 'blocks/whitelist.php' );
				} elseif ( 'blacklist' === $tab ) {
					include( WPS_LIMIT_LOGIN_DIR . 'blocks/blacklist.php' );
				} elseif ( 'log' === $tab ) {
					include( WPS_LIMIT_LOGIN_DIR . 'blocks/log.php' );
				} ?>
            </div>
        </div>
    </div>
    <?php // ByAldon custom build: promotional blocks removed. ?>
</div>