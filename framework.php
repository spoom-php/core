<?php

/**
 * Directory base of the framework.
 * Can be used to include files in php without worry the correct include path
 *
 * TODO This can be replaced with the \Framework::PATH_BASE after PHP5.6
 */
define( '_PATH_BASE', rtrim( dirname( __FILE__ ), '\\/' ) . '/' );

/**
 * Class Framework
 */
class Framework {

  /**
   * The minimum PHP version for the framework
   */
  const DEPENDENCY_PHP = '5.4.0';

  /**
   * The level of silence
   */
  const LEVEL_NONE = 0;
  /**
   * The level of critical problems
   */
  const LEVEL_CRITICAL = 1;
  /**
   * The level of errors
   */
  const LEVEL_ERROR = 2;
  /**
   * The level of warnings
   */
  const LEVEL_WARNING = 3;
  /**
   * The level of noticable problems but nothing serious
   */
  const LEVEL_NOTICE = 4;
  /**
   * The level of informations
   */
  const LEVEL_INFO = 5;
  /**
   * The level of all problems (debugging)
   */
  const LEVEL_DEBUG = 6;

  /**
   * Directory base of the framework.
   * Can be used to include files in php without worry the correct include path
   */
  const PATH_BASE = _PATH_BASE;
  /**
   * Extension directory without the _PATH_BASE
   */
  const PATH_EXTENSION = 'extension/';
  /**
   * Tmp directory without the _PATH_BASE
   */
  const PATH_TMP = 'tmp/';

  /**
   * The extension id maximum depth. This is the maximum number of parts that is separated by the _EXTENSION_SEPARATOR
   */
  const EXTENSION_DEPTH = 3;
  /**
   * The extension id part separator character
   */
  const EXTENSION_SEPARATOR = '-';
  /**
   * The extensions library files path relative from the extension base directory
   */
  const EXTENSION_LIBRARY = 'library/';

  /**
   * Link level names to their numeric representation
   *
   * @var int[string]
   */
  private static $LEVEL = [
    'none'     => self::LEVEL_NONE,
    'critical' => self::LEVEL_CRITICAL,
    'error'    => self::LEVEL_ERROR,
    'warning'  => self::LEVEL_WARNING,
    'notice'   => self::LEVEL_NOTICE,
    'info'     => self::LEVEL_INFO,
    'debug'    => self::LEVEL_DEBUG
  ];

  /**
   * The framework log level
   *
   * @var int
   */
  private static $level_log = self::LEVEL_WARNING;
  /**
   * The framework report level
   *
   * @var int
   */
  private static $level_report = self::LEVEL_NONE;

  /**
   * Store the custom namespace connections to their root paths
   *
   * @var array[string]string
   */
  private static $connection = [ ];

  /**
   * Run the framework setup (autoloader, log and report configuration...etc)
   *
   * @param callable $main A runnable that will be called after the framework setup
   *
   * @throws Exception
   */
  public static function run( callable $main ) {

    // check php version to avoid syntax and other ugly error messages
    if( version_compare( PHP_VERSION, self::DEPENDENCY_PHP ) < 0 ) {
      throw new \Exception( 'You need at least PHP ' . self::DEPENDENCY_PHP . ', but you only have ' . PHP_VERSION . '.' );
    }

    // register the autoloader
    try {
      spl_autoload_register( function ( $class ) {
        \Framework::import( $class );
      } );
    } catch( \Exception $e ) {
      throw new \Exception( "Can't register the autoload function." );
    }

    // setup log and report levels
    self::logLevel( self::$level_log );
    self::reportLevel( self::$level_report );

    // call the main function
    $main();
  }

  /**
   * Get or set the log level
   *
   * @param int|string|null $value The new log level
   *
   * @return int
   */
  public static function logLevel( $value = null ) {

    if( $value !== null ) {
      self::$level_log = is_string( $value ) ? self::$LEVEL[ $value ] : $value;
    }

    return self::$level_log;
  }
  /**
   * Get or set the report level
   *
   * @param int|string|null $value The new report level
   *
   * @return int
   */
  public static function reportLevel( $value = null ) {

    if( $value !== null ) {

      self::$level_report = is_string( $value ) ? self::$LEVEL[ $value ] : $value;

      // setup error reporting in PHP
      $reporting = 0;
      switch( self::$level_report ) {
        case _LEVEL_NONE:

          error_reporting( -1 );
          ini_set( 'display_errors', 0 );

          break;

        /** @noinspection PhpMissingBreakStatementInspection */
        case _LEVEL_DEBUG:
          $reporting = E_ALL;
        /** @noinspection PhpMissingBreakStatementInspection */
        case _LEVEL_INFO:
          $reporting |= E_STRICT | E_DEPRECATED | E_USER_DEPRECATED;
        /** @noinspection PhpMissingBreakStatementInspection */
        case _LEVEL_NOTICE:
          $reporting |= E_NOTICE | E_USER_NOTICE;
        /** @noinspection PhpMissingBreakStatementInspection */
        case _LEVEL_WARNING:
          $reporting |= E_WARNING | E_COMPILE_WARNING | E_CORE_WARNING | E_USER_WARNING;
        /** @noinspection PhpMissingBreakStatementInspection */
        case _LEVEL_ERROR:
          $reporting |= E_ERROR | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
        /** @noinspection PhpMissingBreakStatementInspection */
        case _LEVEL_CRITICAL:
          $reporting |= E_COMPILE_ERROR | E_PARSE;
        /** @noinspection PhpMissingBreakStatementInspection */
        default:

          ini_set( 'display_errors', 1 );
          error_reporting( $reporting );
      }
    }

    return self::$level_report;
  }

