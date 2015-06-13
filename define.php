<?php

/**
 * Check php version to avoid syntax and other ugly error messages
 */
if( version_compare( PHP_VERSION, '5.4.0' ) < 0 ) die( 'You need at least PHP 5.4, but you only have ' . PHP_VERSION . '.' );

/**
 * The level of silence
 */
define( '_LEVEL_NONE', 0 );
/**
 * The level of critical problems
 */
define( '_LEVEL_CRITICAL', 1 );
/**
 * The level of errors
 */
define( '_LEVEL_ERROR', 2 );
/**
 * The level of warnings
 */
define( '_LEVEL_WARNING', 3 );
/**
 * The level of noticable problems but nothing serious
 */
define( '_LEVEL_NOTICE', 4 );
/**
 * The level of informations
 */
define( '_LEVEL_INFO', 5 );
/**
 * The level of all problems (debugging)
 */
define( '_LEVEL_DEBUG', 6 );

/*
 * State variable that define how site react to exceptions and other type of missbehaviors and what type of errors
 * displayed by PHP. It can be:
 *  
 *  _LEVEL_NONE: Silent mode
 *  _LEVEL_CRITICAL: Enable reporting from 'critical' level (PHP: E_COMPILE_ERROR | E_PARSE)
 *  _LEVEL_ERROR: Enable reporting from 'error' level (PHP: E_ERROR | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR)
 *  _LEVEL_WARNING: Enable reporting from 'warning' level (PHP: E_WARNING | E_COMPILE_WARNING | E_CORE_WARNING | E_USER_WARNING)
 *  _LEVEL_NOTICE: Enable reporting from 'notice' level (PHP: E_NOTICE | E_USER_NOTICE)
 *  _LEVEL_INFO: Enable reporting from 'info' level (PHP: E_STRICT | E_DEPRECATED | E_USER_DEPRECATED)
 *  _LEVEL_DEBUG: Debug mode
 */
define( '_REPORT_LEVEL', _LEVEL_NONE );
/*
 * State variable that define how site handle log messages. It can be:
 *  
 *  _LEVEL_NONE: Silent mode
 *  _LEVEL_CRITICAL: Enable logs from 'critical' level
 *  _LEVEL_ERROR: Enable logs from 'error' level
 *  _LEVEL_WARNING: Enable logs from 'warning' level
 *  _LEVEL_NOTICE: Enable logs from 'notice' level
 *  _LEVEL_INFO: Enable logs from 'info' level
 *  _LEVEL_DEBUG: Debug mode
 */
define( '_LOG_LEVEL', _LEVEL_NONE );

/**
 * The path part of the url.
 * This will be the relative path to the index.php ( or an another entry point of the site, tha include this file )
 *
 * @depricated
 */
define( '_URL_PATH', rtrim( dirname( $_SERVER[ 'SCRIPT_NAME' ] ), '\\/ ' ) . '/' );
/**
 * Detect secure http protocol
 *
 * @depricated
 */
define( '_URL_HTTPS', isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' );
/**
 * The server name from the url, configuration or the ip address
 *
 * @depricated
 */
define( '_URL_SERVER', @$_SERVER[ 'SERVER_NAME' ] ?: gethostbyname( gethostname() ) );
/**
 * The port number for the request, or null if not defined
 *
 * @depricated
 */
define( '_URL_PORT', @$_SERVER[ 'SERVER_PORT' ] ?: null );
/**
 * The host name from the url with port definition if necessary
 *
 * @depricated
 */
define( '_URL_HOST', _URL_SERVER . ( !_URL_PORT || _URL_PORT == ( _URL_HTTPS ? 443 : 80 ) ? '' : ( ':' . _URL_PORT ) ) );
/**
 * The root url with protocol, host and port definition ( if neccessary )
 *
 * @depricated
 */
define( '_URL_ROOT', 'http' . ( _URL_HTTPS ? 's' : '' ) . '://' . _URL_HOST . '/' );
/**
 * The root url with the url path.
 * This is the "real" url with all neccessary parameter
 *
 * @depricated
 */
define( '_URL_BASE', _URL_ROOT . ltrim( _URL_PATH, '/' ) );

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
 * The extension id maximum depth. This is the maximum number of parts that is separated by the _EXTENSION_SEPARATOR
 */
define( '_EXTENSION_DEPTH', 3 );
/**
 * The extension id part separator character
 */
define( '_EXTENSION_SEPARATOR', '-' );
/**
 * The extensions library files path relative from the extension base directory
 */
define( '_EXTENSION_LIBRARY', 'library/' );

/**
 * Class autoloader for the site.
 * Handle extension class load based on namespaces
 *
 * @param string $class_name
 */
spl_autoload_register( function ( $class_name ) {

  // explode by namespace separator
  $pieces = explode( '\\', $class_name );

  // find the extension from the class namespace
  $extension = '';
  $length = 0;
  for( $i = 0, $count = count( $pieces ), $tmp = ''; $i < _EXTENSION_DEPTH && $i < $count; ++$i ) {

    // check if this path is an extension: check existance of the extension directory
    $tmp .= ( $i > 0 ? _EXTENSION_SEPARATOR : '' ) . mb_strtolower( $pieces[ $i ] );
    if( is_dir( _PATH_BASE . _PATH_EXTENSION . $tmp . '/' ) ) {

      $length = $i + 1;
      $extension = $tmp;
    }
  }

  // further check is performed only if extension definition exist
  if( !empty( $extension ) ) {

    // finalize the class file path with the remain pieces
    $class     = array_pop( $pieces );
    $pieces = ltrim( implode( '/', array_splice( $pieces, $length ) ) . '/', '/' );
    $directory = _PATH_BASE . _PATH_EXTENSION . $extension . '/' . _EXTENSION_LIBRARY;

    // support for camelCase nested classes
    $matches = [];
    preg_match_all( '/((?:^|[A-Z]+)([a-z]+|$))/', $class, $matches );
    do {

      // prepare the next path
      $tmp = $pieces . implode( '', $matches[ 0 ] );

      // load the class file with the standard lowercase format
      $file = $directory . mb_strtolower( $tmp ) . '.php';
      if( is_file( $file ) ) include( $file );
      else {
        
        // TODO support for lowercase path but case sensitive filename

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
