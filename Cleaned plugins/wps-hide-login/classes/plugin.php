<?php

namespace WPS\WPS_Hide_Login;


class Plugin {

	use Singleton;

	private $wp_login_php;

	protected function init() {
		global $wp_version;

		if ( version_compare( $wp_version, '4.0-RC1-src', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notices_incompatible' ) );
			add_action( 'network_admin_notices', array( $this, 'admin_notices_incompatible' ) );

			return;
		}


		if ( is_multisite() && ! function_exists( 'is_plugin_active_for_network' ) || ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

		}

		if ( is_plugin_active_for_network( 'rename-wp-login/rename-wp-login.php' ) ) {
			deactivate_plugins( WPS_HIDE_LOGIN_BASENAME );
			add_action( 'network_admin_notices', array( $this, 'admin_notices_plugin_conflict' ) );
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}

			return;
		}

		if ( is_plugin_active( 'rename-wp-login/rename-wp-login.php' ) ) {
			deactivate_plugins( WPS_HIDE_LOGIN_BASENAME );
			add_action( 'admin_notices', array( $this, 'admin_notices_plugin_conflict' ) );
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}

			return;
		}

		if ( is_multisite() && is_plugin_active_for_network( WPS_HIDE_LOGIN_BASENAME ) ) {
			add_action( 'wpmu_options', array( $this, 'wpmu_options' ) );
			add_action( 'update_wpmu_options', array( $this, 'update_wpmu_options' ) );

			add_filter( 'network_admin_plugin_action_links_' . WPS_HIDE_LOGIN_BASENAME, array(
				$this,
				'plugin_action_links'
			) );
		}

