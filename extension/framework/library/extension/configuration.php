<?php namespace Framework\Extension;

use Framework\Extension;
use Framework\Storage;

interface ConfigurationInterface extends Storage\PermanentInterface {

  /**
   * Set defaults
   *
   * @param Extension $source
   */
  public function __construct( Extension $source );

  /**
   * @return Extension
   */
  public function getExtension();
}

/**
 * Class Configuration
 * @package Framework\Extension
 *
 * @property-read Extension $extension The extension source of the configuration
 */
class Configuration extends Storage\File implements ConfigurationInterface {

  /**
   * Extension data source
   *
   * @var Extension
   */
  private $_extension = null;

  /**
   * Set defaults and init the FileStorage
   *
   * @param Extension $source
   */
  public function __construct( Extension $source ) {
    parent::__construct( $source->directory( '' ) . Extension::DIRECTORY_CONFIGURATION );

    $this->_extension = $source;
  }

  /**
   * @return Extension
   */
  public function getExtension() {
    return $this->_extension;
  }
}
