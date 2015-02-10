<?php namespace Engine\Extension;

use Engine\Extension;
use Engine\Storage\File as FileStorage;
use Engine\Utility\String;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Localization
 * @package Engine\Extension
 *
 * @property Extension extension
 * @property string    localization
 */
final class Localization extends FileStorage {

  /**
   * Store actual localization string
   *
   * @var string
   */
  private static $_localization = null;

  /**
   * Extension data source
   *
   * @var Extension
   */
  private $_extension = null;

  /**
   * Default language directory defined in default configuration file 'localization' entry ( only localization string
   * defined )
   *
   * @var string
   */
  private $default_directory;

  /**
   * Extension defined base directory for languages
   *
   * @var string
   */
  private $base_directory;

  /**
   * Currently loaded localization
   *
   * @var string
   */
  private $active_localization;

  /**
   * Set defaults
   *
   * @param Extension $extension
   */
  function __construct( Extension $extension ) {
    parent::__construct( null, array( 'json', 'ini', 'xml' ) );

    $this->_extension = $extension;
    $this->namespace  = 'default';
    $this->base_directory = $this->_extension->directory( '', true ) . Extension::DIRECTORY_LOCALIZATION;

    // define default localizations
    $this->default_directory = $this->find( $extension->option( 'manifest:localization' ) );
  }

  /**
   * @param $index
   *
   * @return Extension|string|null
   */
  public function __get( $index ) {

    if( $index === 'extension' ) return $this->_extension;
    else if( $index === 'localization' ) {

      // call to reload active localization
      $this->path( '' );

      return $this->active_localization;
    }

    return parent::__get( $index );
  }

  /**
   * @param string $index
   *
   * @return bool
   */
  public function __isset( $index ) {
    return $index == 'extension' || $index == 'localization' || parent::__isset( $index );
  }

  /**
   * Get localization directory if it's available
   *
   * @param string $string
   *
   * @return string|bool
   */
  protected function find( $string ) {
    if( is_string( $string ) && is_dir( $this->base_directory . $string . '/' ) ) {
      return $this->base_directory . $string . '/';
    }

    return false;
  }

  /**
   * @param string $namespace
   *
   * @return mixed
   */
  protected function path( $namespace ) {
    $global = $this->find( self::getLocalization() );

    if( $global ) {
      $this->active_localization = self::getLocalization();
      $this->_directory = $global;
    } else if( $this->default_directory ) {
      $this->active_localization = $this->_extension->option( 'manifest:localization' );
      $this->_directory = $this->default_directory;
    } else {
      $this->active_localization = false;
      $this->_directory = false;
    }

    return parent::path( $namespace );
  }

  /**
   * Same as the gets method, but insert data to string with fString::insert()
   *
   * @param string $index
   * @param array  $insertion
   *
   * @return null|string
   */
  public function getf( $index, $insertion ) {

    $value = $this->gets( $index );

    return $value == null ? null : String::insert( $value, is_array( $insertion ) ? $insertion : array( $insertion ), $this );
  }

  /**
   * Get active localization string
   *
   * @return string
   */
  public static function getLocalization() {
    if( !isset( self::$_localization ) ) {
      $extension = new Extension( 'engine' );
      self::$_localization = $extension->option( 'manifest:localization' );
    }

    return self::$_localization;
  }

  /**
   * Set active localization
   *
   * @param string $new_localization
   */
  public static function setLocalization( $new_localization ) {

    $new_localization = trim( strtolower( $new_localization ) );
    if( preg_match( '/[a-z]/', $new_localization ) > 0 ) {
      self::$_localization = $new_localization;
    }
  }
}