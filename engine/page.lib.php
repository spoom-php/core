<?php namespace Engine;

use Engine\Event\Event;
use Engine\Exception\Collector;
use Engine\Exception\Exception;
use Engine\Request\Session\Handler as SessionHandler;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Page
 * @package Engine
 */
abstract class Page {

  /**
   * Prevent doube rendering
   *
   * @var boolean
   */
  private static $initialise = false;

  /**
   * Exception collector, for runtime error collect
   *
   * @var Collector
   */
  private static $exceptions = null;

  public static function initialise( $render = true ) {
    if( self::$initialise ) throw new Exception();
    self::$initialise = true;
    ob_start();

    // FIXME remove line
    \Engine\Event\Helper::reload();

    // attribute initialization
    self::$exceptions = new Collector();

    // output buffer
    SessionHandler::start();

    // Call initialise event
    new Event( 'engine.initialise' );

    if( $render ) self::render();
  }

  /**
   * Render the page
   */
  public static function render() {

    // call display event to let extensions render the content
    $e       = new Event( 'engine.render' );
    $content = $e->getResultList();

    // clean output buffer and call display end event ( the render )
    new Event( 'engine.finish', array( 'content' => $content,
                                       'buffer'  => ob_get_clean() ) );
  }

  /**
   * Getter for collector
   *
   * @return Collector
   */
  public static function &getCollector() {
    return self::$exceptions;
  }
}