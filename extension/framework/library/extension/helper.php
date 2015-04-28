<?php namespace Framework\Extension;

use Framework\Extension;

/**
 * Class Helper
 * @package Framework\Extension
 */
abstract class Helper {

  /**
   * The valid extension id
   */
  const REGEXP_ID = '/^([a-z][a-z0-9_]*)(-[a-z][a-z0-9_]*){0,2}$/';

  /**
   * Extension id part separator character
   */
  const ID_SEPARATOR = _EXTENSION_SEPARATOR;
  /**
   * Extension id part count
   */
  const ID_PART = _EXTENSION_DEPTH;
  /**
   * Extension id required part index (the package)
   */
  const ID_PART_PACKAGE = 0;
  /**
   * Extension id optional part index (the name)
   */
  const ID_PART_NAME = 1;
  /**
   * Extension id optional part index (the feature)
   */
  const ID_PART_FEATURE = 2;

  /**
   * Check extension id format
   *
   * @param string $id
   *
   * @return bool
   */
  public static function validate( $id ) {
    return (bool) preg_match( self::REGEXP_ID, $id );
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
    return ( !$validate || self::validate( $id ) ) && is_dir( _PATH_BASE . self::directory( $id, false ) );
  }

  /**
   * Return extension directory from given param ( extension id ). It will return  false if params are invalid or the
   * given extension doesn't exist otherwise the directory without _PATH_BASE
   *
   * @param string  $id
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
    return empty( $package ) ? false : trim( implode( self::ID_SEPARATOR, [ $package, $name, $feature ] ), self::ID_SEPARATOR );
  }
  /**
   * Find extension id from an array of string
   *
   * @param string[] $input
   *
   * @return bool|string
   */
  public static function search( array &$input ) {

    $name   = '';
    $length = 0;
    for( $i = 0, $count = count( $input ), $tmp = ''; $i < _EXTENSION_DEPTH && $i < $count; ++$i ) {

      // check if this path is an extension: check existance of the extension directory
      $tmp .= ( $i > 0 ? _EXTENSION_SEPARATOR : '' ) . mb_strtolower( $input[ $i ] );
      if( self::exist( $tmp, true ) ) {

        $length = $i + 1;
        $name   = $tmp;
      }
    }

    if( !$length ) return '';
    else {

      $input = array_slice( $input, $length );
      return $name;
    }
  }
}