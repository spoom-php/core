<?php namespace Framework\Helper;

use Framework\Extension;

/**
 * This should be the base class for every library in the framework or at least needs to implement the LibraryInterface
 *
 * @package Framework\Helper
 */
class Library implements LibraryInterface {

  /**
   * Getter for _ prefixed attributes
   *
   * @param string $index
   *
   * @return string|null
   */
  public function __get( $index ) {

    $index = '_' . $index;
    if( isset( $this->{$index} ) ) return $this->{$index};
    else return null;
  }
  /**
   * Existance check of dynamic attributes
   *
   * @param string $index
   *
   * @return bool
   */
  public function __isset( $index ) {
    return property_exists( $this, '_' . $index );
  }
  /**
   * toString overwrite of Object class. It's the extension and the library separated by an ':'
   *
   * @return string
   */
  public function __toString() {

    $class = explode( '\\', mb_strtolower( get_class( $this ) ) );
    return Extension\Helper::search( $class ) . ':' . implode( '.', $class );
  }
}

/**
 * Interface LibraryInterface
 * @package Framework\Helper
 */
interface LibraryInterface {
}
