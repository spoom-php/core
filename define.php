<?php defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Check php version to avoid syntax and other ugly error messages
 */
if( version_compare( PHP_VERSION, '5.4.0' ) < 0 ) die( 'You need at least PHP 5.4, but you only have ' . PHP_VERSION . '.' );

/*
 * State variable that define how site react to exceptions and other type of missbehaviors and what type of errors
 * displayed by PHP. It can be:
 *  
 *  0: Disable all reporting
 *  1: Enable reporting from 'critical' level (PHP: E_COMPILE_ERROR | E_PARSE)
 *  2: Enable reporting from 'error' level (PHP: E_ERROR | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR)
 *  3: Enable reporting from 'warning' level (PHP: E_WARNING | E_COMPILE_WARNING | E_CORE_WARNING | E_USER_WARNING)
 *  4: Enable reporting from 'notice' level (PHP: E_NOTICE | E_USER_NOTICE)
 *  5: Enable reporting from 'info' level (PHP: E_STRICT | E_DEPRECATED | E_USER_DEPRECATED)
 *  6: Enable all reporting (PHP: E_ALL)
 */
define( '_REPORTING', 6 );

/*
 * State variable that define how site handle log messages. It can be:
 *  
 *  0: Disable all log
 *  1: Enable logs from 'critical' level
 *  2: Enable logs from 'error' level
 *  3: Enable logs from 'warning' level
 *  4: Enable logs from 'notice' level
 *  5: Enable logs from 'info' level
 *  6: Enable all log level
 */
define( '_LOGGING', 6 );

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
define( '_URL_ROOT', 'http' . ( _URL_HTTPS ? 's' : '' ) . '://' . $_SERVER[ 'SERVER_NAME' ] . ( !in_array( $_SERVER[ 'SERVER_PORT' ], [ 80 ] ) ? ':' . $_SERVER[ 'SERVER_PORT' ] : '' ) . '/' );
/**
 * The root url with the url path.
 * This is the "real" url with all neccessary parameter
 */
define( '_URL_BASE', _URL_ROOT . ( _URL_PATH != '/' ? _URL_PATH : '' ) );

/**
 * Directory base of the framework.
 * Can be used to include files in php without worry the correct include path
 */
define( '_PATH_BASE', rtrim( dirname( __FILE__ ), '\\/' ) . '/' );
/**
 * Extension directory without the _PATH_BASE
 */
define( '_PATH_EXTENSION', 'extension/' );
/**
 * Tmp directory without the _PATH_BASE
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
  $extension = '';
  for( $i = 0, $count = count( $pieces ); $i < 3 && $i < $count; ) {

    // check if this path is an extension: check existance of the extension directory
    $tmp = $extension . ( $i > 0 ? '-' : '' ) . mb_strtolower( $pieces[ $i ] );
    if( !is_dir( \_PATH_BASE . \_PATH_EXTENSION . $tmp . '/' ) ) break;
    else {

      ++$i;
      $extension = $tmp;
    }
  }

  // further check is performed only if extension definition exist 
  if( !empty( $extension ) ) {

    // finalize the class file path with the remain pieces
    $class     = array_pop( $pieces );
    $pieces = ltrim( implode( '/', array_splice( $pieces, $i ) ) . '/', '/' );
    $directory = \_PATH_BASE . \_PATH_EXTENSION . $extension . '/library/';

    // support for camelCase nested classes
    $matches = [ ];
    preg_match_all( '/((?:^|[A-Z])[a-z]+)/', $class, $matches );
    do {

      // prepare the next path
      $tmp = $pieces . implode( '', $matches[ 0 ] );

      // load the class file with the standard lowercase format
      $file = $directory . mb_strtolower( $tmp ) . '.php';
      if( is_file( $file ) ) include( $file );
      else {

        // check for non-standard, capital letter files (this files probably 3th part libs)
        $file = $directory . $tmp . '.php';
        if( is_file( $file ) ) include( $file );
        else continue;  // skip the break statement if we don't find the file
      }

      // in here we find a matching filename and included it, so the work is done for now
      break;

    } while( array_pop( $matches[ 0 ] ) );
  }

} ) or die( 'Can\'t register the autoload function.' );
