<?php
/**
 * Plugin Name:       Simple Scroll To Top WP
 * Plugin URI:        https://wordpress.org/plugins/simple-scroll-to-top-wp/
 * Description:       Simple Scroll to top plugin will help you to enable Back to Top button to your WordPress website.
 * Version:           3.0.2
 * Update URI:        https://byaldon.invalid/simple-scroll-top-wp/
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Ali Hossain
 * Author URI:        https://alihossain.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sstt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SSTT_VERSION', '3.0.2' );
define( 'SSTT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/* ---------------------------------------------------------------
 * Update Lock
 * ------------------------------------------------------------- */
function sstt_disable_auto_update( $update, $item ) {
    if ( isset( $item->plugin ) && SSTT_PLUGIN_BASENAME === $item->plugin ) {
        return false;
    }

    return $update;
}
add_filter( 'auto_update_plugin', 'sstt_disable_auto_update', 10, 2 );

function sstt_remove_update_offer( $transient ) {
    if ( is_object( $transient ) ) {
        if ( isset( $transient->response[ SSTT_PLUGIN_BASENAME ] ) ) {
            unset( $transient->response[ SSTT_PLUGIN_BASENAME ] );
        }

        if ( isset( $transient->no_update[ SSTT_PLUGIN_BASENAME ] ) ) {
            unset( $transient->no_update[ SSTT_PLUGIN_BASENAME ] );
        }
    }

    return $transient;
}
add_filter( 'site_transient_update_plugins', 'sstt_remove_update_offer' );
add_filter( 'pre_set_site_transient_update_plugins', 'sstt_remove_update_offer' );

function sstt_block_plugin_info_popup( $result, $action, $args ) {
    $blocked_slugs = array( 'simple-scroll-top-wp', 'simple-scroll-to-top-wp' );

    if ( 'plugin_information' === $action && isset( $args->slug ) && in_array( $args->slug, $blocked_slugs, true ) ) {
        return new WP_Error( 'sstt_update_locked', __( 'Plugin information is disabled for this custom locked build.', 'sstt' ) );
    }

    return $result;
}
add_filter( 'plugins_api', 'sstt_block_plugin_info_popup', 10, 3 );

/* ---------------------------------------------------------------
 * Settings Sanitization
 * ------------------------------------------------------------- */
function sstt_sanitize_true_false( $value ) {
    return ( 'true' === (string) $value ) ? 'true' : 'false';
}

function sstt_sanitize_checkbox( $value ) {
    return ( '1' === (string) $value ) ? '1' : '';
}

function sstt_sanitize_color( $value ) {
    $color = sanitize_hex_color( wp_unslash( $value ) );
    return $color ? $color : '#22c55e';
}

function sstt_sanitize_icon_url( $value ) {
    return esc_url_raw( wp_unslash( $value ) );
}

function sstt_sanitize_button_size( $value ) {
    $value = absint( $value );
    return min( 100, max( 30, $value ) );
}

function sstt_sanitize_border_width( $value ) {
    $value = absint( $value );
    return min( 10, max( 1, $value ) );
}

function sstt_register_settings() {
    register_setting( 'sstt_settings_group', 'sstt-enabled', array( 'sanitize_callback' => 'sstt_sanitize_true_false' ) );
    register_setting( 'sstt_settings_group', 'sstt-custom-icon', array( 'sanitize_callback' => 'sstt_sanitize_icon_url' ) );
    register_setting( 'sstt_settings_group', 'sstt-primary-color', array( 'sanitize_callback' => 'sstt_sanitize_color' ) );
    register_setting( 'sstt_settings_group', 'sstt-image-position', array( 'sanitize_callback' => 'sstt_sanitize_true_false' ) );
    register_setting( 'sstt_settings_group', 'sstt-round-corner', array( 'sanitize_callback' => 'sstt_sanitize_true_false' ) );
    register_setting( 'sstt_settings_group', 'sstt-hide-mobile', array( 'sanitize_callback' => 'sstt_sanitize_checkbox' ) );
    register_setting( 'sstt_settings_group', 'sstt-hide-tablet', array( 'sanitize_callback' => 'sstt_sanitize_checkbox' ) );
    register_setting( 'sstt_settings_group', 'sstt-hide-desktop', array( 'sanitize_callback' => 'sstt_sanitize_checkbox' ) );
    register_setting( 'sstt_settings_group', 'sstt-button-size', array( 'sanitize_callback' => 'sstt_sanitize_button_size' ) );
    register_setting( 'sstt_settings_group', 'sstt-border-width', array( 'sanitize_callback' => 'sstt_sanitize_border_width' ) );
}
add_action( 'admin_init', 'sstt_register_settings' );

