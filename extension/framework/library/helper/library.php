<?php namespace Framework\Helper;

use Framework\Exception;
use Framework\Extension;

/**
 * This should be the base class for every library in the framework or at least needs to implement the LibraryInterface
 *
 * @package Framework\Helper
 */
class Library implements LibraryInterface {

  /**
   * Global cache for getter and setter methods
   *
   * @var string[string]
   */
  private static $cache = [ ];

  /**
   * There is no getter or setter for the requested property. Argument:
   *  - property [string]: The requested property name
   */
  const EXCEPTION_MISSING_PROPERTY = 'framework#20N';

  /**
   * @param $index
   *
   * @return mixed
   * @throws Exception\Strict
   */
  public function __get( $index ) {

    // TODO remove the legacy '_...' getters

    $method = static::searchGetter( $index, $this );
    if( $method ) return $this->{$method}();
    else if( property_exists( $this, '_' . $index ) ) return $this->{'_' . $index};
    else throw new Exception\Strict( self::EXCEPTION_MISSING_PROPERTY, [ 'property' => $index ] );
  }
  /**
   * @param $index
   * @param $value
   *
   * @return mixed
   * @throws Exception\Strict
   */
  public function __set( $index, $value ) {

    $method = static::searchSetter( $index, $this );
    if( !$method ) throw new Exception\Strict( self::EXCEPTION_MISSING_PROPERTY, [ 'property' => $index ] );
    else return $this->{$method}( $value );
  }
  /**
   * @param $index
   *
   * @return bool
   */
  public function __isset( $index ) {

    // TODO remove the legacy '_...' getters

    return property_exists( $this, '_' . $index ) || static::searchGetter( $index, $this ) !== null;
  }

  /**
   * toString overwrite of Object class. It's the extension and the library separated by an ':'
   *
   * @return string
   */
  public function __toString() {

    $class = explode( '\\', mb_strtolower( get_class( $this ) ) );
    return \Framework::search( $class ) . ':' . implode( '.', $class );
  }

  /**
   * Find a getter method name in the instance for the field
   *
   * @param string $field    The field name in the instance
   * @param object $instance The instance of the class
   *
   * @return string|null The getter method name that exists in the instance or null on failure
   */
  public static function searchGetter( $field, $instance ) {

    $cache = get_class( $instance ) . '->get.' . $field;
    if( !array_key_exists( $cache, self::$cache ) ) {

      self::$cache[ $cache ] = null;
      if( property_exists( $instance, '_' . $field ) ) {

        $property = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $field ) ) );
        $getters  = [ 'get', 'is', 'has' ];
        foreach( $getters as $getter ) {

          $method = $getter . $property;
          if( is_callable( [ $instance, $method ] ) ) {
            self::$cache[ $cache ] = $method;
            break;
          }
        }
      }
    }

    return self::$cache[ $cache ];
  }
  /**
   * Find a setter method name in the instance for the field
   *
   * @param string $field    The field name in the instance
   * @param object $instance The instance of the class
   *
   * @return string|null The setter method name that exists in the instance or null on failure
   */
  public static function searchSetter( $field, $instance ) {

    $cache = get_class( $instance ) . '->set.' . $field;
    if( !array_key_exists( $cache, self::$cache ) ) {

      self::$cache[ $cache ] = null;
      if( property_exists( $instance, '_' . $field ) ) {
        $property = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $field ) ) );
        $method   = 'set' . $property;

        if( is_callable( [ $instance, $method ] ) ) self::$cache[ $cache ] = $method;
      }
    }

    return self::$cache[ $cache ];
  }
}

/**
 * Interface LibraryInterface
 * @package Framework\Helper
 */
interface LibraryInterface {
}
