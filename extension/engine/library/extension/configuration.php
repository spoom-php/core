<?php namespace Engine\Extension;

use Engine\Extension;
use Engine\Storage\File as FileStorage;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Configuration
 * @package Engine\Extension
 *
 * @property Extension $source
 */
final class Configuration extends FileStorage {

  /**
   * Extension data source
   *
   * @var Extension
   */
  private $_source = null;

  /**
   * Set defaults and init the FileStorage
   *
   * @param Extension $source
   */
  function __construct( Extension $source ) {
    parent::__construct( $source->directory( '', true ) . Extension::DIRECTORY_CONFIGURATION );

    $this->_source   = $source;
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

    if( $index === 'source' ) return $this->_source;
    else return parent::__get( $index );
  }

  /**
   * @param string $index
   *
   * @return bool
   */
  public function __isset( $index ) {
    return $index === 'source' || parent::__isset( $index );
  }
}