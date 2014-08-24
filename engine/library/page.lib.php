<?php namespace Engine;

use Engine\Exception\Collector;
use Engine\Extension\Extension;
use Engine\Request\Session\Handler as SessionHandler;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Page
 * @package Engine
 */
abstract class Page {

  const EVENT_INITIALISE = 'initialise';
  const EVENT_RENDER = 'render';
  const EVENT_FINISH = 'finish';

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
    if( self::$initialise ) return;
    self::$initialise = true;
    if( _REPORTING == 1 ) ob_start();

    // attribute initialization
    self::$exceptions = new Collector();

    // output buffer
    SessionHandler::start();

    // Call initialise event
    $extension = new Extension( '.engine' );
    $extension->trigger( self::EVENT_INITIALISE );

    if( $render ) self::render();
  }

  /**
   * Render the page
   */
  public static function render() {

    // call display event to let extensions render the content
    $extension = new Extension( '.engine' );
    $e       = $extension->trigger( self::EVENT_RENDER );
    $content = $e->getResultList();

    // clean output buffer and call display end event ( the render )
    $buffer = _REPORTING == 1 ? ob_get_clean() : null;
    $extension->trigger( self::EVENT_FINISH, array( 'content' => $content,
                                                    'buffer'  => $buffer ) );
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