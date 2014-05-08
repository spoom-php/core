<?php namespace Engine\Utility;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Dynamic
 * @package Engine\Utility
 *
 * @property string|bool extension extension of the class in dot separated style ( package.extension, lowercase ). It's ===false if Engine class
 * @property string      library dot separated list of namespaces and the class name relative to the extension ( lowercase )
 */
class Library {

  /**
   * Extension of the class or false if its engine class
   *
   * @var string
   */
  private $_extension = null;

  /**
   * The class library. Dot separated relative namespace from extension
   * base or engine directory.
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

          $this->_extension = false;
          $class = explode( '\\', strtolower( get_class( $this ) ), 3 );
          if( $class[ 0 ] !== 'engine' ) $this->_extension = $class[ 0 ] . '.' . $class[ 1 ];
        }

        return $this->_extension;

      // get the library
      case '_library':

        if( $this->_library === null ) {

          $class          = explode( '\\', strtolower( get_class( $this ) ), 3 );
          $tmp = array();
          if( $class[ 0 ] === 'engine' ) $tmp[] = $class[1];
          if( isset( $class[2] ) ) $tmp[] = str_replace( '\\', '.', $class[2] );

          $this->_library = implode( '.', $tmp );
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
   * toString overwrite of Object class. It's the extension and the library
   * separated by an ':'
   *
   * @return string
   */
  public function __toString() {
    return "{$this->extension}:{$this->library}";
  }
}