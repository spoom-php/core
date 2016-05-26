<?php

/**
 * Directory base of the framework.
 * Can be used to include files in php without worry the correct include path
 */
define( '_PATH_BASE', rtrim( dirname( __FILE__ ), '\\/' ) . '/' );

/**
 * Class Framework
 *
 * @since 0.6.0
 */
class Framework {

  /**
   * The minimum PHP version for the framework
   */
  const DEPENDENCY = '5.6.0';

  /**
   * Production environment
   */
  const ENVIRONMENT_PRODUCTION = 'production';
  /**
   * Main development environment
   */
  const ENVIRONMENT_DEVELOPMENT = 'development';

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
   * Directory base of the framework
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
   * TODO change to constant after PHP7
   *
   * @var array[string]int
   */
  private static $LEVEL_NAME = [
    'none'     => self::LEVEL_NONE,
    'critical' => self::LEVEL_CRITICAL,
    'error'    => self::LEVEL_ERROR,
    'warning'  => self::LEVEL_WARNING,
    'notice'   => self::LEVEL_NOTICE,
    'info'     => self::LEVEL_INFO,
    'debug'    => self::LEVEL_DEBUG
  ];

  /**
   * @var string
   */
  private static $environment;
  /**
   * The framework log level
   *
   * @var int
   */
  private static $log;
  /**
   * The framework report level
   *
   * @var int
   */
  private static $report;

  /**
   * @param string|null     $environment
   * @param string|int|null $report
   * @param string|int|null $log
   *
   * @return true
   * @throws \Exception
   */
  public static function setup( $environment = null, $report = null, $log = null ) {

    // check the php version
    if( version_compare( PHP_VERSION, self::DEPENDENCY ) < 0 ) {
      throw new \Exception( 'Fatal exception at the startup: PHP' . PHP_VERSION . ' (<' . \Framework::DEPENDENCY . ') isn\'t enough to run the framework' );
    }

    // check for the environment
    self::$environment = $environment ?: getenv( 'ENVIRONMENT_TYPE' );
    if( empty( self::$environment ) ) throw new \Exception( 'Fatal exception at the startup: Invalid or missing environment definition' );
    else {

      $development = self::$environment == \Framework::ENVIRONMENT_DEVELOPMENT;

      // set the report level
      $report = $report ?: getenv( 'ENVIRONMENT_REPORT' );
      self::setReport( $report ?: ( $development ? \Framework::LEVEL_DEBUG : \Framework::LEVEL_NONE ) );

      // set the log level
      $log = $log ?: getenv( 'ENVIRONMENT_LOG' );
      self::setLog( $log ?: ( $development ? \Framework::LEVEL_DEBUG : \Framework::LEVEL_WARNING ) );

      return true;
    }
  }
  /**
   * Run the framework
   *
   * @param callable $main      Callback to run the main program after the Framework setup
   * @param callable $terminate Callback( \Exception $e ) for terminated (with exception) runs
   * @param callable $failure   Callback( int $level, int $code, string $message, string $file, array $trace ) for non-fatal PHP errors
   *
   * @throws Exception
   */
  public static function execute( callable $main, $terminate = null, $failure = null ) {

    try {

      // register the autoloader
      spl_autoload_register( function ( $class ) {
        FrameworkImport::load( $class );
      } );

      // register the terminate process(es)
      set_exception_handler( function ( $exception ) use ( $terminate ) {
        if( $exception && is_callable( $terminate ) ) {

          // register shutdown function if there was any exception
          register_shutdown_function( function () use ( $terminate, $exception ) {
            call_user_func( $terminate, $exception );
          } );
        }
      } );

      // override the PHP error handler
      set_error_handler( function ( $code, $message, $file, $line ) use ( $failure ) {

        if( !is_callable( $failure ) ) return false;
        else {

          // process the input into standard values
          $file .= ':' . $line;
          if( !error_reporting() ) $level = self::LEVEL_NONE;
          else switch( true ) {
            case ( E_STRICT | E_DEPRECATED | E_USER_DEPRECATED ) & $code:
              $level = self::LEVEL_INFO;
              break;
            case ( E_NOTICE | E_USER_NOTICE ) & $code:
              $level = self::LEVEL_NOTICE;
              break;
            case ( E_WARNING | E_COMPILE_WARNING | E_CORE_WARNING | E_USER_WARNING ) & $code:
              $level = self::LEVEL_WARNING;
              break;
            case ( E_ERROR | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR ) & $code:
              $level = self::LEVEL_ERROR;
              break;
            case ( E_COMPILE_ERROR | E_PARSE ) & $code:
            default:
              $level = self::LEVEL_ERROR;
              break;
          }

          return call_user_func( $failure, $level, $code, $message, $file, debug_backtrace() );
        }
      } );

      // call the main function
      $main();

    } catch( Exception $e ) {
      throw new Exception( 'Fatal exception at the startup: #' . $e->getCode() . ', ' . $e->getMessage(), 0, $e );
    }
  }

