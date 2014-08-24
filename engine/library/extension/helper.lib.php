<?php namespace Engine\Extension;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Helper
 * @package Engine\Extension
 */
abstract class Helper {

  /**
   * TODO implement cache
   * Returns all ( or from specified package )
   * installed extension id ( dot separated package and name )
   *
   * @param bool $package
   *
   * @return array
   */
  public static function get( $package = false ) {

    $path      = _PATH . _PATH_EXTENSION;
    $extension = glob( $path . ( is_string( $package ) ? $package : '*' ) . '/*' );

    // Iterate path and remove base and replace DS
    $extension_array = array();
    foreach( $extension as $e ) {
      $e = explode( '/', substr( $e, strlen( $path ) ) );
      if( count( $e ) < 2 ||
          !preg_match( '/^(' . Extension::REGEXP_PACKAGE_NAME . ')$/', $e[ 0 ] ) ||
          !preg_match( '/^(' . Extension::REGEXP_EXTENSION_NAME . ')$/', $e[ 1 ] )
      ) continue;

      $extension_array[ ] = $e[ 0 ] . '.' . $e[ 1 ];
    }

    // return the result
    return $extension_array;
  }

  /**
   * Check extension existance. In fact is't only check
   * extension directory existance.
   *
   * @param string $extension
   *
   * @return boolean
   */
  public static function validate( $extension ) {
    if( $extension == '.engine' ) return true;

    if( preg_match( '/^(' . Extension::REGEXP_PACKAGE_NAME . '\\.' . Extension::REGEXP_EXTENSION_NAME . ')$/', $extension ) ) {
      $extension = explode( '.', $extension );

      return count( $extension ) == 2 && is_dir( _PATH . _PATH_EXTENSION . $extension[ 0 ] . '/' . $extension[ 1 ] . '/' );
    }

    return false;
  }

  /**
   * Return extension directory from given param ( extension id ). It will return
   * Exception if params are invalid or the given extension
   * doesn't exist otherwise the directory without _PATH.
   *
   * @param string $extension
   *
   * @return string|false
   */
  public static function directory( $extension ) {

    // check existance
    if( !self::validate( $extension ) ) return false;

    // return the directory
    return $extension == '.engine' ? 'engine/' : ( _PATH_EXTENSION . str_replace( '.', '/', $extension ) . '/' );
  }
}