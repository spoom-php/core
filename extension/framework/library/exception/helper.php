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
  const EXCEPTION_UNKNOWN = 'framework#0E';
  /**
   * Id for wrap the native \Exception instances
   */
  const EXCEPTION_WRAP = 'framework#1E';
  /**
   * Exception when the extension id is not like ::REGEXP_ID
   */
  const EXCEPTION_INVALID_ID = 'framework#3N';

  /**
   * Exception id format
   */
  const REGEXP_ID = '/^([a-z0-9_\\-]+)(#([0-9]+)([NWEC]?))?$/';

  /**
   * Map the types to the corresponding level
   *
   * @var int[string]
   */
  private static $LEVEL = [
    Exception::TYPE_CRITICAL => \Framework::LEVEL_CRITICAL,
    Exception::TYPE_ERROR    => \Framework::LEVEL_ERROR,
    Exception::TYPE_WARNING  => \Framework::LEVEL_WARNING,
    Exception::TYPE_NOTICE   => \Framework::LEVEL_NOTICE
  ];
  /**
   * Map the levels to the corresponding type
   *
   * @var int[string]
   */
  private static $TYPE = [
    \Framework::LEVEL_CRITICAL => Exception::TYPE_CRITICAL,
    \Framework::LEVEL_ERROR    => Exception::TYPE_ERROR,
    \Framework::LEVEL_WARNING  => Exception::TYPE_WARNING,
    \Framework::LEVEL_NOTICE   => Exception::TYPE_NOTICE
  ];

  /**
   * Test the given object instance is \Exception or Collector and has \Exception
   *
   * @param mixed $input
   *
   * @return boolean
   */
  public static function is( $input ) {
    return $input instanceof \Exception || ( $input instanceof Collector && $input->exist() );
  }
  /**
   * Warp native \Exceptions with ::EXCEPTION_WRAP id. This will leave \Framework\Exception classes untouched
   *
   * @param \Exception $exception
   *
   * @return Runtime
   */
  public static function wrap( \Exception $exception ) {
    return $exception instanceof Exception ? $exception : new Runtime( self::EXCEPTION_WRAP, [
      'message' => $exception->getMessage(),
      'code'    => $exception->getCode()
    ], $exception );
  }
  /**
   * Compare and exception' id to a valid exception id
   *
   * @param \Exception $exception
   * @param string     $id
   *
   * @return bool
   */
  public static function match( \Exception $exception, $id ) {

    $raw = $exception instanceof Exception ? (object) [
      'extension' => $exception->extension->id,
      'code'      => $exception->getCode(),
      'type'      => $exception->type
    ] : self::parse( static::EXCEPTION_WRAP );

    if( empty( $id ) ) return true;
    else {

      $id = self::parse( $id );
      if( empty( $id ) ) return false;
      if( $raw->extension != $id->extension ) return false;
      else if( $id->code >= 0 && $raw->code != $id->code ) return false;
      else if( !empty( $id->type ) && $raw->type != $id->type ) return false;
      else return true;
    }
  }

  /**
   * Exception id parser to information object
   *
   * @param string $id ::REGEXP_ID formatted string
   *
   * @return object { extension: string, code: int, type: char }
   */
  public static function parse( $id ) {

    if( empty( $id ) ) return null;
    else {

      $matches = [ ];
      if( !preg_match( self::REGEXP_ID, $id, $matches ) ) return null;
      else return (object) [
        'extension' => $matches[ 1 ],
        'code'      => !isset( $matches[ 3 ] ) ? -1 : (int) $matches[ 3 ],
        'type'      => empty( $matches[ 4 ] ) ? '' : $matches[ 4 ],
      ];
    }
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
   * Get Exception type postfix character from the level number
   *
   * @since ?
   *
   * @param int $level Framework::LEVEL_* constant
   *
   * @return string|null
   */
  public static function getType( $level ) {
    return isset( self::$TYPE[ $level ] ) ? self::$TYPE[ $level ] : null;
  }
  /**
   * Get the level number from the given Exception type character
   *
   * @since ?
   *
   * @param string $type Exception::TYPE_* contant
   *
   * @return int|null
   */
  public static function getLevel( $type ) {
    return isset( self::$LEVEL[ $type ] ) ? self::$LEVEL[ $type ] : null;
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
