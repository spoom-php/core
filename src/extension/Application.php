<?php namespace Spoom\Framework;

/**
 * Class Application
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
   * @var static
   */
  private static $instance;

  /**
   * @var string
   */
  private $localization;
  /**
   * @var string
   */
  private $environment;
  /**
   * @var LogInterface
   */
  private $log;
  /**
   * @var File\SystemInterface
   */
  private $filesystem;
  /**
   * The framework log level
   *
   * @var int
   */
  private $log_level;
  /**
   * The framework report level
   *
   * @var int
   */
  private $report_level;

  /**
   * @param string $root
   * @param array  $configuration
   *
   * @throws \Exception
   */
  public function __construct( $root, array $configuration = [] ) {

    if( self::$instance ) throw new \Exception( 'Framework can\'t have multiple Application instances' );
    else {

      //
      self::$instance = $this;

      // check for the environment
      if( empty( $configuration[ 'environment' ] ) ) throw new \Exception( 'Fatal exception at setup: Missing environment configuration' );
      else if( empty( $configuration[ 'localization' ] ) ) throw new \Exception( 'Fatal exception at setup: Missing localization configuration' );
      else try {

        $this->environment  = $configuration[ 'environment' ];
        $this->localization = $configuration[ 'localization' ];
        // TODO setup report/log

        // register the exception handler to log every unhandled exception
        set_exception_handler( function ( $exception ) {

          // log the exception
          if( $exception ) Exception::wrap( $exception )->log();

        } );

        // override the PHP error handler
        set_error_handler( function ( $code, $message, $file, $line ) {

          // process the input into standard values
          if( !error_reporting() ) $level = self::LEVEL_NONE;
          else {

            //
            switch( true ) {
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
          }

          // log the fail
          self::getLog()->create( 'Unexpected code failure: #{code} with \'{message}\' message, at \'{file}\'', [
            'code'    => $code,
            'message' => $message,
            'file'    => $file . ':' . $line
          ], 'framework:application', $level );

          // TODO this should(?) throw an exception on errors

          return false;
        } );

        setlocale( LC_ALL, $configuration[ 'locale' ] );

        // setup encoding
        mb_internal_encoding( $configuration[ 'encoding' ] );
        mb_http_output( $configuration[ 'encoding' ] );

        // setup timezones
        date_default_timezone_set( $configuration[ 'timezone' ] );

        $this->filesystem = new File\System( $root );
        // TODO make logger independent from the application
        $this->log = new Log( 'spoom' );

      } catch( Exception $e ) {
        throw new \Exception( 'Fatal exception at setup: #' . $e->getCode() . ', ' . $e->getMessage(), 0, $e );
      }
    }
  }

  /**
   * @return string
   */
  public function getEnvironment() {
    return $this->environment;
  }
  /**
   * @return int
   */
  public function getReportLevel() {
    return $this->report_level;
  }
  /**
   * @param int $value
   */
  public function setReportLevel( $value ) {
    $this->report_level = (int) $value;

    // setup error reporting in PHP
    $reporting = 0;
    switch( $this->report_level ) {
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
  public function getLogLevel() {
    return $this->log_level;
  }
  /**
   * @param int $value
   */
  public function setLogLevel( $value ) {
    $this->log_level = (int) $value;
  }

  /**
   * Get file from the local root filesystem
   *
   * @param string $path
   *
   * @return FileInterface
   */
  public function getFile( $path = '' ) {
    return $this->filesystem->get( $path );
  }
  /**
   * @return LogInterface
   */
  public function getLog() {
    return $this->log;
  }

  /**
   * Get page localization string
   *
   * @return string
   */
  public function getLocalization() {
    return $this->localization;
  }
  /**
   * Set request localization
   *
   * @param string $value
   */
  public function setLocalization( $value ) {
    $this->localization = trim( mb_strtolower( $value ) );
  }

  /**
   * @return static
   * @throws \Exception
   */
  public static function instance() {
    if( empty( self::$instance ) ) throw new \Exception( 'There is no Application instance right now' );
    else return self::$instance;
  }
}

/**
 * General exception for a missing (but needed) PHP extension/feature
 *
 * @package Framework
 */
class ApplicationExceptionFeature extends Exception\System {

  const ID = '0#framework';

  /**
   * @param string $feature Extension or feature name
   * @param string $version Minimum required version
   */
  public function __construct( $feature, $version ) {

    $data = [ 'feature' => $feature, 'version' => $version ];
    parent::__construct( '(Un)serialization failed, due to an error', static::ID, $data, null, Application::LEVEL_CRITICAL );
  }
}
