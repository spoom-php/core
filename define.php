<?php defined( '_PROTECT' ) or die( 'DENIED!' );
/*
 * The versioning system ( this will applied to all extension included the engine ):
 *
 *      <major>.<minor>.<fix>.<build>
 *
 * major: Massive changes of the system. Backwards compatibility isn't necessary!
 * minor: New features, as well as bigger changes (that stay compatible in the major version) in the system.
 * fix: Bugfixes, as well as smaller changes.
 * build: Text or configuration modification.
 *
 * Zero major is a version, which is still under development. The minor version doesn't have to
 * be compatible backwards in that case. Details: http://semver.org/
 */

/**
 * Check php version to avoid syntax and other ugly error messages
 */
if( version_compare( PHP_VERSION, '5.4.0' ) < 0 ) die( 'You need at least PHP 5.4, but you only have ' . PHP_VERSION . '.' );

/*
 * State variable that define how site react to exceptions and other type of missbehaviors. It can be:
 *  0: Production state, no exception shown try to ignore or solve them
 *  1: Development state, every little notice and exception shown
 *
 *  ...more state coming when neccessary
 */
define( '_REPORTING', 0 );

/**
 * Detect secure http protocol
 */
define( '_URL_HTTPS', isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' );
/**
 * The path part of the url.
 * This will be the relative path to the index.php ( or an another entry point of the site, tha include this file )
 */
define( '_URL_PATH', rtrim( dirname( $_SERVER[ 'SCRIPT_NAME' ] ), '\\/ ' ) . '/' );
/**
 * The root url with protocol, host and port definition ( if neccessary )
 */
define( '_URL_ROOT', 'http' . ( _URL_HTTPS ? 's' : '' ) . '://' . $_SERVER[ 'SERVER_NAME' ] . ( !in_array( $_SERVER[ 'SERVER_PORT' ], array( 80 ) ) ? ':' . $_SERVER[ 'SERVER_PORT' ] : '' ) . '/' );
/**
 * The root url with the url path.
 * This is the "real" url with all neccessary parameter
 */
define( '_URL_BASE', _URL_ROOT . ( _URL_PATH != '/' ? _URL_PATH : '' ) );

/**
 * Directory root of the framework.
 * Can be used to include files in php without worry the correct include path
 */
define( '_PATH', rtrim( dirname( __FILE__ ), '\\/' ) . '/' );
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
spl_autoload_register( function ( $class_name ) {

  // explode by namespace separator
  $pieces = explode( '\\', $class_name );

  // find the extension from the class namespace
  $extension = null;
  for( $i = 0, $count = count( $pieces ); $i < 3 && $i < $count; ) {

    // check if this path is an extension: check existance of manifest file with any file extension
    $tmp = $extension . ( $i > 0 ? '-' : '' ) . strtolower( $pieces[ $i ] );
    if( !count( glob( \_PATH . \_PATH_EXTENSION . $tmp . '/configuration/manifest.*' ) ) ) break;
    else {

      ++$i;
      $extension = $tmp;
    }
  }

  // finalize the class file path with the reamining pieces
  $directory = \_PATH_EXTENSION . $extension . '/library/';
  $pieces = array_splice( $pieces, $i );

  // load the class file with the standard (.php) format
  $file = \_PATH . $directory . strtolower( implode( '/', $pieces ) );
  if( is_file( $file . '.php' ) ) include( $file . '.php' );
  else {

    // check for non-standard capital letter files (there is no need for legacy check, cause this files probably 3th part libs)
    $file = \_PATH . $directory . implode( '/', $pieces );
    if( is_file( $file . '.php' ) ) include( $file . '.php' );
  }

} ) or die( 'Can\'t register the autoload function.' );