/* ---------------------------------------------------------------
 * Admin Menu
 * ------------------------------------------------------------- */
function sstt_add_theme_page() {
    add_menu_page(
        'Scroll To Top Option for Admin',
        'Scroll To Top',
        'manage_options',
        'sstt-plugin-option',
        'sstt_create_page',
        'dashicons-arrow-up-alt',
        101
    );
}
add_action( 'admin_menu', 'sstt_add_theme_page' );

/* ---------------------------------------------------------------
 * Admin Styles
 * ------------------------------------------------------------- */
function sstt_add_theme_css( $hook ) {
    if ( 'toplevel_page_sstt-plugin-option' !== $hook ) {
        return;
    }

    wp_enqueue_style( 'sstt-admin-style', plugins_url( 'css/sstt-admin-style.css', __FILE__ ), array(), SSTT_VERSION );
}
add_action( 'admin_enqueue_scripts', 'sstt_add_theme_css' );

/* ---------------------------------------------------------------
 * Admin: Media Uploader JS
 * ------------------------------------------------------------- */
function sstt_admin_enqueue_media( $hook ) {
    if ( 'toplevel_page_sstt-plugin-option' !== $hook ) return;
    wp_enqueue_media();
    wp_add_inline_script( 'jquery-core', "
        jQuery(document).ready(function(\$){
            var mediaUploader;
            \$('#sstt-upload-btn').on('click', function(e){
                e.preventDefault();
                if ( mediaUploader ) { mediaUploader.open(); return; }
                mediaUploader = wp.media({
                    title: 'Choose Icon Image',
                    button: { text: 'Use This Image' },
                    multiple: false
                });
                mediaUploader.on('select', function(){
                    var att = mediaUploader.state().get('selection').first().toJSON();
                    \$('#sstt-custom-icon').val(att.url);
                    \$('#sstt-icon-preview').attr('src', att.url).show();
                    \$('#sstt-remove-btn').show();
                });
                mediaUploader.open();
            });
            \$('#sstt-remove-btn').on('click', function(e){
                e.preventDefault();
                \$('#sstt-custom-icon').val('');
                \$('#sstt-icon-preview').attr('src','').hide();
                \$(this).hide();
            });
        });
    " );
}
add_action( 'admin_enqueue_scripts', 'sstt_admin_enqueue_media' );

/* ---------------------------------------------------------------
 * Admin: Settings Page HTML
 * ------------------------------------------------------------- */
function sstt_create_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'sstt' ) );
    }

    $enabled      = sstt_sanitize_true_false( get_option( 'sstt-enabled', 'true' ) );
    $icon_url     = esc_url_raw( get_option( 'sstt-custom-icon', '' ) );
    $round_corner = sstt_sanitize_true_false( get_option( 'sstt-round-corner', 'false' ) );
    $position     = sstt_sanitize_true_false( get_option( 'sstt-image-position', 'false' ) );
    $primary_color = sstt_sanitize_color( get_option( 'sstt-primary-color', '#22c55e' ) );
    $hide_mobile  = sstt_sanitize_checkbox( get_option( 'sstt-hide-mobile', '' ) );
    $hide_tablet  = sstt_sanitize_checkbox( get_option( 'sstt-hide-tablet', '' ) );
    $hide_desktop = sstt_sanitize_checkbox( get_option( 'sstt-hide-desktop', '' ) );
    $btn_size     = sstt_sanitize_button_size( get_option( 'sstt-button-size',  50 ) );
    $border_width = sstt_sanitize_border_width( get_option( 'sstt-border-width', 3  ) );
    ?>
    <div class="sstt_main_area">
      <div class="sstt_body_area sstt_common">
        <h3 id="title">Simple Scroll To Top WP</h3>

        <form action="options.php" method="post">
          <?php settings_fields( 'sstt_settings_group' ); ?>

          <!-- 1. Enabled -->
          <label><?php esc_html_e( 'Enabled', 'sstt' ); ?></label>
          <small>Enable or disable the scroll-to-top button on your site.</small>
          <label class="radios">
            <input type="radio" name="sstt-enabled" value="true" <?php checked( $enabled, 'true' ); ?>>
            <span>Yes</span>
          </label>
          <label class="radios" style="margin-bottom:20px;">
            <input type="radio" name="sstt-enabled" value="false" <?php checked( $enabled, 'false' ); ?>>
            <span>No</span>
          </label>

          <!-- 2. Round Corner -->
          <label><?php esc_html_e( 'Round Corner', 'sstt' ); ?></label>
          <small>Toggle between a circular and a square button shape.</small>
          <label class="radios">
            <input type="radio" name="sstt-round-corner" value="false" <?php checked( $round_corner, 'false' ); ?>>
            <span>Yes (circle)</span>
          </label>
          <label class="radios" style="margin-bottom:20px;">
            <input type="radio" name="sstt-round-corner" value="true" <?php checked( $round_corner, 'true' ); ?>>
            <span>No (square)</span>
          </label>

          <!-- 3. Custom Icon -->
          <label><?php esc_html_e( 'Custom Icon', 'sstt' ); ?></label>
          <small>Upload a custom icon image. Recommended size: <strong>64&times;64&nbsp;px</strong>.</small>
          <input type="hidden" name="sstt-custom-icon" id="sstt-custom-icon" value="<?php echo esc_url( $icon_url ); ?>">
          <div class="sstt-icon-wrap">
            <img id="sstt-icon-preview" src="<?php echo esc_url( $icon_url ); ?>" alt="Custom Icon"
                 style="width:64px;height:64px;object-fit:contain;border:1px solid #ddd;border-radius:5px;margin-bottom:10px;display:<?php echo esc_attr( $icon_url ? 'block' : 'none' ); ?>;">
            <button type="button" id="sstt-upload-btn" class="button button-secondary" style="margin-right:8px;">Upload Image</button>
            <button type="button" id="sstt-remove-btn" class="button" style="display:<?php echo esc_attr( $icon_url ? 'inline-block' : 'none' ); ?>;">Remove</button>
          </div>

          <!-- 4. Button Size -->
          <label for="sstt-button-size"><?php esc_html_e( 'Button Size', 'sstt' ); ?></label>
          <small>Width and height of the scroll-to-top button (30&ndash;100&nbsp;px). Default: 50.</small>
          <div class="sstt-number-wrap">
            <input type="number" name="sstt-button-size" id="sstt-button-size"
                   min="30" max="100" value="<?php echo esc_attr( $btn_size ); ?>">
            <span class="sstt-unit">px</span>
          </div>

          <!-- 5. Progress Ring Width -->
          <label for="sstt-border-width"><?php esc_html_e( 'Progress Ring Width', 'sstt' ); ?></label>
          <small>Stroke width of the progress ring border (1&ndash;10&nbsp;px).</small>
          <div class="sstt-number-wrap">
            <input type="number" name="sstt-border-width" id="sstt-border-width"
                   min="1" max="10" value="<?php echo esc_attr( $border_width ); ?>">
            <span class="sstt-unit">px</span>
          </div>

          <!-- 6. Button Position -->
          <label for="sstt-image-position"><?php esc_html_e( 'Button Position', 'sstt' ); ?></label>
          <small>Choose which side of the screen the button appears on.</small>
          <select name="sstt-image-position" id="sstt-image-position">
            <option value="true"  <?php selected( $position, 'true' );  ?>>Left</option>
            <option value="false" <?php selected( $position, 'false' ); ?>>Right</option>
          </select>

          <!-- 7. Primary Color -->
          <label for="sstt-primary-color"><?php esc_html_e( 'Primary Color', 'sstt' ); ?></label>
          <small>Background color of the button.</small>
          <input type="color" name="sstt-primary-color"
                 value="<?php echo esc_attr( $primary_color ); ?>">

          <!-- 8. Visibility -->
          <label><?php esc_html_e( 'Visibility', 'sstt' ); ?></label>
          <small>Choose on which layouts the button should be hidden.</small>

          <label class="sstt-checkbox">
            <input type="checkbox" name="sstt-hide-mobile" value="1" <?php checked( $hide_mobile, '1' ); ?>>
            <span class="sstt-checkmark"></span>
            <span class="sstt-cb-label">
              Hide on Mobile Layout
              <em>Maximum width is 767&nbsp;px.</em>
            </span>
          </label>

          <label class="sstt-checkbox">
            <input type="checkbox" name="sstt-hide-tablet" value="1" <?php checked( $hide_tablet, '1' ); ?>>
            <span class="sstt-checkmark"></span>
            <span class="sstt-cb-label">
              Hide on Tablet Layout
              <em>Minimum width is 768&nbsp;px and maximum width is 991&nbsp;px.</em>
            </span>
          </label>

          <label class="sstt-checkbox" style="margin-bottom:20px;">
            <input type="checkbox" name="sstt-hide-desktop" value="1" <?php checked( $hide_desktop, '1' ); ?>>
            <span class="sstt-checkmark"></span>
            <span class="sstt-cb-label">
              Hide on Desktop Layout
              <em>Minimum width is 992&nbsp;px.</em>
            </span>
          </label>
          <input type="submit" name="submit" value="<?php esc_attr_e( 'Save Changes', 'sstt' ); ?>">
        </form>
      </div><!-- .sstt_body_area -->

      <div class="sstt_sidebar_area sstt_common">
        <h3 id="title"><?php echo esc_html( '👩‍💻 About Author' ); ?></h3>
        <p><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'img/author.png' ); ?>" alt=""></p>
        <p>I'm <strong><a href="https://alihossain.com/" target="_blank" rel="noopener noreferrer">Ali Hossain</a></strong> a Front End Web developer who is passionate about making error-free websites with 100% client satisfaction.</p>
        <p><a href="https://www.buymeacoffee.com/aliHossain" target="_blank" rel="noopener noreferrer"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'img/buyme.png' ); ?>" alt=""></a></p>
        <h5 id="title"><?php esc_html_e( 'Watch Help Video', 'sstt' ); ?></h5>
        <p><a href="https://youtu.be/8CqTi5iyQJU" target="_blank" rel="noopener noreferrer" class="btn">Watch On YouTube</a></p>
      </div>
    </div>
    <?php
}

