<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PremiumAddons\Includes\Helper_Functions;

$docs_url = Helper_Functions::get_campaign_link( 'https://premiumaddons.com/docs/', 'docs', 'wp-dash', 'dashboard' );

$pa_news = self::get_pa_news();

?>

<div class="pa-section-content">
	<div class="row">
		<div id="pa-general-settings" class="pa-settings-tab">
			<?php // ByAldon custom hardening: promotional social/newsletter dashboard blocks removed. ?>

			<?php if ( is_array( $pa_news ) ) : ?>
				<div class="pa-dash-block col-6">
					<div class="pa-section-info-wrap">

						<div class="pa-section-info pa-news-section">
							<h4>
								<i class="pa-element-icon dashicons dashicons-admin-post icon-inline"></i>
								<?php esc_html_e( 'Latest News', 'premium-addons-for-elementor' ); ?>
							</h4>

							<div class="pa-news-grid">
								<?php foreach ( $pa_news as $index => $post ) : ?>
									<div class="pa-news-post">
										<div class="pa-post-img-container">
											<img src="<?php echo esc_url( $post['featured_img_url'] ); ?>">
										</div>
										<p><?php echo wp_kses_post( $post['title']['rendered'] ); ?></p>
										<p><?php echo wp_kses_post( gmdate( 'j F, Y', strtotime( $post['date'] ) ) ); ?></p>
										<a href="<?php echo esc_url( Helper_Functions::get_campaign_link( $post['link'], 'news', 'wp-dash', 'dashboard' ) ); ?>" target="_blank" rel="noopener noreferrer"></a>
									</div>
								<?php endforeach; ?>
							</div>

						</div>
					</div>
				</div>
			<?php endif; ?>

			<div class="pa-dash-block col-3">
				<div class="pa-section-info-wrap">
					<div class="pa-section-info pa-support-section">
						<h4>
							<i class="pa-element-icon dashicons dashicons-sos icon-inline"></i>
							<?php esc_html_e( 'Docs and Support', 'premium-addons-for-elementor' ); ?>
						</h4>
						<p><?php echo esc_html( __( 'It’s highly recommended to check our documentation and FAQs before using this plugin. ', 'premium-addons-for-elementor' ) ); ?></p>
						<ul class="pa-support-list">
							<li><a href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '> Documentation.', 'premium-addons-for-elementor' ); ?></a></li>
							<li><a href="https://wordpress.org/support/plugin/premium-addons-for-elementor/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '> Support Tickets.', 'premium-addons-for-elementor' ); ?></a></li>
						</ul>
					</div>
				</div>
			</div>

		</div>
	</div>
</div> <!-- End Section Content -->