  /**
   * Search the extension name from the input string array. The strings, used for the extension name will be sliced from the input array
   *
   * @param string[] $input The input strings
   *
   * @return string The extension name if any
   */
  public static function search( array &$input ) {

    // find the extension from the class namespace
    $name   = '';
    $length = 0;
    for( $i = 0, $count = count( $input ), $tmp = ''; $i < self::EXTENSION_DEPTH && $i < $count; ++$i ) {

      // check if this path is an extension: check existance of the extension directory
      $tmp .= ( $i > 0 ? self::EXTENSION_SEPARATOR : '' ) . mb_strtolower( $input[ $i ] );
      if( is_dir( self::PATH_BASE . self::PATH_EXTENSION . $tmp . '/' ) ) {

        $length = $i + 1;
        $name   = $tmp;
      }
    }

    if( !$length ) return '';
    else {

      $input = array_slice( $input, $length );
      return $name;
    }
  }

  /**
   * Add custom namespace root directory for the importer
   *
   * @param string $namespace The namespace definition
   * @param string $path      The path for the namespace
   */
  public static function connect( $namespace, $path ) {
    self::$connection[ $namespace ] = $path;
    uksort( self::$connection, function ( $a, $b ) {
      return strlen( $b ) - strlen( $a );
    } );
  }
  /**
   * Remove a custom namespace definition
   *
   * @param string $namespace The namespace to remove
   */
  public static function disconnect( $namespace ) {
    unset( self::$connection[ $namespace ] );
  }
  /**
   * Import class files based on the class name and the namespace
   *
   * @param string $class The class fully qualified name
   *
   * @return bool True only if the class is exists after the import
   */
  public static function import( $class ) {

    // do not import class that is exists already
    if( class_exists( $class, false ) ) return true;
    else {

      // fix for absolute class definitions
      $class = trim( $class, '\\' );

      // try first the custom paths (custom autoload path support)
      foreach( self::$connection as $namespace => $directory ) {

        $namespace = trim( $namespace, '\\' ) . '\\';
        if( strpos( $class, $namespace ) === 0 ) {

          $root  = rtrim( $directory, '/' ) . '/';
          $class = substr( $class, strlen( $namespace ) );

          break;
        }
      }

      // define the path and the class name for the further checks
      $path = explode( '\\', trim( $class, '\\' ) );
      $name = array_pop( $path );

      // try to find an extension library
      if( !isset( $root ) ) {

        $extension = self::search( $path );
        if( !empty( $extension ) ) {
          $root = self::PATH_BASE . self::PATH_EXTENSION . $extension . '/' . self::EXTENSION_LIBRARY;
        }
      }

      // check root existance and then try to find the original full named class file
      $path = ltrim( implode( '/', $path ) . '/', '/' );
      if( isset( $root ) && !self::read( $name, $path, $root ) ) {

        // try to tokenize the class name (based on camel or TitleCase) to support for nested classes
        $tmp = self::tokenize( $name );
        foreach( $tmp as $name ) if( self::read( $name, $path, $root ) ) {
          break;
        }
      }

      return class_exists( $class, false );
    }
  }

