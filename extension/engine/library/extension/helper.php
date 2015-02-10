<?php namespace Engine\Extension;

use Engine\Extension;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Helper
 * @package Engine\Extension
 */
abstract class Helper {

  /**
   * The valid extension id
   */
  const REGEXP_ID = '/^([a-z][a-z0-9_]*)(-[a-z][a-z0-9_]*){0,2}$/';

  /**
   * Check extension id format
   *
   * @param string $id
   *
   * @return bool
   */
  public static function validate( $id ) {
    return preg_match( self::REGEXP_ID, $id );
  }

  /**
   * Check extension directory existance by id
   *
   * @param string $id
   * @param bool   $validate
   *
   * @return bool
   */
  public static function exist( $id, $validate = false ) {
    return ( !$validate || self::validate( $id ) ) && count( glob( _PATH . self::directory( $id, false ) . Extension::DIRECTORY_CONFIGURATION . 'manifest.*' ) );
  }

  /**
   * Return extension directory from given param ( extension id ). It will return  false if params are invalid or the
   * given extension doesn't exist otherwise the directory without _PATH
   *
   * @param string $id
   * @param boolean $validate
   *
   * @return string|bool
   */
  public static function directory( $id, $validate = true ) {

    // check existance
    if( $validate && ( !self::validate( $id ) || !self::exist( $id ) ) ) return false;
    else {

      // return the directory
      return _PATH_EXTENSION . $id . '/';
    }
  }

  /**
   * Build extension id from pieces
   *
   * @param string      $package
   * @param string|null $name
   * @param string|null $feature
   *
   * @return string|bool
   */
  public static function build( $package, $name = null, $feature = null ) {
    return empty( $package ) ? false : trim( implode( '-', [ $package, $name, $feature ] ), '-' );
  }

  /**
   * Find extension id from an array of string
   *
   * @param string[] $input
   *
   * @return bool|string
   */
  public static function search( array &$input ) {

    $counter   = 0;
    $extension = '';
    while( !empty( $input ) && $counter++ < 3 ) {

      $tmp = ( empty( $extension ) ? '' : '-' ) . $input[ 0 ];
      if( !self::validate( $tmp ) ) return empty( $extension ) ? false : $extension;
      else {

        $extension = $tmp;
        array_shift( $input );
      }
    }

    return false;
  }
}