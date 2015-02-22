<?php namespace Engine\Extension;

use Engine\Extension;
use Engine\Helper\String;
use Engine\Page;
use Engine\Storage\File as FileStorage;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Localization
 * @package Engine\Extension
 *
 * @property Extension $source
 * @property string    $localization
 */
class Localization extends FileStorage {

  /**
   * Extension data source
   *
   * @var Extension
   */
  private $_source = null;

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
  private $_localization;

  /**
   * Set defaults
   *
   * @param Extension $source
   */
  function __construct( Extension $source ) {
    parent::__construct( null, [ 'json', 'ini', 'xml' ] );

    $this->_source        = $source;
    $this->namespace      = 'default';
    $this->base_directory = $this->_source->directory( '', true ) . Extension::DIRECTORY_LOCALIZATION;

    // define default localizations
    $this->default_directory = $this->find( $source->option( 'manifest:localization' ) );
  }

  /**
   * @param $index
   *
   * @return Extension|string|null
   */
  public function __get( $index ) {

    if( $index == 'source' ) return $this->_source;
    else if( $index == 'localization' ) {

      // call to reload active localization
      $this->path( '' );

      return $this->_localization;
    }

    return parent::__get( $index );
  }

  /**
   * @param string $index
   *
   * @return bool
   */
  public function __isset( $index ) {
    return $index == 'source' || $index == 'localization' || parent::__isset( $index );
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

    $localization = Page::getLocalization();
    $global       = $this->find( $localization );

    if( $global ) {
      $this->_localization = $localization;
      $this->_directory = $global;
    } else if( $this->default_directory ) {
      $this->_localization = $this->_source->option( 'manifest:localization' );
      $this->_directory    = $this->default_directory;
    } else {
      $this->_localization = false;
      $this->_directory = false;
    }

    return parent::path( $namespace );
  }

  /**
   * Same as the getString method, but insert data to string with String::insert()
   *
   * @param string $index
   * @param array  $insertion
   * @param string $default
   *
   * @return null|string
   */
  public function getPattern( $index, $insertion, $default = '' ) {

    $value = $this->getString( $index, $default );
    return String::insert( $value, is_array( $insertion ) ? $insertion : [ $insertion ] );
  }
}