<?php namespace Framework\Exception;

use Framework\Exception;
use Framework\Helper\Library;

/**
 * Class Collector
 * @package Framework\Exception
 */
class Collector extends Library implements \Iterator, \Countable {

  /**
   * Exception storage
   *
   * @var Exception[]
   */
  private $storage = [ ];
  /**
   * Cursor for the iterator implementation
   *
   * @var int
   */
  private $cursor = 0;

  /**
   * Store an exception or every exception from a Collector object
   *
   * @param mixed $input
   *
   * @return bool True if at least one Exception is stored
   */
  public function add( $input ) {

    if( !Helper::is( $input ) ) return false;
    else {

      if( $input instanceof \Exception ) $input = [ Helper::wrap( $input ) ];
      foreach( $input as $exception ) {
        $this->storage[] = $exception;
      }

      return true;
    }
  }
  /**
   * Check if contains any exception that match with the filters
   *
   * @param string|null $id    The exception's id filter
   * @param int         $level The exception's minimum log level
   *
   * @return bool
   */
  public function exist( $id = null, $level = \Framework::LEVEL_NOTICE ) {

    // check for simple matches
    if( empty( $id ) && $level >= \Framework::LEVEL_NOTICE ) return !empty( $this->storage );
    else foreach( $this->storage as $exception ) {
      if( Helper::match( $exception, $id ) && $exception->level <= $level ) {
        return true;
      }
    }

    return false;
  }

  /**
   * @param bool $top Return based on the level or the storage order
   *
   * @return \Exception
   */
  public function get( $top = false ) {

    if( !$this->exist() ) return null;
    else {

      $count     = count( $this->storage );
      $exception = $this->storage[ $count - 1 ];
      if( $top ) for( $i = $count - 2; $i >= 0; --$i ) {
        if( $exception->level > $this->storage[ $i ]->level ) {
          $exception = $this->storage[ $i ];
        }
      }

      return $exception;
    }
  }
  /**
   * Get stored exception list from the collector
   *
   * @param string|null $id    The exception's id filter
   * @param int         $level The exception's minimum level
   *
   * @return Exception[]
   */
  public function getList( $id = null, $level = \Framework::LEVEL_NOTICE ) {

    // check for simple matches
    if( empty( $id ) && $level >= \Framework::LEVEL_NOTICE ) return $this->storage;
    else {

      $list = [ ];
      foreach( $this->storage as $exception ) {
        if( Helper::match( $exception, $id ) && $exception->level <= $level ) {
          $list[] = $exception;
        }
      }

      return $list;
    }
  }

  /**
   * @inheritdoc
   *
   * @return Exception
   */
  public function current() {
    return $this->storage[ $this->cursor ];
  }
  /**
   * @inheritdoc
   */
  public function next() {
    ++$this->cursor;
  }
  /**
   * @inheritdoc
   *
   * @return int
   */
  public function key() {
    return $this->cursor;
  }
  /**
   * @inheritdoc
   *
   * @return bool
   */
  public function valid() {
    return isset( $this->storage[ $this->cursor ] );
  }
  /**
   * @inheritdoc
   */
  public function rewind() {
    $this->cursor = 0;
  }

  /**
   * @inheritdoc
   *
   * @return int
   */
  public function count() {
    return count( $this->storage );
  }
}
