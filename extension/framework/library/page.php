<?php namespace Framework;

use Framework\Exception\Collector;
use Framework\Exception\Strict;
use Framework\Extension;
use Framework\Helper\Log;

/**
 * Class Page
 * @package Framework
 */
abstract class Page {

  /**
   * Header already sent when try to redirect the page
   */
  const EXCEPTION_ERROR_FAIL_REDIRECT = 'framework#9E';
  /**
   * Trying to set invalid localization
   */
  const EXCEPTION_WARNING_INVALID_LOCALIZATION = 'framework#10W';

  /**
   * Runs right before the Page::start() method finished. No argument
   */
  const EVENT_START = 'page.start';
  /**
   * Runs in the Page::run() method, and this method returns this event result. No argument
   */
  const EVENT_RUN = 'page.run';
  /**
   * Runs in the Page::stop() method after enable output buffering. Arguments:
   *  - content [string]: The content to render
   *  - buffer [string]: Some output "trash" or empty
   */
  const EVENT_STOP = 'page.stop';

  /**
   * Exception collector, for runtime error collect
   *
   * @var Collector
   */
  private static $collector = null;

  /**
   * Default logger
   *
   * @var Log
   */
  private static $log = null;

  /**
   * @var string
   */
  private static $localization = null;

  /**
   * Execute the page method in the right order (start -> run -> stop). This is the proper (and complete)
   * way to execute the framework. This method handle output buffering before the stop, exception handling after
   * the start and argument passing between run and stop methods
   */
  public static function execute() {
    if( !_REPORT_LEVEL ) ob_start();
    self::start();

    try {
      $content = self::run();
    } catch( \Exception $e ) {
      self::$collector->add( $e );
      $content = [ ];
    }

    $buffer = !_REPORT_LEVEL ? ob_get_clean() : null;
    echo self::stop( $content, $buffer );

    exit();
  }

  /**
   * Trigger page start event and initialise some basics for the Page. This should be called once and before the run
   */
  public static function start() {

    // setup error reporting based on _REPORTING flag
    $reporting = 0;
    switch( _REPORT_LEVEL ) {
      case 0:

        error_reporting( -1 );
        ini_set( 'display_errors', 0 );

        break;

      /** @noinspection PhpMissingBreakStatementInspection */
      case 6:
        $reporting = E_ALL;
      /** @noinspection PhpMissingBreakStatementInspection */
      case 5:
        $reporting |= E_STRICT | E_DEPRECATED | E_USER_DEPRECATED;
      /** @noinspection PhpMissingBreakStatementInspection */
      case 4:
        $reporting |= E_NOTICE | E_USER_NOTICE;
      /** @noinspection PhpMissingBreakStatementInspection */
      case 3:
        $reporting |= E_WARNING | E_COMPILE_WARNING | E_CORE_WARNING | E_USER_WARNING;
      /** @noinspection PhpMissingBreakStatementInspection */
      case 2:
        $reporting |= E_ERROR | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
      /** @noinspection PhpMissingBreakStatementInspection */
      case 1:
        $reporting |= E_COMPILE_ERROR | E_PARSE;
      /** @noinspection PhpMissingBreakStatementInspection */
      default:

        ini_set( 'display_errors', 1 );
        error_reporting( $reporting );
    }

    // setup localization options
    $extension = Extension::instance( 'framework' );
    self::$localization = $extension->option( 'manifest:localization', 'en' );
    setlocale( LC_ALL, $extension->option( 'default:locale', null ) );

    // setup encoding
    mb_internal_encoding( $extension->option( 'default:encoding', 'utf8' ) );
    mb_http_output( $extension->option( 'default:encoding', 'utf8' ) );

    // setup timezones
    date_default_timezone_set( $extension->option( 'default:timezone', 'UTC' ) );

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
    $event = $extension->trigger( self::EVENT_RUN );
    return $event->result;
  }
  /**
   * Trigger the page stop event with the given arguments. In this method the page should be rendered to
   * the output, based on the content (and maybe the buffer)
   *
   * @param array       $content the page content array
   * @param string|null $buffer  additional but probably trash or error information
   *
   * @return string the page result
   */
  public static function stop( array $content = [ ], $buffer = null ) {
    ob_start();

    // call display end event ( the render )
    $extension = Extension::instance( 'framework' );
    $extension->trigger( self::EVENT_STOP, [
      'content' => $content,
      'buffer'  => $buffer
    ] );

    return ob_get_clean();
  }

  /**
   * Redirect to an url with header redirect
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
    $stop ? self::stop() : exit();
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
   * Getter for log
   *
   * @return Log
   */
  public static function getLog() {
    if( !self::$log ) self::$log = new Log( 'framework' );
    return self::$log;
  }

  /**
   * Get page localization string
   *
   * @return string
   */
  public static function getLocalization() {
    if( !self::$localization ) {

      $extension = Extension::instance( 'framework' );
      self::$localization = $extension->option( 'manifest:localization', 'en' );
    }

    return self::$localization;
  }
  /**
   * Set page localization
   *
   * @param string $new_localization
   *
   * @throws Strict ::EXCEPTION_WARNING_INVALID_LOCALIZATION
   */
  public static function setLocalization( $new_localization ) {

    $new_localization = trim( mb_strtolower( $new_localization ) );
    if( preg_match( '/[a-z_-]/', $new_localization ) > 0 ) self::$localization = $new_localization;
    else throw new Strict( self::EXCEPTION_WARNING_INVALID_LOCALIZATION, [ 'localization' => $new_localization ] );
  }
}
