<?php
/**
 * These functions are needed to load WordPress.
 *
 * @package WordPress
 */

/**
 * Returns the HTTP protocol sent by the server.
 *
 * @since 4.4.0
 *
 * @return string The HTTP protocol. Default: HTTP/1.0.
 */
function wp_get_server_protocol() {
 $protocol = isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : '';

 if ( ! in_array( $protocol, array( 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0', 'HTTP/3' ), true ) ) {
  $protocol = 'HTTP/1.0';
 }

 return $protocol;
}

/**
 * Fixes $_SERVER variables for various setups.
 *
 * @since 3.0.0
 * @access private
 *
 * @global string $PHP_SELF The filename of the currently executing script,
 *                          relative to the document root.
 */
function wp_fix_server_vars() {
 global $PHP_SELF;

 $default_server_values = array(
  'SERVER_SOFTWARE' => '',
  'REQUEST_URI'     => '',
 );

 $_SERVER = array_merge( $default_server_values, $_SERVER );

 // Fix for IIS when running with PHP ISAPI.
 if ( empty( $_SERVER['REQUEST_URI'] )
  || ( 'cgi-fcgi' !== PHP_SAPI && preg_match( '/^Microsoft-IIS\//', $_SERVER['SERVER_SOFTWARE'] ) )
 ) {

  if ( isset( $_SERVER['HTTP_X_ORIGINAL_URL'] ) ) {
   // IIS Mod-Rewrite.
   $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
  } elseif ( isset( $_SERVER['HTTP_X_REWRITE_URL'] ) ) {
   // IIS Isapi_Rewrite.
   $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
  } else {
   // Use ORIG_PATH_INFO if there is no PATH_INFO.
   if ( ! isset( $_SERVER['PATH_INFO'] ) && isset( $_SERVER['ORIG_PATH_INFO'] ) ) {
    $_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
   }

   // Some IIS + PHP configurations put the script-name in the path-info (no need to append it twice).
   if ( isset( $_SERVER['PATH_INFO'] ) ) {
    if ( $_SERVER['PATH_INFO'] === $_SERVER['SCRIPT_NAME'] ) {
     $_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
    } else {
     $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
    }
   }

   // Append the query string if it exists and isn't null.
   if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
    $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
   }
  }
 }

 // Fix for PHP as CGI hosts that set SCRIPT_FILENAME to something ending in php.cgi for all requests.
 if ( isset( $_SERVER['SCRIPT_FILENAME'] ) && str_ends_with( $_SERVER['SCRIPT_FILENAME'], 'php.cgi' ) ) {
  $_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];
 }

 // Fix for Dreamhost and other PHP as CGI hosts.
 if ( isset( $_SERVER['SCRIPT_NAME'] ) && str_contains( $_SERVER['SCRIPT_NAME'], 'php.cgi' ) ) {
  unset( $_SERVER['PATH_INFO'] );
 }

 // Fix empty PHP_SELF.
 $PHP_SELF = $_SERVER['PHP_SELF'];
 if ( empty( $PHP_SELF ) ) {
  $_SERVER['PHP_SELF'] = preg_replace( '/(\?.*)?$/', '', $_SERVER['REQUEST_URI'] );
  $PHP_SELF            = $_SERVER['PHP_SELF'];
 }

 wp_populate_basic_auth_from_authorization_header();
}

/**
 * Populates the Basic Auth server details from the Authorization header.
 *
 * Some servers running in CGI or FastCGI mode don't pass the Authorization
 * header on to WordPress.  If it's been rewritten to the HTTP_AUTHORIZATION header,
 * fill in the proper $_SERVER variables instead.
 *
 * @since 5.6.0
 */
function wp_populate_basic_auth_from_authorization_header() {
 // If we don't have anything to pull from, return early.
 if ( ! isset( $_SERVER['HTTP_AUTHORIZATION'] ) && ! isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
  return;
 }

 // If either PHP_AUTH key is already set, do nothing.
 if ( isset( $_SERVER['PHP_AUTH_USER'] ) || isset( $_SERVER['PHP_AUTH_PW'] ) ) {
  return;
 }

 // From our prior conditional, one of these must be set.
 $header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? $_SERVER['HTTP_AUTHORIZATION'] : $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];

 // Test to make sure the pattern matches expected.
 if ( ! preg_match( '%^Basic [a-z\d/+]*={0,2}$%i', $header ) ) {
  return;
 }

 // Removing Basic  the token would start six characters in.
 $token    = substr( $header, 6 );
 $userpass = base64_decode( $token );

 // There must be at least one colon in the string.
 if ( ! str_contains( $userpass, ':' ) ) {
  return;
 }

 list( $user, $pass ) = explode( ':', $userpass, 2 );

 // Now shove them in the proper keys where we're expecting later on.
 $_SERVER['PHP_AUTH_USER'] = $user;
 $_SERVER['PHP_AUTH_PW']   = $pass;
}

/**
 * Checks for the required PHP version, and the mysqli extension or
 * a database drop-in.
 *
 * Dies if requirements are not met.
 *
 * @since 3.0.0
 * @access private
 *
 * @global string $required_php_version The required PHP version string.
 * @global string $wp_version           The WordPress version string.
 */
