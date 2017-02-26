<?php namespace Framework\Exception;

use Framework\Exception;
use Framework;
use Framework\Application;

/**
 * Class Collector
 * @package Framework\Exception
 *
 * @property-read Exception[] $list Stored exceptions
 */
class Collector implements \Iterator, \Countable, Framework\Helper\AccessableInterface {
  use Framework\Helper\Accessable;

  /**
   * Exception storage
   *
   * @var Exception[]
   */
  private $_list = [];
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

    if( !Exception\Helper::is( $input ) ) return false;
    else {

      if( $input instanceof \Exception ) $input = [ Exception\Helper::wrap( $input ) ];
      foreach( $input as $exception ) {
        $this->_list[] = $exception;
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
  public function exist( $id = null, $level = Application::LEVEL_NOTICE ) {

    // check for simple matches
    if( empty( $id ) && $level >= Application::LEVEL_NOTICE ) return !empty( $this->_list );
    else foreach( $this->_list as $exception ) {
      if( Exception\Helper::match( $exception, $id ) && $exception->level <= $level ) {
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

      $count     = count( $this->_list );
      $exception = $this->_list[ $count - 1 ];
      if( $top ) for( $i = $count - 2; $i >= 0; --$i ) {
        if( $exception->level > $this->_list[ $i ]->level ) {
          $exception = $this->_list[ $i ];
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
  public function getList( $id = null, $level = Application::LEVEL_NOTICE ) {

    // check for simple matches
    if( empty( $id ) && $level >= Application::LEVEL_NOTICE ) return $this->_list;
    else {

      $list = [];
      foreach( $this->_list as $exception ) {
        if( Exception\Helper::match( $exception, $id ) && $exception->level <= $level ) {
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
   *
   * @return int
   */
  public function count() {
    return count( $this->_list );
  }
}
