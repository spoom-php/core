<?php namespace Framework\Helper;

use Framework\Exception;

/**
 * Interface AccessableInterface
 * @package Framework\Helper
 */
interface AccessableInterface {

  /**
   * There is no getter or setter for the requested property
   *
   * @param string $property The requested property name
   */
  const EXCEPTION_MISSING_PROPERTY = 'framework#20N';

  /**
   * Access private/protected (or dynamic) properties trough a getter
   *
   * @param string $property
   *
   * @return mixed
   * @throws Exception\Strict
   */
  public function __get( $property );
  /**
   * Access private/protected (or dynamic) properties trough a setter
   *
   * @param string $property
   * @param mixed  $value
   *
   * @return void
   * @throws Exception\Strict
   */
  public function __set( $property, $value );
  /**
   * Check for access to a private/protected (or dynamic) property
   *
   * @param string $property
   *
   * @return bool
   */
  public function __isset( $property );
}

/**
 * Class Accessable
 * @package Framework\Helper
 */
trait Accessable {

  /**
   * Property get/set method names cache
   *
   * @var array
   */
  private static $accessible_cache = [];

  /**
   * Access private/protected (or dynamic) properties trough a getter
   *
   * @param string $property
   *
   * @return mixed
   * @throws Exception\Strict
   */
  public function __get( $property ) {

    $cache = get_class( $this ) . '->g.' . $property;
    if( !array_key_exists( $cache, self::$accessible_cache ) ) {
      self::$accessible_cache[ $cache ] = null;

      $tmp = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $property ) ) );
      foreach( [ 'get', 'is', 'has' ] as $getter ) {
        if( is_callable( [ $this, $getter . $tmp ] ) ) {
          self::$accessible_cache[ $cache ] = $getter . $tmp;
          break;
        }
      }
    }

    $method = self::$accessible_cache[ $cache ];
    if( $method ) return $this->{$method}();
    else throw new Exception\Strict( AccessableInterface::EXCEPTION_MISSING_PROPERTY, [ 'property' => $property ] );
  }
  /**
   * Access private/protected (or dynamic) properties trough a setter
   *
   * @param string $property
   * @param mixed  $value
   *
   * @return void
   * @throws Exception\Strict
   */
  public function __set( $property, $value ) {

    $cache = get_class( $this ) . '->s.' . $property;
    if( !array_key_exists( $cache, self::$accessible_cache ) ) {
      self::$accessible_cache[ $cache ] = null;

      $tmp = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $property ) ) );
      if( is_callable( [ $this, 'set' . $tmp ] ) ) {
        self::$accessible_cache[ $cache ] = 'set' . $tmp;
      }
    }

    $method = self::$accessible_cache[ $cache ];
    if( !$method ) throw new Exception\Strict( AccessableInterface::EXCEPTION_MISSING_PROPERTY, [ 'property' => $property ] );
    else $this->{$method}( $value );
  }
  /**
   * Check for access to a private/protected (or dynamic) property
   *
   * @param string $property
   *
   * @return bool
   */
  public function __isset( $property ) {
    try {
      return $this->__get( $property ) !== null;
    } catch( Exception\Strict $e ) {
      return false;
    }
  }
}