function wp_check_php_mysql_versions() {
 global $required_php_version, $wp_version;

 $php_version = PHP_VERSION;

 if ( version_compare( $required_php_version, $php_version, '>' ) ) {
  $protocol = wp_get_server_protocol();
  header( sprintf( '%s 500 Internal Server Error', $protocol ), true, 500 );
  header( 'Content-Type: text/html; charset=utf-8' );
  printf(
   'Your server is running PHP version %1$s but WordPress %2$s requires at least %3$s.',
   $php_version,
   $wp_version,
   $required_php_version
  );
  exit( 1 );
 }

 // This runs before default constants are defined, so we can't assume WP_CONTENT_DIR is set yet.
 $wp_content_dir = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content';

 if ( ! function_exists( 'mysqli_connect' )
  && ! file_exists( $wp_content_dir . '/db.php' )
 ) {
  require_once ABSPATH . WPINC . '/functions.php';
  wp_load_translations_early();

  $message = '<p>' . __( 'Your PHP installation appears to be missing the MySQL extension which is required by WordPress.' ) . "</p>\n";

  $message .= '<p>' . sprintf(
   /* translators: %s: mysqli. */
   __( 'Please check that the %s PHP extension is installed and enabled.' ),
   '<code>mysqli</code>'
  ) . "</p>\n";

  $message .= '<p>' . sprintf(
   /* translators: %s: Support forums URL. */
   __( 'If you are unsure what these terms mean you should probably contact your host. If you still need help you can always visit the <a href="%s">WordPress support forums</a>.' ),
   __( 'https://wordpress.org/support/forums/' )
  ) . "</p>\n";

  $args = array(
   'exit' => false,
   'code' => 'mysql_not_found',
  );
  wp_die(
   $message,
   __( 'Requirements Not Met' ),
   $args
  );
  exit( 1 );
 }
}

/**
 * Retrieves the current environment type.
 *
 * The type can be set via the WP_ENVIRONMENT_TYPE global system variable,
 * or a constant of the same name.
 *
 * Possible values are 'local', 'development', 'staging', and 'production'.
 * If not set, the type defaults to 'production'.
 *
 * @since 5.5.0
 * @since 5.5.1 Added the 'local' type.
 * @since 5.5.1 Removed the ability to alter the list of types.
 *
 * @return string The current environment type.
 */
function wp_get_environment_type() {
 static $current_env = '';

 if ( ! defined( 'WP_RUN_CORE_TESTS' ) && $current_env ) {
  return $current_env;
 }

 $wp_environments = array(
  'local',
  'development',
  'staging',
  'production',
 );

 // Add a note about the deprecated WP_ENVIRONMENT_TYPES constant.
 if ( defined( 'WP_ENVIRONMENT_TYPES' ) && function_exists( '_deprecated_argument' ) ) {
  if ( function_exists( '__' ) ) {
   /* translators: %s: WP_ENVIRONMENT_TYPES */
   $message = sprintf( __( 'The %s constant is no longer supported.' ), 'WP_ENVIRONMENT_TYPES' );
  } else {
   $message = sprintf( 'The %s constant is no longer supported.', 'WP_ENVIRONMENT_TYPES' );
  }

  _deprecated_argument(
   'define()',
   '5.5.1',
   $message
  );
 }

 // Check if the environment variable has been set, if getenv is available on the system.
 if ( function_exists( 'getenv' ) ) {
  $has_env = getenv( 'WP_ENVIRONMENT_TYPE' );
  if ( false !== $has_env ) {
   $current_env = $has_env;
  }
 }

 // Fetch the environment from a constant, this overrides the global system variable.
 if ( defined( 'WP_ENVIRONMENT_TYPE' ) && WP_ENVIRONMENT_TYPE ) {
  $current_env = WP_ENVIRONMENT_TYPE;
 }

 // Make sure the environment is an allowed one, and not accidentally set to an invalid value.
 if ( ! in_array( $current_env, $wp_environments, true ) ) {
  $current_env = 'production';
 }

 return $current_env;
}

/**
 * Retrieves the current development mode.
 *
 * The development mode affects how certain parts of the WordPress application behave,
 * which is relevant when developing for WordPress.
 *
 * Development mode can be set via the WP_DEVELOPMENT_MODE constant in `wp-config.php`.
 * Possible values are 'core', 'plugin', 'theme', 'all', or an empty string to disable
 * development mode. 'all' is a special value to signify that all three development modes
 * ('core', 'plugin', and 'theme') are enabled.
 *
 * Development mode is considered separately from WP_DEBUG and wp_get_environment_type().
 * It does not affect debugging output, but rather functional nuances in WordPress.
 *
 * This function retrieves the currently set development mode value. To check whether
 * a specific development mode is enabled, use wp_is_development_mode().
 *
 * @since 6.3.0
 *
 * @return string The current development mode.
 */
function wp_get_development_mode() {
 static $current_mode = null;

 if ( ! defined( 'WP_RUN_CORE_TESTS' ) && null !== $current_mode ) {
  return $current_mode;
 }

 $development_mode = WP_DEVELOPMENT_MODE;

 // Exclusively for core tests, rely on the $_wp_tests_development_mode global.
 if ( defined( 'WP_RUN_CORE_TESTS' ) && isset( $GLOBALS['_wp_tests_development_mode'] ) ) {
  $development_mode = $GLOBALS['_wp_tests_development_mode'];
 }

 $valid_modes = array(
  'core',
  'plugin',
  'theme',
  'all',
  '',
 );

 if ( ! in_array( $development_mode, $valid_modes, true ) ) {
  $development_mode = '';
 }

 $current_mode = $development_mode;

 return $current_mode;
}

/**
 * Checks whether the site is in the given development mode.
 *
 * @since 6.3.0
 *
 * @param string $mode De
