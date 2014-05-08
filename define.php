<?php defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Check php version for a nicer message then the namespace caused syntax error
 */
if( version_compare( PHP_VERSION, '5.3.0' ) < 0 ) die('This framework needs at least PHP 5.3 to run.');

/**
 * Engine version number. The versioning system ( this will applied to all extension ):
 *
 *      <main>.<sub>.<fixing>
 *
 * Main versions DO NOT need to be backward compatible, other MUST!
 * Fixing version is for really small changes and bug fixes.
 */
define( '_VERSION', '1.0.4' );

/*
 * State variable that define how site react to exceptions and other type of missbehaviors. It can be:
 *  0: Development state, every little notice and exception shown
 *  1: Production state, no exception shown try to ignore or solve them
 *
 *  ...more state coming when neccessary
 */
define( '_SITE_STATE', 0 );
/**
 * Default site localization.
 * It can be changed runtime with \Engine\Extension\Localization::setLocalization() static method.
 */
define( '_SITE_LOCALIZATION', 'en' );

/**
 * Detect secure http protocol
 */
define( '_URL_HTTPS', isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' );
/**
 * The path part of the url.
 * This will be the relative path to the index.php ( or an another entry point of the site, tha include this file )
 */
define( '_URL_PATH', trim( dirname( $_SERVER[ 'SCRIPT_NAME' ] ), '/' ) . '/' );
/**
 * The root url with protocol, host and port definition ( if neccessary )
 */
define( '_URL_ROOT', 'http' . ( _URL_HTTPS ? 's' : '' ) . '://' . $_SERVER[ 'SERVER_NAME' ] . ( !in_array( $_SERVER[ 'SERVER_PORT' ], array( 80 ) ) ? ':' . $_SERVER[ 'SERVER_PORT' ] : '' ) . '/' );
/**
 * The root url with the url path.
 * This is the "real" url with all neccessary parameter
 */
define( '_URL_BASE', _URL_ROOT . ( strlen( _URL_PATH ) != '/' ? _URL_PATH : '' ) );

/**
 * Directory root of the site.
 * Can be used to include files in php without worry the correct include path
 */
define( '_PATH', dirname( __FILE__ ) . '/' );
/**
 * Engine directory without the _PATH
 */
define( '_PATH_ENGINE', 'engine/' );
/**
 * Extension directory without the _PATH
 */
define( '_PATH_EXTENSION', 'extension/' );
/**
 * Tmp directory without the _PATH
 */
define( '_PATH_TMP', 'tmp/' );

/**
 * Class autoloader for the site.
 * Handle engine and extension class load based on namespaces
 *
 * @param string $class_name
 */
function __webengine_loader( $class_name ) {

  // explode by namespace separator
  $pieces = explode( '\\', strtolower( $class_name ) );

  // check the pieces count, if less then 2 it's not an extension ( except the engine )
  if( count( $pieces ) > 2 || $pieces[ 0 ] === 'engine' ) {

    // load class file
    if( $pieces[ 0 ] === 'engine' ) $file = \_PATH . \_PATH_ENGINE . implode( '/', array_splice( $pieces, 1 ) ) . '.lib' . '.php';
    else $file = \_PATH . \_PATH_EXTENSION . $pieces[ 0 ] . '/' . $pieces[ 1 ] . '/' . 'library' . '/' . implode( '/', array_splice( $pieces, 2 ) ) . '.lib' . '.php';

    // load the class file
    if( is_file( $file ) ) require $file;

    // re-check existance
    if( !class_exists( $class_name, false ) ) {
      // TODO throw exception, or somethin'..
    }
  }
}
spl_autoload_register( '__webengine_loader' );