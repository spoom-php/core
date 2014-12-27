<?php namespace Engine\Extension;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Helper
 * @package Engine\Extension
 */
abstract class Helper {

  /**
   * Check extension existance. In fact is't only check extension directory existance
   *
   * @param string $extension
   *
   * @return boolean
   */
  public static function validate( $extension ) {
    return preg_match( '/^(' . Extension::REGEXP_PACKAGE_NAME . '\\.' . Extension::REGEXP_EXTENSION_NAME . ')$/', $extension ) &&
           count( glob( _PATH . self::directory( $extension, false ) . Extension::DIRECTORY_CONFIGURATION . 'manifest.*' ) );
  }

  /**
   * Return extension directory from given param ( extension id ). It will return  false if params are invalid or the
   * given extension doesn't exist otherwise the directory without _PATH
   *
   * @param string $extension
   * @param boolean $validate
   *
   * @return string|false
   */
  public static function directory( $extension, $validate = true ) {

    // check existance
    if( $validate && !self::validate( $extension ) ) return false;
    else {

      // return the directory
      return $extension == '.engine' ? 'engine/' : ( _PATH_EXTENSION . str_replace( '.', '-', trim( $extension, '.' ) ) . '/' );
    }
  }
}