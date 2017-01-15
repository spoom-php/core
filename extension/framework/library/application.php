<?php namespace Framework;

use Framework\Exception\Collector;
use Framework\Exception\Strict;

/**
 * Class Application
 * @package Framework
 */
abstract class Application {

  /**
   * General exception for a missing (but needed) PHP extension/feature
   *
   * @param string $name Extension or feature name with version (separated by @)
   */
  const EXCEPTION_FEATURE_MISSING = 'framework#0C';
  /**
   * Header already sent when try to redirect the page
   */
  const EXCEPTION_FAIL_REDIRECT = 'framework#9N';
  /**
   * Trying to set invalid localization
   */
  const EXCEPTION_INVALID_LOCALIZATION = 'framework#10W';
  /**
   * Prevented request start
   */
  const EXCEPTION_FAIL_START = 'framework#0C';
  /**
   * Exception based on non-fatal failure (with multiple type, defined later)
   */
  const EXCEPTION_FAIL = 'framework#27';

  /**
   * Runs right before the Application::start() method finished
   */
  const EVENT_START = 'application.start';
  /**
   * Runs in the Application::run() method, and this method returns this event result
   */
  const EVENT_RUN = 'application.run';
  /**
   * Runs in the Application::stop() method after enable output buffering
   *
   * @param string[] $content The contents to render
   * @param string   $buffer  Some output "trash" or empty
   */
  const EVENT_STOP = 'application.stop';
  /**
   * Runs in the Application::terminate() method before the request was ended
   *
   * @param \Exception $exception The reason of the termination
   */
  const EVENT_TERMINATE = 'application.terminate';

  /**
   * @var File\SystemInterface|null
   */
  private static $filesystem = null;
  /**
   * Exception collector, for runtime error collect
   *
   * @var Collector
   */
  private static $collector = null;
  /**
   * @var string
   */
  private static $localization = null;

  /**
   * Execute the request
   */
  public static function execute() {
    self::start() && self::stop( self::run() );
  }

  /**
   * Initialise some basics for the Application and triggers start event. This should be called once and before the run
   *
   * @return true
   * @throws \Exception
   */
  public static function start() {
    $extension = Extension::instance( 'framework' );

    // setup the reporting levels
    \Framework::setReport( $extension->option( 'application:level.report', \Framework::getReport() ) );
    \Framework::setLog( $extension->option( 'application:level.log', \Framework::getLog() ) );

    // add custom namespaces from configuration
    $import = $extension->option( 'application:import!array' );
    if( !empty( $import ) ) foreach( $import as $namespace => $path ) {
      \FrameworkImport::define( $namespace, $path );
    }

    self::$localization = $extension->option( 'application:localization', $extension->manifest->getString( 'localization', 'en' ) );
    setlocale( LC_ALL, $extension->option( 'application:locale', null ) );

    // setup encoding
    mb_internal_encoding( $extension->option( 'application:encoding', mb_internal_encoding() ) );
    mb_http_output( $extension->option( 'application:encoding', mb_internal_encoding() ) );

    // setup timezones
    date_default_timezone_set( $extension->option( 'application:timezone', date_default_timezone_get() ) );

    // call initialise event
    $event = $extension->trigger( self::EVENT_START );
    if( $event->collector->count() ) throw $event->collector->get();
    else if( $event->prevented ) throw new Exception\Strict( self::EXCEPTION_FAIL_START );

    return true;
  }
  /**
   * Trigger run event and return the result. The request result (for the render) should be in the event result
   *
   * @return mixed The run event result
   * @throws \Exception
   */
  public static function run() {

    $extension = Extension::instance( 'framework' );

    // call display event to let extensions render the content
    $event = $extension->trigger( self::EVENT_RUN );

    if( $event->collector->count() ) throw $event->collector->get();
    else return !$event->prevented ? $event->get( '' ) : null;
  }
  /**
   * Trigger the stop event. In this event the request result should be rendered to the output, based on the content
   *
   * @param string $content
   *
   * @return mixed|null
   * @throws \Exception
   */
  public static function stop( $content = '' ) {

    // call display end event ( the render )
    $extension = Extension::instance( 'framework' );
    $event     = $extension->trigger( self::EVENT_STOP, [
      'content' => $content
    ] );

    if( $event->collector->count() ) throw $event->collector->get();
    else return !$event->prevented ? $event->get( '' ) : null;
  }

