<?php namespace Engine\Exception;

use Engine\Extension\Extension;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Helper
 * @package Engine\Exception
 */
abstract class Helper {

  /**
   * Id for unknown exception
   */
  const EXCEPTION_ERROR_UNKNOWN = 'engine#0E';
  /**
   * Id for wrap the native \Exception instances
   */
  const EXCEPTION_ERROR_WRAPPER = 'engine#1E';
  /**
   * Exception when the extension id is not like ::REGEXP_ID
   */
  const EXCEPTION_NOTICE_INVALID_ID = 'engine#3N';

  /**
   * Exception id format
   */
  const REGEXP_ID = '^([a-z\\-]+)#([0-9]+)([NWEC])$';

  /**
   * Exception id parser to information object
   *
   * @param string $id ::REGEXP_ID formatted string
   *
   * @return object { extension: string, code: int, type: char }
   * @throws Runtime ::EXCEPTION_INVALID_ID if the format is wrong
   */
  public static function parse( $id ) {

    // validate the id
    $matches = [ ];
    if( preg_match( self::REGEXP_ID, $id, $matches ) === false ) throw new Runtime( self::EXCEPTION_NOTICE_INVALID_ID, [ 'id' => $id ] );
    else return (object) [
      'extension' => $matches[ 1 ],
      'code'      => (int) $matches[ 2 ],
      'type'      => $matches[ 3 ],
    ];
  }

  /**
   * TODO documentation
   *
   * @param Extension $extension
   * @param string    $code
   * @param array     $data
   *
   * @return string
   */
  public static function build( Extension $extension, $code, array $data = [ ] ) {
    return $extension->text( 'exception:#' . $code, $data );
  }

  /**
   * Intelligent throwing method. It throws from given objects which is an \Exception
   * object or Collector with at least one \Exception. If one of the given object is === false
   * and the last object is an \Exception object or Collector with at least one \Exception
   * then throws the last given object.
   *
   * note: the last given object only used for false throw!
   *
   * TODO upgrade
   * 
*@param mixed $objects
   * @param null  $_
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
      $objects     = is_array( $objects ) ? $objects : func_get_args();
      $num         = count( $objects );
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
   * TODO upgrade
   * 
*@param mixed $object
   *
   * @return boolean
   */
  public static function isException( $object ) {
    return $object instanceof \Exception || ( $object instanceof Collector && $object->hasException() );
  }
}