  /**
   * Search the extension name from the input string array. The strings, used for the extension name will be sliced from the input array
   *
   * @param string[] $input The input strings
   * @param int      $depth Maximum depth for the extension id
   *
   * @return string The extension name if any
   */
  public static function search( array &$input, $depth = self::EXTENSION_DEPTH ) {

    // find the extension from the class namespace
    $name   = '';
    $length = 0;
    for( $i = 0, $count = count( $input ), $tmp = ''; $i < $depth && $i < $count; ++$i ) {

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
   * Get a class fully qualified name
   *
   * @param string    $definition A fully qualified classname or an extension library with 'extension:library' syntax where the library is in dot notated format
   * @param bool|true $validate   Only return the class if it's really exists
   *
   * @return string|null The class fully qualified name or null if not exist and validate is true
   */
  public static function library( $definition, $validate = true ) {

    if( empty( $definition ) ) return $validate ? null : $definition;
    else if( !strpos( $definition, ':' ) ) $class = '\\' . trim( $definition, '\\' );
    else {

      list( $extension, $library ) = explode( ':', $definition, 2 );
      $class = str_replace( self::EXTENSION_SEPARATOR, ' ', $extension ) . ' ' . str_replace( '.', ' ', $library );
      if( ctype_lower( preg_replace( '/[\\W\\d]/i', '', $class ) ) ) $class = ucwords( $class );

      $class = '\\' . str_replace( ' ', '\\', $class );
    }

    return !$validate || FrameworkImport::load( $class ) ? $class : null;
  }

  /**
   * @return string
   */
  public static function getEnvironment() {
    return self::$environment;
  }

  /**
   * @return int
   */
  public static function getReport() {
    return self::$report;
  }
  /**
   * @param string|int $value
   */
  public static function setReport( $value ) {
    self::$report = self::getLevel( $value, false );

    // setup error reporting in PHP
    $reporting = 0;
    switch( self::$report ) {
      case self::LEVEL_NONE:

        error_reporting( -1 );
        ini_set( 'display_errors', 0 );

        break;

      /** @noinspection PhpMissingBreakStatementInspection */
      case self::LEVEL_DEBUG:
        $reporting = E_ALL;
      /** @noinspection PhpMissingBreakStatementInspection */
      case self::LEVEL_INFO:
        $reporting |= E_STRICT | E_DEPRECATED | E_USER_DEPRECATED;
      /** @noinspection PhpMissingBreakStatementInspection */
      case self::LEVEL_NOTICE:
        $reporting |= E_NOTICE | E_USER_NOTICE;
      /** @noinspection PhpMissingBreakStatementInspection */
      case self::LEVEL_WARNING:
        $reporting |= E_WARNING | E_COMPILE_WARNING | E_CORE_WARNING | E_USER_WARNING;
      /** @noinspection PhpMissingBreakStatementInspection */
      case self::LEVEL_ERROR:
        $reporting |= E_ERROR | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
      /** @noinspection PhpMissingBreakStatementInspection */
      case self::LEVEL_CRITICAL:
        $reporting |= E_COMPILE_ERROR | E_PARSE;
      /** @noinspection PhpMissingBreakStatementInspection */
      default:

        ini_set( 'display_errors', 1 );
        error_reporting( $reporting );
    }
  }

  /**
   * @return int
   */
  public static function getLog() {
    return self::$log;
  }
  /**
   * @param string|int $value
   */
  public static function setLog( $value = null ) {
    self::$log = self::getLevel( $value, false );
  }

  /**
   * Get the level from the name or the name from the level
   *
   * @param int|string $input   The level or the name
   * @param bool       $numeric Return the level or the name
   *
   * @return int|string|null Null, if the input is invalid
   */
  public static function getLevel( $input, $numeric = false ) {

    if( is_string( $input ) ) return isset( self::$LEVEL_NAME[ $input ] ) ? ( $numeric ? $input : self::$LEVEL_NAME[ $input ] ) : null;
    else {

      $tmp = array_search( $input, self::$LEVEL_NAME );
      return $tmp === false ? null : ( $numeric ? $tmp : (int) $input );
    }
  }
}
/**
 * Class FrameworkImport
 */
class FrameworkImport {

  /**
   * Store the custom namespace connections to their root paths
   *
   * @var array[string]string
   */
  private static $definition = [ ];

  /**
   * Add custom namespace root directory for the importer
   *
   * @param string $namespace The namespace definition
   * @param string $path      The path for the namespace
   */
  public static function define( $namespace, $path ) {
    self::$definition[ trim( $namespace, '\\' ) . '\\' ] = rtrim( $path, '/' ) . '/';
    uksort( self::$definition, function ( $a, $b ) {
      return strlen( $b ) - strlen( $a );
    } );
  }
  /**
   * Remove a custom namespace definition
   *
   * @param string $namespace The namespace to remove
   */
  public static function undefine( $namespace ) {
    unset( self::$definition[ trim( $namespace, '\\' ) . '\\' ] );
  }

  /**
   * Import class files based on the class name and the namespace
   *
   * @param string $class The class fully qualified name
   *
   * @return bool True only if the class is exists after the import
   */
  public static function load( $class ) {

    // do not import class that is exists already
    if( class_exists( $class, false ) ) return true;
    else {

      // fix for absolute class definitions
      $class = ltrim( $class, '\\' );

      // try first the custom paths (custom autoload path support)
      foreach( static::$definition as $namespace => $directory ) {
        if( strpos( $class, $namespace ) === 0 ) {

          $path = explode( '\\', substr( $class, strlen( $namespace ) ) );
          $name = array_pop( $path );

          // return when the loader find a perfect match, and the class really exist
          if( self::search( $name, $path, $directory ) && class_exists( $class, false ) ) {
            return true;
          }
        }
      }

      // try to find an extension library
      $depth = Framework::EXTENSION_DEPTH;
      for( $i = $depth; $i > 0; --$i ) {

        $path = explode( '\\', $class );
        $name = array_pop( $path );

        $extension = Framework::search( $path );
        if( empty( $extension ) ) break;
        else {

          $root = Framework::PATH_BASE . Framework::PATH_EXTENSION . $extension . '/' . Framework::EXTENSION_LIBRARY;
          if( self::search( $name, $path, $root ) && class_exists( $class, false ) ) {
            return true;
          }
        }
      }

      return false;
    }
  }

  /**
   * Find the class file and load it
   *
   * @param string $name The class name
   * @param string $path The file path
   * @param string $root The path's root
   *
   * @return bool True if the file was successfully loaded
   */
  protected static function search( $name, $path, $root ) {

    // check root existance and then try to find the original full named class file
    $path = ltrim( implode( '/', $path ) . '/', '/' );
    if( self::read( $name, $path, $root ) ) return true;
    else {

      // try to tokenize the class name (based on camel or TitleCase) to support for nested classes
      $tmp = self::explode( $name );
      foreach( $tmp as $name ) if( self::read( $name, $path, $root ) ) {
        return true;
      }

      return false;
    }
  }
  /**
   * Read library files. This will check several case scenario of the name/path
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
   * @example `Framework::tokenize( 'Pop3MailerClass' ) // [ 'Pop3', 'Pop3Mailer' ]`
   * @example `Framework::tokenize( 'POP3MailerClass' ) // [ 'POP3', 'POP3Mailer' ]`
   * @example `Framework::tokenize( 'POP3Mailer' ) // [ 'POP3' ]`
   *
   * @param string $name The original classname
   *
   * @return string[] desc ordered classname "tokens"
   */
  protected static function explode( $name ) {

    $result  = [ ];
    $buffer  = '';
    $counter = 0;
    for( $uppercase = ctype_upper( $name{0} ), $count = strlen( $name ), $i = 0; $i < $count; ++$i ) {

      $character = $name{$i};
      if( $character == '_' ) return [ ];
      else if( !is_numeric( $character ) ) {

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