  /**
   * Application termination after a fatal exception
   *
   * TODO define \Throwable param type after PHP7
   *
   * @param \Exception $exception
   */
  public static function terminate( $exception ) {

    // log the exception
    Exception\Helper::wrap( $exception )->log();

    // trigger the terminate event
    $extension = Extension::instance( 'framework' );
    $extension->trigger( self::EVENT_TERMINATE, [
      'exception' => $exception
    ] );

    // TODO maybe this should return the event 'result'
  }
  /**
   * Non-fatal error handler (notice, warning, error, ...)
   *
   * @param int    $level
   * @param int    $code    The PHP error code
   * @param string $message The original message
   * @param string $file    The file with line number postfix
   * @param array  $trace   Stack trace
   *
   * @return bool
   * @throws Exception
   */
  public static function failure( $level, $code, $message, $file, $trace ) {

    // log the fail
    self::getLog()->create( 'Unexpected code failure: #{code} with \'{message}\' message, at \'{file}\'', [
      'code'    => $code,
      'message' => $message,
      'file'    => $file,
      'trace'   => $trace
    ], 'framework:application', $level );

    // throw an exception that match the fail level
    if( $level <= \Framework::getReport() ) {

      $type = Exception\Helper::getPostfix( $level );
      if( $type ) throw new Exception\Strict( self::EXCEPTION_FAIL . $type, [
        'code'    => $code,
        'message' => $message,
        'file'    => $file,
        'trace'   => $trace
      ] );
    }

    return false;
  }

  /**
   * Redirect to an url with header redirect
   *
   * @deprecated Use one of the HTTP related extensions
   *
   * @param mixed $url  The new url. It will be converted to string
   * @param int   $code HTTP Redirect type respsonse code. This number added to 300 to make 30x status code
   * @param bool  $stop Call the page stop() method ot not
   *
   * @throws Strict ::EXCEPTION_FAIL_REDIRECT
   */
  public static function redirect( $url, $code = 3, $stop = false ) {
    $url = ltrim( trim( $url, ' ' ), '/' );

    // add url base if the url doesn't contains protocol
    if( !preg_match( '#^[a-z]+\://#i', $url ) ) $url = _URL_BASE . $url;

    // check the header state
    if( headers_sent() ) throw new Strict( self::EXCEPTION_FAIL_REDIRECT, [ 'url' => $url ] );
    else {

      // add status code and redirect
      http_response_code( 300 + ( (int) $code ) );
      header( 'Location: ' . $url );
    }

    // call stop (or exit) to finish the page
    if( $stop ) self::stop();

    exit();
  }

  /**
   * Get file from the local root filesystem
   *
   * @param string $path
   *
   * @return FileInterface
   */
  public static function getFile( $path = '' ) {

    if( empty( static::$filesystem ) ) {
      static::$filesystem = new File\System( \Framework::PATH_BASE );
    }

    return static::$filesystem->get( $path );
  }
  /**
   * Getter for collector
   *
   * @return Collector
   */
  public static function getCollector() {
    if( !self::$collector ) self::$collector = new Collector();
    return self::$collector;
  }
  /**
   * Getter for log. It is just a wrapper for `Log::instance('framework');`
   *
   * @return LogInterface
   */
  public static function getLog() {
    return Log::instance( 'framework' );
  }

  /**
   * Get page localization string
   *
   * @return string
   */
  public static function getLocalization() {
    if( !self::$localization ) {

      $extension          = Extension::instance( 'framework' );
      self::$localization = $extension->manifest->getString( 'localization', 'en' );
    }

    return self::$localization;
  }
  /**
   * Set request localization
   *
   * @param string $value
   *
   * @throws Exception\Strict ::EXCEPTION_INVALID_LOCALIZATION
   */
  public static function setLocalization( $value ) {

    $value = trim( mb_strtolower( $value ) );
    if( preg_match( '/^[a-z_-]+$/', $value ) < 1 ) throw new Exception\Strict( self::EXCEPTION_INVALID_LOCALIZATION, [ 'localization' => $value ] );
    else self::$localization = $value;
  }
}
