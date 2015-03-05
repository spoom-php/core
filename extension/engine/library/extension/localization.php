<?php namespace Engine\Extension;

use Engine\Extension;
use Engine\Helper\String;
use Engine\Page;
use Engine\Storage\Directory as DirectoryStorage;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Localization
 * @package Engine\Extension
 *
 * @property Extension $extension
 * @property string    $localization
 */
class Localization extends DirectoryStorage {

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
  private $_localization;

  /**
   * Set defaults
   *
   * @param Extension $source
   */
  function __construct( Extension $source ) {
    parent::__construct( null, [ 'json', 'ini', 'xml' ] );

    $this->_extension     = $source;
    $this->namespace      = 'default';
    $this->base_directory = $this->_extension->directory( '', true ) . Extension::DIRECTORY_LOCALIZATION;

    // define default localizations
    $this->default_directory = $this->find( $source->option( 'manifest:localization' ) );
  }

  /**
   * @param $index
   *
   * @return Extension|string|null
   */
  public function __get( $index ) {

    if( $index == 'extension' ) return $this->_extension;
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

    $localization = Page::getLocalization();
    $global       = $this->find( $localization );

    if( $global ) {
      $this->_localization = $localization;
      $this->_directory = $global;

      // log: debug
      Page::getLog()->debug( 'Global \'{localization}\' localization selected', [ 'localization' => $this->_localization, 'directory' => $this->_directory ], '\Engine\Extension\Localization' );
      
    } else if( $this->default_directory ) {
      $this->_localization = $this->_extension->option( 'manifest:localization' );
      $this->_directory    = $this->default_directory;

      // log: debug
      Page::getLog()->debug( 'Default \'{localization}\' localization selected', [ 'localization' => $this->_localization, 'directory' => $this->_directory ], '\Engine\Extension\Localization' );
      
    } else {
      $this->_localization = false;
      $this->_directory = false;

      // log: notice
      Page::getLog()->notice( 'No localization selected', [ 'extension' => (string) $this->_extension, 'namespace' => $namespace ], '\Engine\Extension\Localization' );
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