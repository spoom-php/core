<?php namespace Spoom\Framework\Extension;

use Spoom\Framework\Extension;
use Spoom\Framework\Application;
use Spoom\Framework\Storage;
use Spoom\Framework\Converter;

/**
 * Interface LocalizationInterface
 * @package Framework\Extension
 *
 * @since   0.6.0
 */
interface LocalizationInterface extends Storage\PermanentInterface {

  /**
   * Set defaults
   *
   * @param Extension $source
   */
  public function __construct( Extension $source );

  /**
   * @since 0.6.0
   *
   * @return Extension
   */
  public function getExtension();
  /**
   * Get the current localization name
   *
   * @since 0.6.0
   *
   * @return string
   */
  public function getLocalization();
  /**
   * Set the current localization name
   *
   * @since 0.6.0
   *
   * @param string $value
   */
  public function setLocalization( $value );
}

/**
 * Class Localization
 * @package Framework\Extension
 *
 * @property-read Extension $extension    The extension source of the localization
 * @property      string    $localization The current localization name
 */
class Localization extends Storage\File implements LocalizationInterface {

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
  private $_localization;

  /**
   * Set defaults
   *
   * @param Extension $source
   */
  public function __construct( Extension $source ) {
    parent::__construct( $source->file( Extension::DIRECTORY_LOCALIZATION ), [
      new Converter\Json( JSON_PRETTY_PRINT ),
      new Converter\Ini()
    ] );

    $this->_extension = $source;
  }

  /**
   * Check if the given localization name directory exists
   *
   * @param string $name The localization name to check
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
      if( !empty( $this->getLocalization() ) ) {
        $this->setDirectory( $tmp->get( $this->getLocalization() . '/' ) );
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
  public function getLocalization() {

    if( !isset( $this->_localization ) ) {
      $this->setLocalization( Application::instance()->getLocalization() );
    }

    return $this->_localization;
  }
  //
  public function setLocalization( $value ) {

    // save the original localization for later compare
    $tmp = $this->_localization;

    // set the new localization
    $global = Application::instance()->getLocalization();
    if( $this->validate( $value ) ) $this->_localization = $value;
    else if( $global != $value && $this->validate( $global ) ) $this->_localization = $global;
    else if( $this->validate( $this->_extension->manifest->getString( 'localization' ) ) ) {
      $this->_localization = $this->_extension->manifest->getString( 'localization' );
    }

    // clear meta/cache/storage when the localization has changed
    if( $this->_localization != $tmp ) {

      $this->_source         = [];
      $this->converter_cache = [];
      $this->clean();
    }
  }
}
