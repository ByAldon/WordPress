<?php

namespace WPS\WPS_Child_Theme_Generator;

// Do not load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

class Helpers {

	use Singleton;

	private static function post_value( $key, $default = '' ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			return $default;
		}

		return wp_unslash( $_POST[ $key ] );
	}

	private static function post_array( $key ) {
		if ( ! isset( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) {
			return array();
		}

		return wp_unslash( $_POST[ $key ] );
	}

	private static function sanitize_php_class_name( $value ) {
		return preg_replace( '/[^A-Za-z0-9_\\\\]/', '', (string) $value );
	}

	private static function clean_css( $css ) {
		$css = (string) $css;
		$css = str_replace( "\0", '', $css );
		$css = str_replace( array( "\r\n", "\r" ), "\n", $css );

		return trim( $css );
	}

	private static function php_string( $value ) {
		return var_export( (string) $value, true );
	}

	private static function php_array( $values ) {
		$values = array_values( array_filter( array_map( 'strval', (array) $values ) ) );

		return var_export( $values, true );
	}

	private static function theme_zip_path( $theme_slug ) {
		return trailingslashit( get_theme_root() ) . sanitize_file_name( $theme_slug ) . '.zip';
	}

	public static function available_parent_themes() {
		$themes  = wp_get_themes();
		$options = '';

		foreach ( $themes as $theme => $theme_data ) {
			$template = $theme_data->get_template();
			if ( $theme !== $template ) {
				continue;
			}

			$this_theme = wp_get_theme( $template );
			$options   .= sprintf(
				'<option value="%s">%s</option>',
				esc_attr( $template ),
				esc_html( $this_theme->get( 'Name' ) )
			);
		}

		return $options;
	}

	public static function create_child_theme() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '<div class="notice notice-error"><p>' . esc_html__( 'You do not have permission to create child themes.', 'wps-child-theme-generator' ) . '</p></div>';
		}

		$nonce = isset( $_POST['form_field_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['form_field_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'form_generator' ) ) {
			return '<div class="notice notice-error"><p>' . esc_html__( 'Security check failed. Please reload the page and try again.', 'wps-child-theme-generator' ) . '</p></div>';
		}

		$parent_theme_slug = sanitize_key( self::post_value( 'b7ectg_parenttheme' ) );
		$parent_theme      = wp_get_theme( $parent_theme_slug );
		if ( empty( $parent_theme_slug ) || ! $parent_theme->exists() || $parent_theme_slug !== $parent_theme->get_template() ) {
			return '<div class="notice notice-error"><p>' . esc_html__( 'Please select a valid parent theme.', 'wps-child-theme-generator' ) . '</p></div>';
		}

		$child_title = sanitize_text_field( self::post_value( 'b7ectg_childtheme' ) );
		if ( '' === $child_title ) {
			$child_title = $parent_theme->get( 'Name' ) . ' Child';
		}

		$theme_url  = esc_url_raw( self::post_value( 'b7ectg_themeurl', home_url( '/' ) ) );
		$author     = sanitize_text_field( self::post_value( 'b7ectg_author', get_bloginfo( 'name' ) ) );
		$author_url = esc_url_raw( self::post_value( 'b7ectg_authurl', home_url( '/' ) ) );

		if ( empty( $theme_url ) ) {
			$theme_url = home_url( '/' );
		}
		if ( empty( $author ) ) {
			$author = get_bloginfo( 'name' );
		}
		if ( empty( $author_url ) ) {
			$author_url = home_url( '/' );
		}

		$theme_slug = sanitize_title( $child_title . '-' . $parent_theme_slug . '-child' );
		if ( empty( $theme_slug ) ) {
			$theme_slug = sanitize_title( $parent_theme_slug . '-child' );
		}

		$theme_root = get_theme_root();
		$theme_dir  = trailingslashit( $theme_root ) . $theme_slug;

		if ( file_exists( $theme_dir ) ) {
			return '<div class="notice notice-error"><p>' . sprintf( esc_html__( 'Child theme %s already exists.', 'wps-child-theme-generator' ), esc_html( $theme_slug ) ) . '</p></div>';
		}

		if ( ! wp_mkdir_p( $theme_dir ) ) {
			return '<div class="notice notice-error"><p>' . esc_html__( 'Failed to create the child theme directory.', 'wps-child-theme-generator' ) . '</p></div>';
		}

		$css_content  = '/*' . PHP_EOL;
		$css_content .= 'Theme Name: ' . $child_title . PHP_EOL;
		$css_content .= 'Theme URI:  ' . $theme_url . PHP_EOL;
		$css_content .= 'Author:     ' . $author . PHP_EOL;
		$css_content .= 'Author URI: ' . $author_url . PHP_EOL;
		$css_content .= 'Template:   ' . $parent_theme_slug . PHP_EOL;
		$css_content .= 'Version:    1.0' . PHP_EOL;
		$css_content .= 'License:    GNU General Public License v2 or later' . PHP_EOL;
		$css_content .= '*/' . PHP_EOL;

		$extra_css = self::clean_css( self::post_value( 'b7ectg_add_css' ) );
		if ( '' !== $extra_css ) {
			$css_content .= PHP_EOL . '/* CSS added with WPS Child Theme Generator */' . PHP_EOL . $extra_css . PHP_EOL;
		}

		$php_content = "<?php\n";
		$php_content .= "/* Child theme generated with WPS Child Theme Generator */\n\n";
		$php_content .= "if ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n\n";
		$php_content .= "if ( ! function_exists( 'b7ectg_theme_enqueue_styles' ) ) {\n\tadd_action( 'wp_enqueue_scripts', 'b7ectg_theme_enqueue_styles' );\n\n\tfunction b7ectg_theme_enqueue_styles() {\n\t\twp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );\n\t\twp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'parent-style' ), wp_get_theme()->get( 'Version' ) );\n\t}\n}\n";

		$image_id = absint( self::post_value( 'b7ectg_img_id' ) );
		if ( $image_id ) {
			$thumbnail_path = get_attached_file( $image_id );
			$filetype       = wp_check_filetype( $thumbnail_path );
			$allowed        = array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );
			if ( $thumbnail_path && file_exists( $thumbnail_path ) && in_array( strtolower( $filetype['ext'] ), $allowed, true ) ) {
				copy( $thumbnail_path, trailingslashit( $theme_dir ) . 'screenshot.' . strtolower( $filetype['ext'] ) );
			}
		}

		if ( isset( $_POST['b7ectg_options'] ) ) {
			$perform_options = self::perform_options();
			foreach ( $perform_options as $_php_content ) {
				$php_content .= $_php_content;
			}
		}

		if ( true !== self::_create_child_theme( $css_content, $php_content, $theme_dir ) ) {
			return '<div class="notice notice-error"><p>' . esc_html__( 'Failed to create files.', 'wps-child-theme-generator' ) . '</p></div>';
		}

		$zip_created = false;
		if ( extension_loaded( 'zip' ) ) {
			$zip_created = self::_create_zip( $theme_dir, $theme_slug );
		}

		$email = sanitize_email( self::post_value( 'be7ctg_send_email' ) );
		if ( $zip_created && isset( $_POST['be7ctg_send'] ) && ! empty( $email ) ) {
			$subject     = __( 'Your child theme', 'wps-child-theme-generator' );
			$message     = sprintf(
				"Hello,\n\nYou can find your child theme attached.\n\nName: %s\nTemplate: %s\n\nAll the best!",
				$child_title,
				$parent_theme_slug
			);
			$attachments = array( self::theme_zip_path( $theme_slug ) );
			wp_mail( $email, $subject, $message, '', $attachments );
		}

		$message = '<div class="notice notice-success"><p>' . esc_html__( 'Child theme created.', 'wps-child-theme-generator' ) . '</p>';
		if ( $zip_created ) {
			$zip_link = self::abs_path_to_url( self::theme_zip_path( $theme_slug ) );
			$message .= '<p><a href="' . esc_url( $zip_link ) . '">' . esc_html__( 'Download child theme', 'wps-child-theme-generator' ) . '</a></p>';
		}
		$message .= '</div>';

		return $message;
	}

	public static function abs_path_to_url( $path = '' ) {
		$url = str_replace(
			wp_normalize_path( untrailingslashit( ABSPATH ) ),
			site_url(),
			wp_normalize_path( $path )
		);

		return esc_url_raw( $url );
	}

	public static function _create_child_theme( $css_content, $php_content, $dir_name ) {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( empty( $wp_filesystem ) ) {
			return false;
		}

		if ( ! $wp_filesystem->put_contents( trailingslashit( $dir_name ) . 'style.css', $css_content, 0644 ) ) {
			return false;
		}
		if ( ! $wp_filesystem->put_contents( trailingslashit( $dir_name ) . 'functions.php', $php_content, 0644 ) ) {
			return false;
		}

		return true;
	}

	public static function _create_zip( $source, $file_name ) {
		$source = realpath( $source );
		if ( false === $source || ! is_dir( $source ) ) {
			return false;
		}

		$zip = new \ZipArchive();
		if ( true !== $zip->open( self::theme_zip_path( $file_name ), \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) ) {
			return false;
		}

		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $source, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $files as $file ) {
			if ( ! $file->isDir() ) {
				$file_path     = $file->getRealPath();
				$relative_path = substr( $file_path, strlen( $source ) + 1 );
				$zip->addFile( $file_path, $relative_path );
			}
		}

		return $zip->close();
	}

	public static function _get_image_sizes() {
		global $_wp_additional_image_sizes;

		$sizes = array();

		foreach ( get_intermediate_image_sizes() as $_size ) {
			if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ), true ) ) {
				$sizes[ $_size ]['width']  = absint( get_option( "{$_size}_size_w" ) );
				$sizes[ $_size ]['height'] = absint( get_option( "{$_size}_size_h" ) );
				$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
				$sizes[ $_size ] = array(
					'width'  => absint( $_wp_additional_image_sizes[ $_size ]['width'] ),
					'height' => absint( $_wp_additional_image_sizes[ $_size ]['height'] ),
					'crop'   => (bool) $_wp_additional_image_sizes[ $_size ]['crop'],
				);
			}
		}

		return $sizes;
	}

	public static function image_sizes_render() {
		foreach ( self::_get_image_sizes() as $name => $size ) {
			echo '<label class="wps-cb-separator"><input type="checkbox" value="' . esc_attr( $name ) . '" name="b7ectg_image_sizes[]" checked="checked"/> ' . esc_html( $name . ' ' . $size['width'] . 'x' . $size['height'] ) . '</label><br />';
		}
	}

	public static function _get_post_types() {
		$args = apply_filters(
			'b7ectg_search_cpt',
			array(
				'public'            => true,
				'show_in_nav_menus' => true,
			)
		);

		return get_post_types( $args, 'objects', 'and' );
	}

	public static function get_post_type_render() {
		$results = '';
		foreach ( self::_get_post_types() as $post_type ) {
			$results .= '<label class="wps-cb-separator"><input type="checkbox" name="b7ectg_search_cpt[]" value="' . esc_attr( $post_type->name ) . '"/> ' . esc_html( $post_type->label ) . '</label>';
		}

		return $results;
	}

	public static function _registered_widgets() {
		global $wp_widget_factory;

		$widgets = array();
		if ( empty( $wp_widget_factory->widgets ) || ! is_array( $wp_widget_factory->widgets ) ) {
			return $widgets;
		}

		foreach ( $wp_widget_factory->widgets as $class_name => $widget ) {
			$widgets[ $class_name ] = is_object( $widget ) ? $widget->name : $widget['name'];
		}

		return $widgets;
	}

	public static function _sanitize_value( $data ) {
		return absint( $data );
	}

	public static function registered_widgets_render() {
		foreach ( self::_registered_widgets() as $widget_class_name => $widget_name ) {
			echo '<label class="wps-cb-separator"> <input type="checkbox" name="b7ectg_widget[]" value="' . esc_attr( $widget_class_name ) . '" checked="checked"> ' . esc_html( $widget_name ) . '</label>';
		}
	}

	public static function perform_options() {
		$image_sizes        = self::_get_image_sizes();
		$posted_image_sizes = array_map( 'sanitize_key', self::post_array( 'b7ectg_image_sizes' ) );
		$unwanted_image_size = array_diff( array_keys( $image_sizes ), $posted_image_sizes );

		$registered_widgets = self::_registered_widgets();
		$posted_widgets     = array_map( array( __CLASS__, 'sanitize_php_class_name' ), self::post_array( 'b7ectg_widget' ) );
		$unwanted_widgets   = array_diff( array_keys( $registered_widgets ), $posted_widgets );

		$image_size_code = '';
		if ( ! empty( $unwanted_image_size ) ) {
			$image_size_code .= "\n\n/* Remove image sizes. */\nif ( ! function_exists( 'b7ectg_remove_image_size' ) ) {\n\tadd_action( 'init', 'b7ectg_remove_image_size' );\n\n\tfunction b7ectg_remove_image_size() {\n";
			foreach ( $unwanted_image_size as $name ) {
				$image_size_code .= "\t\tremove_image_size( " . self::php_string( sanitize_key( $name ) ) . " );\n";
			}
			$image_size_code .= "\t}\n}\n";
		}

		$new_sizes       = self::post_array( 'b7ectg_new_img_size' );
		$media_size_map  = array();
		$new_size_lines  = '';
		if ( ! empty( $new_sizes ) ) {
			foreach ( $new_sizes as $new_size ) {
				if ( ! is_array( $new_size ) ) {
					continue;
				}
				$slug = isset( $new_size['slug'] ) ? sanitize_key( $new_size['slug'] ) : '';
				$name = isset( $new_size['name'] ) ? sanitize_text_field( $new_size['name'] ) : '';
				if ( empty( $slug ) || empty( $name ) ) {
					continue;
				}
				$width  = isset( $new_size['width'] ) ? max( 1, absint( $new_size['width'] ) ) : 100;
				$height = isset( $new_size['height'] ) ? max( 1, absint( $new_size['height'] ) ) : 9999;
				$crop   = isset( $new_size['crop'] ) ? 'true' : 'false';

				$new_size_lines                 .= "\t\tadd_image_size( " . self::php_string( $slug ) . ", {$width}, {$height}, {$crop} );\n";
				$media_size_map[ $slug ] = $name;
			}
		}

		if ( '' !== $new_size_lines ) {
			$image_size_code .= "\n\n/* Image sizes. */\nif ( function_exists( 'add_image_size' ) ) {\n\tadd_action( 'after_setup_theme', 'b7ectg_register_image_sizes' );\n\n\tfunction b7ectg_register_image_sizes() {\n";
			$image_size_code .= $new_size_lines;
			$image_size_code .= "\t}\n}\n";

			$image_size_code .= "\nif ( ! function_exists( 'b7ectg_add_size_to_media_library' ) ) {\n\tadd_filter( 'image_size_names_choose', 'b7ectg_add_size_to_media_library' );\n\n\tfunction b7ectg_add_size_to_media_library( \$sizes ) {\n\t\t\$add_sizes = " . var_export( $media_size_map, true ) . ";\n\t\treturn array_merge( \$sizes, \$add_sizes );\n\t}\n}\n";
		}

		$widget_code = '';
		if ( ! empty( $unwanted_widgets ) ) {
			$widget_code .= "\n\n/* Unregistered widgets. */\nif ( ! function_exists( 'b7ectg_unregister_widget' ) ) {\n\tadd_action( 'widgets_init', 'b7ectg_unregister_widget' );\n\n\tfunction b7ectg_unregister_widget() {\n";
			foreach ( $unwanted_widgets as $widget ) {
				$widget_code .= "\t\tunregister_widget( " . self::php_string( self::sanitize_php_class_name( $widget ) ) . " );\n";
			}
			$widget_code .= "\t}\n}\n";
		}

		if ( isset( $_POST['b7ectg_widget_shortcode'] ) ) {
			$widget_code .= "\nif ( ! is_admin() ) {\n\tadd_filter( 'widget_text', 'do_shortcode', 11 );\n}\n";
		}

		$search_slug = '';
		if ( isset( $_POST['b7ectg_search_slug'] ) ) {
			$search_slug .= "\n\n/* Search slug. */\nif ( ! function_exists( 'b7ectg_rewrite_search_slug' ) ) {\n\tadd_action( 'template_redirect', 'b7ectg_rewrite_search_slug' );\n\n\tfunction b7ectg_rewrite_search_slug() {\n\t\tif ( is_search() && ! empty( \$_GET['s'] ) ) {\n\t\t\twp_safe_redirect( home_url( '/search/' . urlencode( get_query_var( 's' ) ) ) );\n\t\t\texit;\n\t\t}\n\t}\n}\n";
			$search_slug .= "\nif ( ! function_exists( 'b7ectg_rewrite_search_slug_pagination' ) ) {\n\tadd_action( 'init', 'b7ectg_rewrite_search_slug_pagination' );\n\n\tfunction b7ectg_rewrite_search_slug_pagination() {\n\t\tadd_rewrite_rule( 'search(/([^/]+))?(/page/([0-9]+))?/?$', 'index.php?s=\$matches[2]&paged=\$matches[4]', 'top' );\n\t}\n}\n";
		}

		$search_cpt = '';
		$posted_cpt = array_map( 'sanitize_key', self::post_array( 'b7ectg_search_cpt' ) );
		$valid_cpt  = array_intersect( $posted_cpt, array_keys( self::_get_post_types() ) );
		if ( ! empty( $valid_cpt ) ) {
			$search_cpt .= "\n\n/* Search custom post types. */\nif ( ! function_exists( 'be7ctg_search_cpt' ) ) {\n\tadd_action( 'pre_get_posts', 'be7ctg_search_cpt' );\n\n\tfunction be7ctg_search_cpt( \$query ) {\n\t\tif ( \$query->is_search() && \$query->is_main_query() && ! is_admin() ) {\n\t\t\t\$query->set( 'post_type', " . self::php_array( $valid_cpt ) . " );\n\t\t}\n\t}\n}\n";
		}

		$thumb_col = '';
		if ( isset( $_POST['b7ectg_admin_post_thumb_col'] ) ) {
			$thumb_col .= "\n\n/* Thumbnail column. */\n";
			if ( isset( $_POST['b7ectg_admin_post_thumb_col_post'] ) ) {
				$thumb_col .= "add_filter( 'manage_posts_columns', 'b7ectg_posts_thumb_columns', 5 );\nadd_filter( 'manage_posts_columns', 'b7ectg_post_thumb_order', 5 );\nadd_action( 'manage_posts_custom_column', 'b7ectg_posts_custom_thumb_columns', 5, 2 );\n";
			}
			if ( isset( $_POST['b7ectg_admin_post_thumb_col_page'] ) ) {
				$thumb_col .= "add_filter( 'manage_pages_columns', 'b7ectg_posts_thumb_columns', 5 );\nadd_filter( 'manage_pages_columns', 'b7ectg_post_thumb_order', 5 );\nadd_action( 'manage_pages_custom_column', 'b7ectg_posts_custom_thumb_columns', 5, 2 );\n";
			}
			$thumb_col .= "if ( ! function_exists( 'b7ectg_posts_thumb_columns' ) ) {\n\tfunction b7ectg_posts_thumb_columns( \$defaults ) {\n\t\t\$defaults['b7ectg_post_thumbs'] = __( 'Thumbs' );\n\t\treturn \$defaults;\n\t}\n}\n";
			$thumb_col .= "if ( ! function_exists( 'b7ectg_post_thumb_order' ) ) {\n\tfunction b7ectg_post_thumb_order( \$columns ) {\n\t\t\$n_columns = array();\n\t\tforeach ( \$columns as \$key => \$value ) {\n\t\t\tif ( 'title' === \$key ) {\n\t\t\t\t\$n_columns['b7ectg_post_thumbs'] = '';\n\t\t\t}\n\t\t\t\$n_columns[\$key] = \$value;\n\t\t}\n\t\treturn \$n_columns;\n\t}\n}\n";
			$thumb_col .= "if ( ! function_exists( 'b7ectg_posts_custom_thumb_columns' ) ) {\n\tfunction b7ectg_posts_custom_thumb_columns( \$column_name, \$id ) {\n\t\tif ( 'b7ectg_post_thumbs' === \$column_name ) {\n\t\t\tthe_post_thumbnail( array( 100, 100 ) );\n\t\t}\n\t}\n}\n";
		}

		if ( isset( $_POST['b7ectg_admin_post_id_col'] ) ) {
			$the_col_before = isset( $_POST['b7ectg_admin_post_thumb_col'] ) ? 'b7ectg_post_thumbs' : 'title';
			$thumb_col     .= "\n\n/* ID column. */\n";
			if ( isset( $_POST['b7ectg_admin_post_id_col_post'] ) ) {
				$thumb_col .= "add_filter( 'manage_posts_columns', 'b7ectg_posts_id_columns', 5 );\nadd_filter( 'manage_posts_columns', 'b7ectg_post_id_order', 5 );\nadd_action( 'manage_posts_custom_column', 'b7ectg_posts_custom_id_columns', 5, 2 );\n";
			}
			if ( isset( $_POST['b7ectg_admin_post_id_col_page'] ) ) {
				$thumb_col .= "add_filter( 'manage_pages_columns', 'b7ectg_posts_id_columns', 5 );\nadd_filter( 'manage_pages_columns', 'b7ectg_post_id_order', 5 );\nadd_action( 'manage_pages_custom_column', 'b7ectg_posts_custom_id_columns', 5, 2 );\n";
			}
			$thumb_col .= "if ( ! function_exists( 'b7ectg_posts_id_columns' ) ) {\n\tfunction b7ectg_posts_id_columns( \$defaults ) {\n\t\t\$defaults['b7ectg_post_ID'] = __( 'ID' );\n\t\treturn \$defaults;\n\t}\n}\n";
			$thumb_col .= "if ( ! function_exists( 'b7ectg_post_id_order' ) ) {\n\tfunction b7ectg_post_id_order( \$columns ) {\n\t\t\$n_columns = array();\n\t\t\$before = " . self::php_string( $the_col_before ) . ";\n\t\tforeach ( \$columns as \$key => \$value ) {\n\t\t\tif ( \$key === \$before ) {\n\t\t\t\t\$n_columns['b7ectg_post_ID'] = '';\n\t\t\t}\n\t\t\t\$n_columns[\$key] = \$value;\n\t\t}\n\t\treturn \$n_columns;\n\t}\n}\n";
			$thumb_col .= "if ( ! function_exists( 'b7ectg_posts_custom_id_columns' ) ) {\n\tfunction b7ectg_posts_custom_id_columns( \$column_name, \$id ) {\n\t\tif ( 'b7ectg_post_ID' === \$column_name ) {\n\t\t\techo esc_html( absint( \$id ) );\n\t\t}\n\t}\n}\n";
		}

		$supports_remove = '';
		if ( isset( $_POST['b7ectg_supports_block'] ) ) {
			global $_wp_post_type_features;

			$posted_supports = self::post_array( 'b7ectg_support' );
			$remove_map       = array();
			foreach ( (array) $_wp_post_type_features as $cpt_type => $supports ) {
				$all_supports    = array_keys( (array) $supports );
				$posted_for_type = isset( $posted_supports[ $cpt_type ] ) && is_array( $posted_supports[ $cpt_type ] ) ? array_keys( $posted_supports[ $cpt_type ] ) : array();
				$posted_for_type = array_map( 'sanitize_key', $posted_for_type );
				$remove          = array_diff( $all_supports, $posted_for_type );
				if ( ! empty( $remove ) ) {
					$remove_map[ sanitize_key( $cpt_type ) ] = array_map( 'sanitize_key', $remove );
				}
			}

			if ( ! empty( $remove_map ) ) {
				$supports_remove .= "\n\n/* Remove supports. */\nif ( ! function_exists( 'b7ectg_remove_supports' ) ) {\n\tadd_action( 'init', 'b7ectg_remove_supports' );\n\n\tfunction b7ectg_remove_supports() {\n";
				foreach ( $remove_map as $cpt_type => $supports ) {
					foreach ( $supports as $support ) {
						$supports_remove .= "\t\tremove_post_type_support( " . self::php_string( $cpt_type ) . ", " . self::php_string( $support ) . " );\n";
					}
				}
				$supports_remove .= "\t}\n}\n";
			}
		}

		return array(
			'image_sizes'         => $image_size_code,
			'thumb_post_col'      => $thumb_col,
			'widget'              => $widget_code,
			'rewrite_search_slug' => $search_slug,
			'search_cpt'          => $search_cpt,
			'remove_support'      => $supports_remove,
		);
	}
}
