<?php namespace Framework\Extension;

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
    parent::__construct( $source->directory( '' ) . Extension::DIRECTORY_CONFIGURATION, [
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
    return is_string( $name ) && is_dir( \Framework::PATH_BASE . $this->_path . $name . '/' );
  }
  /**
   * @param string      $namespace
   * @param string|null $format
   * @param bool        $exist
   *
   * @return mixed
   */
  protected function getFile( $namespace, $format = null, &$exist = false ) {

    // change the directory temporary then search for the path
    $tmp = $this->_path;
    if( !empty( $this->environment ) ) $this->_path .= $this->environment . '/';

    $result      = parent::getFile( $namespace, $format, $exist );
    $this->_path = $tmp;

    return $result;
  }

  /**
   * @return Extension
   */
  public function getExtension() {
    return $this->_extension;
  }
  /**
   * @return string
   */
  public function getEnvironment() {

    // load the first environment
    if( !isset( $this->_environment ) ) {
      $this->setEnvironment( \Framework::getEnvironment() );
    }

    return $this->_environment;
  }
  /**
   * @param string $value
   */
  public function setEnvironment( $value ) {

    // save the original environment for later compare
    $tmp = $this->_environment;

    // set the new environment
    $global = \Framework::getEnvironment();
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
