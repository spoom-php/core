<?php namespace Framework\Extension;

use Framework\Application;
use Framework\Extension;
use Framework\Storage;
use Framework\Converter;

/**
 * Interface ConfigurationInterface
 * @package Framework\Extension
 *
 * @since   0.6.0
 */
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
  /**
   * @return string
   */
  public function getEnvironment();
  /**
   * @param string $value
   */
  public function setEnvironment( $value );
}

/**
 * Class Configuration
 * @package Framework\Extension
 *
 * @property-read Extension $extension   The extension source of the configuration
 * @property      string    $environment The actual environment's name
 */
class Configuration extends Storage\File implements ConfigurationInterface {

  /**
   * Extension data source
   *
   * @var Extension
   */
  private $_extension = null;
  /**
   * Currently loaded environment
   *
   * @var string|null
   */
  private $_environment = null;

  /**
   * Set defaults and init the FileStorage
   *
   * @param Extension $source
   */
  public function __construct( Extension $source ) {
    parent::__construct( $source->file( Extension::DIRECTORY_CONFIGURATION ), [
      new Converter\Json( JSON_PRETTY_PRINT ),
      new Converter\Xml(),
      new Converter\Ini()
    ] );

    $this->_extension = $source;
  }

  /**
   * Check if the given environment' directory exists
   *
   * @param string $name The environment name
   *
   * @return bool
   */
  protected function validate( $name ) {
    return is_string( $name ) && $this->getDirectory()->get( $name . '/' )->exist();
  }
  //
  protected function searchFile( $namespace, $format = null ) {

    $tmp = $this->getDirectory();
    try {

      // change the directory temporary then search for the path
      if( !empty( $this->getEnvironment() ) ) {
        $this->setDirectory( $tmp->get( $this->getEnvironment() . '/' ) );
      }

      return parent::searchFile( $namespace, $format );

    } finally {
      $this->setDirectory( $tmp );
    }
  }

  //
  public function getExtension() {
    return $this->_extension;
  }
  //
  public function getEnvironment() {

    // load the first environment
    if( !isset( $this->_environment ) ) {
      $this->setEnvironment( Application::instance()->getEnvironment() );
    }

    return $this->_environment;
  }
  //
  public function setEnvironment( $value ) {

    // save the original environment for later compare
    $tmp = $this->_environment;

    // set the new environment
    $global = Application::instance()->getEnvironment();
    if( $this->validate( $value ) ) $this->_environment = $value;
    else if( $this->validate( $global ) ) $this->_environment = $global;
    else $this->_environment = '';

    // clear meta/cache/storage when the environment has changed
    if( $this->_environment != $tmp ) {

      $this->_source         = [];
      $this->converter_cache = [];
      $this->clean();
    }
  }
}
