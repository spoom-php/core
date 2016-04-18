<?php namespace Framework;

use Framework\Exception\Collector;
use Framework\Exception\Strict;
use Framework\Extension;
use Framework\Helper\Log;

/**
 * Class Request
 * @package Framework
 */
class Request {

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
  const EXCEPTION_WARNING_INVALID_ENVIRONMENT = 'framework#0W';

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
   * Execute the page method in the right order (start -> run -> stop). This is the proper (and complete)
   * way to execute the framework. This method handle output buffering before the stop, exception handling after
   * the start and argument passing between run and stop methods
   *
   * @param string $environment
   */
  public static function execute( $environment = '' ) {

    $report = \Framework::reportLevel();
    if( $report != \Framework::LEVEL_DEBUG ) ob_start();

    self::start( $environment );
    try {
      $content = self::run();
    } catch( \Exception $e ) {
      self::$collector->add( $e );
      $content = [ ];
    }

    $buffer = $report != \Framework::LEVEL_DEBUG ? ob_get_clean() : null;
    self::stop( $content, $buffer );
  }

  /**
   * Trigger page start event and initialise some basics for the Request. This should be called once and before the run
   *
   * @param string $environment
   */
  public static function start( $environment = '' ) {

    //
    self::setEnvironment( $environment );
    $extension = Extension::instance( 'framework' );

    // setup the reporting levels
    \Framework::reportLevel( $extension->option( 'default:level.report', null ) );
    \Framework::logLevel( $extension->option( 'default:level.log', null ) );

    // add custom namespaces from configuration
    $import = $extension->option( 'default:import!array' );
    if( !empty( $import ) ) foreach( $import as $namespace => $path ) {
      \Framework::connect( $namespace, $path );
    }

    self::$localization = $extension->option( 'default:localization', $extension->manifest->getString( 'localization', 'en' ) );
    setlocale( LC_ALL, $extension->option( 'default:locale', null ) );

    // setup encoding
    mb_internal_encoding( $extension->option( 'default:encoding', 'utf8' ) );
    mb_http_output( $extension->option( 'default:encoding', 'utf8' ) );

    // setup timezones
    date_default_timezone_set( $extension->option( 'default:timezone', date_default_timezone_get() ) );

    // Call initialise event
    $extension->trigger( self::EVENT_START );
  }
  /**
   * Trigger page run event and return the result array. The page contents (for the render) should be in
   * the event results
   *
   * @return array|null The collected page contents
   */
  public static function run() {

    $extension = Extension::instance( 'framework' );

    // call display event to let extensions render the content
    $result = $extension->trigger( self::EVENT_RUN );
    return $result->getArray( '' );
  }
  /**
   * Trigger the page stop event with the given arguments. In this method the page should be rendered to
   * the output, based on the content (and maybe the buffer)
   *
   * @param array       $content the page content array
   * @param string|null $buffer  additional but probably trash or error information
   */
  public static function stop( array $content = [ ], $buffer = null ) {

    // call display end event ( the render )
    $extension = Extension::instance( 'framework' );
    $extension->trigger( self::EVENT_STOP, [
      'content' => $content,
      'buffer'  => $buffer
    ] );
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
