<?php namespace Engine\Helper;

use Engine\Extension;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Library
 * @package Engine\Helper
 *
 * @property string|bool extension Extension id of the class
 * @property string      library   Dot separated list of namespaces and the class name relative to the extension
 */
class Library {

  /**
   * Extension of the class or false if its engine class
   *
   * @var string
   */
  private $_extension = null;

  /**
   * The class library. Dot separated relative namespace from extension base or engine directory
   *
   * @var string
   */
  private $_library = null;

  /**
   * Getter for _ prefixed attributes
   *
   * @param string $index
   *
   * @return string|null
   */
  public function __get( $index ) {

    $index = '_' . $index;
    switch( $index ) {

      // get the extension
      case '_extension':
        if( $this->_extension === null ) {

          $class = explode( '\\', mb_strtolower( get_class( $this ) ) );
          $this->_extension = Extension\Helper::search( $class );
        }

        return $this->_extension;

      // get the library
      case '_library':

        if( $this->_library === null ) {

          $class = explode( '\\', mb_strtolower( get_class( $this ) ) );
          $this->_extension = Extension\Helper::search( $class );
          $this->_library   = implode( '.', $class );
        }

        return $this->_library;
      default:
        if( isset( $this->{$index} ) ) return $this->{$index};
    }

    return null;
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
    return "{$this->extension}:{$this->library}";
  }
}