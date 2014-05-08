<?php namespace Engine\Event;

use Engine\Exception\Exception;
use Engine\Extension\Extension;
use Engine\Extension\Helper as ExtensionHelper;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Helper
 * @package Engine\Events
 */
abstract class Helper {

  /**
   * @todo clear non exist events after the reload
   *
   * Reload all event listener but keep their exist options
   *
   * @return Exception on error, else true
   */
  public static function reload() {
    $extensions = ExtensionHelper::get();
    $storage = new Storage();
    $tmp = array();

    // collect extension listeners
    foreach( $extensions as $e ) {

      try {
        $e = new Extension( $e );
        $listeners = $e->configuration->get( 'listener:' );

        if( !is_array( $listeners ) || !count( $listeners ) ) continue;

        foreach( $listeners as $listener => $events ) {

          foreach( $events as $event ) {

            // collect exist data
            $event = str_replace( '.', '-', $event );
            $key = $event . ':' . $listener;
            $exist = $storage->geto( $key, new \stdClass() );

            // set exists data
            $exist->order = isset( $exist->order ) ? $exist->order : -1;
            $exist->enabled = isset( $exist->enabled ) ? !!$exist->enabled : true;

            // store in the storage
            @$tmp[ $event ][ $listener ] = $exist;
          }
        }
      } catch( \Exception $e ) {
      }
    }

    foreach( $tmp as $event => $listeners ) {

      // order listeners in the event
      uasort( $listeners, function ( $a, $b ) {
        if( $a->order == -1 ) return 1;
        if( $b->order == -1 ) return -1;

        return $a->order - $b->order;
      } );

      // normalise order numbers
      $i = 0;
      foreach( $listeners as &$v ) $v->order = $i++;

      $storage->set( $event . ':', $listeners )
              ->save( $event, 'json' );
    }
  }
}