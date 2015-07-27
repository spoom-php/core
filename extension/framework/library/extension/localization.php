<?php namespace Framework\Extension;

use Framework\Extension;
use Framework\Request;
use Framework\Storage;

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
   * @return Extension
   */
  public function getExtension();
  /**
   * Get the current localization name
   *
   * @return string
   */
  public function getLocalization();
  /**
   * Set the current localization name
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
  protected $_localization;

  /**
   * Set defaults
   *
   * @param Extension $source
   */
  public function __construct( Extension $source ) {
    parent::__construct( $source->directory( '' ) . Extension::DIRECTORY_LOCALIZATION );

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
    return is_string( $name ) && is_dir( _PATH_BASE . $this->_path . $name . '/' );
  }
  /**
   * @param string      $namespace
   * @param string|null $format
   * @param bool        $exist
   *
   * @return mixed
   */
  protected function getFile( $namespace, $format = null, &$exist = false ) {

    // define the localization of not already
    if( !isset( $this->_localization ) ) $this->localization = Request::getLocalization();

    // change the directory temporary then search for the path
    $tmp = $this->_path;
    $this->_path .= $this->_localization . '/';

    $result      = parent::getFile( $namespace, $format, $exist );
    $this->_path = $tmp;

    return $result;
  }

  /**
   * @since 0.6.0
   *
   * @return Extension
   */
  public function getExtension() {
    return $this->_extension;
  }
  /**
   * @since 0.6.0
   *
   * @return string
   */
  public function getLocalization() {

    if( !isset( $this->_localization ) ) $this->setLocalization( Request::getLocalization() );

    return $this->_localization;
  }
  /**
   * @since 0.6.0
   *
   * @param string $value
   */
  public function setLocalization( $value ) {

    // save the original localization for later compare
    $tmp = $this->_localization;

    // set the new localization
    $global = Request::getLocalization();
    if( $this->validate( $value ) ) $this->_localization = $value;
    else if( $global != $value && $this->validate( $global ) ) $this->_localization = $global;
    else if( $this->validate( $this->_extension->manifest->getString( 'localization' ) ) ) {
      $this->_localization = $this->_extension->manifest->getString( 'localization' );
    }

    // clear meta/cache/storage when the localization has changed
    if( $this->_localization != $tmp ) {

      $this->_source = [ ];
      $this->meta    = [ ];
      $this->clean();
    }

    // log: debug
    Request::getLog()->debug( 'The \'{localization}\' localization selected', [
      'localization' => $this->_localization,
      'directory'    => $this->_path
    ], '\Framework\Extension\Localization' );
  }
}
