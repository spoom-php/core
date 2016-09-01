<?php namespace Framework\Event;

use Framework\Event;
use Framework\Exception;
use Framework\Extension;
use Framework\Helper\Library;
use Framework\Storage\File as StorageFile;
use Framework\Helper\Converter;

/**
 * Class Storage
 * @package Framework\Event
 */
class Storage extends Library implements \Countable, \Iterator {

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
  private $_list = [ ];

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

    $this->_list  = [ ];
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
  /**
   * @return Event
   */
  public function getEvent() {
    return $this->_event;
  }

  /**
   * Return the current element
   * @link  http://php.net/manual/en/iterator.current.php
   * @return mixed Can return any type.
   * @since 5.0.0
   */
  public function current() {

    $this->load();
    return $this->_list[ $this->cursor ];
  }
  /**
   * Move forward to next element
   * @link  http://php.net/manual/en/iterator.next.php
   * @return void Any returned value is ignored.
   * @since 5.0.0
   */
  public function next() {
    ++$this->cursor;
  }
  /**
   * Return the key of the current element
   * @link  http://php.net/manual/en/iterator.key.php
   * @return mixed scalar on success, or null on failure.
   * @since 5.0.0
   */
  public function key() {
    return $this->cursor;
  }
  /**
   * Checks if current position is valid
   * @link  http://php.net/manual/en/iterator.valid.php
   * @return boolean The return value will be casted to boolean and then evaluated.
   * Returns true on success or false on failure.
   * @since 5.0.0
   */
  public function valid() {

    $this->load();
    return isset( $this->_list[ $this->cursor ] );
  }
  /**
   * Rewind the Iterator to the first element
   * @link  http://php.net/manual/en/iterator.rewind.php
   * @return void Any returned value is ignored.
   * @since 5.0.0
   */
  public function rewind() {
    $this->cursor = 0;
  }

  /**
   * Count elements of an object
   * @link  http://php.net/manual/en/countable.count.php
   * @return int The custom count as an integer.
   * </p>
   * <p>
   * The return value is cast to an integer.
   * @since 5.1.0
   */
  public function count() {

    $this->load();
    return count( $this->_list );
  }
}
