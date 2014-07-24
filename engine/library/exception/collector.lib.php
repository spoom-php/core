<?php namespace Engine\Exception;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * This class can collect \Exception-s from anywhere
 * and return or test the collected exceptions so many ways.
 * The best thing is it can collect function return with only
 * one operation with testr() method
 *
 * Class Collector
 * @package Engine\Exception
 */
class Collector {

  /**
   * The last saved \Exception
   *
   * @var \Exception
   */
  private $last_exception = null;

  /**
   * \Exception storage
   *
   * @var array
   */
  private $storage_exceptions = array();

  /**
   * Store an \Exception object ( if it is )
   *
   * @param mixed $object
   *
   * @return $this
   */
  public function add( $object ) {

    // check if it's an exception object
    $this->testr( $object, true );

    // keep chain
    return $this;
  }

  /**
   * Test first param for \Exception instance or Collector and store it if need.
   * It will return the given object or boolean if it was an \Exception
   *
   * @param mixed   $object
   * @param boolean $store
   *
   * @return mixed
   */
  public function &testr( &$object, $store = true ) {

    // if not object, no need to check further
    if( is_object( $object ) ) {

      // is \Exception
      if( $object instanceof \Exception ) {

        // ..if needed save
        if( $store ) $this->store( $object );

        // php reference notice fix
        $true = true;

        return $true;
      }

      // if is an Collector, merge it!
      if( $object instanceof Collector ) {

        if( $store ) {
          $exceptions = $object->getExceptionList();
          foreach( $exceptions as $e ) $this->store( $e );
        }

        $result = !$object->hasException();

        return $result;
      }
    }

    return $object;
  }

  /**
   * Test first param for \Exception instance or Collector and store it
   * if need. It will return the given object or boolean if it was an
   * \Exception No reference!
   *
   * @param mixed   $object
   * @param boolean $store
   *
   * @return mixed
   */
  public function test( $object, $store = true ) {
    return $this->testr( $object, $store );
  }

  /**
   * Return true if instance contains at least one \Exception
   *
   * @param number|null $filter the filtered error code
   *
   * @return bool
   */
  public function hasException( $filter = null ) {

    if( $filter > 0 ) {
      foreach( $this->storage_exceptions as $e ) if( $e instanceof Exception && $e->getCode() == $filter ) return true;

      return false;
    }
    else return isset( $this->last_exception );

  }

  /**
   * Get last ( or the indexed ) \Exception
   *
   * @param number $index
   *
   * @return \Exception
   */
  public function getException( $index = null ) {

    // handle last getter
    if( $index === null ) return $this->last_exception;

    // check and return index
    return is_numeric( $index ) && isset( $this->storage_exceptions[ $index ] ) ? $this->storage_exceptions[ $index ] : null;
  }

  /**
   * Get \Exception list from the collector within the limit and max
   *
   * @param number $limit
   * @param number $max
   *
   * @return Exception[]
   */
  public function getExceptionList( $limit = null, $max = null ) {
    $storage = & $this->storage_exceptions;

    // if limit not set, return the whole array
    if( !is_numeric( $limit ) ) return $storage;

    $return = array();
    $count  = count( $storage );

    // list from zero to limit
    if( !is_numeric( $max ) ) for( $i = 0; $i < $limit && $i < $count; ++$i ) $return[ ] = $storage[ $i ];

    // list from limit to max
    else for( $i = $limit; $i < $max && $i < $count; ++$i ) $return[ ] = $storage[ $i ];

    return $return;
  }

  /**
   * Store \Exception object
   *
   * @param \Exception $exception
   *
   * @return self
   */
  private function store( \Exception &$exception ) {

    $this->storage_exceptions[ ] = & $exception;
    $this->last_exception        = & $exception;

    // keep chain
    return $this;
  }
}