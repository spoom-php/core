<?php namespace Framework\Exception;

use Framework\Exception;
use Framework\Extension;

/**
 * Class Helper
 * @package Framework\Exception
 */
abstract class Helper {

  /**
   * Id for unknown exception
   */
  const EXCEPTION_ERROR_UNKNOWN = 'framework#0E';
  /**
   * Id for wrap the native \Exception instances
   */
  const EXCEPTION_ERROR_WRAP = 'framework#1E';
  /**
   * Exception when the extension id is not like ::REGEXP_ID
   */
  const EXCEPTION_NOTICE_INVALID_ID = 'framework#3N';

  /**
   * Exception id format
   */
  const REGEXP_ID = '/^([a-z\\-]+)#([0-9]+)([NWEC])$/';

  /**
   * Test the given object instance is \Exception or Collector and has \Exception
   *
   * @param mixed $object
   *
   * @return boolean
   */
  public static function is( $object ) {
    return $object instanceof \Exception || ( $object instanceof Collector && $object->contains() );
  }

  /**
   * Exception id parser to information object
   *
   * @param string $id ::REGEXP_ID formatted string
   *
   * @return object { extension: string, code: int, type: char }
   * @throws Strict ::EXCEPTION_INVALID_ID when the ID format is wrong
   */
  public static function parse( $id ) {

    // validate the id
    $matches = [ ];
    if( !preg_match( self::REGEXP_ID, $id, $matches ) ) throw new Strict( self::EXCEPTION_NOTICE_INVALID_ID, [ 'id' => $id ] );
    else return (object) [
      'extension' => $matches[ 1 ],
      'code'      => (int) $matches[ 2 ],
      'type'      => $matches[ 3 ],
    ];
  }
  /**
   * Build the exception message
   *
   * @param Extension $extension The exception message source
   * @param string    $code      The exception code (with the type postix)
   * @param array     $data      The insertion data to the message
   *
   * @return string
   */
  public static function build( Extension $extension, $code, array $data = [ ] ) {

    $tmp = $extension->text( 'framework-exception:#' . $code, $data, null );
    return $tmp ? $tmp : $extension->text( 'framework-exception:#' . ( (int) $code ), $data );
  }
  /**
   * Extract exception data into array format
   *
   * @param \Exception $exception The exception to convert
   * @param bool       $more      Append additional data to the result
   *
   * @return array|null
   */
  public static function convert( \Exception $exception = null, $more = false ) {

    if( empty( $exception ) ) return null;
    else if( $exception instanceof Exception ) return $exception->toArray( $more );
    else {

      $tmp = [ 'code' => $exception->getCode(), 'message' => $exception->getMessage() ];
      if( $more ) {

        $tmp[ 'line' ]     = $exception->getFile() . ':' . $exception->getLine();
        $tmp[ 'trace' ]    = $exception->getTrace();
        $tmp[ 'previous' ] = self::convert( $exception->getPrevious(), $more );
      }

      return $tmp;
    }
  }

  /**
   * Warp native \Exceptions with ::EXCEPTION_ERROR_WRAP id. This will leave \Framework\Exception classes untouched
   *
   * @param \Exception $exception
   *
   * @return Runtime
   */
  public static function wrap( \Exception $exception ) {
    return $exception instanceof Exception ? $exception : new Runtime( self::EXCEPTION_ERROR_WRAP, [
      'message' => $exception->getMessage(),
      'code'    => $exception->getCode()
    ], $exception );
  }

  /**
   * Intelligent throwing method. It throws from given objects which is an \Exception
   * object or Collector with at least one \Exception. If one of the given object is === false
   * and the last object is an \Exception object or Collector with at least one \Exception
   * then throws the last given object.
   *
   * note: the last given object only used for false throw!
   *
   * @param mixed $objects
   * @param null  $args
   *
   * @return array
   * @throws \Exception
   */
  public static function &thrower( $objects, $args = null ) {

    if( func_num_args() == 1 ) {

      // check if the object is throwable
      if( self::is( $objects ) ) {

        // if it's an eCollector throw the first exception
        if( $objects instanceof Collector ) throw $objects->get();
        else if( $objects instanceof \Exception ) throw self::wrap( $objects );
      }

    } else {

      $throw_alter = is_array( $objects ) ? $args : null;
      $objects     = is_array( $objects ) ? $objects : func_get_args();
      $num         = count( $objects );
      if( $throw_alter == null ) $throw_alter = isset( $objects[ $num - 1 ] ) ? $objects[ $num - 1 ] : null;

      // iterate trought the given objects
      for( $i = 0; $i < $num - 1; ++$i ) {
        $object = $objects[ $i ];

        if( $object === false ) self::thrower( $throw_alter );
        else self::thrower( $object );
      }
    }

    return $objects;
  }
}
