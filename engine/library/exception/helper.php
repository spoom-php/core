<?php namespace Engine\Exception;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Helper
 * @package Engine\Exception
 */
abstract class Helper {

  /**
   * Intelligent throwing method. It throws from given objects which is an \Exception
   * object or Collector with at least one \Exception. If one of the given object is === false
   * and the last object is an \Exception object or Collector with at least one \Exception
   * then throws the last given object.
   *
   * note: the last given object only used for false throw!
   *
   * @param mixed $objects
   * @param null $_
   *
   * @return array
   * @throws \Exception
   */
  public static function &thrower( $objects, $_ = null ) {

    if( func_num_args() == 1 ) {

      // check if the object is throwable
      if( self::isException( $objects ) ) {

        // if it's an eCollector throw the first exception
        if( $objects instanceof Collector ) throw $objects->getException();
        else if( $objects instanceof \Exception ) throw $objects;
      }

    } else {

      // Declare usable variables
      $throw_alter = is_array( $objects ) ? $_ : null;
      $objects = is_array( $objects ) ? $objects : func_get_args();
      $num = count( $objects );
      if( $throw_alter == null ) $throw_alter = isset( $objects[ $num - 1 ] ) ? $objects[ $num - 1 ] : null;

      // iterate trought the given objects
      for( $i = 0; $i < $num - 1; ++$i ) {
        $o = $objects[ $i ];

        if( $o === false ) self::thrower( $throw_alter );
        else self::thrower( $o );
      }
    }

    return $objects;
  }

  /**
   * Test the given object instance is \Exception or Collector and has \Exception
   *
   * @param mixed $object
   *
   * @return boolean
   */
  public static function isException( $object ) {
    return $object instanceof \Exception || ( $object instanceof Collector && $object->hasException() );
  }
}