		if ( is_multisite() ) {
			add_action( 'wp_before_admin_bar_render', array( $this, 'modify_mysites_menu' ), 999 );
		}

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 9999 );
		add_action( 'init', array( $this, 'init_block_access' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'network_admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'wp_loaded', array( $this, 'wp_loaded' ) );
		add_action( 'setup_theme', array( $this, 'setup_theme' ), 1 );

		add_filter( 'plugin_action_links_' . WPS_HIDE_LOGIN_BASENAME, array( $this, 'plugin_action_links' ) );
		add_filter( 'site_url', array( $this, 'site_url' ), 10, 4 );
		add_filter( 'network_site_url', array( $this, 'network_site_url' ), 10, 3 );
		add_filter( 'wp_redirect', array( $this, 'wp_redirect' ), 10, 2 );
		add_filter( 'site_option_welcome_email', array( $this, 'welcome_email' ) );

		remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		add_action( 'admin_menu', array( $this, 'wps_hide_login_menu_page' ) );
		add_action( 'admin_init', array( $this, 'whl_template_redirect' ) );

		add_action( 'template_redirect', array( $this, 'redirect_export_data' ) );
		add_filter( 'login_url', array( $this, 'login_url' ), 10, 3 );

		add_filter( 'user_request_action_email_content', array( $this, 'user_request_action_email_content' ), 999, 2 );

		add_filter( 'site_status_tests', array( $this, 'site_status_tests' ) );

		add_action( 'wp_ajax_dismiss_admin_notice', array( $this, 'dismiss_admin_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts_notifs' ) );
		add_action( 'admin_notices', array( $this, 'warning_options_discussion' ) );
		//add_action( 'admin_notices', array( $this, 'warning_notice_for_comment_registration' ) );

		add_filter( 'manage_sites_action_links', array( $this, 'manage_sites_action_links' ), 10, 3 );
	}

	public function site_status_tests( $tests ) {
		unset( $tests['async']['loopback_requests'] );

		return $tests;
	}

	public function user_request_action_email_content( $email_text, $email_data ) {
		$email_text = str_replace( '###CONFIRM_URL###', esc_url_raw( str_replace( $this->new_login_slug() . '/', 'wp-login.php', $email_data['confirm_url'] ) ), $email_text );

		return $email_text;
	}

	private function use_trailing_slashes() {

		return ( '/' === substr( get_option( 'permalink_structure' ), - 1, 1 ) );

	}

	private function user_trailingslashit( $string ) {

		return $this->use_trailing_slashes() ? trailingslashit( $string ) : untrailingslashit( $string );

	}

	private function wp_template_loader() {

		global $pagenow;

		$pagenow = 'index.php';

		if ( ! defined( 'WP_USE_THEMES' ) ) {

			define( 'WP_USE_THEMES', true );

		}

		wp();

		require_once( ABSPATH . WPINC . '/template-loader.php' );

		die;

	}

	public function modify_mysites_menu() {
		global $wp_admin_bar;

		$all_toolbar_nodes = $wp_admin_bar->get_nodes();

		foreach ( $all_toolbar_nodes as $node ) {
			if ( preg_match( '/^blog-(\d+)(.*)/', $node->id, $matches ) ) {
				$blog_id = $matches[1];
				if ( $login_slug = $this->new_login_slug( $blog_id ) ) {
					if ( ! $matches[2] || '-d' === $matches[2] ) {
						$args       = $node;
						$old_href   = $args->href;
						$args->href = preg_replace( '/wp-admin\/$/', "$login_slug/", $old_href );
						if ( $old_href !== $args->href ) {
							$wp_admin_bar->add_node( $args );
						}
					} elseif ( strpos( $node->href, '/wp-admin/' ) !== false ) {
						$wp_admin_bar->remove_node( $node->id );
					}
				}
			}
		}
	}

	private function new_login_slug( $blog_id = '' ) {
		if ( $blog_id ) {
			if ( $slug = get_blog_option( $blog_id, 'whl_page' ) ) {
				return $slug;
			}
		} else {
			if ( $slug = get_option( 'whl_page' ) ) {
				return $slug;
			} else if ( ( is_multisite() && is_plugin_active_for_network( WPS_HIDE_LOGIN_BASENAME ) && ( $slug = get_site_option( 'whl_page', 'login' ) ) ) ) {
				return $slug;
			} else if ( $slug = 'login' ) {
				return $slug;
			}
		}
	}

	private function new_redirect_slug() {
		if ( $slug = get_option( 'whl_redirect_admin' ) ) {
			return $slug;
		} else if ( ( is_multisite() && is_plugin_active_for_network( WPS_HIDE_LOGIN_BASENAME ) && ( $slug = get_site_option( 'whl_redirect_admin', '404' ) ) ) ) {
			return $slug;
		} else if ( $slug = '404' ) {
			return $slug;
		}
	}

	public function new_login_url( $scheme = null ) {

		$url = apply_filters( 'wps_hide_login_home_url', home_url( '/', $scheme ) );

		if ( get_option( 'permalink_structure' ) ) {

			return $this->user_trailingslashit( $url . $this->new_login_slug() );

		} else {

			return $url . '?' . $this->new_login_slug();

		}

	}

	public function new_redirect_url( $scheme = null ) {

		if ( get_option( 'permalink_structure' ) ) {

			return $this->user_trailingslashit( home_url( '/', $scheme ) . $this->new_redirect_slug() );

		} else {

			return home_url( '/', $scheme ) . '?' . $this->new_redirect_slug();

		}

	}


	private function sanitize_slug_value( $value, $fallback ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return $fallback;
		}

		$value = sanitize_title_with_dashes( wp_unslash( (string) $value ) );

		if ( '' === $value ) {
			return $fallback;
		}

		return $value;
	}

	public function sanitize_whl_page( $value ) {
		$value = $this->sanitize_slug_value( $value, $this->new_login_slug() );

		if ( false !== strpos( $value, 'wp-login' ) || in_array( $value, $this->forbidden_slugs(), true ) ) {
			return $this->new_login_slug();
		}

		return $value;
	}

	public function sanitize_whl_redirect_admin( $value ) {
		$value = $this->sanitize_slug_value( $value, $this->new_redirect_slug() );

		if ( false !== strpos( $value, 'wp-login' ) || $value === $this->new_login_slug() ) {
			return $this->new_redirect_slug();
		}

		return $value;
	}

	public function admin_notices_incompatible() {

		echo '<div class="error notice is-dismissible"><p>' . esc_html__( 'Please upgrade to the latest version of WordPress to activate', 'wps-hide-login' ) . ' <strong>' . esc_html__( 'WPS Hide Login', 'wps-hide-login' ) . '</strong>.</p></div>';

	}

	public function admin_notices_plugin_conflict() {

		echo '<div class="error notice is-dismissible"><p>' . esc_html__( 'WPS Hide Login could not be activated because you already have Rename wp-login.php active. Please uninstall rename wp-login.php to use WPS Hide Login', 'wps-hide-login' ) . '</p></div>';

	}

	/**
	 * Plugin activation
	 */
	public static function activate() {
		//add_option( 'whl_redirect', '1' );

		do_action( 'wps_hide_login_activate' );
	}

	public function wpmu_options() {

		$out = '';

		$out .= '<h3>' . esc_html__( 'WPS Hide Login', 'wps-hide-login' ) . '</h3>';
		$out .= '<p>' . esc_html__( 'This option allows you to set a networkwide default, which can be overridden by individual sites. Simply go to to the site’s permalink settings to change the url.', 'wps-hide-login' ) . '</p>';
		$out .= '<p>' . sprintf( esc_html__( 'Need help? Try the %s.', 'wps-hide-login' ), '<a href="' . esc_url( 'https://wordpress.org/support/plugin/wps-hide-login/' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'support forum', 'wps-hide-login' ) . '</a>' ) . '</p>';
		$out .= '<table class="form-table">';
		$out .= '<tr valign="top">';
		$out .= '<th scope="row"><label for="whl_page">' . __( 'Networkwide default', 'wps-hide-login' ) . '</label></th>';
		$out .= '<td><input id="whl_page" type="text" name="whl_page" value="' . esc_attr( get_site_option( 'whl_page', 'login' ) ) . '"></td>';
		$out .= '<th scope="row"><label for="whl_redirect_admin">' . __( 'Redirection url default', 'wps-hide-login' ) . '</label></th>';
		$out .= '<td><input id="whl_redirect_admin" type="text" name="whl_redirect_admin" value="' . esc_attr( get_site_option( 'whl_redirect_admin', '404' ) ) . '"></td>';
		$out .= '</tr>';
		$out .= '</table>';

		echo $out;

	}

	public function update_wpmu_options() {
		if ( empty( $_POST ) || ! check_admin_referer( 'siteoptions' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_network_options' ) ) {
			return;
		}

		if ( isset( $_POST['whl_page'] ) ) {
			$whl_page = $this->sanitize_whl_page( $_POST['whl_page'] );

			if ( ! empty( $whl_page ) ) {
				flush_rewrite_rules( true );
				update_site_option( 'whl_page', $whl_page );
			}
		}

		if ( isset( $_POST['whl_redirect_admin'] ) ) {
			$whl_redirect_admin = $this->sanitize_whl_redirect_admin( $_POST['whl_redirect_admin'] );

			if ( ! empty( $whl_redirect_admin ) ) {
				flush_rewrite_rules( true );
				update_site_option( 'whl_redirect_admin', $whl_redirect_admin );
			}
		}
	}

	public function admin_init() {

		global $pagenow;

		add_settings_section(
			'wps-hide-login-section',
			'WPS Hide Login',
			array( $this, 'whl_section_desc' ),
			'general'
		);

		add_settings_field(
			'whl_page',
			'<label for="whl_page">' . esc_html__( 'Login url', 'wps-hide-login' ) . '</label>',
			array( $this, 'whl_page_input' ),
			'general',
			'wps-hide-login-section'
		);

		add_settings_field(
			'whl_redirect_admin',
			'<label for="whl_redirect_admin">' . esc_html__( 'Redirection url', 'wps-hide-login' ) . '</label>',
			array( $this, 'whl_redirect_admin_input' ),
			'general',
			'wps-hide-login-section'
		);

		register_setting( 'general', 'whl_page', array( $this, 'sanitize_whl_page' ) );
		register_setting( 'general', 'whl_redirect_admin', array( $this, 'sanitize_whl_redirect_admin' ) );

		if ( get_option( 'whl_redirect' ) ) {

			delete_option( 'whl_redirect' );

			if ( is_multisite()
			     && is_super_admin()
			     && is_plugin_active_for_network( WPS_HIDE_LOGIN_BASENAME ) ) {

				$redirect = network_admin_url( 'settings.php#whl_settings' );

			} else {

				$redirect = admin_url( 'options-general.php#whl_settings' );

			}

			wp_safe_redirect( $redirect );
			die();

		}

	}

	public function whl_section_desc() {

		$out = '<div id="whl_settings">';
		$out .= '<p>' . esc_html__( 'Change the login URL and redirect direct wp-login.php/wp-admin requests from visitors who are not logged in.', 'wps-hide-login' ) . '</p>';
		$out .= '</div>';

		if ( is_multisite()
		     && is_super_admin()
		     && is_plugin_active_for_network( WPS_HIDE_LOGIN_BASENAME ) ) {

			$out .= '<p>' . sprintf(
				esc_html__( 'To set a networkwide default, go to %s.', 'wps-hide-login' ),
				'<a href="' . esc_url( network_admin_url( 'settings.php#whl_settings' ) ) . '">' . esc_html__( 'Network Settings', 'wps-hide-login' ) . '</a>'
			) . '</p>';

		}

		echo $out;

	}

	public function whl_page_input() {

		if ( get_option( 'permalink_structure' ) ) {

			echo '<code>' . esc_url( trailingslashit( home_url() ) ) . '</code> <input id="whl_page" type="text" name="whl_page" value="' . esc_attr( $this->new_login_slug() ) . '">' . ( $this->use_trailing_slashes() ? ' <code>/</code>' : '' );

		} else {

			echo '<code>' . esc_url( trailingslashit( home_url() ) ) . '?</code> <input id="whl_page" type="text" name="whl_page" value="' . esc_attr( $this->new_login_slug() ) . '">';

		}

		echo '<p class="description">' . esc_html__( 'Protect your website by changing the login URL and preventing access to the wp-login.php page and the wp-admin directory to non-connected people.', 'wps-hide-login' ) . '</p>';

	}

	public function whl_redirect_admin_input() {
		if ( get_option( 'permalink_structure' ) ) {

			echo '<code>' . esc_url( trailingslashit( home_url() ) ) . '</code> <input id="whl_redirect_admin" type="text" name="whl_redirect_admin" value="' . esc_attr( $this->new_redirect_slug() ) . '">' . ( $this->use_trailing_slashes() ? ' <code>/</code>' : '' );

		} else {

			echo '<code>' . esc_url( trailingslashit( home_url() ) ) . '?</code> <input id="whl_redirect_admin" type="text" name="whl_redirect_admin" value="' . esc_attr( $this->new_redirect_slug() ) . '">';

		}

		echo '<p class="description">' . esc_html__( 'Redirect URL when someone tries to access the wp-login.php page and the wp-admin directory while not logged in.', 'wps-hide-login' ) . '</p>';
	}

	public function admin_notices() {

		global $pagenow;

		$out = '';

		if ( ! is_network_admin()
		     && $pagenow === 'options-general.php'
		     && isset( $_GET['settings-updated'] )
		     && ! isset( $_GET['page'] ) ) {

			echo '<div class="updated notice is-dismissible"><p>' . sprintf( wp_kses( __( 'Your login page is now here: <strong><a href="%1$s">%2$s</a></strong>. Bookmark this page!', 'wps-hide-login' ), array( 'strong' => array(), 'a' => array( 'href' => array() ) ) ), esc_url( $this->new_login_url() ), esc_html( $this->new_login_url() ) ) . '</p></div>';

		}

	}

	public function plugin_action_links( $links ) {

		if ( is_network_admin()
		     && is_plugin_active_for_network( WPS_HIDE_LOGIN_BASENAME ) ) {

			array_unshift( $links, '<a href="' . esc_url( network_admin_url( 'settings.php#whl_settings' ) ) . '">' . esc_html__( 'Settings', 'wps-hide-login' ) . '</a>' );

		} elseif ( ! is_network_admin() ) {

			array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php#whl_settings' ) ) . '">' . esc_html__( 'Settings', 'wps-hide-login' ) . '</a>' );

		}

		return $links;

	}

	public function redirect_export_data() {
		if ( ! empty( $_GET ) && isset( $_GET['action'] ) && 'confirmaction' === $_GET['action'] && isset( $_GET['request_id'] ) && isset( $_GET['confirm_key'] ) ) {
			$request_id = (int) $_GET['request_id'];
			$key        = sanitize_text_field( wp_unslash( $_GET['confirm_key'] ) );
			$result     = wp_validate_user_request_key( $request_id, $key );
			if ( ! is_wp_error( $result ) ) {
				wp_safe_redirect( add_query_arg( array(
					'action'      => 'confirmaction',
					'request_id'  => $request_id,
					'confirm_key' => $key,
				), $this->new_login_url()
				) );
				exit();
			}
		}
	}

	public function plugins_loaded() {

		global $pagenow;

		$request = parse_url( rawurldecode( $_SERVER['REQUEST_URI'] ) );

		if ( ( strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-login.php' ) !== false
		       || ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === site_url( 'wp-login', 'relative' ) ) )
		     && ! is_admin() ) {

			$this->wp_login_php = true;

			$_SERVER['REQUEST_URI'] = $this->user_trailingslashit( '/' . str_repeat( '-/', 10 ) );

			$pagenow = 'index.php';

		} elseif ( ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === home_url( $this->new_login_slug(), 'relative' ) )
		           || ( ! get_option( 'permalink_structure' )
		                && isset( $_GET[ $this->new_login_slug() ] )
		                && empty( $_GET[ $this->new_login_slug() ] ) ) ) {

			$_SERVER['SCRIPT_NAME'] = $this->new_login_slug();

			$pagenow = 'wp-login.php';

		} elseif ( ( strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-register.php' ) !== false
		             || ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === site_url( 'wp-register', 'relative' ) ) )
		           && ! is_admin() ) {

			$this->wp_login_php = true;

			$_SERVER['REQUEST_URI'] = $this->user_trailingslashit( '/' . str_repeat( '-/', 10 ) );

			$pagenow = 'index.php';
		}

	}

	public function setup_theme() {
		global $pagenow;

		if ( ! is_user_logged_in() && 'customize.php' === $pagenow ) {
			wp_die( __( 'This has been disabled', 'wps-hide-login' ), 403 );
		}
	}

	public function wp_loaded() {
		global $pagenow;

		$request = parse_url( rawurldecode( $_SERVER['REQUEST_URI'] ) );

		do_action( 'wps_hide_login_before_redirect', $request );

		if ( ! ( isset( $_GET['action'] ) && $_GET['action'] === 'postpass' && isset( $_POST['post_password'] ) ) ) {

			if ( is_admin() && ! is_user_logged_in() && ! defined( 'WP_CLI' ) && ! defined( 'DOING_AJAX' ) && ! defined( 'DOING_CRON' ) && $pagenow !== 'admin-post.php' && $request['path'] !== '/wp-admin/options.php' ) {
				wp_safe_redirect( $this->new_redirect_url() );
				die();
			}

			if ( ! is_user_logged_in() && isset( $_GET['wc-ajax'] ) && $pagenow === 'profile.php' ) {
				wp_safe_redirect( $this->new_redirect_url() );
				die();
			}

			if ( ! is_user_logged_in() && isset( $request['path'] ) && $request['path'] === '/wp-admin/options.php' ) {
				header( 'Location: ' . $this->new_redirect_url() );
				die;
			}

			if ( $pagenow === 'wp-login.php' && isset( $request['path'] ) && $request['path'] !== $this->user_trailingslashit( $request['path'] ) && get_option( 'permalink_structure' ) ) {
				wp_safe_redirect( $this->user_trailingslashit( $this->new_login_url() )
				                  . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ) );

				die;

			} elseif ( $this->wp_login_php ) {

				if ( ( $referer = wp_get_referer() )
				     && strpos( $referer, 'wp-activate.php' ) !== false
				     && ( $referer = parse_url( $referer ) )
				     && ! empty( $referer['query'] ) ) {

					parse_str( $referer['query'], $referer );

					@require_once WPINC . '/ms-functions.php';

					if ( ! empty( $referer['key'] )
					     && ( $result = wpmu_activate_signup( $referer['key'] ) )
					     && is_wp_error( $result )
					     && ( $result->get_error_code() === 'already_active'
					          || $result->get_error_code() === 'blog_taken' ) ) {

						wp_safe_redirect( $this->new_login_url()
						                  . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ) );

						die;

					}

				}

				$this->wp_template_loader();

			} elseif ( $pagenow === 'wp-login.php' ) {
				global $error, $interim_login, $action, $user_login;

				$redirect_to = admin_url();

				$requested_redirect_to = '';
				if ( isset( $_REQUEST['redirect_to'] ) ) {
					$requested_redirect_to = $_REQUEST['redirect_to'];
				}

				if ( is_user_logged_in() ) {
					$user = wp_get_current_user();
					if ( ! isset( $_REQUEST['action'] ) ) {
						$logged_in_redirect = apply_filters( 'whl_logged_in_redirect', $redirect_to, $requested_redirect_to, $user );
						wp_safe_redirect( $logged_in_redirect );
						die();
					}
				}

				@require_once ABSPATH . 'wp-login.php';

				die;

			}

		}

	}

	public function site_url( $url, $path, $scheme, $blog_id ) {
		return $this->filter_wp_login_php( $url, $scheme );
	}

	public function network_site_url( $url, $path, $scheme ) {
		return $this->filter_wp_login_php( $url, $scheme );
	}

	public function wp_redirect( $location, $status ) {
		if ( strpos( $location, 'https://wordpress.com/wp-login.php' ) !== false ) {
			return $location;
		}

		return $this->filter_wp_login_php( $location );
	}

	public function filter_wp_login_php( $url, $scheme = null ) {
		global $pagenow;

		$origin_url = $url;

		if ( strpos( $url, 'wp-login.php?action=postpass' ) !== false ) {
			return $url;
		}

		if ( is_multisite() && 'install.php' === $pagenow ) {
			return $url;
		}

		/*if ( strpos( $url, 'wp-admin/?action=postpass' ) === false ) {
			return $url;
		}*/

		if ( strpos( $url, 'wp-login.php' ) !== false && strpos( wp_get_referer(), 'wp-login.php' ) === false ) {

			if ( is_ssl() ) {
				$scheme = 'https';
			}

			$args = explode( '?', $url );

			if ( isset( $args[1] ) ) {

				parse_str( $args[1], $args );

				if ( isset( $args['login'] ) ) {
					$args['login'] = rawurlencode( $args['login'] );
				}

				$url = add_query_arg( $args, $this->new_login_url( $scheme ) );

			} else {

				$url = $this->new_login_url( $scheme );

			}

		}

		if ( isset( $_POST['post_password'] ) ) {
			global $current_user;
			if ( ! is_user_logged_in() && is_wp_error( wp_authenticate_username_password( null, $current_user->user_login, $_POST['post_password'] ) ) ) {
				return $origin_url;
			}
		}

		if ( ! is_user_logged_in() ) {
			if ( file_exists( WP_CONTENT_DIR . '/plugins/gravityforms/gravityforms.php' ) && isset( $_GET['gf_page'] ) ) {
				return $origin_url;
			}
		}

		return $url;
	}

	public function welcome_email( $value ) {
		return $value = str_replace( 'wp-login.php', trailingslashit( get_site_option( 'whl_page', 'login' ) ), $value );
	}

	public function forbidden_slugs() {

		$wp = new \WP;

		return array_merge( $wp->public_query_vars, $wp->private_query_vars );

	}

	/**
	 * Load scripts
	 */
	public function admin_enqueue_scripts( $hook ) {
		return false;
	}

	public function wps_hide_login_menu_page() {
		$title = esc_html__( 'WPS Hide Login', 'wps-hide-login' );

		add_options_page( $title, $title, 'manage_options', 'whl_settings', array(
			$this,
			'settings_page'
		) );
	}

	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		esc_html_e( 'WPS Hide Login', 'wps-hide-login' );
	}

	public function whl_template_redirect() {
		if ( ! empty( $_GET ) && isset( $_GET['page'] ) && 'whl_settings' === sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			wp_safe_redirect( admin_url( 'options-general.php#whl_settings' ) );
			exit();
		}
	}

	/**
	 *
	 * Update url redirect : wp-admin/options.php
	 *
	 * @param $login_url
	 * @param $redirect
	 * @param $force_reauth
	 *
	 * @return string
	 */
	public function login_url( $login_url, $redirect, $force_reauth ) {
		if ( is_404() ) {
			return '#';
		}

		if ( $force_reauth === false ) {
			return $login_url;
		}

		if ( empty( $redirect ) ) {
			return $login_url;
		}

		$redirect = explode( '?', $redirect );

		if ( $redirect[0] === admin_url( 'options.php' ) ) {
			$login_url = admin_url();
		}

		return $login_url;
	}

	/**
	 * Handles Ajax request to persist notices dismissal.
	 */
	public function dismiss_admin_notice() {
		check_ajax_referer( 'wps-hide-login-dismissible-notice' );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_network_options' ) ) {
			wp_die( -1, 403 );
		}

		$option_name        = isset( $_POST['option_name'] ) ? sanitize_key( wp_unslash( $_POST['option_name'] ) ) : '';
		$dismissible_length = isset( $_POST['dismissible_length'] ) ? sanitize_text_field( wp_unslash( $_POST['dismissible_length'] ) ) : '';

		if ( 0 !== strpos( $option_name, 'disable-notice-warning-' ) ) {
			wp_die( -1, 400 );
		}

		$transient = 0;
		if ( 'forever' !== $dismissible_length ) {
			$dismissible_length = ( 0 === absint( $dismissible_length ) ) ? 1 : absint( $dismissible_length );
			$transient          = $dismissible_length * DAY_IN_SECONDS;
			$dismissible_length = strtotime( $dismissible_length . ' days' );
		}

		set_site_transient( $option_name, $dismissible_length, $transient );
		wp_die();
	}

	/**
	 * Load scripts
	 */
	public function admin_enqueue_scripts_notifs( $hook ) {

		if ( 'options-discussion.php' !== $hook ) {
			return false;
		}

		wp_enqueue_script( 'wps-hide-login-functions', WPS_HIDE_LOGIN_URL . 'assets/js/functions.js', array(
			'jquery',
		), false, true );

		wp_localize_script(
			'wps-hide-login-functions',
			'dismissible_notice',
			array(
				'nonce' => wp_create_nonce( 'wps-hide-login-dismissible-notice' ),
			)
		);
	}

	public function warning_options_discussion() {
		if ( ! self::is_admin_notice_active( 'disable-notice-warning-comments' ) ) {
			return false;
		}

		$current_screen = get_current_screen();

		// Vérifie si la page actuelle est options-discussion.php
		if ( $current_screen && $current_screen->id === 'options-discussion' ) :
			/*if ( function_exists( 'wp_admin_notice' ) ) :
				wp_admin_notice(
					__( 'WPS Hide Login : Please note, if you check the comment_registration option "Users must be registered and logged in to comment", the login link will not be hidden on the comment block.', 'wps-hide-login' ),
					array(
						'type'        => 'warning',
						'dismissible' => true,
						'attributes'  => array( 'data-slug' => 'wps-hide-login' )
					)
				);
			else :*/ ?>
            <div class="wps-updates notice notice-warning is-dismissible"
                 data-dismissible="disable-notice-warning-comments-forever">
                <p><?php esc_html_e( 'WPS Hide Login : Please note, if you check the comment_registration option "Users must be registered and logged in to comment", the login link will not be hidden on the comment block.', 'wps-hide-login' ); ?></p>
            </div>
		<?php
			//endif;
		endif;
	}

	public function warning_notice_for_comment_registration() {
		if ( ! self::is_admin_notice_active( 'disable-notice-warning-comment-registration' ) ) {
			return false;
		}

		$comment_registration_option = get_option( 'comment_registration' );

		if ( $comment_registration_option == '1' ) :
			/*if ( function_exists( 'wp_admin_notice' ) ) :
				wp_admin_notice(
					__( 'WPS Hide Login : Please note that the comment_registration option “Users must be registered and logged in to comment” is activated on your site, the connection link will not be hidden on the comments block.', 'wps-hide-login' ),
					array(
						'type'        => 'warning',
						'dismissible' => true,
						'attributes'  => array( 'data-slug' => 'wps-hide-login' )
					)
				);
			else :*/ ?>
            <div class="wps-updates notice notice-warning is-dismissible"
                 data-dismissible="disable-notice-warning-comment-registration-forever">
                <p><?php esc_html_e( 'WPS Hide Login : Please note that the comment_registration option “Users must be registered and logged in to comment” is activated on your site, the connection link will not be hidden on the comments block.', 'wps-hide-login' ); ?></p>
            </div>
		<?php
			//endif;
		endif;
	}

	/**
	 * Is admin notice active?
	 *
	 * @param string $arg data-dismissible content of notice.
	 *
	 * @return bool
	 */
	public static function is_admin_notice_active( $arg ) {
		$array       = explode( '-', $arg );
		$option_name = implode( '-', $array );
		$db_record   = get_site_transient( $option_name );

		if ( 'forever' == $db_record ) {
			return false;
		} elseif ( absint( $db_record ) >= time() ) {
			return false;
		} else {
			return true;
		}
	}

	public function manage_sites_action_links( $actions, $blog_id, $blogname ) {

		$actions['backend'] = sprintf(
			'<a href="%1$s" class="edit">%2$s</a>',
			esc_url( get_site_url( $blog_id, $this->new_login_slug() ) ),
			__( 'Dashboard' )
		);

		return $actions;
	}

	/**
	 * Block access to wp-signup.php and wp-activate.php on non-multisite installations.
	 * This runs on 'init' hook to ensure translations are loaded.
	 */
	public function init_block_access() {
		if ( ! is_multisite()
		     && ( strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-signup' ) !== false
		          || strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-activate' ) !== false ) && apply_filters( 'wps_hide_login_signup_enable', false ) === false ) {

			wp_die( __( 'This feature is not enabled.', 'wps-hide-login' ) );

		}
	}
}