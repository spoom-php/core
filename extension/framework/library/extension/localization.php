<?php namespace Framework\Extension;

use Framework\Extension;
use Framework\Helper\String;
use Framework\Page;
use Framework\Storage\Directory as DirectoryStorage;

/**
 * Class Localization
 * @package Framework\Extension
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
   * Currently loaded localization
   *
   * @var string
   */
  protected $_localization;

  /**
   * Set defaults
   *
   * @param Extension $source
   */
  public function __construct( Extension $source ) {
    parent::__construct( $source->directory( '' ) . Extension::DIRECTORY_LOCALIZATION, [ 'json', 'ini', 'xml' ] );

    $this->_extension = $source;
  }

  /**
   * @param $index
   *
   * @return Extension|string|null
   */
  public function __get( $index ) {

    if( $index == 'extension' ) return $this->_extension;
    else if( $index == 'localization' ) {

      if( !isset( $this->_localization ) ) $this->localization = Page::getLocalization();
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
   * Dynamic setter for privates
   *
   * @param string $index
   * @param mixed  $value
   */
  public function __set( $index, $value ) {
    switch( $index ) {
      case 'localization':

        $global = Page::getLocalization();
        if( $this->validate( $value ) ) $this->_localization = $value;
        else if( $global != $value && $this->validate( $global ) ) $this->_localization = $global;
        else if( $this->validate( $this->_extension->option( 'manifest:localization' ) ) ) {
          $this->_localization = $this->_extension->option( 'manifest:localization' );
        }

        // log: debug
        Page::getLog()->debug( 'The \'{localization}\' localization selected', [
          'localization' => $this->_localization,
          'directory'    => $this->_directory
        ], '\Framework\Extension\Localization' );

        break;
      default:
        parent::__set( $index, $value );
    }
  }

  /**
   * Check if the given localization name directory exists
   *
   * @param string $name The localization name to check
   *
   * @return bool
   */
  protected function validate( $name ) {
    return is_string( $name ) && is_dir( _PATH_BASE . $this->_directory . $name . '/' );
  }
  /**
   * @param string      $namespace
   * @param string|null $extension
   * @param bool        $exist
   *
   * @return mixed
   */
  protected function path( $namespace, $extension = null, &$exist = false ) {

    // define the localization of not already
    if( !isset( $this->_localization ) ) $this->localization = Page::getLocalization();

    // change the directory temporary then search for the path
    $tmp = $this->_directory;
    $this->_directory .= $this->_localization . '/';

    $result           = parent::path( $namespace, $extension, $exist );
    $this->_directory = $tmp;

    return $result;
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
