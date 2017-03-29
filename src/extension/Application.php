<?php namespace Spoom\Framework;

use Spoom\Composer\Autoload;

/**
 * Class Application
 *
 * TODO create Unittests
 *
 * @package Framework
 */
class Application {

  /**
   * Production environment
   */
  const ENVIRONMENT_PRODUCTION = 'production';
  /**
   * Main test environment
   */
  const ENVIRONMENT_TEST = 'test';
  /**
   * Developer's environment
   */
  const ENVIRONMENT_DEVELOPMENT = 'development';

  /**
   * The level of silence
   */
  const SEVERITY_NONE = 0;
  /**
   * The level of total devastation, when the system is unuseable
   */
  const SEVERITY_EMERGENCY = 1;
  /**
   * The level of immediate attention
   */
  const SEVERITY_ALERT = 2;
  /**
   * The level of critical problems
   */
  const SEVERITY_CRITICAL = 3;
  /**
   * The level of errors
   */
  const SEVERITY_ERROR = 4;
  /**
   * The level of warnings
   */
  const SEVERITY_WARNING = 5;
  /**
   * The level of nothing serious but still need some attention
   */
  const SEVERITY_NOTICE = 6;
  /**
   * The level of useful informations
   */
  const SEVERITY_INFO = 7;
  /**
   * The level of detailed informations
   */
  const SEVERITY_DEBUG = 8;

  /**
   * @var static
   */
  private static $instance;
  /**
   * Map severity levels to PHP error levels
   *
   * @var array
   */
  const SEVERITY = [
    self::SEVERITY_NONE      => 0,
    self::SEVERITY_EMERGENCY => 0,
    self::SEVERITY_ALERT     => 0,
    self::SEVERITY_CRITICAL  => E_COMPILE_ERROR | E_PARSE,
    self::SEVERITY_ERROR     => E_ERROR | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR,
    self::SEVERITY_WARNING   => E_WARNING | E_COMPILE_WARNING | E_CORE_WARNING | E_USER_WARNING,
    self::SEVERITY_NOTICE    => E_NOTICE | E_USER_NOTICE,
    self::SEVERITY_INFO      => E_STRICT | E_DEPRECATED | E_USER_DEPRECATED,
    self::SEVERITY_DEBUG     => E_ALL
  ];

  /**
   * @var string
   */
  private $_environment;
  /**
   * @var string
   */
  private $_localization;
  /**
   * @var LogInterface
   */
  private $_log;
  /**
   * App root filesystem
   *
   * @var FileInterface
   */
  private $_root;
  /**
   * Spoom's public directory
   *
   * @var FileInterface
   */
  private $_file;

  /**
   * @param string        $environment
   * @param string        $localization
   * @param FileInterface $root
   * @param LogInterface  $log
   */
  public function __construct( string $environment, string $localization, FileInterface $root, LogInterface $log ) {

    if( self::$instance ) throw new \LogicException( 'Unable to create another instance, use ::instance() instead' );
    else if( empty( $environment ) ) throw new \InvalidArgumentException( "Missing configuration: 'environment'" );
    else if( empty( $localization ) ) throw new \InvalidArgumentException( "Missing configuration: 'localization'" );
    else {

      //
      $this->_environment  = $environment;
      $this->_localization = $localization;
      $this->_log          = $log;
      $this->_root = $root;

      //
      $this->_file = new File( Autoload::DIRECTORY );

      //
      self::$instance = $this;

      // register the exception handler to log every unhandled exception
      set_exception_handler( function ( $exception ) {

        // log the exception
        if( $exception ) Exception::log( $exception );

      } );

      // override the PHP error handler
      set_error_handler( function ( $code, $message, $file, $line ) {

        // process the input into standard values
        $level = self::SEVERITY_NONE;
        if( error_reporting() ) {
          foreach( static::SEVERITY as $severity => $tmp ) {
            if( $tmp & $code ) {
              $level = $severity;
              break;
            }
          }
        }

        // log the fail
        $this->getLog()->create( 'Unexpected code failure: #{code} with \'{message}\' message, at \'{file}\'', [
          'code'    => $code,
          'message' => $message,
          'file'    => $file . ':' . $line
        ], static::class, $level );

        // TODO this should(?) throw an exception on errors

        return false;
      } );
    }
  }

  /**
   * Environment of the application
   *
   * @return string
   */
  public function getEnvironment(): string {
    return $this->_environment;
  }
  /**
   * File from the app root directory
   *
   * @param string $path
   *
   * @return FileInterface
   */
  public function getRootFile( string $path = '' ): FileInterface {
    return $this->_root->get( $path );
  }
  /**
   * File from the Spoom's public directory
   *
   * @param string $path
   *
   * @return FileInterface
   */
  public function getPublicFile( string $path = '' ): FileInterface {
    return $this->_file->get( $path );
  }
  /**
   * Default logger of the application
   *
   * @return LogInterface
   */
  public function getLog(): LogInterface {
    return $this->_log;
  }

  /**
   * Localization of the application
   *
   * @return string
   */
  public function getLocalization(): string {
    return $this->_localization;
  }
  /**
   * Set localization of the application
   *
   * @param string $value
   */
  public function setLocalization( string $value ) {
    $this->_localization = trim( $value );
  }

  /**
   * Global setup of the PHP environment
   *
   * This SHOULD be done before the framework...or anything else in the PHP code
   *
   * @param int   $severity Maximum severity level tht should be reported
   * @param array $configuration
   */
  public static function environment( int $severity, array $configuration = [] ) {

    // setup error reporting in PHP
    $reporting = ~E_ALL;
    foreach( self::SEVERITY as $key => $value ) {
      if( $key <= $severity ) $reporting |= $value;
    }
    error_reporting( $reporting );

    // setup locale
    if( isset( $configuration[ 'locale' ] ) ) {
      setlocale( LC_ALL, $configuration[ 'locale' ] );
    }

    // setup encoding
    if( isset( $configuration[ 'encoding' ] ) ) {
      mb_internal_encoding( $configuration[ 'encoding' ] );
      mb_http_output( $configuration[ 'encoding' ] );
    }

    // setup timezones
    if( isset( $configuration[ 'timezone' ] ) ) {
      date_default_timezone_set( $configuration[ 'timezone' ] );
    }
  }
  /**
   * @return static
   * @throws \Exception
   */
  public static function instance() {
    if( empty( self::$instance ) ) throw new \LogicException( 'There is no Application instance right now' );
    else return self::$instance;
  }
}

/**
 * General exception for a missing (but needed) PHP extension/feature
 *
 * @package Framework
 */
class ApplicationExceptionFeature extends Exception\Runtime {

  const ID = '0#framework';

  /**
   * @param string $feature Extension or feature name
   * @param string $version Minimum required version
   */
  public function __construct( string $feature, string $version ) {

    $data = [ 'feature' => $feature, 'version' => $version ];
    parent::__construct( '(Un)serialization failed, due to an error', static::ID, $data, null, Application::SEVERITY_CRITICAL );
  }
}
