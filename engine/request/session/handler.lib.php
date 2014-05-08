<?php namespace Engine\Request\Session;

use Engine\Event\Event;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * @todo    recheck the whole class
 *
 * Class Handler
 * @package Engine\Request\Session
 */
abstract class Handler {

  /**
   * Start or resume the session
   *
   * @param bool  $new
   * @param array $options
   *
   * @return bool
   */
  public static function start( $new = false, array $options = array() ) {

    $eobject = array( 'new'     => $new,
                      'options' => array() );
    $e       = new Event( 'engine.session.start.before', $eobject );

    if( !$e->prevented ) {

      // restart the session if necessary
      if( $new ) {
        self::destroy();
        session_id( self::generate() );
      }

      // if session dont start, terminate the application
      if( !session_start() ) {
        new Event( 'engine.session.start.failed', $eobject );

        return false;
      }

      new Event( 'engine.session.start.after', $eobject );
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

    $e = new Event( 'engine.session.destroy.before' );
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

      new Event( 'engine.session.destroy.after' );

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
    $e       = new Event( 'engine.session.generate' );
    $results = $e->getResultList();

    return !$e->prevented && !count( $results ) ? md5( uniqid( rand(), true ) ) : $results[ 0 ];
  }
}