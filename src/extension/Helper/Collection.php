<?php namespace Spoom\Core\Helper;

use Spoom\Core\StorageInterface;

/**
 * Class Collection
 *
 */
abstract class Collection {

  /**
   * Test if the variable is a collection (array or object)
   *
   * @param mixed $test
   * @param bool  $iterable  Only traversable objects
   * @param bool  $arraylike Only objects with array access
   *
   * @return bool True if the test is an array or (a traversable/arraylike) object
   */
  public static function is( $test, bool $iterable = false, bool $arraylike = false ): bool {
    if( is_array( $test ) ) return true;
    else if( !is_object( $test ) ) return false;
    else return ( !$iterable || $test instanceof \StdClass || $test instanceof \Traversable ) && ( !$arraylike || $test instanceof \ArrayAccess );
  }
  /**
   * Check for the input is a numeric array(like)
   *
   * @param mixed $test
   * @param bool  $ordered it will check the index ordering, not just the type
   *
   * @return bool true, if the $data was a array(like) with numeric indexes
   */
  public static function isNumeric( $test, bool $ordered = true ): bool {
    if( !static::is( $test, true, true ) ) return false;
    else {

      $i = 0;
      foreach( $test as $key => $value ) {
        if( !is_int( $key ) || ( $ordered && $key != $i++ ) ) return false;
      }
    }

    return true;
  }

  /**
   * Recursive merge of two arrays
   *
   * This is like the array_merge_recursive() without the strange array-creating thing
   *
   * @param array|object $destination
   * @param array|object $source
   * @param bool         $deep
   * @param bool         $assoc Handle numeric arrays like associative (overwrite keys, not extend)
   *
   * @return array|object The extended destination
   * @throws \TypeError
   */
  public static function merge( $destination, $source, bool $deep = true, bool $assoc = false ) {

    if( !static::is( $destination, false, true ) ) throw new \TypeError( 'Destination must be array(like)' );
    else if( !static::is( $source, true ) ) throw new \TypeError( 'Source must be iterable' );
    else {

      // handle special case when both arrays are completly numeric
      if( !$assoc && static::isNumeric( $source ) && static::isNumeric( $destination ) ) {

        $index = -1;
        foreach( $destination as $tmp => $_ ) $index = max( $index, $tmp );
      }

      $result = $destination;
      foreach( $source as $key => $value ) {

        $tmp = isset( $index ) ? ++$index : $key;
        if( !$deep || !static::is( $value, true ) || !static::is( $result[ $tmp ] ?? null, true ) ) $result[ $tmp ] = $value;
        else $result[ $tmp ] = static::merge( $result[ $tmp ], $value, $deep );
      }
    }

    return $result;
  }

  /**
   * Deep copy of a collection
   *
   * @param array|object $input
   * @param bool         $deep Deep copy or not
   *
   * @return array|object
   */
  public static function copy( $input, bool $deep = true ) {

    if( !static::is( $input ) ) return $input;
    else {

      if( is_array( $input ) ) {

        $result = [];
        foreach( $input as $k => $e ) {
          $result[ $k ] = $deep ? static::copy( $e, $deep ) : $e;
        }

      } else {

        // handle (and detect non-)cloneable object (private __clone method is considered non-cloneable)
        if( method_exists( $input, '__clone' ) ) $result = is_callable( [ $input, '__clone' ] ) ? clone $input : $input;
        else if( static::is( $input, true ) ) {

          $result = new \stdClass();
          foreach( $input as $k => $e ) {
            $result->{$k} = $deep ? static::copy( $e, $deep ) : $e;
          }
        }
      }

      return $result ?? $input;
    }
  }
  /**
   * Convert any input into arrayOn error/invalid input returns the $default parameter
   *
   * @param mixed      $input
   * @param array|null $default
   * @param bool       $deep
   *
   * @return array|null
   */
  public static function read( $input, ?array $default = null, bool $deep = false ): ?array {
    if( !static::is( $input, true ) ) return $default;
    else {

      // handle simple cases to speed up the process
      if( !$deep ) {
        if( is_array( $input ) ) return $input;
        else if( $input instanceof \StdClass ) return (array) $input;
        else if( $input instanceof StorageInterface ) return $input->getArray( '' );
      }

      $tmp = [];
      foreach( $input as $k => $t ) {
        $tmp[ $k ] = $deep && static::is( $t, true ) ? static::read( $t, null, $deep ) : $t;
      }

      return $tmp;
    }
  }
}