/* ---------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------- */
function sstt_is_enabled() {
    return get_option( 'sstt-enabled', 'true' ) === 'true';
}

/* ---------------------------------------------------------------
 * Front-end: CSS asset
 * ------------------------------------------------------------- */
function sstt_enqueue_style() {
    if ( ! sstt_is_enabled() ) return;
    wp_enqueue_style( 'sstt-style', plugins_url( 'css/sstt-style.css', __FILE__ ), array(), SSTT_VERSION );
}
add_action( 'wp_enqueue_scripts', 'sstt_enqueue_style' );

/* ---------------------------------------------------------------
 * Front-end: JS asset
 * ------------------------------------------------------------- */
function sstt_enqueue_scripts() {
    if ( ! sstt_is_enabled() ) return;
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'sstt-plugin-script', plugins_url( 'js/sstt-plugin.js', __FILE__ ), array( 'jquery' ), SSTT_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'sstt_enqueue_scripts' );

/* ---------------------------------------------------------------
 * Front-end: dynamic <style> — size, color, position, breakpoints
 * ------------------------------------------------------------- */
function sstt_theme_color_cus() {
    if ( ! sstt_is_enabled() ) return;

    $color        = sanitize_hex_color( get_option( 'sstt-primary-color', '#22c55e' ) );
    $color        = $color ? $color : '#22c55e';
    $position     = sstt_sanitize_true_false( get_option( 'sstt-image-position', 'false' ) );
    $corner       = sstt_sanitize_true_false( get_option( 'sstt-round-corner', 'false' ) );
    $icon_url     = esc_url_raw( get_option( 'sstt-custom-icon', '' ) );
    $hide_mobile  = get_option( 'sstt-hide-mobile',  '' );
    $hide_tablet  = get_option( 'sstt-hide-tablet',  '' );
    $hide_desktop = get_option( 'sstt-hide-desktop', '' );
    $btn_size     = sstt_sanitize_button_size( get_option( 'sstt-button-size',  50 ) );

    $pos_css    = ( $position === 'true' ) ? 'left:16px;right:auto;' : 'right:16px;';
    $radius_css = ( $corner   === 'true' ) ? 'border-radius:0;' : 'border-radius:50%;';
    $icon_css   = $icon_url
        ? 'background-image:url(' . esc_url( $icon_url ) . ') !important;background-size:60% !important;'
        : '';
    ?>
    <style id="sstt-dynamic-styles">
      #scrollUp {
        background-color: <?php echo esc_html( $color ); ?> !important;
        width:  <?php echo esc_html( (string) $btn_size ); ?>px !important;
        height: <?php echo esc_html( (string) $btn_size ); ?>px !important;
        <?php echo esc_html( $pos_css ); ?>
        <?php echo esc_html( $radius_css ); ?>
        <?php echo esc_html( $icon_css ); ?>
        bottom: 20px;
        position: fixed;
        overflow: visible;
      }
      <?php if ( $hide_mobile ) : ?>
      @media (max-width: 767px) {
        #scrollUp { display: none !important; }
      }
      <?php endif; ?>
      <?php if ( $hide_tablet ) : ?>
      @media (min-width: 768px) and (max-width: 991px) {
        #scrollUp { display: none !important; }
      }
      <?php endif; ?>
      <?php if ( $hide_desktop ) : ?>
      @media (min-width: 992px) {
        #scrollUp { display: none !important; }
      }
      <?php endif; ?>
    </style>
    <?php
}
add_action( 'wp_head', 'sstt_theme_color_cus' );

