<?php namespace Engine\Extension;

use Engine\Storage\File as FileStorage;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Configuration
 * @package Engine\Extension
 *
 * @property Extension extension
 */
final class Configuration extends FileStorage {

  /**
   * Extension data source
   *
   * @var Extension
   */
  private $_extension = null;

  /**
   * Set defaults and init the FileStorage
   *
   * @param Extension $extension
   */
  function __construct( Extension $extension ) {
    parent::__construct( $extension->directory( '', true ) . Extension::DIRECTORY_CONFIGURATION );

    $this->_extension = $extension;
    $this->namespace = 'default';
  }

  /**
   * Getter for extension
   *
   * @param $index
   *
   * @return Extension|mixed
   */
  public function __get( $index ) {

    if( $index === 'extension' ) return $this->_extension;
    else return parent::__get( $index );
  }

  /**
   * @param string $index
   *
   * @return bool
   */
  public function __isset( $index ) {
    return $index === 'extension' || parent::__isset( $index );
  }
}