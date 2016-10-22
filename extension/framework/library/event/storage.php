<?php namespace Framework\Event;

use Framework\Event;
use Framework\Exception;
use Framework\Extension;
use Framework\Helper;
use Framework\Storage\File as StorageFile;
use Framework\Converter;

/**
 * Class Storage
 * @package Framework\Event
 *
 * @property-read Listener[] $list Attached listeners
 * @property-read Event      $event
 */
class Storage implements \Countable, \Iterator, Helper\AccessableInterface {
  use Helper\Accessable;

  const DIRECTORY_SOURCE = 'asset/event/';

  /**
   * The static listener storage (file based)
   *
   * @var StorageFile
   */
  private static $source;

  /**
   * Iterator cursor
   *
   * @var int
   */
  private $cursor = 0;
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
  private $_list = [];

  /**
   * @var Event
   */
  private $_event;

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

    $this->_list  = [];
    $this->loaded = $deep;
  }

  /**
   * Load the static listeners
   */
  protected function load() {

    // load only once
    if( !$this->loaded ) {
      $this->loaded = true;

      // create the static source handler
      if( !isset( self::$source ) ) {

        $extension    = Extension::instance( 'framework' );
        self::$source = new StorageFile( $extension->directory( self::DIRECTORY_SOURCE ), [
          new Converter\Json( JSON_PRETTY_PRINT ),
          new Converter\Xml()
        ] );
      }

      // try to create the listeners (the reverse order is for the unshifting)
      $tmp = array_reverse( self::$source->getArray( (string) $this->_event ) );
      foreach( $tmp as $listener ) try {

        array_unshift( $this->_list, new Event\Listener(
          isset( $listener->library ) ? $listener->library : null,
          isset( $listener->data ) ? $listener->data : [],
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
  /**
   * @return Event
   */
  public function getEvent() {
    return $this->_event;
  }

  /**
   * @inheritdoc
   */
  public function current() {

    $this->load();
    return $this->_list[ $this->cursor ];
  }
  /**
   * @inheritdoc
   */
  public function next() {
    ++$this->cursor;
  }
  /**
   * @inheritdoc
   */
  public function key() {
    return $this->cursor;
  }
  /**
   * @inheritdoc
   */
  public function valid() {

    $this->load();
    return isset( $this->_list[ $this->cursor ] );
  }
  /**
   * @inheritdoc
   */
  public function rewind() {
    $this->cursor = 0;
  }

  /**
   * @inheritdoc
   */
  public function count() {

    $this->load();
    return count( $this->_list );
  }
}