/* ---------------------------------------------------------------
 * Front-end: init scrollUp + embed progress ring inside button
 * ------------------------------------------------------------- */
function sstt_scroll_script() {
    if ( ! sstt_is_enabled() ) return;

    $btn_size     = sstt_sanitize_button_size( get_option( 'sstt-button-size',  50 ) );
    $border_width = sstt_sanitize_border_width( get_option( 'sstt-border-width', 3  ) );
    // sstt-round-corner: 'false' = round (Yes), 'true' = square (No)
    $is_round = ( sstt_sanitize_true_false( get_option( 'sstt-round-corner', 'false' ) ) !== 'true' );
    ?>
    <script>
    (function($){
        var S        = <?php echo wp_json_encode( $btn_size ); ?>;
        var SW       = <?php echo wp_json_encode( $border_width ); ?>;
        var IS_ROUND = <?php echo wp_json_encode( true === $is_round ); ?>;
        var CX       = S / 2;

        /* circle ring */
        var R        = CX - SW;
        var CIRC     = parseFloat((2 * Math.PI * R).toFixed(4));

        /* square ring: real perimeter of the inset rect */
        var RECT_SIDE  = S - SW;           /* side length after inset */
        var RECT_PERIM = 4 * RECT_SIDE;

        var MAX_DASH = IS_ROUND ? CIRC : RECT_PERIM;

        /* ── init scrollUp – show after 50 px (near-immediate) ── */
        $(document).ready(function(){
            $.scrollUp({ scrollText: '', scrollDistance: 50 });
            attachRing();
        });

        /* ── build SVG ring inside #scrollUp ── */
        function attachRing() {
            var attempts = 0;
            (function tryBuild(){
                var btn = document.getElementById('scrollUp');
                if (!btn) {
                    if (++attempts < 20) setTimeout(tryBuild, 100);
                    return;
                }
                if (document.getElementById('sstt-ring-svg')) return;

                var ns  = 'http://www.w3.org/2000/svg';
                var svg = document.createElementNS(ns, 'svg');
                svg.setAttribute('id', 'sstt-ring-svg');
                svg.setAttribute('viewBox', '0 0 ' + S + ' ' + S);
                svg.style.cssText =
                    'position:absolute;top:0;left:0;width:100%;height:100%;' +
                    'pointer-events:none;overflow:visible;';

                var track, arc;

                if (IS_ROUND) {
                    /* ---- circular ring ---- */
                    svg.style.transform = 'rotate(-90deg)'; /* arc starts from top */

                    track = document.createElementNS(ns, 'circle');
                    track.setAttribute('cx', CX); track.setAttribute('cy', CX);
                    track.setAttribute('r', R);   track.setAttribute('fill', 'none');
                    track.setAttribute('stroke', 'rgba(255,255,255,0.25)');
                    track.setAttribute('stroke-width', SW);
                    svg.appendChild(track);

                    arc = document.createElementNS(ns, 'circle');
                    arc.setAttribute('id', 'sstt-ring-arc');
                    arc.setAttribute('cx', CX); arc.setAttribute('cy', CX);
                    arc.setAttribute('r', R);   arc.setAttribute('fill', 'none');
                    arc.setAttribute('stroke', 'rgba(255,255,255,0.92)');
                    arc.setAttribute('stroke-width', SW);
                    arc.setAttribute('stroke-linecap', 'round');
                    arc.setAttribute('stroke-dasharray',  CIRC);
                    arc.setAttribute('stroke-dashoffset', CIRC);
                    arc.style.transition = 'stroke-dashoffset 0.25s ease-out';
                    svg.appendChild(arc);

                } else {
                    /* ---- square perimeter: <rect> inset by SW/2 so stroke stays inside ---- */
                    var h = SW / 2;

                    track = document.createElementNS(ns, 'rect');
                    track.setAttribute('x', h);  track.setAttribute('y', h);
                    track.setAttribute('width',  RECT_SIDE);
                    track.setAttribute('height', RECT_SIDE);
                    track.setAttribute('rx', 0); track.setAttribute('ry', 0);
                    track.setAttribute('fill',   'none');
                    track.setAttribute('stroke', 'rgba(255,255,255,0.25)');
                    track.setAttribute('stroke-width', SW);
                    svg.appendChild(track);

                    arc = document.createElementNS(ns, 'rect');
                    arc.setAttribute('id', 'sstt-ring-arc');
                    arc.setAttribute('x', h);  arc.setAttribute('y', h);
                    arc.setAttribute('width',  RECT_SIDE);
                    arc.setAttribute('height', RECT_SIDE);
                    arc.setAttribute('rx', 0); arc.setAttribute('ry', 0);
                    arc.setAttribute('fill',   'none');
                    arc.setAttribute('stroke', 'rgba(255,255,255,0.92)');
                    arc.setAttribute('stroke-width', SW);
                    arc.setAttribute('stroke-dasharray',  RECT_PERIM);
                    arc.setAttribute('stroke-dashoffset', RECT_PERIM);
                    arc.style.transition = 'stroke-dashoffset 0.25s ease-out';
                    svg.appendChild(arc);
                }

                btn.style.position = 'fixed';
                btn.appendChild(svg);
            })();
        }

        /* ── update ring on scroll ── */
        $(window).on('scroll.sstt', function(){
            var arc = document.getElementById('sstt-ring-arc');
            if (!arc) { attachRing(); return; }

            var scrollTop = $(window).scrollTop();
            var maxScroll = $(document).height() - $(window).height();
            var pct       = maxScroll > 0 ? scrollTop / maxScroll : 0;
            arc.style.strokeDashoffset = MAX_DASH * (1 - pct);
        });

    }(jQuery));
    </script>
    <?php
}
add_action( 'wp_footer', 'sstt_scroll_script' );

/* ---------------------------------------------------------------
 * Activation Redirect
 * ------------------------------------------------------------- */
register_activation_hook( __FILE__, 'sstt_plugin_activation' );
function sstt_plugin_activation() {
    add_option( 'sstt_plugin_do_activation_redirect', true );
}

add_action( 'admin_init', 'sstt_plugin_redirect' );
function sstt_plugin_redirect() {
    if ( get_option( 'sstt_plugin_do_activation_redirect', false ) ) {
        delete_option( 'sstt_plugin_do_activation_redirect' );
        if ( current_user_can( 'manage_options' ) && ! isset( $_GET['active-multi'] ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=sstt-plugin-option' ) );
            exit;
        }
    }
}
