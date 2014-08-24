<?php namespace Engine\Request\Session;

use Engine\Extension\Extension;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * @todo    recheck the whole class
 *
 * Class Handler
 * @package Engine\Request\Session
 */
abstract class Handler {

  const EVENT_START = 'session.start';
  const EVENT_START_AFTER = 'session.start.after';
  const EVENT_DESTROY = 'session.destroy';
  const EVENT_DESTROY_AFTER = 'session.destroy.after';
  const EVENT_GENERATE = 'session.generate';

  /**
   * Start or resume the session
   *
   * @param bool  $new
   * @param array $options
   *
   * @return bool
   */
  public static function start( $new = false, array $options = array() ) {

    $extension = new Extension('.engine');
    $e = $extension->trigger( self::EVENT_START, array( 'new' => $new, 'options' => array() ) );
    if( !$e->prevented ) {

      // restart the session if necessary
      if( $new ) {
        self::destroy();
        session_id( self::generate() );
      }

      // if session dont start, terminate the application
      if( !session_start() ) {
        $extension->trigger( self::EVENT_START_AFTER, array( 'result' => false, 'new' => $new, 'options' => array() ) );

        return false;
      }

      $extension->trigger( self::EVENT_START_AFTER, array( 'result' => true, 'new' => $new, 'options' => array() ) );
    }

    return true;
  }

  /**
   * Destroy the session, and clear $_SESSION
   * array
   *
   * @return bool
   */
  public static function destroy() {

    $extension = new Extension('.engine');
    $e = $extension->trigger( self::EVENT_DESTROY );
    if( !$e->prevented ) {

      // unset and reinitialise session variable
      session_unset();
      $_SESSION = array();

      // If it's desired to kill the session, also delete the session cookie. ( from PHP Manual )
      if( ini_get( 'session.use_cookies' ) ) {
        $params = session_get_cookie_params();
        setcookie( session_name(), '', time() - 42000, $params [ 'path' ], $params [ 'domain' ], $params [ 'secure' ], $params [ 'httponly' ] );
      }

      // destroy the session and restart it ( if needed )
      $result = @session_destroy();

      $extension->trigger( self::EVENT_DESTROY_AFTER );

      return $result;
    }

    return true;
  }

  /**
   * Destroy and start the session. If restore is true, the $_SESSION
   * content is saved and restored after the start
   *
   * @param bool $restore
   */
  public static function restart( $restore = false ) {

    // save the session to restore
    $saved = $_SESSION;

    // restart the session
    self::start( true );

    // restore session if need
    if( $restore ) $_SESSION = $_SESSION + $saved;
  }

  /**
   * Generate uniq identifier for the session
   *
   * @return string
   */
  private static function generate() {

    $extension = new Extension('.engine');
    $e = $extension->trigger( self::EVENT_GENERATE );
    $results = $e->getResultList();

    return !$e->prevented && !count( $results ) ? md5( uniqid( rand(), true ) ) : $results[ 0 ];
  }
}