<?php namespace Spoom\Core\Helper;

/**
 * Class Collection
 *
 * TODO create tests
 *
 */
abstract class Collection {

  /**
   * Test if the variable is a collection (array or object)
   *
   * TODO this should check (optionally) that the object is \SdtClass or implements \Traversable
   *
   * @param mixed $test
   *
   * @return bool True if the test is an array or object
   */
  public static function is( $test ): bool {
    return is_object( $test ) || is_array( $test );
  }
  /**
   * Check for the input is a real numeric array
   *
   * @param mixed $test
   * @param bool  $ordered it will check the index ordering, not just the type
   *
   * @return bool true, if the $data was a real array with numeric indexes
   */
  public static function isArrayNumeric( $test, bool $ordered = true ): bool {

    if( !is_array( $test ) ) return false;
    else if( $ordered ) for( $i = 0; $i < count( $test ); ++$i ) {
      if( !isset( $test[ $i ] ) ) return false;
    } else foreach( $test as $i => $value ) {
      if( !is_int( $i ) ) return false;
    }

    return true;
  }
  /**
   * Extended array test that includes objects with the \ArrayAccess interface
   *
   * @param mixed $test
   *
   * @since 0.6.4
   * @return bool
   */
  public static function isArrayLike( $test ): bool {
    return is_array( $test ) || $test instanceof \ArrayAccess;
  }

  /**
   * Recursive merge of two arrays. This is like the array_merge_recursive() without the strange array-creating thing
   *
   * @param array   $destination
   * @param array[] $sources
   *
   * @return array
   */
  public static function merge( array $destination, array ...$sources ) {

    $result = $destination;
    foreach( $sources as $source ) {
      foreach( $source as $key => &$value ) {
        if( !is_array( $value ) || !isset( $result[ $key ] ) || !is_array( $result[ $key ] ) ) $result[ $key ] = $value;
        else $result[ $key ] = static::merge( $result[ $key ], $value );
      }
    }

    return $result;
  }

  /**
   * Deep copy of a collection
   *
   * @param array|object $input
   *
   * @return array|object
   */
  public static function copy( $input ) {

    if( is_array( $input ) ) {

      $tmp = [];
      foreach( $input as $k => $e ) {
        $tmp[ $k ] = self::is( $e ) ? self::copy( $e ) : $e;
      }

      $input = $tmp;

    } else if( is_object( $input ) ) {

      if( !( $input instanceof \stdClass ) ) $input = clone $input;
      else {

        $tmp = new \stdClass();
        foreach( $input as $k => $e ) {
          $tmp->{$k} = self::is( $e ) ? self::copy( $e ) : $e;
        }
        $input = $tmp;
      }
    }

    return $input;
  }
  /**
   * Convert any input into array. On error/invalid input returns the $default parameter
   *
   * @since ?
   *
   * @param mixed      $input
   * @param array|null $default
   * @param bool       $deep
   *
   * @return array|null
   */
  public static function read( $input, ?array $default = null, bool $deep = false ): ?array {
    if( !static::is( $input ) ) return $default;
    else {

      $tmp = [];
      foreach( $input as $k => $t ) {
        $tmp[ $k ] = $deep && static::is( $t ) ? static::read( $t, null, $deep ) : $t;
      }

      return $tmp;
    }
  }
}
