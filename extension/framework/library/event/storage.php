<?php namespace Framework\Event;

use Framework\Event;
use Framework\Exception;
use Framework\Extension;
use Framework\Helper\Library;
use Framework\Storage\File as StorageFile;

/**
 * Class Storage
 * @package Framework\Event
 */
class Storage extends Library {

  /**
   * The static listener storage (file based)
   *
   * @var StorageFile
   */
  private static $source;

  /**
   * Static listener status flag
   *
   * @var bool
   */
  private $loaded = false;

  /**
   * Attached listener list
   *
   * @var Listener[]
   */
  private $_list = [ ];

  /**
   * @param Event $event
   */
  public function __construct( Event $event ) {

    $this->_event = $event;
  }

  /**
   * Register new event listener
   *
   * @param Listener $listener
   */
  public function add( Listener $listener ) {
    array_push( $this->_list, $listener );
  }
  /**
   * Remove an event listener
   *
   * @param Listener $listener
   */
  public function remove( Listener $listener ) {

    $this->load();
    foreach( $this->_list as $i => $item ) {
      if( $item === $listener ) {

        array_splice( $this->_list, $i, 1 );
        $this->_list = array_values( $this->_list );
      }
    }
  }

  /**
   * Clear all event listener
   *
   * @param bool $deep Don't reload the static listeners
   */
  public function clear( $deep = false ) {

    $this->_list  = [ ];
    $this->loaded = $deep;
  }

  /**
   * Load the static listeners
   */
  protected function load() {

    if( !$this->loaded ) {
      $this->loaded = true;

      // create the static source handler
      if( !isset( self::$source ) ) {

        $extension    = Extension::instance( 'framework' );
        self::$source = new StorageFile( $extension->directory( 'asset/event' ) );

        self::$source->getConverter()->native = true;
      }

      // try to create the listeners
      $tmp = self::$source->getArray( (string) $this->_event );
      foreach( $tmp as $listener ) try {

        array_unshift( $this->_list, new Event\Listener(
          isset( $listener->library ) ? $listener->library : null,
          isset( $listener->data ) ? $listener->data : [ ],
          !empty( $listener->enable )
        ) );

      } catch( \Exception $e ) {

        // log: *
        Exception\Helper::wrap( $e )->log( [
          'event' => (string) $this->_event
        ] );
      }
    }
  }

  /**
   * @return Listener[]
   */
  public function getList() {

    $this->load();
    return $this->_list;
  }
}
