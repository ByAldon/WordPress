<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
$current_tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
$base_url     = is_network_admin() ? network_admin_url( 'settings.php?page=wps-limit-login' ) : admin_url( 'options-general.php?page=wps-limit-login' );
?>
<div class="wps-wrap-menu">
	<nav class="wps-menu">
		<div class="wps-nav-menu <?php echo ( 'wps-limit-login' === $current_page && '' === $current_tab ) ? 'current' : ''; ?>">
			<a href="<?php echo esc_url( $base_url ); ?>">
				<i class="fal fa-sliders-h"></i> <?php esc_html_e( 'Configuration', 'wps-limit-login' ); ?>
			</a>
		</div>
		<div class="wps-nav-menu <?php echo ( 'wps-limit-login' === $current_page && 'whitelist' === $current_tab ) ? 'current' : ''; ?>">
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'whitelist', $base_url ) ); ?>">
				<i class="fal fa-list-alt"></i> <?php esc_html_e( 'Whitelist', 'wps-limit-login' ); ?>
			</a>
		</div>
		<div class="wps-nav-menu <?php echo ( 'wps-limit-login' === $current_page && 'blacklist' === $current_tab ) ? 'current' : ''; ?>">
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'blacklist', $base_url ) ); ?>">
				<i class="fas fa-list-alt"></i> <?php esc_html_e( 'Blacklist', 'wps-limit-login' ); ?>
			</a>
		</div>
		<div class="wps-nav-menu <?php echo ( 'wps-limit-login' === $current_page && 'log' === $current_tab ) ? 'current' : ''; ?> last-child">
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'log', $base_url ) ); ?>">
				<i class="fal fa-clipboard-list"></i> <?php esc_html_e( 'Log', 'wps-limit-login' ); ?>
			</a>
		</div>
		<div class="clearfix"></div>
	</nav>
</div>
