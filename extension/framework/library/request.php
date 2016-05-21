<?php namespace Framework;

use Framework\Exception\Collector;
use Framework\Exception\Strict;
use Framework\Extension;
use Framework\Helper\Library;
use Framework\Helper\Log;

/**
 * Class Request
 * @package Framework
 */
class Request extends Library {

  /**
   * Header already sent when try to redirect the page
   */
  const EXCEPTION_ERROR_FAIL_REDIRECT = 'framework#9E';
  /**
   * Trying to set invalid localization
   */
  const EXCEPTION_WARNING_INVALID_LOCALIZATION = 'framework#10W';
  /**
   * Trying to set invalid environment
   */
  const EXCEPTION_WARNING_INVALID_ENVIRONMENT = 'framework#26W';
  /**
   * Prevented request start
   */
  const EXCEPTION_FAIL_START = 'framework#0C';
  /**
   * Exception based on non-fatal failure (with multiple type, defined later)
   */
  const EXCEPTION_FAIL = 'framework#27';

  /**
   * Production environment
   */
  const ENVIRONMENT_PRODUCTION = 'production';
  /**
   * Main development environment
   */
  const ENVIRONMENT_DEVELOPMENT = 'development';

  /**
   * Runs right before the Request::start() method finished. No argument
   */
  const EVENT_START = 'request.start';
  /**
   * Runs in the Request::run() method, and this method returns this event result. No argument
   */
  const EVENT_RUN = 'request.run';
  /**
   * Runs in the Request::stop() method after enable output buffering. Arguments:
   *  - content [string[]]: The contents to render
   *  - buffer [string]: Some output "trash" or empty
   */
  const EVENT_STOP = 'request.stop';
  /**
   * Runs in the Request::terminate() method before the request was ended. Arguments:
   *  - exception [\Exception]: The reason of the termination
   */
  const EVENT_TERMINATE = 'request.terminate';

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
   * @var string
   */
  private static $environment = '';

  /**
   * Execute the request
   *
   * @param string $environment
   */
  public static function execute( $environment = '' ) {
    self::start( $environment ) && self::stop( self::run() );
  }

  /**
   * Initialise some basics for the Request and triggers start event. This should be called once and before the run
   *
   * @param string $environment
   *
   * @throws \Exception
   */
  public static function start( $environment = '' ) {

    //
    self::setEnvironment( $environment );
    $extension = Extension::instance( 'framework' );

    // setup the reporting levels
    \Framework::reportLevel( $extension->option( 'request:level.report', null ) );
    \Framework::logLevel( $extension->option( 'request:level.log', null ) );

    // add custom namespaces from configuration
    $import = $extension->option( 'request:import!array' );
    if( !empty( $import ) ) foreach( $import as $namespace => $path ) {
      \Framework::connect( $namespace, $path );
    }

    self::$localization = $extension->option( 'request:localization', $extension->manifest->getString( 'localization', 'en' ) );
    setlocale( LC_ALL, $extension->option( 'request:locale', null ) );

    // setup encoding
    mb_internal_encoding( $extension->option( 'request:encoding', 'utf8' ) );
    mb_http_output( $extension->option( 'request:encoding', 'utf8' ) );

    // setup timezones
    date_default_timezone_set( $extension->option( 'request:timezone', date_default_timezone_get() ) );

    // call initialise event
    $event = $extension->trigger( self::EVENT_START );
    if( $event->collector->count() ) throw $event->collector->get();
    else if( $event->prevented ) throw new Exception\Strict( self::EXCEPTION_FAIL_START );
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
   * Request termination after a fatal exception
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
    self::getLog()->create( 'Unexpected code failing: #{code} with \'{message}\' message, at \'{file}\'', [
      'code'    => $code,
      'message' => $message,
      'file'    => $file,
      'trace'   => $trace
    ], 'framework:request', $level );

    // throw an exception that match the fail level
    // TODO this should affected by the Framework::reportLevel()?!
    $type = Exception\Helper::getType( $level );
    if( $type ) throw new Exception\Strict( self::EXCEPTION_FAIL . $type, [
      'code'    => $code,
      'message' => $message,
      'file'    => $file,
      'trace'   => $trace
    ] );

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
   * @throws Strict ::EXCEPTION_ERROR_FAIL_REDIRECT
   */
  public static function redirect( $url, $code = 3, $stop = false ) {
    $url = ltrim( trim( $url, ' ' ), '/' );

    // add url base if the url doesn't contains protocol
    if( !preg_match( '#^[a-z]+\://#i', $url ) ) $url = _URL_BASE . $url;

    // check the header state
    if( headers_sent() ) throw new Strict( self::EXCEPTION_ERROR_FAIL_REDIRECT, [ 'url' => $url ] );
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
   * @return Log
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
   * @throws Exception\Strict ::EXCEPTION_WARNING_INVALID_LOCALIZATION
   */
  public static function setLocalization( $value ) {

    $value = trim( mb_strtolower( $value ) );
    if( preg_match( '/^[a-z_-]+$/', $value ) < 1 ) throw new Exception\Strict( self::EXCEPTION_WARNING_INVALID_LOCALIZATION, [ 'localization' => $value ] );
    else self::$localization = $value;
  }

  /**
   * @return string
   */
  public static function getEnvironment() {
    return self::$environment;
  }
  /**
   * Set the request environment
   *
   * @param string $value
   *
   * @throws Exception\Strict ::EXCEPTION_WARNING_INVALID_ENVIRONMENT
   */
  private static function setEnvironment( $value ) {

    $value = trim( mb_strtolower( $value ) );
    if( preg_match( '/^[a-z_-]*$/', $value ) < 1 ) throw new Exception\Strict( self::EXCEPTION_WARNING_INVALID_ENVIRONMENT, [ 'value' => $value ] );
    else self::$environment = $value;
  }
}
