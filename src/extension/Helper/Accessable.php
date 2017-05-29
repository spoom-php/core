<?php namespace Spoom\Core\Helper;

use Spoom\Core\Application;
use Spoom\Core\Exception;

/**
 * Interface AccessableInterface
 */
interface AccessableInterface {

  /**
   * Access private/protected (or dynamic) properties trough a getter
   *
   * @param string $property
   *
   * @return mixed
   * @throws AccessibleMissingException
   */
  public function __get( $property );
  /**
   * Access private/protected (or dynamic) properties trough a setter
   *
   * @param string $property
   * @param mixed  $value
   *
   * @return void
   * @throws AccessibleMissingException
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
 */
trait Accessable {

  /**
   * Property get/set method names cache
   *
   * @var array
   */
  private static $accessible_cache = [];

  //
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
    else throw new AccessibleMissingException( $this, $property );
  }
  //
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
    if( !$method ) throw new AccessibleMissingException( $this, $property );
    else $this->{$method}( $value );
  }
  //
  public function __isset( $property ) {
    try {
      return $this->__get( $property ) !== null;
    } catch( AccessibleMissingException $e ) {
      return false;
    }
  }
}

/**
 * There is no getter or setter for the requested property
 *
 */
class AccessibleMissingException extends Exception\Logic {

  const ID = '20#spoom-core';

  /**
   * @param object $instance
   * @param string $property The requested property name
   */
  public function __construct( $instance, string $property ) {

    $data = [ 'class' => get_class( $instance ), 'property' => $property ];
    parent::__construct( Text::apply( 'There is no \'{property}\' in {class}', $data ), static::ID, $data, null, Application::SEVERITY_NOTICE );
  }
}
