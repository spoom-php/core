<?php namespace Engine\Exception;

use Engine\Exception;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * This class can collect \Exception-s and return the collected exceptions
 *
 * @package Engine\Exception
 */
class Collector implements \Iterator, \Countable {

  /**
   * The last saved \Exception
   *
   * @var \Exception
   */
  private $last = null;

  /**
   * \Exception storage
   *
   * @var array
   */
  private $storage = [ ];

  /**
   * Cursor for the iterator implementation
   *
   * @var int
   */
  private $cursor = 0;

  /**
   * Store an \Exception or Collector objects ( if it is )
   *
   * @param mixed $object
   *
   * @return boolean true, if at least one \Exception is stored
   */
  public function add( $object ) {

    if( !Helper::is( $object ) ) return false;
    else {

      $this->store( $object );
      return true;
    }
  }
  /**
   * Return true if instance contains at least one \Exception
   *
   * @param number|null $filter the filtered error code
   *
   * @return bool
   */
  public function contains( $filter = null ) {

    if( $filter > 0 ) {
      foreach( $this->storage as $e ) if( $e instanceof Exception && $e->id == $filter ) return true;

      return false;
    } else return isset( $this->last );

  }

  /**
   * Get last ( or the indexed ) \Exception
   *
   * @param number $index
   *
   * @return \Exception
   */
  public function get( $index = null ) {

    // handle last getter
    if( $index === null ) return $this->last;

    // check and return index
    return is_numeric( $index ) && isset( $this->storage[ $index ] ) ? $this->storage[ $index ] : null;
  }
  /**
   * Get \Exception list from the collector within the offset and limit
   *
   * @param number $offset
   * @param number $limit
   *
   * @return Exception[]
   */
  public function getList( $offset = null, $limit = null ) {
    $storage = &$this->storage;

    // if limit not set, return the whole array
    if( !is_numeric( $offset ) ) return $storage;

    $return = [ ];
    $count  = count( $storage );

    // list from zero to limit
    if( !is_numeric( $limit ) ) for( $i = 0; $i < $offset && $i < $count; ++$i ) $return[ ] = $storage[ $i ];

    // list from limit to max
    else for( $i = $offset; $i < $limit && $i < $count; ++$i ) $return[ ] = $storage[ $i ];

    return $return;
  }

  /**
   * Store \Exception object or Collector contained objects
   *
   * @param \Exception|Collector $exception
   *
   * @return self
   */
  private function store( $exception ) {

    if( $exception instanceof \Exception ) $exception = [ $exception ];
    foreach( $exception as $e ) {
      $this->storage[ ] = $e;
      $this->last       = $e;
    }

    // keep chain
    return $this;
  }

  /**
   * Return the current element
   * @link http://php.net/manual/en/iterator.current.php
   *
   * @return mixed Can return any type.
   */
  public function current() {
    return $this->get( $this->cursor );
  }
  /**
   * Move forward to next element
   * @link http://php.net/manual/en/iterator.next.php
   *
   * @return void Any returned value is ignored.
   */
  public function next() {
    $this->cursor++;
  }
  /**
   * Return the key of the current element
   * @link http://php.net/manual/en/iterator.key.php
   *
   * @return mixed scalar on success, or null on failure.
   */
  public function key() {
    return $this->cursor;
  }
  /**
   * Checks if current position is valid
   * @link http://php.net/manual/en/iterator.valid.php
   *
   * @return boolean The return value will be casted to boolean and then evaluated.
   * Returns true on success or false on failure.
   */
  public function valid() {
    return $this->get( $this->cursor ) !== null;
  }
  /**
   * Rewind the Iterator to the first element
   * @link http://php.net/manual/en/iterator.rewind.php
   *
   * @return void Any returned value is ignored.
   */
  public function rewind() {
    $this->cursor = 0;
  }
  /**
   * Count elements of an object
   * @link http://php.net/manual/en/countable.count.php
   *
   * @return int The custom count as an integer. The return value is cast to an integer.
   */
  public function count() {
    return count( $this->getList() );
  }
}