  /**
   * Find and read library files. This will check several case scenario of the name/path
   *
   * @param string $name The file name
   * @param string $path The path to the file from the root
   * @param string $root The path root
   *
   * @return bool True if a file exist and successfully readed
   */
  protected static function read( $name, $path, $root ) {

    // load the class file with the standard lowercase format
    $file = $root . mb_strtolower( $path . $name ) . '.php';
    if( is_file( $file ) ) include_once $file;
    else {

      // support for lowercase path but case sensitive filename
      $file = $root . mb_strtolower( $path ) . $name . '.php';
      if( is_file( $file ) ) include_once $file;
      else {

        // check for non-standard, capital letter files (this files probably 3th part libs)
        $file = $root . $path . $name . '.php';
        if( is_file( $file ) ) include_once $file;
        else {

          // there is no more options, so the file is not exists
          return false;
        }
      }
    }

    return true;
  }
  /**
   * Split the class name into subclassnames through the camel or TitleCase. The full classname is not included in the result array
   *
   * @example `\Framework::tokenize( 'Pop3MailerClass' ) // [ 'Pop3', 'Pop3Mailer' ]`
   * @example `\Framework::tokenize( 'POP3MailerClass' ) // [ 'POP3', 'POP3Mailer' ]`
   * @example `\Framework::tokenize( 'POP3Mailer' ) // [ 'POP3' ]`
   *
   * @param string $name The original classname
   *
   * @return string[] desc ordered classname "tokens"
   */
  protected static function tokenize( $name ) {

    $result  = [ ];
    $buffer  = '';
    $counter = 0;
    for( $uppercase = ctype_upper( $name{0} ), $count = strlen( $name ), $i = 0; $i < $count; ++$i ) {

      $character = $name{$i};
      if( $character == '_' ) return [ ];

      if( !is_numeric( $character ) ) {

        $uppercase_now = ctype_upper( $character );
        if( $uppercase_now != $uppercase && $counter > 1 ) {
          $result[] = !$uppercase_now ? substr( $buffer, 0, -1 ) : $buffer;
          $counter  = 0;
        }

        $uppercase = $uppercase_now;
      }

      ++$counter;
      $buffer .= $character;
    }

    return array_reverse( $result );
  }
}

/**
 * The level of silence
 *
 * @depricated Use \Framework::LEVEL_NONE instead
 */
define( '_LEVEL_NONE', Framework::LEVEL_NONE );
/**
 * The level of critical problems
 *
 * @depricated Use \Framework::LEVEL_CRITICAL instead
 */
define( '_LEVEL_CRITICAL', Framework::LEVEL_CRITICAL );
/**
 * The level of errors
 *
 * @depricated Use \Framework::LEVEL_ERROR instead
 */
define( '_LEVEL_ERROR', Framework::LEVEL_ERROR );
/**
 * The level of warnings
 *
 * @depricated Use \Framework::LEVEL_WARNING instead
 */
define( '_LEVEL_WARNING', Framework::LEVEL_WARNING );
/**
 * The level of noticable problems but nothing serious
 *
 * @depricated Use \Framework::LEVEL_NOTICE instead
 */
define( '_LEVEL_NOTICE', Framework::LEVEL_NOTICE );
/**
 * The level of informations
 *
 * @depricated Use \Framework::LEVEL_INFO instead
 */
define( '_LEVEL_INFO', Framework::LEVEL_INFO );
/**
 * The level of all problems (debugging)
 *
 * @depricated Use \Framework::LEVEL_DEBUG instead
 */
define( '_LEVEL_DEBUG', Framework::LEVEL_DEBUG );

/**
 * The path part of the url.
 * This will be the relative path to the index.php ( or an another entry point of the site, tha include this file )
 *
 * @depricated Use one of the HTTP specialized extensions
 */
define( '_URL_PATH', rtrim( dirname( $_SERVER[ 'SCRIPT_NAME' ] ), '\\/ ' ) . '/' );
/**
 * Detect secure http protocol
 *
 * @depricated Use one of the HTTP specialized extensions
 */
define( '_URL_HTTPS', isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' );
/**
 * The server name from the url, configuration or the ip address
 *
 * @depricated Use one of the HTTP specialized extensions
 */
define( '_URL_SERVER', @$_SERVER[ 'SERVER_NAME' ] ?: gethostbyname( gethostname() ) );
/**
 * The port number for the request, or null if not defined
 *
 * @depricated Use one of the HTTP specialized extensions
 */
define( '_URL_PORT', @$_SERVER[ 'SERVER_PORT' ] ?: null );
/**
 * The host name from the url with port definition if necessary
 *
 * @depricated Use one of the HTTP specialized extensions
 */
define( '_URL_HOST', _URL_SERVER . ( !_URL_PORT || _URL_PORT == ( _URL_HTTPS ? 443 : 80 ) ? '' : ( ':' . _URL_PORT ) ) );
/**
 * The root url with protocol, host and port definition ( if neccessary )
 *
 * @depricated Use one of the HTTP specialized extensions
 */
define( '_URL_ROOT', 'http' . ( _URL_HTTPS ? 's' : '' ) . '://' . _URL_HOST . '/' );
/**
 * The root url with the url path.
 * This is the "real" url with all neccessary parameter
 *
 * @depricated Use one of the HTTP specialized extensions
 */
define( '_URL_BASE', _URL_ROOT . ltrim( _URL_PATH, '/' ) );

/**
 * Extension directory without the _PATH_BASE
 *
 * @deprecated Use \Framework::PATH_EXTENSION instead
 */
define( '_PATH_EXTENSION', \Framework::PATH_EXTENSION );
/**
 * Tmp directory without the _PATH_BASE
 *
 * @depricated Use \Framework::PATH_TMP instead
 */
define( '_PATH_TMP', \Framework::PATH_TMP );