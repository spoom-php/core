<?php namespace Framework\Extension;

use Framework\Application;
use Framework\File;
use Framework\FileInterface;

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
  const ID_SEPARATOR = \Framework::EXTENSION_SEPARATOR;
  /**
   * Extension id part count
   */
  const ID_PART = \Framework::EXTENSION_DEPTH;
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
    return ( !$validate || self::validate( $id ) ) && ( $tmp = self::directory( $id, false ) ) && $tmp->exist();
  }

  /**
   * Return extension directory from given param ( extension id ). It will return  false if params are invalid or the
   * given extension doesn't exist otherwise the directory without _PATH_BASE
   *
   * @param string $id
   * @param bool   $validate
   *
   * @return FileInterface|null
   */
  public static function directory( $id, $validate = true ) {

    // check existance
    if( $validate && ( !self::validate( $id ) || !self::exist( $id ) ) ) return null;
    else {

      // return the directory
      return Application::getFile( File\System::directory( \Framework::PATH_EXTENSION . $id ) );
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
   * @deprecated Use the \Framework::search() instead
   * @return bool|string
   */
  public static function search( array &$input ) {
    return \Framework::search( $input );
  }
}
