<?php namespace Engine;

use Engine\Exception\Collector;
use Engine\Extension;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Page
 * @package Engine
 */
abstract class Page {

  const EVENT_START = 'page.start';
  const EVENT_RUN  = 'page.run';
  const EVENT_STOP = 'page.stop';

  /**
   * Exception collector, for runtime error collect
   *
   * @var Collector
   */
  private static $collector = null;

  /**
   * Execute the page method in the right order (start -> run -> stop). This is the proper (and complete)
   * way to execute the engine. This method handle output buffering before the stop, exception handling after
   * the start and argument passing between run and stop methods
   */
  public static function execute() {
    if( !_REPORTING ) ob_start();
    self::start();

    try {
      $content = self::run();
    } catch( \Exception $e ) {
      self::$collector->addException( $e );
      $content = [ ];
    }

    $buffer = !_REPORTING ? ob_get_clean() : null;
    echo self::stop( $content, $buffer );

    exit();
  }

  /**
   * Trigger page start event and initialise some basics for the Page. This should be called once and
   * before the run
   */
  public static function start() {

    // setup error reporting based on _REPORTING flag
    switch( _REPORTING ) {
      case 0:

        error_reporting( ~E_ALL );
        ini_set( 'display_errors', 0 );
        break;
      default:

        error_reporting( E_ALL );
        ini_set( 'display_errors', 1 );
    }

    // setup localization options
    $extension = new Extension( 'engine' );
    setlocale( LC_ALL, $extension->option( 'default:locale' ) );
    date_default_timezone_set( $extension->option( 'default:timezone' ) );

    // attribute initialization
    self::$collector = new Collector();

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
    $extension = new Extension( 'engine' );

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
  public static function stop( array $content = array(), $buffer = null ) {
    ob_start();

    // call display end event ( the render )
    $extension = new Extension( 'engine' );
    $extension->trigger( self::EVENT_STOP, array( 'content' => $content,
                                                  'buffer' => $buffer ) );

    return ob_get_clean();
  }

  /**
   * Redirect to an url with header or javascript redirect
   *
   * @param mixed $url  The new url. It will be converted to string
   * @param int   $code HTTP Redirect type respsonse code. This number added to 300 to make 30x status code
   * @param bool  $stop Call the page stop() method ot not
   */
  public static function redirect( $url, $code = 3, $stop = false ) {
    $url = ltrim( trim( $url, ' ' ), '/' );

    // add url base if the url doesn't contains protocol
    if( !preg_match( '#^[a-z]+\://#i', $url ) ) $url = _URL_BASE . $url;

    // switch between javascript and header redirection based on the header state
    if( headers_sent() ) echo "<script>document.location.href='" . str_replace( "'", "&apos;", $url ) . "';</script>";
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
    return self::$collector;
  }
}