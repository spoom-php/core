<?php namespace Engine;

use Engine\Exception\Collector;
use Engine\Extension\Extension;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Page
 * @package Engine
 */
abstract class Page {

  const EVENT_START = 'page.start';
  const EVENT_RUN = 'page.run';
  const EVENT_STOP = 'page.stop';

  /**
   * Exception collector, for runtime error collect
   *
   * @var Collector
   */
  private static $exceptions = null;

  /**
   * Execute the page method in the right order (start -> run -> stop). This is the proper (and complete)
   * way to execute the engine. This method handle output buffering before the stop, exception handling after
   * the start and argument passing between run and stop methods
   */
  public static function execute() {
    if( _REPORTING == 1 ) ob_start();
    self::start();

    try {
      $content = self::run();
    } catch( \Exception $e ) {
      self::$exceptions->add( $e );
      $content = [ ];
    }

    $buffer = _REPORTING == 1 ? ob_get_clean() : null;
    self::stop( $content, $buffer );
  }

  /**
   * Trigger page start event and initialise some basics for the Page. This should be called once and
   * before the run
   */
  public static function start() {

    // attribute initialization
    self::$exceptions = new Collector();

    // Call initialise event
    $extension = new Extension( '.engine' );
    $extension->trigger( self::EVENT_START );
  }
  /**
   * Trigger page run event and return the result array. The page contents (for the render) should be in
   * the event results
   *
   * @return array|null The collected page contents
   */
  public static function run() {
    $extension = new Extension( '.engine' );

    // call display event to let extensions render the content
    $event = $extension->trigger( self::EVENT_RUN );
    return $event->getResultList();
  }
  /**
   * Trigger the page stop event with the given arguments. In this method the page should be rendered to
   * the output, based on the content (and maybe the buffer)
   *
   * @param array       $content the page content array
   * @param string|null $buffer additional but propably trash information
   */
  public static function stop( array $content = array(), $buffer = null ) {
    $extension = new Extension( '.engine' );

    // clean output buffer and call display end event ( the render )
    $extension->trigger( self::EVENT_STOP, array( 'content' => $content,
                                                  'buffer'  => $buffer ) );

    exit();
  }

  /**
   * Redirect to an url with header or javascript redirect
   *
   * @param mixed $url The new url. It will be converted to string
   * @param int   $code HTTP Redirect type respsonse code. This number added to 300 to make 30x status code
   * @param bool  $stop Call the page stop() method ot not
   */
  public static function redirect( $url, $code = 3, $stop = false ) {
    $url = trim( $url, ' /' );

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
    return self::$exceptions;
  }
}